<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <flux:card class="overflow-hidden">
            <flux:text>Uploaded Datasets</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->ownedDatasetsCount) }}
            </flux:heading>
        </flux:card>
        <flux:card class="overflow-hidden">
            <flux:text>Shared Datasets</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->sharedDatasetsCount) }}
            </flux:heading>
        </flux:card>
        <flux:card class="overflow-hidden">
            <flux:text>Critical Notifications</flux:text>
            <flux:heading size="xl" class="mt-2 tabular-nums">
                {{ number_format($this->criticalNotificationsCount) }}
                / {{ number_format($this->totalNotificationsCount) }}
            </flux:heading>
        </flux:card>
    </div>
    <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 p-8">
        <flux:heading level="1" class="mb-4">
            Welcome to the Spectrum Data Platform!
        </flux:heading>
        <flux:text class="mb-6 text-justify">
            The Spectrum Data Platform is a powerful tool for managing and analyzing microbiome datasets in the context
            of plant health. It provides a user-friendly interface for uploading, sharing, and analyzing datasets,
            along with advanced features for data visualization and collaboration.
        </flux:text>
        <flux:text class="mb-6 text-justify">
            To get started, you can upload your own datasets, explore shared datasets, or check out the latest
            notifications. If you have any questions or need assistance, feel free to reach out to our support team.
        </flux:text>
        <flux:text class="mb-6 text-justify">
            We hope you find the Spectrum Data Platform useful for your research and data analysis needs!
        </flux:text>
    </div>
</div>
