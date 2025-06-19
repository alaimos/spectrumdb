@use(\App\Enums\PicrustTables)
<section class="w-full">
    <x-explore-heading :dataset="$dataset"/>

    <x-explore.layout
        heading="PICRUSt Tables"
        subheading="Here you can download the PICRUSt tables for the dataset."
        :dataset="$dataset">

        <flux:card>
            <form wire:submit="downloadTable">
                <div class="space-y-6 mb-4">
                    <flux:select wire:model="selectedTable" :label="__('Select PICRUSt analysis')" variant="listbox">
                        @foreach(PicrustTables::getValues() as $value => $label)
                            @if ($dataset->getPicrustTableFile(PicrustTables::from($value)) !== null)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                </div>
                <div class="flex gap-4">
                    <flux:spacer/>
                    <flux:button variant="primary" type="submit">{{ __('Download') }}</flux:button>
                    <flux:spacer/>
                </div>
            </form>
        </flux:card>

    </x-explore.layout>
</section>

