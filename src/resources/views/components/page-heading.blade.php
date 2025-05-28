@props(['title', 'subtitle' => ''])
<div class="relative mb-6 w-full">
    <div class="flex items-center">
        <div>
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
            @if ($subtitle)
                <flux:subheading size="lg" class="mb-6">{{ $subtitle }}</flux:subheading>
            @endif
        </div>
        <flux:spacer/>
        <div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('datasets.index')" wire:navigate>
                Back to datasets
            </flux:button>
        </div>
    </div>
    <flux:separator variant="subtle"/>
</div>
