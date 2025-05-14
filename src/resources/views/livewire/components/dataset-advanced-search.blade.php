<div class="space-y-6">
    <div>
        <flux:heading size="lg">Advanced Search</flux:heading>
        <flux:subheading>Filter datasets using advanced criteria</flux:subheading>
    </div>

    <div class="space-y-2">
        @foreach($conditions as $index => $condition)
            <div wire:key="condition-{{ $index }}">
                <div class="flex items-center gap-2">
                    @if($index > 0)
                        <flux:select
                            wire:model="connectors.{{ $index-1 }}"
                            size="sm"
                            class="!w-24"
                        >
                            <flux:select.option value="AND">AND</flux:select.option>
                            <flux:select.option value="OR">OR</flux:select.option>
                            <flux:select.option value="NOT">NOT</flux:select.option>
                        </flux:select>
                    @else
                        <div class="!w-24"></div>
                    @endif

                    <div class="flex-1 grid grid-cols-12 gap-2 items-start">
                        {{-- Metadata Type --}}
                        <div class="col-span-2">
                            <flux:select
                                wire:model.live="conditions.{{ $index }}.type"
                                size="sm"
                                label-sr-only="Search in"
                            >
                                <flux:select.option value="dataset">Dataset</flux:select.option>
                                <flux:select.option value="sample">Sample</flux:select.option>
                            </flux:select>
                        </div>

                        {{-- Metadata Key --}}
                        <div class="col-span-4">
                            <flux:select
                                wire:model="conditions.{{ $index }}.key"
                                size="sm"
                                searchable
                                label-sr-only="Field"
                                placeholder="Select field..."
                            >
                                @foreach($this->getMetadataKeysForType($condition['type']) as $key)
                                    <flux:select.option
                                        value="{{ $key }}">{{ $this->getFieldLabel($condition['type'], $key) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        {{-- Operator --}}
                        <div class="col-span-2">
                            <flux:select
                                wire:model="conditions.{{ $index }}.operator"
                                size="sm"
                                label-sr-only="Operator"
                            >
                                <optgroup label="String">
                                    @foreach(\App\Builders\DatasetAdvancedSearchBuilder::STRING_OPERATORS as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Numeric">
                                    @foreach(\App\Builders\DatasetAdvancedSearchBuilder::NUMERIC_OPERATORS as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </optgroup>
                            </flux:select>
                        </div>

                        {{-- Value --}}
                        <div class="col-span-3">
                            <flux:input
                                wire:model="conditions.{{ $index }}.value"
                                size="sm"
                                label-sr-only="Value"
                                placeholder="Enter value..."
                            />
                        </div>

                        {{-- Remove Button --}}
                        <div class="col-span-1">
                            <flux:button
                                wire:click="removeCondition({{ $index }})"
                                variant="ghost"
                                icon="x-mark"
                                size="sm"
                                :disabled="count($conditions) === 1"
                                label-sr-only="Remove condition"
                            />
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-between pt-4 border-t">
        <flux:button
            wire:click="addCondition"
            variant="ghost"
            icon="plus"
            size="sm"
        >
            Add Condition
        </flux:button>

        <div class="flex gap-2">
            <flux:modal.close>
                <flux:button variant="ghost" size="sm">
                    Cancel
                </flux:button>
            </flux:modal.close>
            <flux:button
                variant="primary"
                wire:click="applySearch"
                size="sm"
            >
                Apply Filters
            </flux:button>
        </div>
    </div>
</div>
