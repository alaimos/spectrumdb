@use(\App\Enums\SearchOperator)
<div>
    <x-page-heading-split title="Combine Datasets"
                          subtitle="You can use this tool to combine multiple dataset into a new dataset. You can also filter samples from each dataset based on specific criteria.">
        <flux:button variant="ghost" icon="x-mark" href="{{ route('datasets.index') }}" wire:navigate>
            Cancel
        </flux:button>
    </x-page-heading-split>
    <flux:card>
        <div class="space-y-8">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="base">Dataset Selection</flux:heading>
                    <flux:button variant="primary" size="sm" icon="plus" wire:click="addDataset">
                        Add Dataset
                    </flux:button>
                </div>

                @if (empty($selectedDatasetIds))
                    <div
                        class="text-center py-8 text-zinc-500 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <p>No datasets selected yet.</p>
                        <p class="text-sm">Click "Add Dataset" to start selecting datasets to combine.</p>
                    </div>
                @else
                    <div class="space-y-6">
                        @foreach ($selectedDatasetIds as $index => $datasetId)
                            <div wire:key="dataset-{{ $index }}"
                                 class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-6">
                                <div class="grid grid-cols-[1fr_auto] gap-4 mb-4">
                                    <flux:select wire:model.live="selectedDatasetIds.{{ $index }}"
                                                 label="Select a Dataset"
                                                 placeholder="Select a dataset..."
                                                 variant="listbox" size="sm">
                                        @foreach ($this->availableDatasets as $dataset)
                                            <flux:select.option value="{{ $dataset->id }}">
                                                {{ $dataset->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button variant="danger" size="sm" icon="trash" class="self-end"
                                                 wire:click="removeDataset({{ $index }})"/>
                                </div>

                                @if ($datasetId && array_key_exists($index, $datasetSampleCriteria))
                                    {{-- Sample Selection Criteria --}}
                                    <div class="space-y-4">
                                        <flux:subheading>Sample Selection</flux:subheading>

                                        <div class="flex items-center gap-4">
                                            <flux:switch
                                                wire:model.live="datasetSampleCriteria.{{ $index }}.selectAll"/>
                                            <span class="text-sm">
                                                    {{ $datasetSampleCriteria[$index]['selectAll'] ? 'Include all samples' : 'Filter samples' }}
                                                </span>
                                        </div>

                                        @if (!$datasetSampleCriteria[$index]['selectAll'])
                                            <div
                                                class="space-y-3 pl-4 border-l-2 border-zinc-200 dark:border-zinc-700">
                                                @if (empty($datasetSampleCriteria[$index]['conditions']))
                                                    <div class="text-center py-4 text-zinc-500">
                                                        <p class="text-sm">No conditions set. All samples will be
                                                            included.</p>
                                                        <flux:button variant="ghost" size="sm" icon="plus"
                                                                     wire:click="addSampleCondition({{ $index }})">
                                                            Add Condition
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
                                                                        size="sm" class="!w-24">
                                                                        <flux:select.option value="AND">AND
                                                                        </flux:select.option>
                                                                        <flux:select.option value="OR">OR
                                                                        </flux:select.option>
                                                                        <flux:select.option value="NOT">NOT
                                                                        </flux:select.option>
                                                                    </flux:select>
                                                                @else
                                                                    <div class="!w-24"></div>
                                                                @endif

                                                                <div
                                                                    class="flex-1 grid grid-cols-12 gap-2 items-start">
                                                                    {{-- Field --}}
                                                                    <div class="col-span-4">
                                                                        <flux:select
                                                                            wire:model="datasetSampleCriteria.{{ $index }}.conditions.{{ $conditionIndex }}.key"
                                                                            size="sm" searchable
                                                                            placeholder="Select field...">
                                                                            @foreach ($this->sampleMetadataKeys as $key)
                                                                                <flux:select.option
                                                                                    value="{{ $key }}">
                                                                                    {{ $this->getFieldLabel($key) }}
                                                                                </flux:select.option>
                                                                            @endforeach
                                                                        </flux:select>
                                                                    </div>

                                                                    {{-- Operator --}}
                                                                    <div class="col-span-3">
                                                                        <flux:select wire:model="datasetSampleCriteria.{{ $index }}.conditions.{{ $conditionIndex }}.operator"
                                                                                   size="sm">
                                                                            <optgroup label="String">
                                                                                @foreach(SearchOperator::getStringOperatorsForSelect() as $value => $label)
                                                                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                                                                @endforeach
                                                                            </optgroup>
                                                                            <optgroup label="Numeric">
                                                                                @foreach(SearchOperator::getNumericOperatorsForSelect() as $value => $label)
                                                                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                                                                @endforeach
                                                                            </optgroup>
                                                                        </flux:select>
                                                                    </div>

                                                                    {{-- Value --}}
                                                                    <div class="col-span-4">
                                                                        <flux:input
                                                                            wire:model="datasetSampleCriteria.{{ $index }}.conditions.{{ $conditionIndex }}.value"
                                                                            size="sm" placeholder="Enter value..."/>
                                                                    </div>

                                                                    {{-- Remove Button --}}
                                                                    <div class="col-span-1">
                                                                        <flux:button
                                                                            wire:click="removeSampleCondition({{ $index }}, {{ $conditionIndex }})"
                                                                            variant="ghost" icon="x-mark" size="sm"
                                                                            :disabled="count($datasetSampleCriteria[$index]['conditions']) === 1"/>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach

                                                    <div class="flex justify-start pt-2">
                                                        <flux:button wire:click="addSampleCondition({{ $index }})"
                                                                     variant="ghost" icon="plus" size="sm">
                                                            Add Condition
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

            {{-- Combined Dataset Details --}}
            <div class="space-y-4">
                <flux:heading size="base">Combined Dataset Details</flux:heading>

                <div class="grid grid-cols-1 gap-4">
                    <flux:input wire:model="name" label="Dataset Name"
                                placeholder="Enter name for the combined dataset"/>

                    <flux:textarea wire:model="description" label="Description"
                                   placeholder="Describe the combined dataset" rows="3"/>
                </div>
            </div>

            {{-- Metadata Management --}}
            <flux:accordion transition>
                <flux:accordion.item>
                    <flux:accordion.heading>Dataset Metadata</flux:accordion.heading>
                    <flux:accordion.content class="px-4">
                        <div class="space-y-6">
                            {{-- Copy Metadata from Selected Datasets --}}
                            @if (count($this->selectedDatasets) > 0)
                                <div class="space-y-4">
                                    <flux:subheading>Copy Metadata from Selected Datasets</flux:subheading>

                                    @foreach ($this->selectedDatasets as $dataset)
                                        @if ($dataset->metadata->count() > 0)
                                            <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
                                                <flux:heading size="sm"
                                                              class="mb-3">{{ $dataset->name }}</flux:heading>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                    @foreach ($dataset->metadata as $metadata)
                                                        <label class="flex items-center gap-2 cursor-pointer">
                                                            <flux:checkbox
                                                                :checked="$this->isMetadataSelected($dataset->id, $metadata->key)"
                                                                wire:click="toggleMetadataCopy({{ $dataset->id }}, '{{ $metadata->key }}')"/>
                                                            <span class="text-sm">
                                                                    <strong>{{ $metadata->key }}:</strong> {{ Str::limit($metadata->value, 30) }}
                                                                </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Custom Metadata --}}
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <flux:subheading>Additional Custom Metadata</flux:subheading>
                                    <flux:button variant="primary" size="sm" icon="plus"
                                                 wire:click="addCombinedDatasetMetadata">
                                        Add Metadata
                                    </flux:button>
                                </div>

                                @if (empty($combinedDatasetMetadata))
                                    <div class="text-center py-4 text-zinc-500">
                                        <p class="text-sm">No custom metadata added yet.</p>
                                    </div>
                                @else
                                    <div class="space-y-3">
                                        @foreach ($combinedDatasetMetadata as $index => $metadata)
                                            <div class="flex gap-4 items-center">
                                                <flux:field class="flex-1">
                                                    <flux:input
                                                        wire:model="combinedDatasetMetadata.{{ $index }}.key"
                                                        placeholder="Metadata key..."/>
                                                    <flux:error name="combinedDatasetMetadata.{{ $index }}.key"/>
                                                </flux:field>
                                                <flux:field class="flex-1">
                                                    <flux:input
                                                        wire:model="combinedDatasetMetadata.{{ $index }}.value"
                                                        placeholder="Metadata value..."/>
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
                    </flux:accordion.content>
                </flux:accordion.item>
            </flux:accordion>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end pt-6 border-t border-zinc-200 dark:border-zinc-700">
                <div class="flex gap-3">
                    <flux:button variant="ghost" icon="x-mark" href="{{ route('datasets.index') }}" wire:navigate>
                        Cancel
                    </flux:button>
                    <flux:button variant="primary" wire:click="combine" icon-trailing="arrow-path">
                        Combine Datasets
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:card>
</div>
