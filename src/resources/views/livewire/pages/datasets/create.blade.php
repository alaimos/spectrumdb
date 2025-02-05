@php
    $steps = [
        ['name' => 'Basic Information', 'icon' => 'document-text'],
        ['name' => 'Upload Files', 'icon' => 'arrow-up-tray'],
        ['name' => 'Map Metadata', 'icon' => 'table-cells'],
        ['name' => 'Dataset Metadata', 'icon' => 'tag'],
        ['name' => 'Confirm', 'icon' => 'check-circle'],
    ];
    $fileFields = [
        [
            'model' => 'taxonomyFile',
            'label' => 'Taxonomy Data (TSV)',
            'description' => 'A tab-separated file containing feature taxonomy information',
            'accept' => '.tsv,.txt',
            'help' => [
                'Feature ID column (required)',
                'Taxon column with full taxonomy path (required)',
                'Confidence score column (optional)',
            ],
        ],
        [
            'model' => 'sampleDataFile',
            'label' => 'Feature Table (TSV)',
            'description' => 'A tab-separated matrix of feature abundances per sample',
            'accept' => '.tsv,.txt',
            'help' => [
                'First column: Feature IDs (matching taxonomy file)',
                'Column headers: Sample codes',
                'Values: Feature abundance counts',
            ],
        ],
        [
            'model' => 'metadataFile',
            'label' => 'Sample Metadata (TSV)',
            'description' => 'A tab-separated file containing sample metadata',
            'accept' => '.tsv,.txt',
            'help' => [
                'Sample code column (matching feature table)',
                'Additional columns for sample metadata',
                'One row per sample',
            ],
        ],
        [
            'model' => 'brayCurtisFile',
            'label' => 'Bray-Curtis Distance Matrix (QZA)',
            'description' => 'QIIME 2 artifact containing the Bray-Curtis dissimilarity matrix',
            'accept' => '.qza',
            'help' => [
                'Generated using the \'beta-diversity\' method in QIIME 2',
            ],
        ],
        [
            'model' => 'shannonFile',
            'label' => 'Shannon Diversity Index (QZA)',
            'description' => 'QIIME 2 artifact containing Shannon diversity metrics',
            'accept' => '.qza',
            'help' => [
                'Generated using the \'alpha-diversity\' method in QIIME 2',
            ],
        ],
    ];
@endphp
<div>
    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <flux:heading size="lg">Create Dataset</flux:heading>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Follow the steps below to create your new dataset
                </p>
            </div>
            <flux:button
                variant="ghost"
                icon="x-mark"
                wire:click="cancel"
            >
                Cancel
            </flux:button>
        </div>

        {{-- Progress Steps --}}
        <div class="mb-2">
            <nav aria-label="Progress">
                <div role="list" class="grid grid-cols-1 md:grid-cols-5">
                    @foreach ($steps as $index => $step)
                        <div @class([
                                'group flex flex-col border-l-4 py-2 pl-4 md:border-l-0 md:border-t-4 md:pb-0 md:pl-0 md:pt-4',
                                'border-zinc-700 dark:border-white' => $currentStep >= ($index + 1),
                                'border-zinc-200 dark:border-zinc-700' => $currentStep < ($index + 1),
                        ])>
                            <span @class([
                                        'text-sm font-medium',
                                        'text-primary-600' => $currentStep >= ($index + 1),
                                        'text-zinc-500 dark:text-zinc-400' => $currentStep < ($index + 1),
                            ])>
                                    Step {{ $index + 1 }}
                            </span>
                            <span @class([
                                        'flex items-center text-sm font-medium',
                                        'text-zinc-900 dark:text-white' => $currentStep >= ($index + 1),
                                        'text-zinc-500 dark:text-zinc-400' => $currentStep < ($index + 1),
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
                {{-- Step 1: Basic Information --}}
                @if($currentStep === 1)
                    <div class="space-y-6">
                        <div>
                            <flux:input
                                wire:model="name"
                                label="Dataset Name"
                                placeholder="Enter a descriptive name for your dataset"
                            />
                        </div>

                        <div>
                            <flux:textarea
                                wire:model="description"
                                label="Description"
                                placeholder="Describe the purpose and contents of your dataset"
                                rows="4"
                            />
                        </div>

                        <div>
                            <flux:select
                                wire:model="dataType"
                                label="Data Type"
                            >
                                <flux:option value="processed">QIIME 2 Processed Data</flux:option>
                            </flux:select>
                        </div>
                    </div>
                @endif

                {{-- Step 2: File Upload --}}
                @if($currentStep === 2)
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach ($fileFields as $file)
                                <div class="p-4">
                                    <flux:input
                                        type="file"
                                        :wire:model="$file['model']"
                                        :label="$file['label']"
                                        :description="$file['description']"
                                        :accept="$file['accept']"
                                    />
                                    @if(isset($file['help']))
                                        <div class="mt-2 pl-4 border-l-2 border-primary">
                                            <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                @foreach($file['help'] as $item)
                                                    <li>• {{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Step 3: Metadata Mapping --}}
                @if($currentStep === 3)
                    <div class="space-y-6">
                        <flux:select
                            wire:model="sampleCodeColumn"
                            label="Sample Code Column"
                        >
                            @foreach($metadataColumns as $column)
                                <flux:option value="{{ $column }}">{{ $column }}</flux:option>
                            @endforeach
                        </flux:select>

                        <flux:fieldset>
                            <flux:legend>Column Mapping</flux:legend>
                            <flux:description>
                                Map each column to a sample field or mark as custom metadata
                            </flux:description>

                            <div class="space-y-4 mt-4">
                                @foreach($metadataColumns as $column)
                                    @if($column !== $sampleCodeColumn)
                                        <flux:select
                                            wire:model="columnMapping.{{ $column }}"
                                            label="{{ $column }}"
                                        >
                                            <flux:option value="custom">Custom Metadata</flux:option>
                                            <flux:option value="exclude">Exclude</flux:option>
                                            @foreach($availableSampleFields as $field)
                                                <flux:option
                                                    value="{{ $field }}">{{ Str::title(str_replace('_', ' ', $field)) }}</flux:option>
                                            @endforeach
                                        </flux:select>
                                    @endif
                                @endforeach
                            </div>
                        </flux:fieldset>

                        {{-- Metadata Preview --}}
                        @if(count($metadataPreview))
                            <flux:fieldset>
                                <flux:legend>Preview</flux:legend>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead>
                                            <tr>
                                                @foreach($metadataColumns as $column)
                                                    <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">
                                                        {{ $column }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($metadataPreview as $row)
                                                <tr>
                                                    @foreach($metadataColumns as $column)
                                                        <td class="px-4 py-2 text-sm text-gray-500">
                                                            {{ $row[$column] }}
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </flux:fieldset>
                        @endif
                    </div>
                @endif

                {{-- Step 4: Dataset Metadata --}}
                @if($currentStep === 4)
                    <div class="space-y-6">
                        <div class="flex justify-between items-center">
                            <flux:heading size="base">Dataset Metadata</flux:heading>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="plus"
                                wire:click="addDatasetMetadata"
                            >
                                Add Field
                            </flux:button>
                        </div>

                        @foreach($datasetMetadata as $index => $metadata)
                            <div class="flex gap-4">
                                <flux:input
                                    wire:model="datasetMetadata.{{ $index }}.key"
                                    placeholder="Field name"
                                    class="flex-1"
                                />
                                <flux:input
                                    wire:model="datasetMetadata.{{ $index }}.value"
                                    placeholder="Value"
                                    class="flex-1"
                                />
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="trash"
                                    wire:click="removeDatasetMetadata({{ $index }})"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Step 5: Confirmation --}}
                @if($currentStep === 5)
                    <div class="space-y-6">
                        <flux:heading size="base">Review Your Dataset</flux:heading>

                        <dl class="divide-y divide-gray-100">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $name }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ $description }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">Data Type</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">QIIME 2 Processed Data</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
                @if($currentStep > 1)
                    <flux:button
                        variant="ghost"
                        wire:click="previousStep"
                        icon="arrow-left"
                    >
                        Previous
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if($currentStep < 5)
                    <flux:button
                        variant="primary"
                        wire:click="nextStep"
                        icon-trailing="arrow-right"
                    >
                        Next
                    </flux:button>
                @else
                    <flux:button
                        variant="primary"
                        wire:click="submit"
                        icon-trailing="check"
                    >
                        Create Dataset
                    </flux:button>
                @endif
            </div>
        </flux:card>
    </div>
</div>
