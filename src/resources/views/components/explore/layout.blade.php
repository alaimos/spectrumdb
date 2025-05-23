<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('datasets.show.alpha_diversity', ['dataset' => $dataset])"
                               wire:navigate>Alpha Diversity
            </flux:navlist.item>
            <flux:navlist.item :href="route('datasets.show.beta_diversity', ['dataset' => $dataset])"
                               wire:navigate>Beta Diversity
            </flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div @class(['mt-5 w-full', 'max-w-lg' => ($small ?? false)])>
            {{ $slot }}
        </div>
    </div>
</div>
