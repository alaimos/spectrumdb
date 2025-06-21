<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets;

use App\Models\Dataset;
use App\Models\SampleMetadata;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property Collection<int, Dataset> $selectedDatasets
 * @property Collection<int, string> $allMetadataKeys
 */
final class Combine extends Component
{
    // Current step tracking
    public int $currentStep = 1;

    // Step 2: Dataset Selection & Sample Filtering
    /** @var array<int, int> */
    public array $selectedDatasetIds = [];

    /** @var array<int, array{includeAllSamples: bool, conditions: array, connectors: array}> */
    public array $datasetSampleCriteria = [];

    // Step 3: Combined Dataset Details
    #[Validate]
    public string $name = '';

    #[Validate]
    public string $description = '';

    /** @var array<int, array{key: string, value: string}> */
    #[Validate]
    public array $combinedDatasetMetadata = [];

    // Step 4: Metadata Pairing
    /** @var array<string, array{included: bool, paired_key: string|null, datasets: array<int, string>, default_values: array<int, string>}> */
    public array $metadataPairing = [];

    public function mount(): void
    {
        $this->addDataset();
        $this->addDataset();
    }

    public function nextStep(): void
    {
        // Validate current step
        match ($this->currentStep) {
            2 => $this->validateStep2(),
            3 => $this->validateStep3(),
            4 => $this->validateStep4(),
            default => true,
        };

        if ($this->currentStep === 2) {
            $this->updateMetadataPairing();
        }

        $this->currentStep++;
    }

    public function previousStep(): void
    {
        $this->currentStep--;
    }

    public function addDataset(): void
    {
        $this->selectedDatasetIds[] = null;
        $this->initializeSampleCriteria(count($this->selectedDatasetIds) - 1);
    }

    public function removeDataset(int $index): void
    {
        if (count($this->selectedDatasetIds) <= 2) {
            Flux::toast(
                text: __('You must select at least 2 datasets to combine.'),
                heading: __('Validation Error'),
                variant: 'danger'
            );

            return;
        }

        unset($this->selectedDatasetIds[$index], $this->datasetSampleCriteria[$index]);

        $this->selectedDatasetIds = array_values($this->selectedDatasetIds);
        $this->datasetSampleCriteria = array_values($this->datasetSampleCriteria);
    }

    public function updatedSelectedDatasetIds($value, $key): void
    {
        $index = (int) $key;
        $this->initializeSampleCriteria($index);
    }

    public function toggleSampleSelectionType(int $index): void
    {
        $this->datasetSampleCriteria[$index]['includeAllSamples'] = ! $this->datasetSampleCriteria[$index]['includeAllSamples'];

        if ($this->datasetSampleCriteria[$index]['includeAllSamples']) {
            $this->datasetSampleCriteria[$index]['conditions'] = [];
            $this->datasetSampleCriteria[$index]['connectors'] = [];
        } else {
            $this->addSampleCondition($index);
        }
    }

    public function addSampleCondition(int $datasetIndex): void
    {
        $this->datasetSampleCriteria[$datasetIndex]['conditions'][] = [
            'key' => '',
            'values' => [],
        ];

        if (count($this->datasetSampleCriteria[$datasetIndex]['conditions']) > 1) {
            $this->datasetSampleCriteria[$datasetIndex]['connectors'][] = 'AND';
        }
    }

    public function removeSampleCondition(int $datasetIndex, int $conditionIndex): void
    {
        unset($this->datasetSampleCriteria[$datasetIndex]['conditions'][$conditionIndex]);
        $this->datasetSampleCriteria[$datasetIndex]['conditions'] = array_values(
            $this->datasetSampleCriteria[$datasetIndex]['conditions']
        );

        if (isset($this->datasetSampleCriteria[$datasetIndex]['connectors'][$conditionIndex - 1])) {
            unset($this->datasetSampleCriteria[$datasetIndex]['connectors'][$conditionIndex - 1]);
            $this->datasetSampleCriteria[$datasetIndex]['connectors'] = array_values(
                $this->datasetSampleCriteria[$datasetIndex]['connectors']
            );
        }
    }

    public function addCombinedDatasetMetadata(): void
    {
        $this->combinedDatasetMetadata[] = ['key' => '', 'value' => ''];
    }

    public function removeCombinedDatasetMetadata(int $index): void
    {
        unset($this->combinedDatasetMetadata[$index]);
        $this->combinedDatasetMetadata = array_values($this->combinedDatasetMetadata);
    }

    public function pairMetadataAutomatically(): void
    {
        $this->updateMetadataPairing();
    }

    public function combine(): void
    {
        $this->validate();

        dd([
            'selectedDatasetIds' => $this->selectedDatasetIds,
            'name' => $this->name,
            'description' => $this->description,
            'combinedDatasetMetadata' => $this->combinedDatasetMetadata,
            'metadataPairing' => $this->metadataPairing,
            'datasetSampleCriteria' => $this->datasetSampleCriteria,
        ]);

        // For now, just show a toast indicating the feature is not implemented
        Flux::toast(
            text: __('Dataset combination will be implemented in the next step.'),
            heading: __('Not Implemented'),
            variant: 'warning'
        );
    }

    #[Computed]
    public function availableDatasets(): Collection
    {
        return Dataset::query()
            ->visibleTo(auth()->user())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedDatasets(): Collection
    {
        $validIds = array_filter($this->selectedDatasetIds);

        return Dataset::whereIn('id', $validIds)->get();
    }

    public function getSampleMetadataKeys(int $datasetIndex): Collection
    {
        return Cache::memo()->remember(
            $this->getCacheKey($datasetIndex),
            now()->addMinutes(5),
            function () use ($datasetIndex) {
                $datasetId = $this->selectedDatasetIds[$datasetIndex] ?? null;
                if (! $datasetId) {
                    return collect();
                }

                return SampleMetadata::whereHas('sample', static function ($query) use ($datasetId) {
                    $query->where('dataset_id', $datasetId);
                })
                    ->distinct()
                    ->pluck('key')
                    ->map(fn ($key) => [
                        'key' => $key,
                        'label' => $this->getFieldLabel($key),
                    ]);
            }
        );
    }

    public function getSampleMetadataValues(int $datasetIndex, string $key): Collection
    {
        $datasetId = $this->selectedDatasetIds[$datasetIndex] ?? null;
        if (! $datasetId || ! $key) {
            return collect();
        }

        return Cache::memo()->remember(
            "sample_metadata_values_{$datasetId}_{$key}_".auth()->id(),
            now()->addMinutes(5),
            function () use ($datasetId, $key) {
                return SampleMetadata::whereHas('sample', static function ($query) use ($datasetId) {
                    $query->where('dataset_id', $datasetId);
                })
                    ->where('key', $key)
                    ->distinct()
                    ->pluck('value')
                    ->filter()
                    ->sort()
                    ->values();
            }
        );
    }

    #[Computed]
    public function allMetadataKeys(): Collection
    {
        $keys = collect();

        foreach ($this->selectedDatasets as $dataset) {
            $datasetKeys = SampleMetadata::whereHas('sample', static function ($query) use ($dataset) {
                $query->where('dataset_id', $dataset->id);
            })
                ->distinct()
                ->pluck('key');

            $keys = $keys->merge($datasetKeys);
        }

        return $keys->unique()->sort()->values();
    }

    public function getFieldLabel(string $key): string
    {
        return str($key)->replace('_', ' ')->title()->toString();
    }

    public function getSampleCount(int $datasetIndex): int
    {
        $datasetId = $this->selectedDatasetIds[$datasetIndex] ?? null;
        if (! $datasetId) {
            return 0;
        }

        $dataset = Dataset::find($datasetId);
        if (! $dataset) {
            return 0;
        }

        // If including all samples, return total count
        if ($this->datasetSampleCriteria[$datasetIndex]['includeAllSamples']) {
            return $dataset->samples()->count();
        }

        // If no conditions are set, return total count
        $conditions = $this->datasetSampleCriteria[$datasetIndex]['conditions'] ?? [];
        if (empty($conditions)) {
            return $dataset->samples()->count();
        }

        // Apply filtering conditions
        $query = $dataset->samples();

        foreach ($conditions as $index => $condition) {
            if (empty($condition['key']) || empty($condition['values'])) {
                continue;
            }

            $connector = $index > 0 ? ($this->datasetSampleCriteria[$datasetIndex]['connectors'][$index - 1] ?? 'AND') : null;

            $subQuery = function ($q) use ($condition) {
                $q->whereHas('metadata', function ($metaQuery) use ($condition) {
                    $metaQuery->where('key', $condition['key'])
                        ->whereIn('value', $condition['values']);
                });
            };

            if ($connector === 'OR') {
                $query->orWhere($subQuery);
            } elseif ($connector === 'NOT') {
                $query->whereNot($subQuery);
            } else {
                $query->where($subQuery);
            }
        }

        return $query->count();
    }

    public function render(): View
    {
        return view('livewire.pages.datasets.combine');
    }

    protected function rules(): array
    {
        return [
            // Step 2 validation
            'selectedDatasetIds' => ['required', 'array', 'min:2'],
            'selectedDatasetIds.*' => ['required', 'integer', 'exists:datasets,id'],

            // Step 3 validation
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:3', 'max:1000'],

            // Combined dataset metadata validation
            'combinedDatasetMetadata' => ['array'],
            'combinedDatasetMetadata.*.key' => [
                'required_with:combinedDatasetMetadata.*.value',
                'string',
                'max:255',
                'not_regex:/^original_.+_filename$/',
            ],
            'combinedDatasetMetadata.*.value' => [
                'required_with:combinedDatasetMetadata.*.key',
                'string',
                'max:2000',
            ],

        ];
    }

    protected function messages(): array
    {
        return [
            'selectedDatasetIds.min' => __('Please select at least 2 datasets to combine.'),
            'selectedDatasetIds.*.required' => __('Please select a dataset.'),
            'selectedDatasetIds.*.exists' => __('The selected dataset does not exist or you do not have access to it.'),
            'combinedDatasetMetadata.*.key.required_with' => __('Please enter a metadata key.'),
            'combinedDatasetMetadata.*.key.not_regex' => __('This metadata key is reserved and cannot be used.'),
            'combinedDatasetMetadata.*.value.required_with' => __('Please enter a metadata value.'),
        ];
    }

    private function validateStep2(): void
    {
        $this->validate([
            'selectedDatasetIds' => ['required', 'array', 'min:2'],
            'selectedDatasetIds.*' => ['required', 'integer', 'exists:datasets,id'],
        ]);
    }

    private function validateStep3(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:3', 'max:1000'],
            'combinedDatasetMetadata' => ['array'],
            'combinedDatasetMetadata.*.key' => [
                'required_with:combinedDatasetMetadata.*.value',
                'string',
                'max:255',
                'not_regex:/^original_.+_filename$/',
            ],
            'combinedDatasetMetadata.*.value' => [
                'required_with:combinedDatasetMetadata.*.key',
                'string',
                'max:2000',
            ],
        ]);
    }

    private function validateStep4(): void
    {
        // Validation for metadata pairing step if needed
        // For now, we'll skip validation as this is more of a configuration step
    }

    private function getCacheKey(int $datasetIndex): string
    {
        return "sample_metadata_keys_{$this->selectedDatasetIds[$datasetIndex]}_".auth()->id();
    }

    private function initializeSampleCriteria(int $index): void
    {
        $this->datasetSampleCriteria[$index] = [
            'includeAllSamples' => true,
            'conditions' => [],
            'connectors' => [],
        ];
    }

    private function updateMetadataPairing(): void
    {
        $allKeys = $this->allMetadataKeys;
        $existingKeys = array_keys($this->metadataPairing);

        // Remove keys that are no longer present
        foreach ($existingKeys as $key) {
            if (! $allKeys->contains($key)) {
                unset($this->metadataPairing[$key]);
            }
        }

        // Add new keys and auto-pair
        foreach ($allKeys as $key) {
            if (! isset($this->metadataPairing[$key])) {
                $this->metadataPairing[$key] = [
                    'included' => true, // Default to included
                    'paired_key' => $key, // Auto-pair with same name
                    'datasets' => [],
                    'default_values' => [],
                ];

                // Track which datasets have this metadata and set default values for missing ones
                foreach ($this->selectedDatasets as $dataset) {
                    $hasMetadata = SampleMetadata::whereHas('sample', static function ($query) use ($dataset) {
                        $query->where('dataset_id', $dataset->id);
                    })->where('key', $key)->exists();

                    if ($hasMetadata) {
                        $this->metadataPairing[$key]['datasets'][$dataset->id] = $dataset->name;
                    } else {
                        // Set default value for datasets that don't have this metadata
                        $this->metadataPairing[$key]['default_values'][$dataset->id] = '';
                    }
                }
            }
        }
    }
}
