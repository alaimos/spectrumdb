<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Introduction"
        :dataset="$dataset"
        small>

        <flux:text class="mb-4 text-justify">
            Here you can explore the content of the dataset {{ $dataset->name }}.
            You can view the data using several analysis targeted to plant microbiomes.
            Click on the links on the left to navigate to the different analyses.
        </flux:text>

    </x-explore.layout>
</section>

