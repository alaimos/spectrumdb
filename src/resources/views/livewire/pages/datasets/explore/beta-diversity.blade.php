<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="Beta Diversity"
        subheading="Description goes here"
        :dataset="$dataset">
        TODO: Beta diversity analysis
    </x-explore.layout>
</section>
