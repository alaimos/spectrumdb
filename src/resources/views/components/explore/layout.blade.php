<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[230px]">
        <flux:navlist>
            @can('analyze', $dataset)
                <flux:navlist.item :href="route('datasets.show.taxa_composition', ['dataset' => $dataset])"
                                   wire:navigate>Taxa Composition
                </flux:navlist.item>
                @if ($dataset->hasAlphaDiversity())
                    <flux:navlist.item :href="route('datasets.show.alpha_diversity', ['dataset' => $dataset])"
                                       wire:navigate>Alpha Diversity
                    </flux:navlist.item>
                @endif
                @if ($dataset->hasBetaDiversity())
                    <flux:navlist.item :href="route('datasets.show.beta_diversity', ['dataset' => $dataset])"
                                       wire:navigate>Beta Diversity
                    </flux:navlist.item>
                @endif
                <flux:navlist.item :href="route('datasets.show.differential_abundance', ['dataset' => $dataset])"
                                   wire:navigate>Differential Abundance
                </flux:navlist.item>
                @if ($dataset->hasPicrustTables())
                    <flux:navlist.item :href="route('datasets.show.functional_analysis', ['dataset' => $dataset])"
                                       wire:navigate>Functional Analysis
                    </flux:navlist.item>
                @endif
            @endcan
            @can('download', $dataset)
                @if ($dataset->hasPicrustTables())
                    <flux:navlist.item :href="route('datasets.show.picrust_table', ['dataset' => $dataset])"
                                       wire:navigate>PICRUSt Predictions
                    </flux:navlist.item>
                @endif
            @endcan
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden"/>

    <div class="flex-1 self-stretch max-md:pt-6 overflow-hidden">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div @class(['mt-5 w-full', 'max-w-lg' => ($small ?? false)])>
            {{ $slot }}
        </div>
    </div>
</div>
