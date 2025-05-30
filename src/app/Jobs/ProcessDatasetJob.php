<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataObjects\DatasetFilesDataObject;
use App\Enums\NotificationLevel;
use App\Events\RefreshDatasets;
use App\Models\Dataset;
use App\Models\DatasetMetadata;
use App\Models\Sample;
use App\Models\SampleMetadata;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class ProcessDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const int LINES_TO_CHECK_FOR_CONSISTENCY = 10;

    private const string DEFAULT_ID_COLUMN_NAME = 'id';

    /** @var array<string> */
    private const array TSV_FILE_TYPES = [
        'taxonomy',
        'asvTable',
        'metadata',
        'picrust.ko',
        'picrust.pathways',
        'picrust.ec',
    ];

    /**
     * @param  array<string, string>  $columnMapping  Column name to field mapping
     * @param  array<int, array{key: string, value: string}>  $datasetMetadata
     * @param  array<string, array{original_name: string, stored_name: string}>  $uploadedFiles
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $description,
        public string $storagePath,
        public array $columnMapping,
        public string $sampleCodeColumn,
        public array $datasetMetadata,
        public array $uploadedFiles,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $toDeleteIfFailed = [];
        DB::beginTransaction();
        try {
            // Create the dataset
            $dataset = Dataset::create(
                [
                    'name' => $this->name,
                    'description' => $this->description,
                    'created_by' => $this->userId,
                ]
            );

            // Process dataset metadata
            foreach ($this->datasetMetadata as $metadata) {
                if (! empty($metadata['key']) && (isset($metadata['value']) && ($metadata['value'] !== '' && $metadata['value'] !== '0'))) {
                    DatasetMetadata::create(
                        [
                            'dataset_id' => $dataset->id,
                            'key' => $metadata['key'],
                            'value' => $metadata['value'],
                        ]
                    );
                }
            }

            //            // Store original file names as dataset metadata
            //            foreach ($this->uploadedFiles as $type => $fileInfo) {
            //                DatasetMetadata::create(
            //                    [
            //                        'dataset_id' => $dataset->id,
            //                        'key' => "original_{$type}_filename",
            //                        'value' => $fileInfo['original_name'],
            //                    ]
            //                );
            //            }

            // Move files to final location
            $finalPath = "datasets/{$dataset->id}/processed";
            $uploadedArray = [];
            Storage::makeDirectory($finalPath);
            $toDeleteIfFailed[] = $finalPath;
            foreach ($this->uploadedFiles as $type => $fileInfo) {
                if (in_array($type, self::TSV_FILE_TYPES, true)) {
                    $this->cleanUpTSVFile(
                        $this->storagePath.'/'.$fileInfo['stored_name'],
                        $finalPath.'/'.$fileInfo['stored_name']
                    );
                } else {
                    Storage::move(
                        $this->storagePath.'/'.$fileInfo['stored_name'],
                        $finalPath.'/'.$fileInfo['stored_name']
                    );
                }
                $uploadedArray[$type] = $finalPath.'/'.$fileInfo['stored_name'];
            }
            Storage::deleteDirectory($this->storagePath);

            $dataset->update(
                [
                    'files' => new DatasetFilesDataObject(Arr::undot($uploadedArray)),
                ]
            );

            // Process files and create records
            $this->processMetadataFile($dataset);

            DB::commit();
            User::find($this->userId)?->notify(
                new GeneralNotification(
                    title: 'Dataset processing completed',
                    message: 'Your dataset has been processed successfully.',
                    level: NotificationLevel::SUCCESS
                )
            );
            RefreshDatasets::dispatch($this->userId);
        } catch (Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory($this->storagePath);
            foreach ($toDeleteIfFailed as $directory) {
                if (Storage::exists($directory)) {
                    Storage::deleteDirectory($directory);
                }
            }
            Log::error(
                $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                    'user_id' => $this->userId,
                    'name' => $this->name,
                    'description' => $this->description,
                    'storage_path' => $this->storagePath,
                    'column_mapping' => $this->columnMapping,
                    'sample_code_column' => $this->sampleCodeColumn,
                    'dataset_metadata' => $this->datasetMetadata,
                    'uploaded_files' => $this->uploadedFiles,
                ]
            );
            User::find($this->userId)?->notify(
                new GeneralNotification(
                    title: 'Dataset processing failed',
                    message: 'An error occurred while processing your dataset: '.$e->getMessage(),
                    level: NotificationLevel::ERROR
                )
            );
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function processMetadataFile(Dataset $dataset): void
    {
        $filePath = $dataset->files?->metadata; // @phpstan-ignore-line
        if (! $filePath) {
            throw new RuntimeException('Metadata file not found.');
        }
        $handle = fopen(Storage::path($filePath), 'rb');
        $headers = fgetcsv($handle, 0, "\t", escape: '\\');

        while (($row = fgetcsv($handle, 0, "\t", escape: '\\')) !== false) {
            $data = array_combine($headers, $row);
            $sampleCode = $data[$this->sampleCodeColumn];

            // Create sample record
            $sample = Sample::create(
                [
                    'dataset_id' => $dataset->id,
                    'sample_code' => $sampleCode,
                ] + $this->mapSampleFields($data)
            );

            // Store custom metadata
            $metadataRecords = [];
            foreach ($data as $column => $value) {
                $mapping = $this->columnMapping[$column] ?? null;
                if ($mapping === 'custom') {
                    $metadataRecords[] = [
                        'sample_id' => $sample->id,
                        'key' => $column,
                        'value' => Json::encode($value),
                    ];
                }
            }

            if ($metadataRecords !== []) {
                SampleMetadata::insert($metadataRecords);
            }
        }

        fclose($handle);
    }

    private function mapSampleFields(array $data): array
    {
        $mappedFields = [];
        foreach ($this->columnMapping as $column => $field) {
            if ($field !== 'custom' && $field !== 'exclude') {
                $mappedFields[$field] = $data[$column];
            }
        }
        $mappedFields['variety'] ??= '';
        $mappedFields['plant_stage'] ??= '';
        $mappedFields['biological_replica'] ??= 0;
        $mappedFields['sample_conditions'] ??= '';
        $mappedFields['plant_section'] ??= '';
        $mappedFields['sampling_date'] ??= now();
        $mappedFields['location'] ??= '';

        return $mappedFields;
    }

    /**
     * Clean up a TSV file by processing headers and removing comments.
     *
     * This method performs the following operations:
     * 1. Identifies column headers (lines starting with "#" or first data line)
     * 2. Validates header consistency with data lines
     * 3. Removes all comment lines (except identified headers)
     * 4. Adds missing "id" column if header has one less column than data
     * 5. Creates a cleaned temporary file
     *
     * @param  string  $filePath  The path to the TSV file to clean up
     * @param  string  $outputPath  The path where the cleaned file will be saved
     *
     * @throws Exception If file cannot be read or processed
     */
    private function cleanUpTSVFile(string $filePath, string $outputPath): void
    {
        $this->validateInputFile($filePath);

        $inputHandle = $this->openFileForReading($filePath);

        try {
            $headerInfo = $this->analyzeFileStructure($inputHandle);

            $this->createCleanedFile($inputHandle, $outputPath, $headerInfo);
        } finally {
            fclose($inputHandle);
        }
    }

    /**
     * Validate that the input file exists and is accessible.
     */
    private function validateInputFile(string $filePath): void
    {
        if (Storage::missing($filePath)) {
            throw new RuntimeException("File not found: {$filePath}");
        }
    }

    /**
     * Open file for reading with proper error handling.
     */
    private function openFileForReading(string $filePath)
    {
        $handle = Storage::readStream($filePath);
        if (! $handle) {
            throw new RuntimeException("Cannot open file for reading: {$filePath}");
        }

        return $handle;
    }

    /**
     * Analyze the file structure to identify header information.
     *
     * @return array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}
     */
    private function analyzeFileStructure($handle): array
    {
        if (! is_resource($handle)) {
            throw new RuntimeException('Invalid file handle provided.');
        }
        $headerInfo = $this->findHeaderWithHashPrefix($handle);

        // If no # header found, check first data line as potential header
        if ($headerInfo === null) {
            $headerInfo = $this->findFirstDataLineAsHeader($handle);
        }
        if ($headerInfo === null) {
            throw new RuntimeException('No valid header found in the file.');
        }

        return $headerInfo;
    }

    /**
     * Look for header line starting with # and validate its structure.
     *
     * @return array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}|null
     */
    private function findHeaderWithHashPrefix($handle): ?array
    {
        rewind($handle);
        $lineIndex = 0;
        while (($line = fgets($handle)) !== false) {
            $trimmedLine = mb_trim($line);
            if (empty($trimmedLine)) {
                continue;
            }
            if (str_starts_with($trimmedLine, '#')) {
                $headerInfo = $this->validatePotentialHeader($handle, $trimmedLine, $lineIndex);
                if ($headerInfo !== null) {
                    return $headerInfo;
                }
            }
            $lineIndex++;
        }

        return null;
    }

    /**
     * Validate a potential header line by checking consistency with following data lines.
     *
     * @return array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}|null
     */
    private function validatePotentialHeader($handle, string $headerLine, int $lineIndex): ?array
    {
        $headerContent = mb_substr($headerLine, 1); // Remove the #
        $headerColumns = str_getcsv(mb_trim($headerContent), "\t", escape: '\\');
        $headerColumnCount = count($headerColumns);
        $currentPosition = ftell($handle);
        $dataColumnCount = $this->getConsistentDataColumnCount($handle);
        // Restore file position
        fseek($handle, $currentPosition);
        if ($dataColumnCount !== null && $this->isValidHeaderStructure($headerColumnCount, $dataColumnCount)) {
            return [
                'headerLineIndex' => $lineIndex,
                'headerColumnCount' => $headerColumnCount,
                'dataColumnCount' => $dataColumnCount,
            ];
        }

        return null;
    }

    /**
     * Get consistent column count from data lines, checking multiple lines for validation.
     */
    private function getConsistentDataColumnCount($handle): ?int
    {
        $dataLinesToCheck = 0;
        $consistentColumnCount = null;
        while (($line = fgets($handle)) !== false && $dataLinesToCheck < self::LINES_TO_CHECK_FOR_CONSISTENCY) {
            $trimmedLine = mb_trim($line);
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                continue;
            }
            $columns = str_getcsv($trimmedLine, "\t", escape: '\\');
            $columnCount = count($columns);
            if ($consistentColumnCount === null) {
                $consistentColumnCount = $columnCount;
            } elseif ($consistentColumnCount !== $columnCount) {
                return null; // Inconsistent column count
            }
            $dataLinesToCheck++;
        }

        return $consistentColumnCount;
    }

    /**
     * Check if header structure is valid (same count or one less than data).
     */
    private function isValidHeaderStructure(int $headerColumnCount, int $dataColumnCount): bool
    {
        return $headerColumnCount === $dataColumnCount || $headerColumnCount === $dataColumnCount - 1;
    }

    /**
     * Find first data line as potential header if no # header was found.
     *
     * @return array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}|null
     */
    private function findFirstDataLineAsHeader($handle): ?array
    {
        rewind($handle);
        $lineIndex = 0;
        while (($line = fgets($handle)) !== false) {
            $trimmedLine = mb_trim($line);
            if (empty($trimmedLine) || str_starts_with($trimmedLine, '#')) {
                $lineIndex++;

                continue;
            }
            // Found first data line
            $headerColumnCount = count(str_getcsv($trimmedLine, "\t", escape: '\\'));
            $dataColumnCount = $this->getConsistentDataColumnCount($handle);
            if ($dataColumnCount !== null && $this->isValidHeaderStructure($headerColumnCount, $dataColumnCount)) {
                return [
                    'headerLineIndex' => $lineIndex,
                    'headerColumnCount' => $headerColumnCount,
                    'dataColumnCount' => $dataColumnCount,
                ];
            }
            break;
        }

        return null;
    }

    /**
     * Create cleaned file with processed headers and without comments.
     *
     * @param  array{headerLineIndex: ?int, headerColumnCount: ?int, dataColumnCount: ?int}  $headerInfo
     */
    private function createCleanedFile($inputHandle, string $outputPath, array $headerInfo): void
    {
        if (! is_resource($inputHandle)) {
            throw new RuntimeException('Invalid input file handle provided.');
        }
        $outputHandle = $this->openFileForWriting($outputPath);
        try {
            $this->processAndWriteCleanedContent($inputHandle, $outputHandle, $headerInfo);
        } finally {
            fclose($outputHandle);
        }
    }

    /**
     * Open file for writing with proper error handling.
     */
    private function openFileForWriting(string $fileName)
    {
        $handle = fopen(Storage::path($fileName), 'wb');
        if (! $handle) {
            throw new RuntimeException("Cannot create temporary file: {$fileName}");
        }

        return $handle;
    }

    /**
     * Process input file and write cleaned content to output file.
     *
     * @param  array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}  $headerInfo
     */
    private function processAndWriteCleanedContent($inputHandle, $outputHandle, array $headerInfo): void
    {
        if (! is_resource($inputHandle) || ! is_resource($outputHandle)) {
            throw new RuntimeException('Invalid file handles provided.');
        }
        rewind($inputHandle);
        $currentLineIndex = 0;
        $headerProcessed = false;

        while (($line = fgets($inputHandle)) !== false) {
            $trimmedLine = mb_trim($line);
            if (empty($trimmedLine)) {
                continue;
            }
            if (str_starts_with($trimmedLine, '#')) {
                $headerProcessed = $this->processCommentLine(
                    $outputHandle,
                    $trimmedLine,
                    $currentLineIndex,
                    $headerInfo,
                    $headerProcessed
                );

                $currentLineIndex++;

                continue;
            }

            $headerProcessed = $this->processDataLine(
                $outputHandle,
                $line,
                $trimmedLine,
                $currentLineIndex,
                $headerInfo,
                $headerProcessed
            );

            $currentLineIndex++;
        }
    }

    /**
     * Process comment lines (lines starting with #).
     *
     * @param  array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}  $headerInfo
     */
    private function processCommentLine(
        $outputHandle,
        string $trimmedLine,
        int $currentLineIndex,
        array $headerInfo,
        bool $headerProcessed
    ): bool {
        // Only process the identified header line, skip all other comments
        if ($currentLineIndex === $headerInfo['headerLineIndex'] && ! $headerProcessed) {
            $headerContent = mb_substr($trimmedLine, 1); // Remove #
            $headerColumns = str_getcsv(mb_trim($headerContent), "\t", escape: '\\');

            if ($this->shouldAddIdColumn($headerInfo)) {
                array_unshift($headerColumns, self::DEFAULT_ID_COLUMN_NAME);
            }
            fputcsv($outputHandle, $headerColumns, separator: "\t", escape: '\\');
            // fwrite($outputHandle, implode("\t", $headerColumns)."\n");

            return true;
        }

        return $headerProcessed;
    }

    /**
     * Process data lines.
     *
     * @param  array{headerLineIndex: int, headerColumnCount: int, dataColumnCount: int}  $headerInfo
     */
    private function processDataLine(
        $outputHandle,
        string $line,
        string $trimmedLine,
        int $currentLineIndex,
        array $headerInfo,
        bool $headerProcessed
    ): bool {
        if (! $headerProcessed && $currentLineIndex === $headerInfo['headerLineIndex']) {
            $headerColumns = str_getcsv($trimmedLine, "\t", escape: '\\');
            if ($this->shouldAddIdColumn($headerInfo)) {
                array_unshift($headerColumns, self::DEFAULT_ID_COLUMN_NAME);
            }
            fputcsv($outputHandle, $headerColumns, separator: "\t", escape: '\\');

            return true;
        }

        if ($headerProcessed) {
            fwrite($outputHandle, $line);
        }

        return $headerProcessed;
    }

    /**
     * Determine if an ID column should be added to the header.
     */
    private function shouldAddIdColumn(array $headerInfo): bool
    {
        return $headerInfo['headerColumnCount'] === $headerInfo['dataColumnCount'] - 1;
    }
}
