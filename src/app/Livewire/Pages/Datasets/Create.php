<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets;

use App\Jobs\ProcessDatasetJob;
use Exception;
use Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

final class Create extends Component
{
    use WithFileUploads;

    public const string REQUIRED_TSV_FILES_RULES = 'required|file|mimes:txt,tsv,gz';

    public const string OPTIONAL_TSV_FILES_RULES = 'nullable|file|mimes:txt,tsv,gz';

    public const string OPTIONAL_FILES_RULES = 'nullable|file';

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
    #[Validate(self::REQUIRED_TSV_FILES_RULES)]
    public $taxonomyFile;

    #[Validate(self::REQUIRED_TSV_FILES_RULES)]
    public $asvTableFile;

    #[Validate(self::REQUIRED_TSV_FILES_RULES)]
    public $metadataFile;

    // Alpha diversity files
    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $faithFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $chaoFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $evennessFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $shannonFile;

    // Beta diversity files
    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $jaccardFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $brayCurtisFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $unweightedUnifracFile;

    #[Validate(self::OPTIONAL_FILES_RULES)]
    public $weightedUnifracFile;

    #[Validate(self::OPTIONAL_TSV_FILES_RULES)]
    public $picrustKoFile;

    #[Validate(self::OPTIONAL_TSV_FILES_RULES)]
    public $picrustEcFile;

    #[Validate(self::OPTIONAL_TSV_FILES_RULES)]
    public $picrustPathwaysFile;

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
    public array $availableSampleFields = [];

    public function mount(): void {}

    public function updatedMetadataFile(): void
    {
        if (! $this->metadataFile) {
            return;
        }

        // Read directly from the temporary upload location
        $path = $this->metadataFile->getRealPath();
        $handle = fopen($path, 'rb');

        // Get headers
        $headers = fgetcsv($handle, 0, "\t", escape: '\\');
        $this->metadataColumns = $headers;

        // Get preview data (first 5 rows)
        $this->metadataPreview = [];
        for ($i = 0; $i < 5; $i++) {
            $row = fgetcsv($handle, 0, "\t", escape: '\\');
            if ($row) {
                $this->metadataPreview[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        // Auto-map columns based on similarity
        $this->autoMapColumns();
    }

    public function nextStep(): void
    {
        // Validate only current step
        match ($this->currentStep) {
            1 => $this->validate(
                [
                    'name' => 'required|min:3|max:255',
                    'description' => 'required|min:3|max:1000',
                    'dataType' => 'required|in:processed',
                ]
            ),
            2 => $this->validate(
                [
                    'taxonomyFile' => self::REQUIRED_TSV_FILES_RULES,
                    'asvTableFile' => self::REQUIRED_TSV_FILES_RULES,
                    'metadataFile' => self::REQUIRED_TSV_FILES_RULES,
                    'faithFile' => self::OPTIONAL_FILES_RULES,
                    'chaoFile' => self::OPTIONAL_FILES_RULES,
                    'evennessFile' => self::OPTIONAL_FILES_RULES,
                    'shannonFile' => self::OPTIONAL_FILES_RULES,
                    'jaccardFile' => self::OPTIONAL_FILES_RULES,
                    'brayCurtisFile' => self::OPTIONAL_FILES_RULES,
                    'unweightedUnifracFile' => self::OPTIONAL_FILES_RULES,
                    'weightedUnifracFile' => self::OPTIONAL_FILES_RULES,
                    'picrustKoFile' => self::OPTIONAL_TSV_FILES_RULES,
                    'picrustEcFile' => self::OPTIONAL_TSV_FILES_RULES,
                    'picrustPathwaysFile' => self::OPTIONAL_TSV_FILES_RULES,
                ]
            ),
            3 => $this->validate(
                [
                    'sampleCodeColumn' => 'required|string',
                    'columnMapping' => 'required|array',
                ]
            ), // Dataset metadata is optional
            default => true,
        };

        $this->currentStep++;
    }

    public function previousStep(): void
    {
        $this->currentStep--;
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
        $this->validate();

        $tempDir = 'datasets/'.Str::uuid();

        try {
            // Create temporary storage directory only when submitting
            Storage::makeDirectory($tempDir);

            // Store files
            $files = $this->prepareDatasetFilesArray();

            $uploadedFiles = [];
            foreach ($files as $name => $file) {
                /** @var TemporaryUploadedFile|null $file */
                if ($file) {
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    if ($extension === 'gz') {
                        $extension = 'tsv.gz';
                    }
                    $storedName = $name.'.'.$extension;
                    $file->storeAs($tempDir, $storedName);
                    $uploadedFiles[$name] = [
                        'original_name' => $originalName,
                        'stored_name' => $storedName,
                    ];
                }
            }

            // Dispatch job to handle all database operations
            ProcessDatasetJob::dispatch(
                userId: auth()->id(),
                name: $this->name,
                description: $this->description,
                storagePath: $tempDir,
                columnMapping: $this->columnMapping,
                sampleCodeColumn: $this->sampleCodeColumn,
                datasetMetadata: $this->datasetMetadata,
                uploadedFiles: $uploadedFiles,
            );

            Flux::toast(
                text: 'Your dataset is being processed. You will be notified when it is ready.',
                heading: 'Dataset creation started',
                variant: 'success'
            );

            $this->redirect(route('datasets.index'), navigate: true);
        } catch (Exception $e) {
            report($e);
            if (Storage::directoryExists($tempDir)) {
                // Delete the temporary directory if it exists
                Storage::deleteDirectory($tempDir);
            }

            Flux::toast(
                text: 'An error occurred while creating your dataset.',
                heading: 'Error',
                variant: 'danger'
            );
        }
    }

    public function render(): View
    {
        return view('livewire.pages.datasets.create');
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function uploadedFiles(): array
    {
        $files = $this->prepareDatasetFilesArray();

        return Arr::mapWithKeys(
            $files,
            static function (?TemporaryUploadedFile $file, string $name): array {
                if (! $file) {
                    return [];
                }

                return [$name => $file->getClientOriginalName()];
            }
        );
    }

    protected function autoMapColumns(): void
    {
        $this->columnMapping = [];

        // Find best match for sample code column
        $sampleCodeMatches = array_filter(
            $this->metadataColumns,
            static function ($column) {
                return Str::contains(Str::lower($column), ['sample', 'code', 'id']);
            }
        );
        $this->sampleCodeColumn = count($sampleCodeMatches) > 0 ? array_values(
            $sampleCodeMatches
        )[0] : $this->metadataColumns[0];

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

    /**
     * @return array{
     *     taxonomy: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile,
     *     asvTable: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile,
     *     metadata: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile,
     *     picrust: array{
     *         ko: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         pathways: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         ec: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null
     *     },
     *     alphaDiversity: array{
     *         faith: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         chao: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         evenness: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         shannon: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null
     *     },
     *     betaDiversity: array{
     *         jaccard: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         brayCurtis: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         unweightedUnifrac: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null,
     *         weightedUnifrac: \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null
     *     }
     * }
     */
    protected function prepareDatasetFilesArray(): array
    {
        return Arr::dot(
            [
                'taxonomy' => $this->taxonomyFile,
                'asvTable' => $this->asvTableFile,
                'metadata' => $this->metadataFile,
                'picrust' => [
                    'ko' => $this->picrustKoFile,
                    'pathways' => $this->picrustPathwaysFile,
                    'ec' => $this->picrustEcFile,
                ],
                'alphaDiversity' => [
                    'faith' => $this->faithFile,
                    'chao' => $this->chaoFile,
                    'evenness' => $this->evennessFile,
                    'shannon' => $this->shannonFile,
                ],
                'betaDiversity' => [
                    'jaccard' => $this->jaccardFile,
                    'brayCurtis' => $this->brayCurtisFile,
                    'unweightedUnifrac' => $this->unweightedUnifracFile,
                    'weightedUnifrac' => $this->weightedUnifracFile,
                ],
            ]
        );
    }
}
