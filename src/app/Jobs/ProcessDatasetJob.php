<?php

namespace App\Jobs;

use App\Models\Dataset;
use App\Models\DatasetMetadata;
use App\Models\GeneticFeature;
use App\Models\Sample;
use App\Models\SampleMetadata;
use App\Models\Taxon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessDatasetJob implements ShouldQueue
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

    public function handle(): void
    {
        DB::beginTransaction();
        try {
            // Create the dataset
            $dataset = Dataset::create([
                'name' => $this->name,
                'description' => $this->description,
                'created_by' => $this->userId,
            ]);

            // Process dataset metadata
            foreach ($this->datasetMetadata as $metadata) {
                if (! empty($metadata['key']) && ! empty($metadata['value'])) {
                    DatasetMetadata::create([
                        'dataset_id' => $dataset->id,
                        'key' => $metadata['key'],
                        'value' => $metadata['value'],
                    ]);
                }
            }

            // Store original file names as dataset metadata
            foreach ($this->uploadedFiles as $type => $fileInfo) {
                DatasetMetadata::create([
                    'dataset_id' => $dataset->id,
                    'key' => "original_{$type}_filename",
                    'value' => $fileInfo['original_name'],
                ]);
            }

            // Process files and create records
            $this->processMetadataFile($dataset);
            $this->processTaxonomyFile($dataset);
            $this->processASVTable($dataset);
            $this->processPICRUStFile($dataset);

            // Move files to final location
            $finalPath = "datasets/{$dataset->id}/processed";
            Storage::makeDirectory($finalPath);
            foreach ($this->uploadedFiles as $type => $fileInfo) {
                Storage::move(
                    $this->storagePath.'/'.$fileInfo['stored_name'],
                    $finalPath.'/'.$fileInfo['stored_name']
                );
            }
            Storage::deleteDirectory($this->storagePath);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Storage::deleteDirectory($this->storagePath);
            throw $e;
        }
    }

    protected function processTaxonomyFile(Dataset $dataset): void
    {
        $filePath = "{$this->storagePath}/taxonomy.tsv";
        $handle = fopen(Storage::path($filePath), 'r');
        $headers = fgetcsv($handle, 0, "\t");

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            $data = array_combine($headers, $row);

            // Extract taxonomy from full path if needed
            $taxonomy = $this->extractTaxonomy($data['Taxon']);

            Taxon::create([
                'feature_id' => $data['Feature ID'],
                'kingdom' => $taxonomy['kingdom'] ?? null,
                'phylum' => $taxonomy['phylum'] ?? null,
                'class' => $taxonomy['class'] ?? null,
                'order' => $taxonomy['order'] ?? null,
                'family' => $taxonomy['family'] ?? null,
                'genus' => $taxonomy['genus'] ?? null,
                'species' => $taxonomy['species'] ?? null,
            ]);
        }

        fclose($handle);
    }

    protected function processASVTable(Dataset $dataset): void
    {
        $filePath = "{$this->storagePath}/asv_table.tsv";
        $handle = fopen(Storage::path($filePath), 'r');
        $headers = fgetcsv($handle, 0, "\t");

        // First column is Feature ID, rest are sample codes
        $sampleCodes = array_slice($headers, 1);

        // Cache sample IDs to avoid repeated queries
        $sampleIds = Sample::where('dataset_id', $dataset->id)
            ->whereIn('sample_code', $sampleCodes)
            ->pluck('id', 'sample_code')
            ->all();

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            $featureId = $row[0];
            $abundances = array_slice($row, 1);

            // Create metadata records in bulk
            $records = [];
            foreach ($sampleCodes as $index => $sampleCode) {
                if (isset($sampleIds[$sampleCode]) && $abundances[$index] > 0) {
                    $records[] = [
                        'sample_id' => $sampleIds[$sampleCode],
                        'key' => 'asv_abundance',
                        'value' => json_encode([
                            'feature_id' => $featureId,
                            'abundance' => (float) $abundances[$index],
                        ]),
                    ];
                }
            }

            if (! empty($records)) {
                SampleMetadata::insert($records);
            }
        }

        fclose($handle);
    }

    protected function processMetadataFile(Dataset $dataset): void
    {
        $filePath = "{$this->storagePath}/metadata.tsv";
        $handle = fopen(Storage::path($filePath), 'r');
        $headers = fgetcsv($handle, 0, "\t");

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            $data = array_combine($headers, $row);
            $sampleCode = $data[$this->sampleCodeColumn];

            // Create sample record
            $sample = Sample::create([
                'dataset_id' => $dataset->id,
                'sample_code' => $sampleCode,
            ] + $this->mapSampleFields($data));

            // Store custom metadata
            $metadataRecords = [];
            foreach ($data as $column => $value) {
                if ($this->columnMapping[$column] === 'custom') {
                    $metadataRecords[] = [
                        'sample_id' => $sample->id,
                        'key' => $column,
                        'value' => json_encode($value),
                    ];
                }
            }

            if (! empty($metadataRecords)) {
                SampleMetadata::insert($metadataRecords);
            }
        }

        fclose($handle);
    }

    protected function processPICRUStFile(Dataset $dataset): void
    {
        $filePath = "{$this->storagePath}/picrust.tsv";
        $handle = fopen(Storage::path($filePath), 'r');
        $headers = fgetcsv($handle, 0, "\t");

        // First two columns are feature ID and description, rest are sample codes
        $sampleCodes = array_slice($headers, 2);

        // Cache sample IDs
        $sampleIds = Sample::where('dataset_id', $dataset->id)
            ->whereIn('sample_code', $sampleCodes)
            ->pluck('id', 'sample_code')
            ->all();

        while (($row = fgetcsv($handle, 0, "\t")) !== false) {
            $featureId = $row[0];
            $description = $row[1];
            $values = array_slice($row, 2);

            // Store genetic feature
            GeneticFeature::create([
                'feature_id' => $featureId,
                'feature_type' => 'picrust',
                'feature_name' => $description,
            ]);

            // Store predictions in bulk
            $records = [];
            foreach ($sampleCodes as $index => $sampleCode) {
                if (isset($sampleIds[$sampleCode]) && $values[$index] > 0) {
                    $records[] = [
                        'sample_id' => $sampleIds[$sampleCode],
                        'key' => 'picrust_prediction',
                        'value' => json_encode([
                            'feature_id' => $featureId,
                            'value' => (float) $values[$index],
                        ]),
                    ];
                }
            }

            if (! empty($records)) {
                SampleMetadata::insert($records);
            }
        }

        fclose($handle);
    }

    protected function mapSampleFields(array $data): array
    {
        $mappedFields = [];
        foreach ($this->columnMapping as $column => $field) {
            if ($field !== 'custom' && $field !== 'exclude') {
                $mappedFields[$field] = $data[$column];
            }
        }

        return $mappedFields;
    }

    protected function extractTaxonomy(string $taxonPath): array
    {
        // Example implementation - adjust based on your taxonomy format
        $levels = ['kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'species'];
        $parts = array_map('trim', explode(';', $taxonPath));

        $taxonomy = [];
        foreach ($parts as $i => $part) {
            if (isset($levels[$i]) && ! empty($part)) {
                $taxonomy[$levels[$i]] = $part;
            }
        }

        return $taxonomy;
    }
}
