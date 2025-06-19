@props(['dataset'])
<x-page-heading :title="__('Explore dataset :name', ['name' => $dataset->name])"
                :subtitle="__('Explore the dataset :name in detail.', ['name' => $dataset->name])"/>
