@use(App\Enums\BetaDiversityMetrics)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Beta Diversity"
        subheading="Here you can explore the beta diversity of your dataset."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                        <flux:select wire:model="metrics" :label="__('Select diversity metrics')" variant="listbox">
                            @foreach(BetaDiversityMetrics::getValues() as $value => $label)
                                @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::from($value)) !== null)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="colorVariable"
                                     :label="__('Select color variable')"
                                     :placeholder="__('Select a variable to color samples by')"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">{{ __('Run Analysis') }}</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

        <x-explore.analysis-viewer :analysisId="$analysisId" :batchStatus="$this->batchStatus">
            <div>
                <img src="{{ $this->betaDiversityPlotUrl }}" alt="{{ __('Beta Diversity Plot') }}"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
        </x-explore.analysis-viewer>
    </x-explore.layout>
</section>
