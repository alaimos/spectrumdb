<?php

namespace App\Livewire\Pages\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public array $selectedNotifications = [];

    public bool $selectAll = false;

    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:App.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => 'render',
        ];
    }

    #[Title('Notifications')]
    public function render(): View
    {
        $notifications = auth()
            ->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view(
            'livewire.pages.notifications.index',
            [
                'notifications' => $notifications,
            ]
        );
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedNotifications = auth()
                ->user()
                ->notifications()
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedNotifications = [];
        }
    }

    public function markAsRead(): void
    {
        if (empty($this->selectedNotifications)) {
            return;
        }

        auth()->user()
            ->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->update(['read_at' => now()]);

        $this->selectedNotifications = [];
        $this->selectAll = false;

        // Dispatch event to update notification count
        $this->dispatch('notifications-updated');
    }

    public function delete(): void
    {
        if (empty($this->selectedNotifications)) {
            return;
        }

        auth()->user()
            ->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->delete();

        $this->selectedNotifications = [];
        $this->selectAll = false;

        // Dispatch event to update notification count
        $this->dispatch('notifications-updated');
    }
}
