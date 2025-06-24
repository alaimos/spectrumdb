<div>
    <x-page-heading-split :title="__('Datasets')" :subtitle="__('View and manage all datasets in the system')">
        <div class="flex gap-2">
            {{-- Search --}}
            <flux:input.group>
                <flux:input wire:model.live.debounce.300ms="search"
                            :placeholder="__('Search datasets...')"
                            icon="magnifying-glass"/>
                <flux:tooltip :content="__('Advanced Search')" x-data="{}">
                    <flux:button icon="adjustments-horizontal"
                                 x-on:click="$flux.modal('advanced-search').show()"/>
                </flux:tooltip>
            </flux:input.group>
            {{-- Action buttons --}}
            <flux:button variant="primary" icon="plus" wire:navigate href="{{ route('datasets.create') }}">
                {{ __('New Dataset') }}
            </flux:button>
            <flux:button variant="primary" icon="arrow-path" wire:navigate href="{{ route('datasets.combine') }}">
                Combine Datasets
            </flux:button>
        </div>
    </x-page-heading-split>
    {{-- Table --}}
    <flux:card>
        <flux:table :paginate="$this->datasets">
            <flux:table.columns>
                <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection"
                                   wire:click="sort('name')">
                    {{ __('Name') }}
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'description'" :direction="$sortDirection"
                                   wire:click="sort('description')">
                    {{ __('Description') }}
                </flux:table.column>
                <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection"
                                   wire:click="sort('created_at')">
                    {{ __('Created At') }}
                </flux:table.column>
                <flux:table.column><span class="sr-only">{{ __('Actions') }}</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->datasets as $dataset)
                    <flux:table.row wire:key="{{ $dataset->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                {{ $dataset->name }}
                                @if($dataset->is_public)
                                    <flux:badge size="sm" color="green" icon="globe-alt">{{ __('Public') }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ Str::limit($dataset->description, 50) }}</flux:table.cell>
                        <flux:table.cell>{{ $dataset->created_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-2 items-center">
                                @canany(['analyze', 'download'], $dataset)
                                    <flux:button variant="ghost" size="sm" icon="eye"
                                                 :href="route('datasets.show', $dataset)"
                                                 wire:navigate>
                                        {{ __('Explore') }}
                                    </flux:button>
                                @endcanany
                                @canany(['update', 'delete'], $dataset)
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal"/>
                                        <flux:menu>
                                            @can('update', $dataset)
                                                <flux:menu.item icon="pencil-square" wire:navigate
                                                                :href="route('datasets.edit', $dataset)">
                                                    {{ __('Edit') }}
                                                </flux:menu.item>
                                                @if(auth()->user()->id === $dataset->created_by)
                                                    <flux:menu.item
                                                        :icon="$dataset->is_public ? 'lock-closed' : 'globe-alt'"
                                                        wire:click="togglePublicStatus({{ $dataset->id }})">
                                                        @if($dataset->is_public)
                                                            {{ __('Make Private') }}
                                                        @else
                                                            {{ __('Make Public') }}
                                                        @endif
                                                    </flux:menu.item>
                                                @endif
                                                @if(!$dataset->is_public)
                                                    <flux:menu.item icon="users"
                                                                    wire:click="showPermissions({{ $dataset->id }})">
                                                        {{ __('Manage Access') }}
                                                    </flux:menu.item>
                                                @endif
                                            @endcan
                                            @can('delete', $dataset)
                                                <flux:menu.separator/>
                                                <x-delete-menu-item
                                                    icon="trash"
                                                    wire:click="deleteDataset({{ $dataset->id }})"
                                                    :id="$dataset->id"
                                                    :title="__('Delete dataset :name?', ['name' => $dataset->name])">
                                                    <p>{{ __('Are you sure you want to delete this dataset?') }}</p>
                                                    <p>{{ __('This action cannot be undone.') }}</p>
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
