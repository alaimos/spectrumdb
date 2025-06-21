@use(\App\Enums\SearchOperator)
@php
    $steps = [
        ['name' => __('spectrum.combine_datasets.step_1_title'), 'icon' => 'information-circle'],
        ['name' => __('spectrum.combine_datasets.step_2_title'), 'icon' => 'folder-open'],
        ['name' => __('spectrum.combine_datasets.step_3_title'), 'icon' => 'document-text'],
        ['name' => __('spectrum.combine_datasets.step_4_title'), 'icon' => 'link'],
        ['name' => __('spectrum.combine_datasets.step_5_title'), 'icon' => 'check-circle'],
    ];
@endphp
<div>
    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <flux:heading size="lg">{{ __('spectrum.combine_datasets.introduction_title') }}</flux:heading>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('spectrum.combine_datasets.follow_steps') }}
                </p>
            </div>
            <flux:button variant="ghost" icon="x-mark" href="{{ route('datasets.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>

        {{-- Progress Steps --}}
        <div class="mb-8">
            <nav aria-label="Progress">
                <div role="list" class="grid grid-cols-1 md:grid-cols-5">
                    @foreach ($steps as $index => $step)
                        <div @class([
                            'group flex flex-col border-l-4 py-2 pl-4 md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4',
                            'border-zinc-700 dark:border-white' => $currentStep >= $index + 1,
                            'border-zinc-200 dark:border-zinc-700' => $currentStep < $index + 1,
                        ])>
                            <span @class([
                                'text-sm font-medium',
                                'text-primary-600' => $currentStep >= $index + 1,
                                'text-zinc-500 dark:text-zinc-400' => $currentStep < $index + 1,
                            ])>
                                {{ __('Step') }} {{ $index + 1 }}
                            </span>
                            <span @class([
                                'flex items-center text-sm font-medium',
                                'text-zinc-900 dark:text-white' => $currentStep >= $index + 1,
                                'text-zinc-500 dark:text-zinc-400' => $currentStep < $index + 1,
                            ])>
                                <flux:icon :name="$step['icon']" class="mr-2 h-4 w-4"/>{{ $step['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </nav>
        </div>

        {{-- Content Card --}}
        <flux:card class="relative">
            {{-- Step Content --}}
            <div class="mb-8">
                {{-- Step 1: Introduction --}}
                @if ($currentStep === 1)
                    <div class="text-center space-y-6">
                        <div
                            class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-primary-100 dark:bg-primary-900">
                            <flux:icon name="circle-stack" class="h-12 w-12 text-primary-600 dark:text-primary-400"/>
                        </div>

                        <div>
                            <flux:heading
                                size="lg">{{ __('spectrum.combine_datasets.introduction_title') }}</flux:heading>
                            <p class="mt-4 text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto text-justify">
                                {{ __('spectrum.combine_datasets.introduction_description') }}
                            </p>
                        </div>

                        <div class="flex justify-center">
                            <flux:button variant="primary" wire:click="nextStep" icon-trailing="arrow-right">
                                {{ __('spectrum.combine_datasets.get_started') }}
                            </flux:button>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Dataset Selection & Sample Filtering --}}
                @if ($currentStep === 2)
                    <div class="space-y-8">
                        <div>
                            <flux:heading size="lg">{{ __('spectrum.combine_datasets.select_datasets') }}</flux:heading>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('spectrum.combine_datasets.select_at_least_2') }}
                            </p>
                        </div>

                        <div class="flex justify-between items-center">
                            <flux:subheading>{{ __('spectrum.combine_datasets.selected_datasets') }}</flux:subheading>
                            <flux:button variant="primary" size="sm" icon="plus" wire:click="addDataset">
                                {{ __('spectrum.combine_datasets.add_dataset') }}
                            </flux:button>
                        </div>

                        @if (empty($selectedDatasetIds))
                            <div
                                class="text-center py-8 text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <p>{{ __('spectrum.combine_datasets.no_datasets_selected') }}</p>
                                <p class="text-sm">{{ __('spectrum.combine_datasets.click_add_dataset') }}</p>
                            </div>
                        @else
                            <div class="space-y-6">
                                @foreach ($selectedDatasetIds as $index => $datasetId)
                                    <div wire:key="dataset-{{ $index }}"
                                         class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                                                                                <div class="grid grid-cols-[1fr_auto] gap-4 mb-4">
                                            <div class="space-y-2">
                                                <flux:select wire:model.live="selectedDatasetIds.{{ $index }}"
                                                                                                      label="{{ __('spectrum.combine_datasets.select_a_dataset') }}"
                                               placeholder="{{ __('spectrum.combine_datasets.select_a_dataset_placeholder') }}"
                                                           variant="listbox" searchable size="sm">
                                                    @foreach ($this->availableDatasets as $dataset)
                                                        <flux:select.option value="{{ $dataset->id }}">
                                                            {{ $dataset->name }}
                                                        </flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                                @if ($datasetId)
                                                    <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                                                        <flux:icon name="puzzle-piece" class="w-4 h-4"/>
                                                        <span>
                                                            <strong>{{ number_format($this->getSampleCount($index)) }}</strong>
                                                            {{ __('samples selected') }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <flux:button variant="danger" size="sm" icon="trash" class="self-end"
                                                       wire:click="removeDataset({{ $index }})"/>
                                        </div>

                                        @if ($datasetId && array_key_exists($index, $datasetSampleCriteria))
                                            {{-- Sample Selection Criteria --}}
                                            <div class="space-y-4">
                                                <flux:subheading>{{ __('spectrum.combine_datasets.sample_filtering') }}</flux:subheading>

                                                <div class="flex items-center gap-4">
                                                    <flux:switch
                                                        :checked="$datasetSampleCriteria[$index]['includeAllSamples']"
                                                        wire:click="toggleSampleSelectionType({{ $index }})"/>
                                                    <span class="text-sm">
                                                        {{ __('spectrum.combine_datasets.all_samples') }}
                                                    </span>
                                                </div>

                                                @if (!$datasetSampleCriteria[$index]['includeAllSamples'])
                                                    <div
                                                        class="space-y-3 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700">
                                                        @if (empty($datasetSampleCriteria[$index]['conditions']))
                                                            <div class="text-center py-4 text-zinc-500">
                                                                <p class="text-sm">{{ __('No conditions set. All samples will be included.') }}</p>
                                                                <flux:button variant="ghost" size="sm" icon="plus"
                                                                             wire:click="addSampleCondition({{ $index }})">
                                                                    {{ __('spectrum.combine_datasets.add_filter') }}
                                                                </flux:button>
                                                            </div>
                                                        @else
                                                            @foreach ($datasetSampleCriteria[$index]['conditions'] as $conditionIndex => $condition)
                                                                <div
                                                                    wire:key="condition-{{ $index }}-{{ $conditionIndex }}">
                                                                    <div class="flex items-center gap-2">
                                                                        @if ($conditionIndex > 0)
                                                                            <flux:select
                                                                                wire:model="datasetSampleCriteria.{{ $index }}.connectors.{{ $conditionIndex-1 }}"
                                                                                variant="listbox" size="sm"
                                                                                class="!w-24">
                                                                                <flux:select.option
                                                                                    value="AND">{{ __('AND') }}</flux:select.option>
                                                                                <flux:select.option
                                                                                    value="OR">{{ __('OR') }}</flux:select.option>
                                                                                <flux:select.option
                                                                                    value="NOT">{{ __('NOT') }}</flux:select.option>
                                                                            </flux:select>
                                                                        @else
                                                                            <div class="!w-24"></div>
                                                                        @endif

                                                                        <div
                                                                            class="flex-1 grid grid-cols-12 gap-2 items-start">
                                                                            {{-- Metadata Field --}}
                                                                            <div class="col-span-5">
                                                                                <flux:select
                                                                                    wire:model.live="datasetSampleCriteria.{{ $index }}.conditions.{{ $conditionIndex }}.key"
                                                                                    variant="listbox" size="sm"
                                                                                    searchable
                                                                                    placeholder="{{ __('Select field...') }}">
                                                                                    @foreach ($this->getSampleMetadataKeys($index) as $meta)
                                                                                        <flux:select.option
                                                                                            value="{{ $meta['key'] }}">
                                                                                            {{ $meta['label'] }}
                                                                                        </flux:select.option>
                                                                                    @endforeach
                                                                                </flux:select>
                                                                            </div>

                                                                            {{-- Values Selection --}}
                                                                            <div class="col-span-6">
                                                                                @if (!empty($condition['key']))
                                                                                    <flux:select
                                                                                        wire:model.live="datasetSampleCriteria.{{ $index }}.conditions.{{ $conditionIndex }}.values"
                                                                                        variant="listbox" size="sm"
                                                                                        multiple
                                                                                        placeholder="{{ __('Select values...') }}">
                                                                                        @foreach ($this->getSampleMetadataValues($index, $condition['key']) as $value)
                                                                                            <flux:select.option
                                                                                                value="{{ $value }}">
                                                                                                {{ $value }}
                                                                                            </flux:select.option>
                                                                                        @endforeach
                                                                                    </flux:select>
                                                                                @else
                                                                                    <div
                                                                                        class="text-center py-2 text-sm text-zinc-500">
                                                                                        {{ __('Select a field first') }}
                                                                                    </div>
                                                                                @endif
                                                                            </div>

                                                                            {{-- Remove Button --}}
                                                                            <div class="col-span-1">
                                                                                <flux:button
                                                                                    wire:click="removeSampleCondition({{ $index }}, {{ $conditionIndex }})"
                                                                                    variant="ghost" icon="x-mark"
                                                                                    size="sm"
                                                                                    :disabled="count($datasetSampleCriteria[$index]['conditions']) === 1"/>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach

                                                            <div class="flex justify-start pt-2">
                                                                <flux:button
                                                                    wire:click="addSampleCondition({{ $index }})"
                                                                    variant="ghost" icon="plus" size="sm">
                                                                    {{ __('spectrum.combine_datasets.add_filter') }}
                                                                </flux:button>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 3: Combined Dataset Details --}}
                @if ($currentStep === 3)
                    <div class="space-y-8">
                        <div>
                            <flux:heading size="lg">{{ __('Combined Dataset Details') }}</flux:heading>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Configure the name, description, and metadata for your combined dataset') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-6">
                            <flux:input wire:model="name" label="{{ __('Dataset Name') }}"
                                        placeholder="{{ __('Enter name for the combined dataset') }}"/>

                            <flux:textarea wire:model="description" label="{{ __('Description') }}"
                                           placeholder="{{ __('Describe the combined dataset') }}" rows="3"/>
                        </div>

                        {{-- Metadata Management --}}
                        <div class="space-y-6">
                            {{-- Custom Metadata --}}
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <flux:subheading>{{ __('Additional Custom Metadata') }}</flux:subheading>
                                    <flux:button variant="primary" size="sm" icon="plus"
                                                 wire:click="addCombinedDatasetMetadata">
                                        {{ __('Add Metadata') }}
                                    </flux:button>
                                </div>

                                @if (empty($combinedDatasetMetadata))
                                    <div class="text-center py-4 text-zinc-500">
                                        <p class="text-sm">{{ __('No custom metadata added yet.') }}</p>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($combinedDatasetMetadata as $index => $metadata)
                                            <div class="flex gap-4 items-center">
                                                <flux:field class="flex-1">
                                                    <flux:input wire:model="combinedDatasetMetadata.{{ $index }}.key"
                                                                placeholder="{{ __('Metadata key...') }}"/>
                                                    <flux:error name="combinedDatasetMetadata.{{ $index }}.key"/>
                                                </flux:field>
                                                <flux:field class="flex-1">
                                                    <flux:input wire:model="combinedDatasetMetadata.{{ $index }}.value"
                                                                placeholder="{{ __('Metadata value...') }}"/>
                                                    <flux:error name="combinedDatasetMetadata.{{ $index }}.value"/>
                                                </flux:field>
                                                <flux:button variant="danger" size="sm" icon="trash"
                                                             wire:click="removeCombinedDatasetMetadata({{ $index }})"/>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Step 4: Metadata Pairing --}}
                @if ($currentStep === 4)
                    <div class="space-y-8">
                        <div class="flex justify-between items-center">
                            <div>
                                <flux:heading
                                    size="lg">{{ __('spectrum.combine_datasets.metadata_pairing') }}</flux:heading>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ __('Configure how sample metadata from different datasets should be paired together') }}
                                </p>
                            </div>
                            <flux:button variant="primary" size="sm" icon="link"
                                         wire:click="pairMetadataAutomatically">
                                {{ __('spectrum.combine_datasets.pair_automatically') }}
                            </flux:button>
                        </div>

                        @if (empty($metadataPairing))
                            <div
                                class="text-center py-8 text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-lg">
                                <p>{{ __('No metadata fields found.') }}</p>
                                <p class="text-sm">{{ __('Go back to the previous step and ensure datasets are selected.') }}</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($metadataPairing as $key => $pairing)
                                    <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                        <div class="grid grid-cols-12 gap-4 items-start">
                                            {{-- Metadata Field Name --}}
                                            <div class="col-span-3">
                                                <flux:field>
                                                    <flux:label>{{ __('Metadata Field') }}</flux:label>
                                                    <div
                                                        class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded text-sm font-medium">
                                                        {{ $this->getFieldLabel($key) }}
                                                    </div>
                                                </flux:field>
                                            </div>

                                            {{-- Source Datasets --}}
                                            <div class="col-span-3">
                                                <flux:field>
                                                    <flux:label>{{ __('Available in Datasets') }}</flux:label>
                                                    <div class="p-2 bg-zinc-50 dark:bg-zinc-800/50 rounded text-sm">
                                                        @if (!empty($pairing['datasets']))
                                                            <div class="space-y-1">
                                                                @foreach ($pairing['datasets'] as $datasetId => $datasetName)
                                                                    <div class="flex items-center gap-2">
                                                                        <flux:icon name="folder"
                                                                                   class="w-3 h-3 text-zinc-400"/>
                                                                        <span>{{ $datasetName }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span
                                                                class="text-zinc-500 italic">{{ __('Not available in any selected dataset') }}</span>
                                                        @endif
                                                    </div>
                                                </flux:field>
                                            </div>

                                            {{-- Include/Exclude Toggle --}}
                                            <div class="col-span-2">
                                                <flux:field>
                                                    <flux:label>{{ __('Include') }}</flux:label>
                                                    <div class="flex items-center gap-2 mt-2">
                                                        <flux:switch :checked="$pairing['included']"
                                                                     wire:click="$set('metadataPairing.{{ $key }}.included', {{ $pairing['included'] ? 'false' : 'true' }})"/>
                                                        <span class="text-sm">
                                                            {{ $pairing['included'] ? __('Included') : __('Excluded') }}
                                                        </span>
                                                    </div>
                                                </flux:field>
                                            </div>

                                            {{-- Field Name in Combined Dataset --}}
                                            <div class="col-span-2">
                                                @if ($pairing['included'])
                                                    <flux:input wire:model="metadataPairing.{{ $key }}.paired_key"
                                                                label="{{ __('Field Name') }}" size="sm"
                                                                placeholder="{{ $key }}"/>
                                                @else
                                                    <flux:field>
                                                        <flux:label>{{ __('Field Name') }}</flux:label>
                                                        <div
                                                            class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded text-sm text-zinc-500 text-center">
                                                            {{ __('Not applicable') }}
                                                        </div>
                                                    </flux:field>
                                                @endif
                                            </div>

                                            {{-- Default Values --}}
                                            <div class="col-span-2">
                                                @if ($pairing['included'] && !empty($pairing['default_values']))
                                                    <flux:field>
                                                        <flux:label>{{ __('Default Values') }}</flux:label>
                                                        <div class="space-y-1">
                                                            @foreach ($pairing['default_values'] as $datasetId => $defaultValue)
                                                                @php
                                                                    $dataset = $this->selectedDatasets->find($datasetId);
                                                                @endphp
                                                                @if ($dataset)
                                                                    <flux:input
                                                                        wire:model="metadataPairing.{{ $key }}.default_values.{{ $datasetId }}"
                                                                        size="xs"
                                                                        placeholder="Default for {{ $dataset->name }}"
                                                                        description="{{ $dataset->name }}"/>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                        @if (count($pairing['default_values']) > 0)
                                                            <flux:description>
                                                                {{ __('spectrum.combine_datasets.default_value_help') }}
                                                            </flux:description>
                                                        @endif
                                                    </flux:field>
                                                @else
                                                    <flux:field>
                                                        <flux:label>{{ __('Default Values') }}</flux:label>
                                                        <div
                                                            class="p-2 bg-zinc-100 dark:bg-zinc-800 rounded text-sm text-zinc-500 text-center">
                                                            {{ __('Not applicable') }}
                                                        </div>
                                                    </flux:field>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 5: Review & Confirm --}}
                @if ($currentStep === 5)
                    <div class="space-y-8">
                        <div>
                            <flux:heading size="lg">{{ __('Review Your Combined Dataset') }}</flux:heading>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ __('Please verify all information before creating the combined dataset') }}
                            </p>
                        </div>

                        <dl class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">{{ __('Name') }}</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">{{ $name }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">{{ __('Description') }}</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">{{ $description }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">{{ __('Selected Datasets') }}</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">
                                    <ul class="space-y-2">
                                        @foreach ($this->selectedDatasets as $datasetIndex => $dataset)
                                            <li class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon name="circle-stack" class="w-4 h-4 text-zinc-400"/>
                                                    <span>{{ $dataset->name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">
                                                    <flux:icon name="puzzle-piece" class="w-3 h-3"/>
                                                    <span class="text-xs">
                                                        {{ number_format($this->getSampleCount($datasetIndex)) }} {{ __('samples') }}
                                                    </span>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">{{ __('Metadata Fields') }}</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">
                                    @if (empty($metadataPairing))
                                        <span class="text-zinc-500">{{ __('No metadata fields configured') }}</span>
                                    @else
                                        <ul class="space-y-1">
                                            @foreach ($metadataPairing as $key => $pairing)
                                                @if ($pairing['included'])
                                                    <li class="flex items-center gap-2">
                                                        <flux:icon name="tag" class="w-4 h-4 text-zinc-400"/>
                                                        {{ $this->getFieldLabel($key) }}
                                                        @if ($pairing['paired_key'] !== $key)
                                                            → {{ $this->getFieldLabel($pairing['paired_key']) }}
                                                        @endif
                                                    </li>
                                                @endif
                                            @endforeach
                                        </ul>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
                @if ($currentStep > 1)
                    <flux:button variant="ghost" wire:click="previousStep" icon="arrow-left">
                        {{ __('Previous') }}
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < 5 && $currentStep > 1)
                    <flux:button variant="primary" wire:click="nextStep" icon-trailing="arrow-right">
                        {{ __('Next') }}
                    </flux:button>
                @elseif ($currentStep === 5)
                    <flux:button variant="primary" wire:click="combine" icon-trailing="check">
                        {{ __('Combine Datasets') }}
                    </flux:button>
                @else
                    <div></div>
                @endif
            </div>
        </flux:card>
    </div>
</div>
