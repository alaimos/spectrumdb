<?php

declare(strict_types=1);

namespace App\Livewire\Pages;

use App\Enums\NotificationLevel;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Dashboard extends Component
{
    #[Computed]
    public function sharedDatasetsCount(): int
    {
        return auth()->user()->sharedDatasets()->count();
    }

    #[Computed]
    public function ownedDatasetsCount(): int
    {
        return auth()->user()->datasets()->count();
    }

    #[Computed]
    public function criticalNotificationsCount(): int
    {
        return auth()->user()->unreadNotifications->filter( // @phpstan-ignore-line
            function ($notification) {
                $level = NotificationLevel::from($notification->data['level'] ?? 'info');

                return $notification->type === 'App\Notifications\GeneralNotification'
                    && $level === NotificationLevel::ERROR;
            }
        )->count();
    }

    #[Computed]
    public function totalNotificationsCount(): int
    {
        return auth()->user()->unreadNotifications->count(); // @phpstan-ignore-line
    }
}
