<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets;

use App\Builders\DatasetAdvancedSearchBuilder;
use App\Models\Dataset;
use App\Models\SampleMetadata;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Combine extends Component
{
    /** @var array<int> */
    public array $selectedDatasetIds = [];

    /** @var array<int, array{selectAll: bool, conditions: array, connectors: array}> */
    public array $datasetSampleCriteria = [];

    #[Validate('required|min:3|max:255')]
    public string $name = '';

    #[Validate('required|min:3|max:1000')]
    public string $description = '';

    /** @var array<int, array{key: string, value: string}> */
    #[Validate('array')]
    public array $combinedDatasetMetadata = [];

    /** @var array<int, array{dataset_id: int, key: string, value: string}> */
    public array $metadataToCopy = [];

    public bool $showMetadataSection = false;

    public function mount(): void
    {
        // Initialize with empty state
    }

    public function addDataset(): void
    {
        $this->selectedDatasetIds[] = 0;
        $this->initializeSampleCriteria(count($this->selectedDatasetIds) - 1);
    }

    public function removeDataset(int $index): void
    {
        unset($this->selectedDatasetIds[$index], $this->datasetSampleCriteria[$index]);

        $this->selectedDatasetIds = array_values($this->selectedDatasetIds);
        $this->datasetSampleCriteria = array_values($this->datasetSampleCriteria);

        $this->updateMetadataToCopy();
    }

    public function updatedSelectedDatasetIds($value, $key): void
    {
        $index = (int) $key;
        $this->initializeSampleCriteria($index);
        $this->updateMetadataToCopy();
    }

    public function toggleSelectAll(int $index): void
    {
        $this->datasetSampleCriteria[$index]['selectAll'] = ! $this->datasetSampleCriteria[$index]['selectAll'];

        if ($this->datasetSampleCriteria[$index]['selectAll']) {
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
            'operator' => 'equals',
            'value' => '',
        ];

        if (count($this->datasetSampleCriteria[$datasetIndex]['conditions']) > 1) {
            $this->datasetSampleCriteria[$datasetIndex]['connectors'][] = 'AND';
        }
    }

    public function removeSampleCondition(int $datasetIndex, int $conditionIndex): void
    {
        unset($this->datasetSampleCriteria[$datasetIndex]['conditions'][$conditionIndex]);
        $this->datasetSampleCriteria[$datasetIndex]['conditions'] = array_values($this->datasetSampleCriteria[$datasetIndex]['conditions']);

        if (isset($this->datasetSampleCriteria[$datasetIndex]['connectors'][$conditionIndex - 1])) {
            unset($this->datasetSampleCriteria[$datasetIndex]['connectors'][$conditionIndex - 1]);
            $this->datasetSampleCriteria[$datasetIndex]['connectors'] = array_values($this->datasetSampleCriteria[$datasetIndex]['connectors']);
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

    public function toggleMetadataCopy(int $datasetId, string $key): void
    {
        $existingIndex = null;
        foreach ($this->metadataToCopy as $index => $metadata) {
            if ($metadata['dataset_id'] === $datasetId && $metadata['key'] === $key) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            unset($this->metadataToCopy[$existingIndex]);
            $this->metadataToCopy = array_values($this->metadataToCopy);
        } else {
            $dataset = Dataset::find($datasetId);
            $metadataValue = $dataset?->metadata()->where('key', $key)->first()->value ?? '';

            $this->metadataToCopy[] = [
                'dataset_id' => $datasetId,
                'key' => $key,
                'value' => $metadataValue,
            ];
        }
    }

    public function toggleMetadataSection(): void
    {
        $this->showMetadataSection = ! $this->showMetadataSection;
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

    #[Computed]
    public function sampleMetadataKeys(): Collection
    {
        return collect(DatasetAdvancedSearchBuilder::SAMPLE_FIXED_FIELDS)
            ->keys()
            ->concat(SampleMetadata::distinct()->pluck('key'))
            ->unique();
    }

    public function getFieldLabel(string $key): string
    {
        if (array_key_exists($key, DatasetAdvancedSearchBuilder::SAMPLE_FIXED_FIELDS)) {
            return DatasetAdvancedSearchBuilder::SAMPLE_FIXED_FIELDS[$key];
        }

        return str($key)->replace('_', ' ')->title()->toString();
    }

    public function isMetadataSelected(int $datasetId, string $key): bool
    {
        return array_any(
            $this->metadataToCopy,
            static fn ($metadata) => $metadata['dataset_id'] === $datasetId && $metadata['key'] === $key
        );
    }

    public function combine(): void
    {
        $this->validate();

        // Validate that at least 2 datasets are selected
        $validDatasetIds = array_filter($this->selectedDatasetIds);
        if (count($validDatasetIds) < 2) {
            Flux::toast(
                text: 'Please select at least 2 datasets to combine.',
                heading: 'Validation Error',
                variant: 'danger'
            );

            return;
        }

        // TODO: Implement actual combination logic
        Flux::toast(
            text: 'Dataset combination will be implemented in the next step.',
            heading: 'Not Implemented',
            variant: 'warning'
        );
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|min:3|max:255',
            'description' => 'required|min:3|max:1000',
            'selectedDatasetIds.*' => 'required|integer|exists:datasets,id',
            'combinedDatasetMetadata.*.key' => 'nullable|string|max:255',
            'combinedDatasetMetadata.*.value' => 'nullable',
        ];
    }

    private function initializeSampleCriteria(int $index): void
    {
        $this->datasetSampleCriteria[$index] = [
            'selectAll' => true,
            'conditions' => [],
            'connectors' => [],
        ];
    }

    private function updateMetadataToCopy(): void
    {
        $validDatasetIds = array_filter($this->selectedDatasetIds);
        $this->metadataToCopy = array_filter(
            $this->metadataToCopy,
            static fn ($metadata) => in_array($metadata['dataset_id'], $validDatasetIds, true)
        );
        $this->metadataToCopy = array_values($this->metadataToCopy);
    }
}
