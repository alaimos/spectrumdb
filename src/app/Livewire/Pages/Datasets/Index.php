<?php

namespace App\Livewire\Pages\Datasets;

use App\Builders\DatasetAdvancedSearchBuilder;
use App\Models\Dataset;
use Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showAdvancedSearch = false;

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

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

    #[On('advanced-search-applied')]
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
                fn (Builder $query, $search) => $query->where(
                    fn (Builder $query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                )
            )
            ->when(
                $this->advancedSearchConditions,
                new DatasetAdvancedSearchBuilder(
                    advancedSearchConditions: $this->advancedSearchConditions,
                    advancedSearchConnectors: $this->advancedSearchConnectors
                )
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);
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
