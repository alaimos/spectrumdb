@use(App\Enums\PicrustTables)
@use(App\Enums\BatchStatus)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Functional Analysis"
        subheading="Here you can run a functional analysis to explore the results obtained by Picrust."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="picrustTable" :label="__('Select PICRUSt table')" variant="listbox">
                            @foreach(PicrustTables::getValues() as $value => $label)
                                @if ($dataset->getPicrustTableFile(PicrustTables::from($value)) !== null)
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
                                         :label="__('Case')"
                                         variant="listbox">
                                @foreach($this->availableClasses as $class)
                                    <flux:select.option
                                        value="{{ $class }}">{{ $class }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:select wire:model="group2"
                                         :label="__('Control')"
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
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 px-4 pt-2 items-start">
                                    <flux:input wire:model="pvThreshold"
                                                :label="__('P-value threshold')"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="1"
                                                placeholder="0.05"/>

                                    <flux:input wire:model="fdrThreshold"
                                                :label="__('FDR threshold')"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="1"
                                                placeholder="0.05"/>

                                    <flux:input wire:model="topN"
                                                :label="__('Top N taxa (for plots)')"
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
                    <flux:button variant="primary" type="submit">{{ __('Run Analysis') }}</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div class="flex flex-col items-center justify-center gap-2 mb-4">
                <div class="w-full max-w-3xl mx-auto">
                    <flux:select wire:model.live="graph" :label="__('Change plot type')" variant="listbox">
                        <flux:select.option :value="0">{{ __('Top Significant Features') }}</flux:select.option>
                        <flux:select.option :value="1">{{ __('Top Changed Features') }}</flux:select.option>
                        <flux:select.option :value="2">{{ __('Top Frequent Features') }}</flux:select.option>
                    </flux:select>
                </div>
                <img src="{{ $this->functionalPlotUrl }}" alt="{{ $this->functionalPlotTitle }}"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
            @if ($this->functionalTable)
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="md">{{ __('Functional Analysis Table') }}</flux:heading>
                        <div>
                            <flux:dropdown>
                                <flux:button icon:trailing="chevron-down">{{ __('Download') }}</flux:button>

                                <flux:menu>
                                    <flux:menu.item href="{{ $this->functionalTableUrl }}">
                                        {{ __('Download all') }}
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ $this->functionalTablePVUrl }}">
                                        {{ __('Download p-Value filtered') }}
                                    </flux:menu.item>
                                    <flux:menu.item href="{{ $this->functionalTableFDRUrl }}">
                                        {{ __('Download FDR filtered') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>
                    @if (is_string($this->functionalTable))
                        <flux:callout icon="x-circle" variant="danger" inline>
                            <flux:callout.heading>
                                {{ __($this->functionalTable) }}
                            </flux:callout.heading>
                        </flux:callout>
                    @else
                        <flux:card class="w-full">
                            <flux:table :paginate="$this->functionalTable">
                                <flux:table.columns>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'feature'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('taxa')">
                                                       {{ __('Feature') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'logFoldChange'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('logFoldChange')">
                                                       {{ __('Log Fold Change') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'lfcSE'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('lfcSE')">
                                                       {{ __('Std. Err.') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'pValue'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('pValue')">
                                                       {{ __('p Value') }}
                                    </flux:table.column>
                                    <flux:table.column sortable
                                                       :sorted="$sortBy === 'padj'"
                                                       :direction="$sortDirection"
                                                       wire:click="sort('padj')">
                                                       {{ __('FDR') }}
                                    </flux:table.column>
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($this->functionalTable as $row)
                                        <flux:table.row wire:key="{{ $row['feature'] }}">
                                            <flux:table.cell class="break-all">{{ $row['feature'] }}</flux:table.cell>
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
