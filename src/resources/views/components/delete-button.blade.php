@props([
    'title' => 'Are you sure?',
    'id' => null,
])
@php
    $name = 'delete-button-'.$id;
    $attributes = $attributes->merge([
        'variant' => 'danger',
    ]);
    $clickAttributes = $attributes->whereStartsWith('wire:click');
    if ($clickAttributes->isNotEmpty()) {
        $attributes = $attributes->whereDoesntStartWith('wire:click');
    }
    $clickAttributes = $clickAttributes->merge([ 'variant' => 'danger' ]);
@endphp
<flux:modal.trigger :$name>
    <flux:button {{ $attributes }}>Delete</flux:button>
</flux:modal.trigger>
@teleport('body')
<flux:modal :$name class="min-w-[22rem] space-y-6">
    <div>
        <flux:heading size="lg">{{ $title }}</flux:heading>

        <flux:subheading>
            {{ $slot }}
        </flux:subheading>
    </div>

    <div class="flex gap-2">
        <flux:spacer/>

        <flux:modal.close>
            <flux:button variant="ghost">Cancel</flux:button>
        </flux:modal.close>
        @php
            $attributes = $clickAttributes;
        @endphp
        <flux:button {{ $attributes }}>Delete</flux:button>
    </div>
</flux:modal>
@endteleport
