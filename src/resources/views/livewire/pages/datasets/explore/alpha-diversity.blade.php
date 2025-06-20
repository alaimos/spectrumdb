@use(App\Enums\AlphaDiversityMetrics)
@use(App\Enums\BatchStatus)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Alpha Diversity"
        subheading="Here you can explore the alpha diversity of this dataset."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="metrics" :label="__('Select diversity metrics')" variant="listbox">
                            @foreach(AlphaDiversityMetrics::getValues() as $value => $label)
                                @if ($dataset->getAlphaDiversityFile(AlphaDiversityMetrics::from($value)) !== null)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="classVariable"
                                     :label="__('Select class variable')"
                                     :placeholder="__('Select a variable to group samples by')"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    @isset($this->classVariable)
                        <div class="flex gap-4 mb-4">
                            <flux:heading>{{ __('Classes comparisons') }}</flux:heading>
                            <flux:spacer/>
                            <flux:button wire:click="addComparison" icon="plus" size="sm" square></flux:button>
                        </div>
                        <div class="space-y-4">
                            <div
                                class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-4 max-w-6xl mx-auto items-start">
                                @if (count($this->comparisons) > 0)
                                    <flux:text variant="strong">
                                        {{ __('Case') }}
                                    </flux:text>
                                    <flux:text variant="strong">
                                        {{ __('Control') }}
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
                                        {{ __('No comparisons added yet. Click the plus button to add a comparison.') }}
                                    </flux:text>
                                @endforelse
                            </div>
                        </div>
                    @endisset
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">{{ __('Run Analysis') }}</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div class="mb-4">
                <flux:tooltip :content="__('Click to view the plot in full size')">
                    <a href="{{ $this->alphaDiversityPlotUrl }}" target="_blank">
                        <img src="{{ $this->alphaDiversityPlotUrl }}" alt="{{ __('Alpha Diversity Plot') }}"
                             class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
                    </a>
                </flux:tooltip>
            </div>
            <div>
                <flux:text class="w-full max-w-3xl mx-auto text-justify mb-2">
                    {{ __('spectrum.alpha_diversity_description_1') }}
                </flux:text>
                <flux:text class="w-full max-w-3xl mx-auto text-justify">
                    {{ __('spectrum.alpha_diversity_description_2') }}
                </flux:text>
            </div>
        </x-explore.analysis-viewer>
    </x-explore.layout>
</section>
