<div>
    <x-page-heading-split title="Datasets" subtitle="View and manage all datasets in the system">
        <div class="flex gap-2">
            {{-- Search --}}
            <flux:input.group>
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search datasets..."
                            icon="magnifying-glass"/>
                <flux:tooltip content="Advanced Search" x-data="{}">
                    <flux:button icon="adjustments-horizontal" x-on:click="$flux.modal('advanced-search').show()"/>
                </flux:tooltip>
            </flux:input.group>
            {{-- Create button --}}
            <flux:button variant="primary" icon="plus" wire:navigate href="{{ route('datasets.create') }}">
                New Dataset
            </flux:button>
        </div>
    </x-page-heading-split>
    {{-- Table --}}
    <flux:card>
        <flux:table :paginate="$this->datasets">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                             wire:click="sort('name')">Name
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection"
                             wire:click="sort('description')">Description
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                             wire:click="sort('created_at')">Created
                </flux:table.column>
                <flux:table.column><span class="sr-only">Actions</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->datasets as $dataset)
                    <flux:table.row wire:key="{{ $dataset->id }}">
                        <flux:table.cell>{{ $dataset->name }}</flux:table.cell>
                        <flux:table.cell>{{ Str::limit($dataset->description, 50) }}</flux:table.cell>
                        <flux:table.cell>{{ $dataset->created_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2">
                                @canany(['update', 'delete'], $dataset)
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                        <flux:menu>
                                            @can('update', $dataset)
                                                <flux:menu.item icon="pencil-square" wire:navigate
                                                                href="{{ route('datasets.edit', $dataset) }}">Edit
                                                </flux:menu.item>
                                                <flux:menu.item icon="users"
                                                                wire:click="showPermissions({{ $dataset->id }})">Manage
                                                    Access
                                                </flux:menu.item>
                                            @endcan
                                            @can('delete', $dataset)
                                                <flux:menu.separator/>
                                                <x-delete-menu-item
                                                    icon="trash"
                                                    wire:click="deleteDataset({{ $dataset->id }})"
                                                    :id="$dataset->id"
                                                    title="Delete dataset {{ $dataset->name }}?">
                                                    <p>Are you sure you want to delete this dataset?</p>
                                                    <p>This action cannot be undone.</p>
                                                </x-delete-menu-item>
                                            @endcan
                                        </flux:menu>
                                    </flux:dropdown>
                                @endcanany
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Advanced Search Dialog --}}
    <flux:modal name="advanced-search" variant="flyout" position="bottom" wire:model="showAdvancedSearch">
        <livewire:components.dataset-advanced-search/>
    </flux:modal>

    {{-- Add Dataset Permissions Modals --}}
    <flux:modal name="dataset-permissions" size="xl" class="w-screen md:w-[40rem]">
        @if ($selectedDataset)
            <livewire:components.dataset-permissions :dataset="$selectedDataset"
                                                     :key="'permissions-' . $selectedDataset->id"/>
        @endif
    </flux:modal>
</div>
