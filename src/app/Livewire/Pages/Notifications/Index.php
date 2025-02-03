<?php

namespace App\Livewire\Pages\Notifications;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public array $selectedNotifications = [];

    public bool $selectAll = false;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('view', $user);
    }

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
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('update', $user);

        if (empty($this->selectedNotifications)) {
            return;
        }

        $user->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->update(['read_at' => now()]);

        $this->selectedNotifications = [];
        $this->selectAll = false;

        $this->dispatch('notifications-updated');
    }

    public function delete(): void
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorize('update', $user);

        if (empty($this->selectedNotifications)) {
            return;
        }

        $user->notifications()
            ->whereIn('id', $this->selectedNotifications)
            ->delete();

        $this->selectedNotifications = [];
        $this->selectAll = false;

        $this->dispatch('notifications-updated');
    }
}
