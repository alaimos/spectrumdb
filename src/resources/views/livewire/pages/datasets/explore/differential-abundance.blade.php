@use(App\Enums\TaxonomicLevels)
@use(App\Enums\BatchStatus)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Differential Abundance Analysis"
        subheading="Here you can find taxa that show significant differences in abundance between different classes of samples."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="taxonomicLevel" label="Select taxonomic level" variant="listbox">
                            @foreach(TaxonomicLevels::getValues() as $value => $label)
                                @if ($value > 1)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classVariable"
                                     label="Select class variable"
                                     placeholder="Select a variable to use for sample grouping"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if (isset($this->classVariable))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                            <flux:select wire:model="group1"
                                         label="Case"
                                         variant="listbox">
                                @foreach($this->availableClasses as $class)
                                    <flux:select.option
                                        value="{{ $class }}">{{ $class }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="group2"
                                         label="Control"
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
                                Please select a class variable to enable group selection.
                            </flux:callout.heading>
                        </flux:callout>
                    @endif

                    <flux:accordion>
                        <flux:accordion.item>
                            <flux:accordion.heading>Advanced options</flux:accordion.heading>
                            <flux:accordion.content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 pt-2 items-start">
                                    <flux:input wire:model="pvThreshold"
                                                label="P-value threshold"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="1"
                                                placeholder="0.05"/>

                                    <flux:input wire:model="fdrThreshold"
                                                label="FDR threshold"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="1"
                                                placeholder="0.05"/>

                                    <flux:input wire:model="topN"
                                                label="Top N taxa (for plots)"
                                                type="number"
                                                step="1"
                                                min="2"
                                                max="100"
                                                placeholder="10"/>
                                </div>
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">Run Analysis</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div class="flex flex-col items-center justify-center gap-2 mb-4">
                <div class="w-full max-w-3xl mx-auto">
                    <flux:select wire:model.live="graph" label="Change plot type" variant="listbox">
                        <flux:select.option :value="0">Top Significant Features</flux:select.option>
                        <flux:select.option :value="1">Top Fold Change Features</flux:select.option>
                        <flux:select.option :value="2">Top Frequency Features</flux:select.option>
                    </flux:select>
                </div>
                <img src="{{ $this->differentialAbundancePlotUrl }}" alt="{{ $this->differentialAbundancePlotTitle }}"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
            @if ($this->differentialAbundanceTable)
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="md">Differential Abundance Table</flux:heading>
                        <div>
                            <flux:dropdown>
                                <flux:button icon:trailing="chevron-down">Download</flux:button>

                                <flux:menu>
                                    <flux:menu.item href="{{ $this->differentialAbundanceTableUrl }}">Download all
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ $this->differentialAbundanceTablePVUrl }}">Download p-Value
                                        filtered
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ $this->differentialAbundanceTableFDRUrl }}">Download FDR
                                        filtered
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                    @if (is_string($this->differentialAbundanceTable))
                        <flux:callout icon="x-circle" variant="danger" inline>
                            <flux:callout.heading>
                                {{ $this->differentialAbundanceTable }}
                            </flux:callout.heading>
                        </flux:callout>
                    @else
                        <flux:card class="w-full">
                            <flux:table :paginate="$this->differentialAbundanceTable">
                                <flux:table.columns>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'taxa'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('taxa')">Taxa
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'logFoldChange'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('logFoldChange')">Log Fold Change
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'lfcSE'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('lfcSE')">Std. Err.
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'pValue'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('pValue')">p Value
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'padj'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('padj')">FDR
                                    </flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($this->differentialAbundanceTable as $row)
                                        <flux:table.row wire:key="{{ $row['taxa'] }}">
                                            <flux:table.cell class="break-all">{{ $row['taxa'] }}</flux:table.cell>
                                            <flux:table.cell>
                                                {{ number_format($row['logFoldChange'], 4) }}
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                {{ number_format($row['lfcSE'], 4) }}
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                @if ($row['pValue'] < 0.0001)
                                                    &lt; 0.0001
                                                @else
                                                    {{ number_format($row['pValue'], 4) }}
                                                @endif
                                            </flux:table.cell>
                                            <flux:table.cell>
                                                @if ($row['padj'] < 0.0001)
                                                    &lt; 0.0001
                                                @else
                                                    {{ number_format($row['padj'], 4) }}
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
