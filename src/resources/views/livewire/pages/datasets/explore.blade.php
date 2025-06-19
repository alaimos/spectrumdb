<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="Introduction"
        :dataset="$dataset"
        small>

        <flux:text class="mb-4 text-justify">
            {{ __('spectrum.explore_introduction', ['name' => $dataset->name]) }}
        </flux:text>

        <flux:heading>{{ __('Description') }}</flux:heading>

        <flux:text class="mt-5 mb-4 text-justify">
            {{ $dataset->description }}
        </flux:text>

    </x-explore.layout>
</section>

