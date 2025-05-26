@use(\App\Enums\PicrustTables)
<section class="w-full">
    <x-page-heading title="Explore dataset {{ $dataset->name }}"
                    subtitle="Explore the dataset {{ $dataset->name }} in detail."/>

    <x-explore.layout
        heading="PICRUSt Tables"
        :dataset="$dataset">

        <flux:text class="mb-4 text-justify">
            Here you can download the PICRUSt tables for the dataset {{ $dataset->name }}.
        </flux:text>

        <flux:card>
            <form wire:submit="downloadTable">
                <div class="space-y-6 mb-4">
                    <flux:select wire:model="selectedTable" label="Select PICRUSt analysis" variant="listbox">
                        @foreach(PicrustTables::getValues() as $value => $label)
                            @if ($dataset->getPicrustTableFile(PicrustTables::from($value)) !== null)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">Download</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

    </x-explore.layout>
</section>

