<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationLevel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Stringable;

final class GeneralNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, Stringable|string>  $replace
     */
    public function __construct(
        public string $title,
        public string $message,
        public NotificationLevel $level = NotificationLevel::INFO,
        public array $replace = [],
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
            'replace' => $this->replace,
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
                'replace' => $this->replace,
                'created_at' => now()->toISOString(),
            ]
        );
    }
}
