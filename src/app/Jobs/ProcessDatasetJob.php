<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataObjects\DatasetFilesDataObject;
use App\Models\Dataset;
use App\Models\DatasetMetadata;
use App\Models\Sample;
use App\Models\SampleMetadata;
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
use Throwable;

final class ProcessDatasetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
            foreach ($this->uploadedFiles as $type => $fileInfo) {
                Storage::move(
                    $this->storagePath.'/'.$fileInfo['stored_name'],
                    $finalPath.'/'.$fileInfo['stored_name']
                );
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
        } catch (Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory($this->storagePath);
            Log::error(
                $e->getMessage(),
                [
                    'trace' => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    private function processMetadataFile(Dataset $dataset): void
    {
        $filePath = $dataset->files?->metadata;
        if (! $filePath) {
            throw new Exception('Metadata file not found.');
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

    //    private function extractTaxonomy(string $taxonPath): array
    //    {
    //        // Example implementation - adjust based on your taxonomy format
    //        $levels = ['kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'species'];
    //        $parts = array_map('trim', explode(';', $taxonPath));
    //
    //        $taxonomy = [];
    //        foreach ($parts as $i => $part) {
    //            if (isset($levels[$i]) && ! empty($part)) {
    //                $taxonomy[$levels[$i]] = $part;
    //            }
    //        }
    //
    //        return $taxonomy;
    //    }
}
