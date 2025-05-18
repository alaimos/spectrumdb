<div
    wire:init="updateUnreadCount"
    x-data
>
    <flux:navlist.item
        icon="bell"
        href="{{ route('notifications') }}"
        wire:navigate
        badge="{{ $unreadCount > 0 ? $unreadCount : null }}"
        badge-color="red">
        Notifications
    </flux:navlist.item>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Echo.private(`App.Models.User.{{ auth()->id() }}`)
            .notification((notification) => {
                Livewire.dispatch('notification-received', notification);
            });
    });
</script>
@endpush
