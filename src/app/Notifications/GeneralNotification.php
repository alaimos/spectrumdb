<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

final class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public NotificationLevel $level = NotificationLevel::INFO
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'level' => $this->level->value,
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            [
                'id' => $this->id,
                'level' => $this->level->value,
                'title' => $this->title,
                'message' => $this->message,
                'created_at' => now()->toISOString(),
            ]
        );
    }
}
