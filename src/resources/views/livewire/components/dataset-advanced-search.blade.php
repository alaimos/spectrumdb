@use(\App\Enums\SearchOperator)
<div class="space-y-6">
    <div>
        <flux:heading size="lg">
            {{ __('Advanced Search') }}
        </flux:heading>
        <flux:subheading>
            {{ __('Filter datasets using advanced criteria') }}
        </flux:subheading>
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
                            <flux:select.option value="AND">{{ __('AND') }}</flux:select.option>
                            <flux:select.option value="OR">{{ __('OR') }}</flux:select.option>
                            <flux:select.option value="NOT">{{ __('NOT') }}</flux:select.option>
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
                                label-sr-only="{{ __('Search in') }}"
                            >
                                <flux:select.option value="dataset">{{ __('Dataset') }}</flux:select.option>
                                <flux:select.option value="sample">{{ __('Sample') }}</flux:select.option>
                            </flux:select>
                        </div>

                        {{-- Metadata Key --}}
                        <div class="col-span-4">
                            <flux:select
                                wire:model="conditions.{{ $index }}.key"
                                size="sm"
                                searchable
                                label-sr-only="{{ __('Field') }}"
                                placeholder="{{ __('Select field...') }}"
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
                                label-sr-only="{{ __('Operator') }}"
                            >
                                <optgroup label="{{ __('String') }}">
                                    @foreach(SearchOperator::getStringOperatorsForSelect() as $value => $label)
                                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="{{ __('Numeric') }}">
                                    @foreach(SearchOperator::getNumericOperatorsForSelect() as $value => $label)
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
                                label-sr-only="{{ __('Value') }}"
                                placeholder="{{ __('Enter value...') }}"
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
                                label-sr-only="{{ __('Remove condition') }}"
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
            {{ __('Add Condition') }}
        </flux:button>

        <div class="flex gap-2">
            @if ($this->hasSearchApplied)
                <flux:button
                    variant="ghost"
                    wire:click="resetSearch"
                    size="sm">
                    {{ __('Cancel') }}
                </flux:button>
            @endif
            <flux:button
                variant="primary"
                wire:click="applySearch"
                size="sm"
            >
                {{ __('Apply Filters') }}
            </flux:button>
        </div>
    </div>
</div>
