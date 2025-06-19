<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <flux:card class="overflow-hidden">
            <flux:text>{{ __('Uploaded Datasets') }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->ownedDatasetsCount) }}
            </flux:heading>
        </flux:card>
        <flux:card class="overflow-hidden">
            <flux:text>{{ __('Shared Datasets') }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->sharedDatasetsCount) }}
            </flux:heading>
        </flux:card>
        <flux:card class="overflow-hidden">
            <flux:text>{{ __('Critical Notifications') }}</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->criticalNotificationsCount) }}
                / {{ number_format($this->totalNotificationsCount) }}
            </flux:heading>
        </flux:card>
    </div>
    <div
        class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-8">
        <flux:heading level="1" class="mb-4">
            {{ __('Welcome to the Spectrum Data Platform!') }}
        </flux:heading>
        <flux:text class="mb-6 text-justify">
            {{ __('spectrum.dashboard_message_1') }}
        </flux:text>
        <flux:text class="mb-6 text-justify">
            {{ __('spectrum.dashboard_message_2') }}
        </flux:text>
        <flux:text class="mb-6 text-justify">
            {{ __('spectrum.dashboard_message_3') }}
        </flux:text>
    </div>
</div>
