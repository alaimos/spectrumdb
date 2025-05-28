@use(App\Enums\AlphaDiversityMetrics)
@use(App\Enums\BatchStatus)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Alpha Diversity"
        subheading="Here you can explore the alpha diversity of the dataset {{ $dataset->name }}."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="metrics" label="Select diversity metrics" variant="listbox">
                            @foreach(AlphaDiversityMetrics::getValues() as $value => $label)
                                @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::from($value)) !== null)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classVariable"
                                     label="Select class variable"
                                     placeholder="Select a variable to group samples by"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @isset($this->classVariable)
                        <div class="flex gap-4 mb-4">
                            <flux:heading>Classes comparisons</flux:heading>
                            <flux:spacer/>
                            <flux:button wire:click="addComparison" icon="plus" size="sm" square></flux:button>
                        </div>
                        <div class="space-y-4">
                            <div
                                class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-4 max-w-6xl mx-auto items-start">
                                @if (count($this->comparisons) > 0)
                                    <flux:text variant="strong">
                                        Case
                                    </flux:text>
                                    <flux:text variant="strong">
                                        Control
                                    </flux:text>
                                    <div></div>
                                @endif
                                @forelse($this->comparisons as $index => $comparison)
                                    <flux:field>
                                        <flux:select wire:model="comparisons.{{ $index }}.0"
                                                     size="sm" variant="listbox">
                                            @foreach($this->availableClasses as $class)
                                                <flux:select.option
                                                    value="{{ $class }}">{{ $class }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error :name="'comparisons.' . $index . '.0'"/>
                                    </flux:field>
                                    <flux:field>
                                        <flux:select wire:model="comparisons.{{ $index }}.1"
                                                     size="sm" variant="listbox">
                                            @foreach($this->availableClasses as $class)
                                                <flux:select.option
                                                    value="{{ $class }}">{{ $class }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error :name="'comparisons.' . $index . '.1'"/>
                                    </flux:field>
                                    <flux:button wire:click="removeComparison({{ $index }})" variant="danger"
                                                 icon="trash" class="self-start"
                                                 size="sm" square></flux:button>
                                @empty
                                    <flux:text class="col-span-3">
                                        No comparisons added yet. Click the plus button to add a comparison.
                                    </flux:text>
                                @endforelse
                            </div>
                        </div>
                    @endisset
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
                <img src="{{ $this->alphaDiversityPlotUrl }}" alt="Alpha Diversity Plot"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
        </x-explore.analysis-viewer>
    </x-explore.layout>
</section>
