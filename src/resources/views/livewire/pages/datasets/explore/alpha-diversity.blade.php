<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Alpha Diversity"
        subheading="Description goes here"
        :dataset="$dataset">
        TODO: Alpha diversity analysis
    </x-explore.layout>
</section>
