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
            'accept' => '.tsv,.txt,.gz',
            'help' => [
                'Feature ID column (required)',
                'Taxon column with full taxonomy path (required)',
                'Confidence score column (optional)',
            ],
        ],
        [
            'model' => 'asvTableFile',
            'label' => 'ASV Table (TSV)',
            'description' => 'A tab-separated matrix of ASV abundances per sample',
            'accept' => '.tsv,.txt,.gz',
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
            'accept' => '.tsv,.txt,.gz',
            'help' => [
                'Sample code column (matching feature table)',
                'Additional columns for sample metadata',
                'One row per sample',
            ],
        ],
    ];
    $picrustFileFields = [
        [
            'model' => 'picrustKoFile',
            'label' => 'KEGG Orthology Table (TSV)',
            'description' => 'A tab-separated file containing predicted metagenomic features for kegg orthology',
            'accept' => '.tsv,.txt,.gz',
            'help' => [
                'First column: Genetic Feature ID',
                'Second column: Feature Description',
                'Other columns: Sample values (matching ASV table)',
            ],
        ],
        [
            'model' => 'picrustEcFile',
            'label' => 'EC Enzymes Table (TSV)',
            'description' => 'A tab-separated file containing predicted metagenomic features for EC enzymes',
            'accept' => '.tsv,.txt,.gz',
            'help' => [
                'First column: Genetic Feature ID',
                'Second column: Feature Description',
                'Other columns: Sample values (matching ASV table)',
            ],
        ],
        [
            'model' => 'picrustPathwaysFile',
            'label' => 'Pathways Table (TSV)',
            'description' => 'A tab-separated file containing predicted metagenomic features for pathways',
            'accept' => '.tsv,.txt,.gz',
            'help' => [
                'First column: Genetic Feature ID',
                'Second column: Feature Description',
                'Other columns: Sample values (matching ASV table)',
            ],
        ],
    ];
    $alphaDiversityFileFields = [
        [
            'model' => 'shannonFile',
            'label' => 'Shannon Diversity Index (QZA)',
            'description' => 'QIIME 2 artifact containing Shannon index metrics',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric shannon\' method in QIIME 2'],
        ],
        [
            'model' => 'faithFile',
            'label' => 'Faith’s phylogenetic diversity Index (QZA)',
            'description' => 'QIIME 2 artifact containing Faith’s phylogenetic diversity metrics',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric faith_pd\' method in QIIME 2'],
        ],
        [
            'model' => 'chaoFile',
            'label' => 'Chao1 index (QZA)',
            'description' => 'QIIME 2 artifact containing Chao1 index metrics',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric chao1\' method in QIIME 2'],
        ],
        [
            'model' => 'evennessFile',
            'label' => 'Evenness Index (QZA)',
            'description' => 'QIIME 2 artifact containing Evenness metrics',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric heip_e|mcintosh_e|pielou_e|simpson_e\' method in QIIME 2'],
        ],
    ];
    $betaDiversityFileFields = [
        [
            'model' => 'brayCurtisFile',
            'label' => 'Bray-Curtis Distance Matrix (QZA)',
            'description' => 'QIIME 2 artifact containing the Bray-Curtis matrix',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric braycurtis\' method in QIIME 2'],
        ],
        [
            'model' => 'jaccardFile',
            'label' => 'Jaccard Distance Matrix (QZA)',
            'description' => 'QIIME 2 artifact containing the Jaccard matrix',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric jaccard\' method in QIIME 2'],
        ],
        [
            'model' => 'weightedUnifracFile',
            'label' => 'Weighted UniFrac Distance Matrix (QZA)',
            'description' => 'QIIME 2 artifact containing the Weighted UniFrac matrix',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric weighted_unifrac\' method in QIIME 2'],
        ],
        [
            'model' => 'unweightedUnifracFile',
            'label' => 'Unweighted UniFrac Distance Matrix (QZA)',
            'description' => 'QIIME 2 artifact containing the Unweighted UniFrac matrix',
            'accept' => '.qza',
            'help' => ['Generated using the \'qiime diversity beta --p-metric unweighted_unifrac\' method in QIIME 2'],
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
            <flux:button variant="ghost" icon="x-mark" href="{{ route('datasets.index') }}" wire:navigate>
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
                            'border-zinc-700 dark:border-white' => $currentStep >= $index + 1,
                            'border-zinc-200 dark:border-zinc-700' => $currentStep < $index + 1,
                        ])>
                            <span @class([
                                'text-sm font-medium',
                                'text-primary-600' => $currentStep >= $index + 1,
                                'text-zinc-500 dark:text-zinc-400' => $currentStep < $index + 1,
                            ])>
                                Step {{ $index + 1 }}
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
                {{-- Step 1: Basic Information --}}
                @if ($currentStep === 1)
                    <div class="space-y-6">
                        <div>
                            <flux:input wire:model="name" label="Dataset Name"
                                        placeholder="Enter a descriptive name for your dataset"/>
                        </div>

                        <div>
                            <flux:textarea wire:model="description" label="Description"
                                           placeholder="Describe the purpose and contents of your dataset" rows="4"/>
                        </div>

                        <div>
                            <flux:select wire:model="dataType" label="Data Type">
                                <flux:select.option value="processed">QIIME 2 Processed Data</flux:select.option>
                            </flux:select>
                        </div>
                    </div>
                @endif

                {{-- Step 2: File Upload --}}
                @if ($currentStep === 2)
                    <div class="space-y-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            @foreach ($fileFields as $file)
                                <div class="p-4">
                                    <flux:input type="file" :wire:model="$file['model']" :label="$file['label']"
                                                :description="$file['description']" :accept="$file['accept']"/>
                                    @if (isset($file['help']))
                                        <div class="mt-2 pl-4 border-l-2 border-primary">
                                            <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                @foreach ($file['help'] as $item)
                                                    <li>• {{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <flux:separator variant="subtle"/>
                    <div class="mt-2">
                        <flux:accordion variant="reverse" transition>
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    PICRUSt Metagenome Tables (Optional)
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-8">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            @foreach ($picrustFileFields as $file)
                                                <div class="p-4">
                                                    <flux:input type="file" :wire:model="$file['model']"
                                                                :label="$file['label']"
                                                                :description="$file['description']"
                                                                :accept="$file['accept']"/>
                                                    @if (isset($file['help']))
                                                        <div class="mt-2 pl-4 border-l-2 border-primary">
                                                            <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                                @foreach ($file['help'] as $item)
                                                                    <li>• {{ $item }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    Alpha Diversity Metrics (Optional)
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-8">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            @foreach ($alphaDiversityFileFields as $file)
                                                <div class="p-4">
                                                    <flux:input type="file" :wire:model="$file['model']"
                                                                :label="$file['label']"
                                                                :description="$file['description']"
                                                                :accept="$file['accept']"/>
                                                    @if (isset($file['help']))
                                                        <div class="mt-2 pl-4 border-l-2 border-primary">
                                                            <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                                @foreach ($file['help'] as $item)
                                                                    <li>• {{ $item }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                            <flux:accordion.item>
                                <flux:accordion.heading>
                                    Beta Diversity Metrics (Optional)
                                </flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="space-y-8">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            @foreach ($betaDiversityFileFields as $file)
                                                <div class="p-4">
                                                    <flux:input type="file" :wire:model="$file['model']"
                                                                :label="$file['label']"
                                                                :description="$file['description']"
                                                                :accept="$file['accept']"/>
                                                    @if (isset($file['help']))
                                                        <div class="mt-2 pl-4 border-l-2 border-primary">
                                                            <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                                                                @foreach ($file['help'] as $item)
                                                                    <li>• {{ $item }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                        </flux:accordion>
                    </div>
                @endif

                {{-- Step 3: Metadata Mapping --}}
                @if ($currentStep === 3)
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {{-- Sample Code Column Selection --}}
                            <div class="lg:col-span-1">
                                <flux:select wire:model.live="sampleCodeColumn" label="Sample Code Column">
                                    @foreach ($metadataColumns as $column)
                                        <flux:select.option value="{{ $column }}">{{ $column }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            {{-- Column Mapping --}}
                            <div class="lg:col-span-2">
                                <flux:fieldset>
                                    <flux:legend>Column Mapping</flux:legend>
                                    <flux:description>
                                        Map each column to a sample field or mark as custom metadata
                                    </flux:description>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        @foreach ($metadataColumns as $column)
                                            @if ($column !== $sampleCodeColumn)
                                                <flux:select wire:model.live="columnMapping.{{ $column }}"
                                                             label="{{ $column }}" size="sm">
                                                    <flux:select.option value="custom">Custom Metadata
                                                    </flux:select.option>
                                                    <flux:select.option value="exclude">Exclude</flux:select.option>
                                                    @foreach ($availableSampleFields as $field)
                                                        <flux:select.option value="{{ $field }}">
                                                            {{ Str::title(str_replace('_', ' ', $field)) }}
                                                        </flux:select.option>
                                                    @endforeach
                                                </flux:select>
                                            @endif
                                        @endforeach
                                    </div>
                                </flux:fieldset>
                            </div>
                        </div>

                        {{-- Metadata Preview --}}
                        @if (count($metadataPreview))
                            <div class="flex flex-col w-full overflow-hidden">
                                <flux:text variant="strong" class="mb-2">
                                    Metadata Preview
                                </flux:text>
                                <flux:table>
                                    <flux:table.columns>
                                        @foreach ($metadataColumns as $column)
                                            <flux:table.column class="whitespace-nowrap">
                                                <div class="flex flex-col">
                                                    <span>{{ $column }}</span>
                                                    @if ($column === $sampleCodeColumn)
                                                        <span class="text-xs text-primary">Sample Code</span>
                                                    @elseif(isset($columnMapping[$column]))
                                                        <span class="text-xs text-zinc-500">
                                                            {{ $columnMapping[$column] === 'custom' ? 'Custom Metadata' : Str::title(str_replace('_', ' ', $columnMapping[$column])) }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </flux:table.column>
                                        @endforeach
                                    </flux:table.columns>

                                    <flux:table.rows>
                                        @foreach ($metadataPreview as $row)
                                            <flux:table.row>
                                                @foreach ($metadataColumns as $column)
                                                    <flux:table.cell class="whitespace-nowrap">{{ $row[$column] }}
                                                    </flux:table.cell>
                                                @endforeach
                                            </flux:table.row>
                                        @endforeach
                                    </flux:table.rows>
                                </flux:table>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 4: Dataset Metadata --}}
                @if ($currentStep === 4)
                    <div class="space-y-6">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <flux:heading size="base">Dataset Metadata</flux:heading>
                                <p class="text-sm text-zinc-500 mt-1">Add custom metadata fields to describe this
                                    dataset</p>
                            </div>
                            <flux:button variant="primary" size="sm" icon="plus"
                                         wire:click="addDatasetMetadata">
                                Add Metadata
                            </flux:button>
                        </div>

                        @if (empty($datasetMetadata))
                            <div class="text-center py-6 text-zinc-500">
                                <p>No metadata fields added yet.</p>
                                <p class="text-sm">Click "Add Metadata" to start adding metadata.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($datasetMetadata as $index => $metadata)
                                    <div class="flex gap-4 items-center align-middle">
                                        <flux:input wire:model="datasetMetadata.{{ $index }}.key"
                                                    placeholder="e.g. Collection Date, Location, Method..."
                                                    class="flex-1"/>
                                        <flux:input wire:model="datasetMetadata.{{ $index }}.value"
                                                    placeholder="Enter value..." class="flex-1"/>
                                        <flux:button variant="danger" size="sm" icon="trash"
                                                     wire:click="removeDatasetMetadata({{ $index }})"/>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Step 5: Confirmation --}}
                @if ($currentStep === 5)
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="base">Review Your Dataset</flux:heading>
                            <p class="text-sm text-zinc-500">Please verify all information before creating the dataset
                            </p>
                        </div>

                        <dl
                            class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">Name</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">{{ $name }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">Description</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">{{ $description }}</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">Data Type</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">QIIME 2 Processed Data</dd>
                            </div>
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500">Files</dt>
                                <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">
                                    <ul class="space-y-1">
                                        @foreach ($this->uploadedFiles as $file)
                                            <li class="flex items-center gap-2">
                                                <flux:icon name="document" class="w-4 h-4 text-zinc-400"/>
                                                {{ $file }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </dd>
                            </div>
                            @if (count($datasetMetadata))
                                <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                    <dt class="text-sm font-medium text-zinc-500">Metadata</dt>
                                    <dd class="mt-1 text-sm text-primary sm:col-span-2 sm:mt-0">
                                        <dl class="space-y-1">
                                            @foreach ($datasetMetadata as $metadata)
                                                @if (!empty($metadata['key']) && !empty($metadata['value']))
                                                    <div class="flex gap-2">
                                                        <dt class="font-medium">{{ $metadata['key'] }}:</dt>
                                                        <dd>{{ $metadata['value'] }}</dd>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </dl>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                @endif
            </div>

            {{-- Navigation Buttons --}}
            <div class="flex items-center justify-between pt-6 border-t border-zinc-200 dark:border-zinc-700">
                @if ($currentStep > 1)
                    <flux:button variant="ghost" wire:click="previousStep" icon="arrow-left">
                        Previous
                    </flux:button>
                @else
                    <div></div>
                @endif

                @if ($currentStep < 5)
                    <flux:button variant="primary" wire:click="nextStep" icon-trailing="arrow-right">
                        Next
                    </flux:button>
                @else
                    <flux:button variant="primary" wire:click="submit" icon-trailing="check">
                        Create Dataset
                    </flux:button>
                @endif
            </div>
        </flux:card>
    </div>
</div>
