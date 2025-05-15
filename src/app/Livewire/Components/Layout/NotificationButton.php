<?php

declare(strict_types=1);

namespace App\Livewire\Components\Layout;

use App\Enums\NotificationLevel;
use Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class NotificationButton extends Component
{
    public $unreadCount = 0;

    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:App.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => 'receivedNotification',
        ];
    }

    #[On('notifications-updated')]
    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    public function receivedNotification(array $notification): void
    {
        $type = $notification['type'] ?? null;
        if ($type !== "App\Notifications\GeneralNotification") {
            return;
        }
        $title = $notification['title'] ?? 'No title';
        $message = $notification['message'] ?? 'No message';
        $notificationLevel = NotificationLevel::from($notification['level'] ?? 'info');
        Flux::toast(
            text: $message,
            heading: $title,
            variant: $notificationLevel->variant(),
        );
        $this->updateUnreadCount();
    }

    public function updateUnreadCount(): void
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function render(): View
    {
        return view('livewire.components.layout.notification-button');
    }
}
