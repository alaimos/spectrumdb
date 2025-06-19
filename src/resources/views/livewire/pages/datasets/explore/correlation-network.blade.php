@use(App\Enums\TaxonomicLevels)
@use(App\Enums\BatchStatus)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Correlation Network Analysis"
        subheading="Here you can build a differential correlation network using to explore differences in taxa relationships between two sample groups."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="taxonomicLevel" :label="__('Select taxonomic level')" variant="listbox">
                            @foreach(TaxonomicLevels::getValues() as $value => $label)
                                @if ($value > 1)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classVariable"
                                     :label="__('Select class variable')"
                                     :placeholder="__('Select a variable to use for sample grouping')"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if (isset($this->classVariable))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <flux:select wire:model="group1"
                                         :label="__('Group 1')"
                                         variant="listbox">
                                @foreach($this->availableClasses as $class)
                                    <flux:select.option
                                        value="{{ $class }}">{{ $class }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="group2"
                                         :label="__('Group 2')"
                                         variant="listbox">
                                @foreach($this->availableClasses as $class)
                                    <flux:select.option
                                        value="{{ $class }}">{{ $class }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @else
                        <flux:callout icon="information-circle" color="blue" inline>
                            <flux:callout.heading>
                                {{ __('Please select a class variable to enable group selection.') }}
                            </flux:callout.heading>
                        </flux:callout>
                    @endif

                    <flux:accordion>
                        <flux:accordion.item>
                            <flux:accordion.heading>{{ __('Advanced options') }}</flux:accordion.heading>
                            <flux:accordion.content>
                                <flux:input wire:model="correlationThreshold"
                                            :label="__('Correlation threshold')"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="1"
                                            placeholder="0.6"/>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">{{ __('Run Analysis') }}</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div class="flex flex-col items-center justify-center gap-2 mb-4">
                <div class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white" x-data="graph">
                </div>
            </div>
            @if ($this->networkTable)
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="md">{{ __('Network Table') }}</flux:heading>
                        <div>
                            <flux:button href="{{ $this->networkTableUrl }}" icon="arrow-down-tray">{{ __('Download') }}
                            </flux:button>
                        </div>
                    </div>
                    @if (is_string($this->networkTable))
                        <flux:callout icon="x-circle" variant="danger" inline>
                            <flux:callout.heading>
                                {{ __($this->networkTable) }}
                            </flux:callout.heading>
                        </flux:callout>
                    @else
                        <flux:card class="w-full">
                            <flux:table :paginate="$this->networkTable">
                                <flux:table.columns>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'source_taxa'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('source_taxa')">{{ __('Source') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'target_taxa'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('target_taxa')">{{ __('Target') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'correlation_group_1'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('correlation_group_1')">{{ $group1 }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'correlation_group_2'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('correlation_group_2')">{{ $group2 }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'pv'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('pv')">{{ __('p') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'fdr'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('fdr')">{{ __('FDR') }}
                                    </flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($this->networkTable as $row)
                                        <flux:table.row wire:key="{{ $row['source_taxa'].'-'.$row['target_taxa'] }}">
                                            <flux:table.cell
                                                class="break-all">{{ $row['source_taxa'] }}</flux:table.cell>
                                            <flux:table.cell
                                                class="break-all">{{ $row['target_taxa'] }}</flux:table.cell>
                                            <flux:table.cell>
                                                {{ number_format($row['correlation_group_1'], 4) }}
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                {{ number_format($row['correlation_group_2'], 4) }}
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                @if ($row['pv'] < 0.0001)
                                                    &lt; 0.0001
                                                @else
                                                    {{ number_format($row['pv'], 4) }}
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                @if ($row['fdr'] < 0.0001)
                                                    &lt; 0.0001
                                                @else
                                                    {{ number_format($row['fdr'], 4) }}
                                                @endif
                                            </flux:table.cell>
                                        </flux:table.row>
                                    @endforeach
                                </flux:table.rows>
                            </flux:table>
                        </flux:card>
                    @endif
                </div>
            @endif
        </x-explore.analysis-viewer>
    </x-explore.layout>
</section>

@script
<script>
    Alpine.data('graph', () => ({
        data: @json($this->networkGraph, JSON_THROW_ON_ERROR),
        backup: null,
        init () {
            this.backup = window.networkChart(this.data);
            this.$el.replaceChildren(this.backup);
            Livewire.hook('morphed', (el, component) => {
                this.$el.replaceChildren(this.backup);
            });
        },
        destroy () {
            if (this.backup) {
                this.backup.remove();
                this.backup = null;
            }
        },
    }));
</script>
@endscript
