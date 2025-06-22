<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CombineDatasetsAction;
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
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * @template TConditionValue of array<int, string> = array<int, string>
 * @template TConditionsArray of array{key: string, values: TConditionValue} = array{key: string, values: TConditionValue}
 * @template TConnector of "AND"|"OR"|"NOT" = "AND"|"OR"|"NOT"
 * @template TConnectorsArray of array<int, TConnector> = array<int, TConnector>
 */
final class CombineDatasetsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $datasetMetadata
     * @param  array<int, array{id: int, criteria: array{includeAllSamples: bool, conditions: array<int, TConditionsArray>, connectors: TConnectorsArray}}>  $selectedDatasets
     * @param  array<string, array{datasets: array<int, string>, default_values: array<int, string|null>}>  $samplesMetadataPairing
     */
    public function __construct(
        public int $userId,
        public string $name,
        public string $description,
        public array $datasetMetadata,
        public array $selectedDatasets,
        public array $samplesMetadataPairing
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        DB::beginTransaction();
        $toDeleteIfFailed = [];
        try {
            $datasets = $this->identifyUniqueDatasets();
            $commonFileTypes = $this->findDatasetsFiles($datasets);
            $this->removeNonCommonFiles($datasets, $commonFileTypes);

            $newDataset = $this->createDataset();
            $storagePath = "datasets/{$newDataset->id}/processed";
            Storage::makeDirectory($storagePath);
            $toDeleteIfFailed[] = $storagePath;
            $outputFiles = $this->buildOutputFilesArray($datasets, $commonFileTypes, $storagePath);

            $configFile = $this->writeConfigFile($datasets, $outputFiles, $storagePath);

            new CombineDatasetsAction($configFile)->handle();

            $newDataset->update(
                [
                    'files' => new DatasetFilesDataObject(Arr::undot($outputFiles)),
                ]
            );

            $this->processMetadataFile($newDataset);

            DB::commit();
            User::find($this->userId)?->notify(
                new GeneralNotification(
                    title: 'Datasets combined successfully',
                    message: 'Your datasets have been combined successfully.',
                    level: NotificationLevel::SUCCESS
                )
            );
            RefreshDatasets::dispatch($this->userId);
        } catch (Throwable $e) {
            DB::rollBack();
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
                    'dataset_metadata' => $this->datasetMetadata,
                    'selected_datasets' => $this->selectedDatasets,
                    'samples_metadata_pairing' => $this->samplesMetadataPairing,
                ]
            );
            User::find($this->userId)?->notify(
                new GeneralNotification(
                    title: 'Datasets combination failed',
                    message: 'An error occurred while combining your datasets: :message',
                    level: NotificationLevel::ERROR,
                    replace: ['message' => $e->getMessage()]
                )
            );
            throw $e;
        }
    }

    /**
     * Identify unique datasets and their samples.
     *
     * @return array<int, array{samples: array<int, string>}>
     */
    private function identifyUniqueDatasets(): array
    {
        $datasets = [];
        foreach ($this->selectedDatasets as $index => ['id' => $datasetId]) {
            $data = $datasets[$datasetId] ?? [
                'samples' => [],
            ];

            $samples = $this->identifySamplesFromSelectedDataset($index);
            $data['samples'] = array_unique(array_merge($data['samples'], $samples));

            $datasets[$datasetId] = $data;
        }

        return $datasets;
    }

    /**
     * Identify samples from a selected dataset.
     *
     * @return array<int, string>
     */
    private function identifySamplesFromSelectedDataset(int $datasetIndex): array
    {
        $dataset = $this->selectedDatasets[$datasetIndex];
        $criteria = $dataset['criteria'];
        $conditions = $criteria['conditions'];

        $query = Sample::where('dataset_id', $dataset['id']);

        if ($criteria['includeAllSamples'] || empty($conditions)) {
            return $query->pluck('sample_code')->toArray();
        }

        foreach ($conditions as $index => $condition) {
            if (empty($condition['key']) || empty($condition['values'])) {
                continue;
            }

            $connector = $criteria['connectors'][$index - 1] ?? 'AND';

            $subQuery = static function (Builder $q) use ($condition) {
                $q->whereHas(
                    'metadata',
                    static function (Builder $metaQuery) use ($condition) {
                        $metaQuery->where('key', $condition['key'])
                            ->whereIn('value', $condition['values']);
                    }
                );
            };

            if ($connector === 'OR') {
                $query->orWhere($subQuery);
            } elseif ($connector === 'NOT') {
                $query->whereNot($subQuery);
            } else {
                $query->where($subQuery);
            }
        }

        return $query->pluck('sample_code')->toArray();
    }

    /**
     * Find common file types across all datasets, and add the files array to each dataset.
     *
     * @param  array<int, array{samples: array<int, string>, files?: array<string, string>}>  $datasets
     * @return array<int, string>
     */
    private function findDatasetsFiles(array &$datasets): array
    {
        $datasetModels = Dataset::whereIn('id', array_keys($datasets))->get();

        $commonFileTypes = null;

        foreach ($datasetModels as $datasetModel) {
            $id = $datasetModel->id;
            /** @var array<string, string> $files */
            $files = Arr::dot($datasetModel->files->toArray());
            $datasets[$id]['files'] = $files;
            $currentFileTypes = array_keys(array_filter($files));

            if ($commonFileTypes === null) {
                $commonFileTypes = $currentFileTypes;
            }

            $commonFileTypes = array_intersect($commonFileTypes, $currentFileTypes);
        }

        return $commonFileTypes ?? [];
    }

    /**
     * Remove files that are not common to all datasets from the list of files for each dataset.
     * This is done so that the merge script can iterate over all files and not have to check if the file is common to
     * all datasets.
     *
     * @param  array<int, array{samples: array<int, string>, files: array<string, string>}>  $datasets
     * @param  array<int, string>  $commonFileTypes
     */
    private function removeNonCommonFiles(array &$datasets, array $commonFileTypes): void
    {
        foreach ($datasets as &$dataset) {
            $dataset['files'] = array_intersect_key($dataset['files'], array_flip($commonFileTypes));
        }
    }

    private function createDataset(): Dataset
    {
        // Create the dataset
        $dataset = Dataset::create(
            [
                'name' => $this->name,
                'description' => $this->description,
                'created_by' => $this->userId,
            ]
        );

        // Process dataset metadata
        foreach ($this->datasetMetadata as $key => $value) {
            if (! empty($key) && ! empty($value)) {
                DatasetMetadata::create(
                    [
                        'dataset_id' => $dataset->id,
                        'key' => $key,
                        'value' => $value,
                    ]
                );
            }
        }

        return $dataset;
    }

    /**
     * Build the output files array for the new dataset.
     *
     * @param  array<int, array{samples: array<int, string>, files: array<string, string>}>  $datasets
     * @param  array<int, string>  $commonFileTypes
     */
    private function buildOutputFilesArray(array $datasets, array $commonFileTypes, string $storagePath): array
    {
        reset($datasets);
        $firstDatasetFiles = current($datasets)['files'];
        $outputFiles = [];

        foreach ($commonFileTypes as $fileType) {
            $extension = pathinfo($firstDatasetFiles[$fileType], PATHINFO_EXTENSION);
            $outputFiles[$fileType] = "{$storagePath}/{$fileType}.{$extension}";
        }

        return $outputFiles;
    }

    /**
     * @param  array<int, array{samples: array<int, string>, files: array<string, string>}>  $datasets
     * @param  array<string, string>  $outputFiles
     *
     * @throws JsonException
     * @throws FileNotFoundException
     */
    private function writeConfigFile(array $datasets, array $outputFiles, string $storagePath): string
    {
        $configData = [
            'datasets' => collect($datasets)->mapWithKeys(static function (array $dataset, int $id) {
                $dataset['files'] = collect($dataset['files'])->mapWithKeys(
                    static fn (string $path, $fileType) => [$fileType => Storage::path($path)]
                )->toArray();

                return [$id => $dataset];
            })->toArray(),
            'outputFiles' => collect($outputFiles)->mapWithKeys(
                static fn (string $outputFile, string $type) => [$type => Storage::path($outputFile)]
            )->toArray(),
            'metadataPairing' => $this->samplesMetadataPairing,
            'storagePath' => Storage::path($storagePath),
        ];

        $configFile = "{$storagePath}/config.json";
        Storage::put($configFile, json_encode($configData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        if (! Storage::exists($configFile)) {
            throw new FileNotFoundException('Failed to write config file');
        }

        return $configFile;
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
        array_shift($headers); // Remove the first column (sample_id) from headers

        while (($row = fgetcsv($handle, 0, "\t", escape: '\\')) !== false) {
            $sampleCode = array_shift($row); // Get the first column (sample_id)
            $data = array_combine($headers, $row);

            // Create sample record
            $sample = Sample::create([
                'dataset_id' => $dataset->id,
                'sample_code' => $sampleCode,
            ]);

            // Store custom metadata
            $metadataRecords = [];
            foreach ($data as $column => $value) {
                if ($value === 'NA' || $value === 'NaN' || $value === '') {
                    continue; // Skip empty or NA values
                }
                $metadataRecords[] = [
                    'sample_id' => $sample->id,
                    'key' => $column,
                    'value' => Json::encode($value),
                ];
            }

            if ($metadataRecords !== []) {
                SampleMetadata::insert($metadataRecords);
            }
        }

        fclose($handle);
    }
}
