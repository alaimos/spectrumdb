<?php

declare(strict_types=1);

namespace App\Livewire\Pages\Datasets;

use App\Enums\SearchOperator;
use App\Models\Dataset;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Combine extends Component
{
    /** @var array<int, int> */
    public array $selectedDatasetIds = [];

    /** @var array<int, array{selectAll: bool, conditions: array, connectors: array}> */
    public array $datasetSampleCriteria = [];

    #[Validate()]
    public string $name = '';

    #[Validate()]
    public string $description = '';

    /** @var array<int, array{key: string, value: string}> */
    #[Validate()]
    public array $combinedDatasetMetadata = [];

    /** @var array<int, int> */
    public array $metadataToCopy = [];

    public bool $showMetadataSection = false;

    public function addDataset(): void
    {
        $this->selectedDatasetIds[] = null;
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

    /**
     * @return Collection<int, Dataset>
     */
    #[Computed]
    public function selectedDatasets(): Collection
    {
        $validIds = array_filter($this->selectedDatasetIds);

        return Dataset::whereIn('id', $validIds)->get();
    }

    /**
     * @return Collection<int, array{key: string, label: string}>
     */
    public function getSampleMetadataKeys(int $datasetIndex): Collection
    {
        return Cache::memo()->remember(
            $this->getCacheKey($datasetIndex),
            now()->addMinutes(5),
            function () use ($datasetIndex) {
                /** @var Dataset|null $dataset */
                $dataset = $this->selectedDatasets[$datasetIndex] ?? null;
                if (! $dataset) {
                    return collect();
                }

                return $dataset
                    ->through('samples')
                    ->has('metadata')
                    ->select('key')
                    ->distinct()
                    ->pluck('key')
                    ->map(
                        fn ($key) => [
                            'key' => $key,
                            'label' => $this->getFieldLabel($key),
                        ]
                    );
            }
        );
    }

    public function getFieldLabel(string $key): string
    {
        return str($key)->replace('_', ' ')->title()->toString();
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

        dd($this->metadataToCopy);
        Flux::toast(
            text: 'Dataset combination will be implemented in the next step.',
            heading: 'Not Implemented',
            variant: 'warning'
        );
    }

    protected function rules(): array
    {
        return [
            // Basic dataset information
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['required', 'string', 'min:3', 'max:1000'],

            // Selected datasets validation
            'selectedDatasetIds' => ['required', 'array', 'min:2'],
            'selectedDatasetIds.*' => ['required', 'integer', 'exists:datasets,id'],

            // Dataset sample criteria validation
            'datasetSampleCriteria' => ['array'],
            'datasetSampleCriteria.*.selectAll' => ['required', 'boolean'],
            'datasetSampleCriteria.*.conditions' => ['array'],
            'datasetSampleCriteria.*.conditions.*.key' => [
                'required_if:datasetSampleCriteria.*.selectAll,false',
                'string',
                'max:255',
            ],
            'datasetSampleCriteria.*.conditions.*.operator' => [
                'required_if:datasetSampleCriteria.*.selectAll,false',
                'string',
                Rule::enum(SearchOperator::class),
            ],
            'datasetSampleCriteria.*.conditions.*.value' => [
                'required_if:datasetSampleCriteria.*.selectAll,false',
                'string',
                'max:1000',
            ],
            'datasetSampleCriteria.*.connectors' => ['array'],
            'datasetSampleCriteria.*.connectors.*' => ['string', 'in:AND,OR,NOT'],

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

            // Metadata to copy validation
            'metadataToCopy' => ['array'],
            'metadataToCopy.*' => ['required', 'integer'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selectedDatasetIds.min' => 'Please select at least 2 datasets to combine.',
            'selectedDatasetIds.*.exists' => 'The selected dataset does not exist or you do not have access to it.',
            'datasetSampleCriteria.*.conditions.*.key.required_if' => 'Please select a field for the sample condition.',
            'datasetSampleCriteria.*.conditions.*.operator.required_if' => 'Please select an operator for the sample condition.',
            'datasetSampleCriteria.*.conditions.*.operator.in' => 'The selected operator is not valid.',
            'datasetSampleCriteria.*.conditions.*.value.required_if' => 'Please enter a value for the sample condition.',
            'datasetSampleCriteria.*.connectors.*.in' => 'The connector must be AND, OR, or NOT.',
            'combinedDatasetMetadata.*.key.required_with' => 'Please enter a metadata key.',
            'combinedDatasetMetadata.*.key.not_regex' => 'This metadata key is reserved and cannot be used.',
            'combinedDatasetMetadata.*.value.required_with' => 'Please enter a metadata value.',
            'metadataToCopy.*.dataset_id.exists' => 'The selected dataset for metadata copy does not exist.',
        ];
    }

    private function getCacheKey(int $datasetIndex): string
    {
        return "sample_metadata_keys_{$this->selectedDatasetIds[$datasetIndex]}_".auth()->id();
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
