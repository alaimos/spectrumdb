<?php

declare(strict_types=1);

namespace App\Livewire\Components;

use App\Builders\DatasetAdvancedSearchBuilder;
use App\Models\DatasetMetadata;
use App\Models\SampleMetadata;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Stringable;

final class DatasetAdvancedSearch extends Component
{
    public array $conditions = [];

    public array $connectors = [];

    public function mount(): void
    {
        $this->addCondition();
    }

    public function addCondition(): void
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

    public function removeCondition($index): void
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
        return SampleMetadata::distinct()->pluck('key')->unique();
    }

    public function getMetadataKeysForType(string $type): Collection
    {
        return match ($type) {
            'dataset' => $this->datasetMetadataKeys(),
            'sample' => $this->sampleMetadataKeys(),
            default => collect(),
        };
    }

    public function getFieldLabel(string $type, string $key): Stringable
    {
        return str($key)->replace('_', ' ')->title();
    }

    public function applySearch(): void
    {
        $this->dispatch('advanced-search-applied', conditions: $this->conditions, connectors: $this->connectors);
    }

    public function updatedConditions($value, $key): void
    {
        // If the type was changed, reset the metadata key
        if (str_ends_with($key, '.type')) {
            $index = explode('.', $key)[0];
            $this->conditions[$index]['key'] = '';
        }
    }

    public function render(): View
    {
        return view('livewire.components.dataset-advanced-search');
    }
}
