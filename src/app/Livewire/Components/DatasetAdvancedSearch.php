<?php

namespace App\Livewire\Components;

use App\Models\Dataset;
use App\Models\DatasetMetadata;
use App\Models\SampleMetadata;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DatasetAdvancedSearch extends Component
{
    public array $conditions = [];

    public array $connectors = [];

    // Available operators for different data types
    const STRING_OPERATORS = [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'contains' => 'Contains',
        'not_contains' => 'Not Contains',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
    ];

    const NUMERIC_OPERATORS = [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'less_than' => 'Less Than',
        'greater_than' => 'Greater Than',
        'less_than_equal' => 'Less Than or Equal',
        'greater_than_equal' => 'Greater Than or Equal',
    ];

    const SAMPLE_FIXED_FIELDS = [
        'variety' => 'Variety',
        'plant_stage' => 'Plant Stage',
        'biological_replica' => 'Biological Replica',
        'sample_conditions' => 'Sample Conditions',
        'plant_section' => 'Plant Section',
        'sampling_date' => 'Sampling Date',
        'location' => 'Location',
    ];

    public function mount()
    {
        $this->addCondition();
    }

    public function addCondition()
    {
        $this->conditions[] = [
            'type' => 'dataset', // dataset or sample
            'key' => '',
            'operator' => 'equals',
            'value' => '',
        ];

        if (count($this->conditions) > 1) {
            $this->connectors[] = 'AND';
        }
    }

    public function removeCondition($index)
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);

        if (isset($this->connectors[$index - 1])) {
            unset($this->connectors[$index - 1]);
            $this->connectors = array_values($this->connectors);
        }
    }

    #[Computed]
    public function datasetMetadataKeys(): Collection
    {
        return DatasetMetadata::distinct()->pluck('key')->unique();
    }

    #[Computed]
    public function sampleMetadataKeys(): Collection
    {
        // Combine fixed fields with dynamic metadata
        return collect(self::SAMPLE_FIXED_FIELDS)
            ->keys()
            ->concat(SampleMetadata::distinct()->pluck('key'))
            ->unique();
    }

    public function getMetadataKeysForType(string $type): Collection
    {
        return match ($type) {
            'dataset' => $this->datasetMetadataKeys(),
            'sample' => $this->sampleMetadataKeys(),
            default => collect(),
        };
    }

    public function getFieldLabel(string $type, string $key): string
    {
        if ($type === 'sample' && array_key_exists($key, self::SAMPLE_FIXED_FIELDS)) {
            return self::SAMPLE_FIXED_FIELDS[$key];
        }

        return $key;
    }

    public function applySearch()
    {
        $this->dispatch('advanced-search-applied', conditions: $this->conditions, connectors: $this->connectors);
    }

    public function updatedConditions($value, $key)
    {
        // If the type was changed, reset the metadata key
        if (str_ends_with($key, '.type')) {
            $index = explode('.', $key)[0];
            $this->conditions[$index]['key'] = '';
        }
    }

    public function render()
    {
        return view('livewire.components.dataset-advanced-search');
    }
}
