<flux:card>
    <div class="flex justify-between items-center mb-4">
        <flux:heading level="2">Notifications</flux:heading>
        <div class="space-x-2">
            <flux:button
                wire:click="markAsRead"
                variant="primary"
                :disabled="empty($selectedNotifications)">
                Mark as Read
            </flux:button>
            <flux:button
                wire:click="delete"
                variant="danger"
                :disabled="empty($selectedNotifications)">
                Delete
            </flux:button>
        </div>
    </div>

    <div id="pagination-top"></div>
    <div class="space-y-4">
        <div class="flex items-center space-x-2">
            <flux:checkbox
                wire:model.live="selectAll"
                label="Select All"
            />
        </div>

        @forelse($notifications as $notification)
            <flux:card
                    @class([
                        'bg-white dark:bg-zinc-800' => ! $notification->read_at,
                        'bg-zinc-50 dark:bg-zinc-800/50' => (bool) $notification->read_at,
                    ])>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <flux:checkbox
                                wire:model.live="selectedNotifications"
                                value="{{ $notification->id }}"
                        />
                    </div>
                    <div class="flex-shrink-0">
                        @php
                            $level = \App\Enums\NotificationLevel::from($notification->data['level'] ?? 'info');
                        @endphp
                        <flux:icon
                                :name="$level->icon()"
                                @class(['w-6 h-6', $level->color()])
                        />
                    </div>
                    <div class="flex-1">
                        <div
                                class="font-medium dark:text-white">{{ $notification->data['title'] ?? 'Notification' }}</div>
                        <div
                                class="text-sm text-zinc-600 dark:text-zinc-400">{{ $notification->data['message'] ?? '' }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-500 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
            </flux:card>
        @empty
            <div class="text-center py-12">
                <flux:icon name="bell" class="mx-auto h-12 w-12 text-zinc-400 dark:text-zinc-600"/>
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">No notifications</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">You don't have any notifications at the
                    moment.</p>
            </div>
        @endforelse

        <div class="mt-4">
            {{ $notifications->links(data: ['scrollTo' => '#pagination-top'])  }}
        </div>
    </div>
</flux:card>
