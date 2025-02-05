<?php

namespace App\Livewire\Pages\Datasets;

use App\Jobs\ProcessDatasetJob;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    // Current step tracking
    public int $currentStep = 1;

    public string $tempDir;

    // Step 1 - Basic Information
    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:3|max:1000')]
    public string $description = '';

    #[Validate('required|in:processed')]
    public string $dataType = 'processed';

    // Step 2 - File Uploads
    #[Validate('required|file|mimes:txt,tsv')]
    public $taxonomyFile;

    #[Validate('required|file|mimes:txt,tsv')]
    public $sampleDataFile;

    #[Validate('required|file|mimes:txt,tsv')]
    public $metadataFile;

    #[Validate('required|file')]
    public $brayCurtisFile;

    #[Validate('required|file')]
    public $shannonFile;

    /** @var array<string, array{original_name: string, stored_name: string}> */
    public array $uploadedFiles = [];

    // Step 3 - Metadata Mapping
    /** @var array<int, string> */
    public array $metadataColumns = [];

    /** @var array<string, string> */
    public array $columnMapping = [];

    /** @var array<int, array<string, string>> */
    public array $metadataPreview = [];

    public string $sampleCodeColumn = '';

    // Step 4 - Dataset Metadata
    /** @var array<int, array{key: string, value: string}> */
    public array $datasetMetadata = [];

    // Available sample fields for mapping
    /** @var array<int, string> */
    public array $availableSampleFields = [
        'variety',
        'plant_stage',
        'biological_replica',
        'sample_conditions',
        'plant_section',
        'sampling_date',
        'location',
    ];

    public function mount(): void
    {
        $this->tempDir = 'temp/'.Str::uuid();
        Storage::makeDirectory($this->tempDir);
    }

    public function updatedMetadataFile(): void
    {
        if (! $this->metadataFile) {
            return;
        }

        // Read the first few lines of the metadata file to get columns and preview
        $path = $this->metadataFile->getRealPath();
        $handle = fopen($path, 'r');

        // Get headers
        $headers = fgetcsv($handle, 0, "\t");
        $this->metadataColumns = $headers ?? [];

        // Get preview data (first 5 rows)
        $this->metadataPreview = [];
        for ($i = 0; $i < 5; $i++) {
            $row = fgetcsv($handle, 0, "\t");
            if ($row) {
                $this->metadataPreview[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        // Auto-map columns based on similarity
        $this->autoMapColumns();
    }

    protected function autoMapColumns(): void
    {
        $this->columnMapping = [];

        // Find best match for sample code column
        $sampleCodeMatches = array_filter($this->metadataColumns, function ($column) {
            return Str::contains(Str::lower($column), ['sample', 'code', 'id']);
        });
        $this->sampleCodeColumn = count($sampleCodeMatches) > 0 ? array_values($sampleCodeMatches)[0] : $this->metadataColumns[0];

        // Map other columns
        foreach ($this->metadataColumns as $column) {
            if ($column === $this->sampleCodeColumn) {
                continue;
            }

            $bestMatch = null;
            $highestSimilarity = 0;

            foreach ($this->availableSampleFields as $field) {
                $similarity = similar_text(Str::lower($column), $field, $percent);
                if ($percent > $highestSimilarity && $percent > 70) {
                    $highestSimilarity = $percent;
                    $bestMatch = $field;
                }
            }

            $this->columnMapping[$column] = $bestMatch ?? 'custom';
        }
    }

    public function nextStep(): void
    {
        // Validate only current step
        match ($this->currentStep) {
            1 => $this->validate([
                'name' => 'required|min:3|max:255',
                'description' => 'required|min:3|max:1000',
                'dataType' => 'required|in:processed',
            ]),
            2 => $this->validate([
                'taxonomyFile' => 'required|file|mimes:txt,tsv',
                'sampleDataFile' => 'required|file|mimes:txt,tsv',
                'metadataFile' => 'required|file|mimes:txt,tsv',
                'brayCurtisFile' => 'required|file',
                'shannonFile' => 'required|file',
            ]),
            3 => $this->validate([
                'sampleCodeColumn' => 'required|string',
                'columnMapping' => 'required|array',
            ]),
            4 => true, // Dataset metadata is optional
            default => true,
        };

        if ($this->currentStep === 2) {
            $this->saveUploadedFiles();
        }

        $this->currentStep++;
    }

    public function previousStep(): void
    {
        $this->currentStep--;
    }

    protected function saveUploadedFiles(): void
    {
        $files = [
            'taxonomy' => $this->taxonomyFile,
            'sample_data' => $this->sampleDataFile,
            'metadata' => $this->metadataFile,
            'bray_curtis' => $this->brayCurtisFile,
            'shannon' => $this->shannonFile,
        ];

        $fileNames = [];
        foreach ($files as $name => $file) {
            if ($file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileNames[$name] = [
                    'original_name' => $originalName,
                    'stored_name' => $name.'.'.$extension,
                ];
                $file->storeAs($this->tempDir, $name.'.'.$extension);
            }
        }

        // Store file names in the component for passing to the job
        $this->uploadedFiles = $fileNames;
    }

    public function addDatasetMetadata(): void
    {
        $this->datasetMetadata[] = ['key' => '', 'value' => ''];
    }

    public function removeDatasetMetadata(int $index): void
    {
        unset($this->datasetMetadata[$index]);
        $this->datasetMetadata = array_values($this->datasetMetadata);
    }

    public function submit(): void
    {
        // Create dataset processing job
        dispatch(new ProcessDatasetJob(
            userId: auth()->id(),
            name: $this->name,
            description: $this->description,
            tempDir: $this->tempDir,
            columnMapping: $this->columnMapping,
            sampleCodeColumn: $this->sampleCodeColumn,
            datasetMetadata: array_filter($this->datasetMetadata, fn ($item) => ! empty($item['key']) && ! empty($item['value'])
            ),
            uploadedFiles: $this->uploadedFiles
        ));

        Flux::toast(
            text: 'Your dataset is being processed. You will be notified when it is ready.',
            heading: 'Dataset creation started',
            variant: 'success'
        );

        $this->redirect(route('datasets.index'));
    }

    public function cancel(): void
    {
        Storage::deleteDirectory($this->tempDir);
        $this->redirect(route('datasets.index'));
    }

    public function render(): View
    {
        return view('livewire.pages.datasets.create');
    }
}
