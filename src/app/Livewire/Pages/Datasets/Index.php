<?php

namespace App\Livewire\Pages\Datasets;

use App\Livewire\Components\DatasetAdvancedSearch;
use App\Models\Dataset;
use Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showAdvancedSearch = false;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public $datasetToDelete = null;

    public array $advancedSearchConditions = [];

    public array $advancedSearchConnectors = [];

    public ?Dataset $selectedDataset = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function applyAdvancedSearch($conditions, $connectors): void
    {
        $this->advancedSearchConditions = $conditions;
        $this->advancedSearchConnectors = $connectors;
        $this->resetPage();
    }

    #[Computed]
    public function datasets(): LengthAwarePaginator
    {
        return Dataset::query()
            ->visibleTo(auth()->user())
            ->when(
                $this->search,
                function ($query, $search) {
                    $query->where(
                        function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        }
                    );
                }
            )
            ->when($this->advancedSearchConditions, function ($query) {
                $this->applyAdvancedSearchConditions($query);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
    }

    protected function applyAdvancedSearchConditions($query): void
    {
        $query->where(function ($query) {
            foreach ($this->advancedSearchConditions as $index => $condition) {
                $method = $index === 0 ? 'where' : strtolower($this->advancedSearchConnectors[$index - 1]);

                if ($method === 'not') {
                    $method = 'whereNot';
                }

                $query->$method(function ($query) use ($condition) {
                    if ($condition['type'] === 'dataset') {
                        $this->applyMetadataCondition($query, 'dataset_metadata', $condition);
                    } else {
                        $query->whereHas('samples', function ($query) use ($condition) {
                            $this->applyMetadataCondition($query, 'sample_metadata', $condition);
                        });
                    }
                });
            }
        });
    }

    protected function applyMetadataCondition($query, $table, $condition): void
    {
        if ($condition['type'] === 'sample' && array_key_exists($condition['key'], DatasetAdvancedSearch::SAMPLE_FIXED_FIELDS)) {
            // Handle fixed sample fields
            $query->where($condition['key'], function ($query) use ($condition) {
                $this->applyOperatorCondition($query, 'samples.'.$condition['key'], $condition['operator'], $condition['value']);
            });
        } else {
            // Handle metadata fields
            $query->whereHas($condition['type'] === 'dataset' ? 'metadata' : 'samples.metadata',
                function ($query) use ($condition) {
                    $query->where('key', $condition['key']);
                    $this->applyOperatorCondition($query, 'value', $condition['operator'], $condition['value']);
                }
            );
        }
    }

    protected function applyOperatorCondition($query, string $field, string $operator, $value): void
    {
        switch ($operator) {
            case 'equals':
                $query->where($field, $value);
                break;
            case 'not_equals':
                $query->where($field, '!=', $value);
                break;
            case 'contains':
                $query->where($field, 'like', "%{$value}%");
                break;
            case 'not_contains':
                $query->where($field, 'not like', "%{$value}%");
                break;
            case 'starts_with':
                $query->where($field, 'like', "{$value}%");
                break;
            case 'ends_with':
                $query->where($field, 'like', "%{$value}");
                break;
            case 'less_than':
                $query->where($field, '<', $value);
                break;
            case 'greater_than':
                $query->where($field, '>', $value);
                break;
            case 'less_than_equal':
                $query->where($field, '<=', $value);
                break;
            case 'greater_than_equal':
                $query->where($field, '>=', $value);
                break;
        }
    }

    public function deleteDataset(Dataset $dataset): void
    {
        $this->authorize('delete', $dataset);
        $dataset->load('samples');
        $dataset->samples->each->delete();
        $dataset->delete();

        Flux::toast(
            text: 'Dataset deleted successfully',
            variant: 'success'
        );
    }

    public function showPermissions(Dataset $dataset): void
    {
        $this->selectedDataset = $dataset;
        Flux::modal('dataset-permissions')->show();
    }

    public function render(): View
    {
        return view('livewire.pages.datasets.index');
    }
}
