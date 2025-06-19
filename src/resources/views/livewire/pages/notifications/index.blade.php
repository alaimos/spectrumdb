@use(App\Enums\NotificationLevel)
<flux:card class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-4">
            <flux:heading level="2">{{ __('Notifications') }}</flux:heading>
            <flux:badge variant="primary" class="text-sm">
                {{ $notifications->total() }}
            </flux:badge>
        </div>
        <div class="space-x-3">
            <flux:button
                wire:click="markAsRead"
                variant="primary"
                size="sm"
                :disabled="empty($selectedNotifications)"
                icon="check">
                {{ __('Mark as Read') }}
            </flux:button>
            <flux:button
                wire:click="delete"
                variant="danger"
                size="sm"
                :disabled="empty($selectedNotifications)"
                icon="trash">
                {{ __('Delete') }}
            </flux:button>
        </div>
    </div>

    <div id="pagination-top"></div>
    <div class="space-y-4">
        <div class="flex items-center space-x-2 mb-4">
            <flux:checkbox
                wire:model.live="selectAll"
                label="{{ __('Select All') }}"
            />
        </div>

        @forelse($notifications as $notification)
            <flux:card
                @class([
                    '!p-0',
                    'transition-colors duration-200 hover:border-zinc-300 dark:hover:border-zinc-600',
                    'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700' => ! $notification->read_at,
                    'bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700' => (bool) $notification->read_at,
                ])>
                <div class="flex items-start space-x-4 p-4">
                    <div class="flex-shrink-0 pt-1">
                        <flux:checkbox
                            wire:model.live="selectedNotifications"
                            value="{{ $notification->id }}"
                        />
                    </div>
                    <div class="flex-shrink-0">
                        @php
                            $level = NotificationLevel::from($notification->data['level'] ?? 'info');
                        @endphp
                        <flux:icon
                            :name="$level->icon()"
                            @class(['w-6 h-6', $level->color()])
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-zinc-900 dark:text-white truncate">
                                {{ __($notification->data['title'] ?? 'Notification', $notification->data['replace'] ?? []) }}
                            </h3>
                            <time class="text-xs text-zinc-500 dark:text-zinc-400 whitespace-nowrap ml-4">
                                {{ $notification->created_at->diffForHumans() }}
                            </time>
                        </div>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">
                            {{ __($notification->data['message'] ?? '', $notification->data['replace'] ?? []) }}
                        </p>
                    </div>
                </div>
            </flux:card>
        @empty
            <div
                class="text-center py-16 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-700">
                <flux:icon name="bell" class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600"/>
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No notifications') }}</h3>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('You don\'t have any notifications at the moment.') }}</p>
            </div>
        @endforelse
        <div class="mt-6">
            {{ $notifications->links(data: ['scrollTo' => '#pagination-top'])  }}
        </div>
    </div>
</flux:card>
