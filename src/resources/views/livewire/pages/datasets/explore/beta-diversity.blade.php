@use(App\Enums\BetaDiversityMetrics)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Beta Diversity"
        subheading="Here you can explore the beta diversity of the dataset {{ $dataset->name }}."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="runAnalysis">
                <div class="space-y-6 mb-4">
                    <flux:text class="mb-4 text-justify">

                    </flux:text>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model="metrics" label="Select diversity metrics" variant="listbox">
                            @foreach(BetaDiversityMetrics::getValues() as $value => $label)
                                @if ($dataset->getBetaDiversityFile(BetaDiversityMetrics::from($value)) !== null)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endif
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="colorVariable"
                                     label="Select color variable"
                                     placeholder="Select a variable to color samples by"
                                     variant="listbox">
                            @foreach($this->availableMetadata as $variable)
                                <flux:select.option value="{{ $variable }}">{{ $variable }}</flux:select.option>
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
                <img src="{{ $this->betaDiversityPlotUrl }}" alt="Beta Diversity Plot"
                     class="w-full max-w-3xl mx-auto rounded-lg shadow-lg bg-white">
            </div>
        </x-explore.analysis-viewer>
    </x-explore.layout>
</section>
