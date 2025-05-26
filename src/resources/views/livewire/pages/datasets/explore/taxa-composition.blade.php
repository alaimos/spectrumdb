@use(App\Enums\TaxaAbundanceCharts)
@use(App\Enums\TaxonomicLevels)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Taxa Composition"
        subheading="Here you can explore the microbial taxa composition of the dataset."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model="taxonomicLevel" label="Select taxonomic level" variant="listbox">
                            @foreach(TaxonomicLevels::getValues() as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
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
                        <flux:select wire:model="chartType" label="Select type of chart" variant="listbox">
                            @foreach(TaxaAbundanceCharts::getValues() as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">Run Analysis</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div>
                <img src="{{ $this->abundancePlotUrl }}" alt="Abundance Composition Plot"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
            @if ($this->abundanceTable)
                <div class="mt-4">
                    <div class="flex items-center justify-between mb-2">
                        <flux:heading size="md">Abundance Table</flux:heading>
                        <flux:button variant="ghost" size="sm" href="{{ $this->abundanceTableUrl }}">
                            Download Table
                        </flux:button>
                    </div>
                    @if (is_string($this->abundanceTable))
                        <flux:callout icon="x-circle" variant="danger" inline>
                            <flux:callout.heading>
                                {{ $this->abundanceTable }}
                            </flux:callout.heading>
                        </flux:callout>
                    @else
                        @php
                            [ $groups, $data ] = $this->abundanceTable;
                        @endphp
                        <flux:card>
                            <flux:table>
                                <flux:table.columns>
                                    <flux:table.column>Taxa</flux:table.column>
                                    @foreach($groups as $group)
                                        <flux:table.column>{{ $group }}</flux:table.column>
                                    @endforeach
                                </flux:table.columns>

                                <flux:table.rows>
                                    @foreach($data as $taxon => $values)
                                        <flux:table.row wire:key="{{ $taxon }}">
                                            <flux:table.cell>{{ $taxon }}</flux:table.cell>
                                            @foreach($values as $value)
                                                <flux:table.cell>{{ number_format($value, 2) }}%</flux:table.cell>
                                            @endforeach
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
