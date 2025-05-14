<div>
    {{-- Header --}}
    <div class="flex items-center mb-6">
        <flux:heading size="lg">Datasets</flux:heading>
        <flux:spacer/>
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
    </div>

    {{-- Table --}}
    <flux:card>
        <flux:table :paginate="$this->datasets">
            <flux:columns>
                <flux:column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                             wire:click="sort('name')">Name
                </flux:column>
                <flux:column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection"
                             wire:click="sort('description')">Description
                </flux:column>
                <flux:column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                             wire:click="sort('created_at')">Created
                </flux:column>
                <flux:column><span class="sr-only">Actions</span></flux:column>
            </flux:columns>

            <flux:rows>
                @foreach ($this->datasets as $dataset)
                    <flux:row wire:key="{{ $dataset->id }}">
                        <flux:cell>{{ $dataset->name }}</flux:cell>
                        <flux:cell>{{ Str::limit($dataset->description, 50) }}</flux:cell>
                        <flux:cell>{{ $dataset->created_at->diffForHumans() }}</flux:cell>
                        <flux:cell>
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
                        </flux:cell>
                    </flux:row>
                @endforeach
            </flux:rows>
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
