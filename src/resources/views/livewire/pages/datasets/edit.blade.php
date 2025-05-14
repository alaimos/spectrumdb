<div>
    <div class="max-w-5xl mx-auto">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <flux:heading size="lg">Edit Dataset</flux:heading>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Update dataset information and metadata
                </p>
            </div>
            <flux:button variant="ghost" icon="x-mark" href="{{ route('datasets.index') }}" wire:navigate>
                Cancel
            </flux:button>
        </div>

        {{-- Content Card --}}
        <flux:card>
            <div class="space-y-6">
                {{-- Basic Information --}}
                <div class="space-y-4">
                    <flux:heading size="base">Basic Information</flux:heading>
                    <div>
                        <flux:input wire:model="name" label="Dataset Name"
                                    placeholder="Enter a descriptive name for your dataset"/>
                    </div>

                    <div>
                        <flux:textarea wire:model="description" label="Description"
                                       placeholder="Describe the purpose and contents of your dataset" rows="4"/>
                    </div>
                </div>

                {{-- Dataset Metadata --}}
                <flux:accordion transition>
                    <flux:accordion.item expanded>
                        <flux:accordion.heading>Dataset Metadata</flux:accordion.heading>
                        <flux:accordion.content>
                            <div class="space-y-4">
                                <div class="flex justify-end">
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
                                            <div class="flex gap-4 align-middle">
                                                <flux:field class="flex-1">
                                                    <flux:input wire:model="datasetMetadata.{{ $index }}.key"
                                                                placeholder="e.g. Collection Date, Location, Method..."/>
                                                    <flux:error name="datasetMetadata.{{ $index }}.key"/>
                                                </flux:field>
                                                <flux:field class="flex-1">
                                                    <flux:input wire:model="datasetMetadata.{{ $index }}.value"
                                                                placeholder="Enter value..."/>
                                                    <flux:error name="datasetMetadata.{{ $index }}.value"/>
                                                </flux:field>
                                                <flux:button variant="danger" size="sm" icon="trash"
                                                             wire:click="removeDatasetMetadata({{ $index }})"/>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </flux:accordion.content>
                    </flux:accordion.item>
                </flux:accordion>

                {{-- Navigation Buttons --}}
                <div class="flex items-center justify-end pt-6 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex gap-3">
                        <flux:button variant="ghost" href="{{ route('datasets.index') }}" wire:navigate>
                            Cancel
                        </flux:button>
                        <flux:button variant="primary" wire:click="save" icon-trailing="check">
                            Save Changes
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>
</div>
