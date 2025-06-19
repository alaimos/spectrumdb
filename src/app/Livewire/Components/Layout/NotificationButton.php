<?php

declare(strict_types=1);

namespace App\Livewire\Components\Layout;

use App\Enums\NotificationLevel;
use App\Notifications\GeneralNotification;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

final class NotificationButton extends Component
{
    public $unreadCount = 0;

    #[Url('analysis_id')]
    public string $queryAnalysisId;

    public function getListeners(): array
    {
        $userId = auth()->id();

        return [
            "echo-private:App.Models.User.{$userId},.Illuminate\Notifications\Events\BroadcastNotificationCreated" => 'receivedNotification',
            "echo-private:analysis.{$userId},.analysis.error" => 'receivedAnalysisError',
            "echo-private:analysis.{$userId},.analysis.completed" => 'receivedAnalysisCompleted',
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

    public function receivedAnalysisError(array $notification): void
    {
        $batchId = $notification['batchId'] ?? null;
        $error = $notification['error'] ?? null;
        if ($batchId === null) {
            return;
        }
        auth()->user()->notify(
            new GeneralNotification(
                title: __('Analysis Error'),
                message: __('One of your analyses failed with an error: :message (ID: :id)', [
                    'message' => $error ?? __('No error message provided.'),
                    'id' => $batchId,
                ]),
                level: NotificationLevel::ERROR
            )
        );
    }

    public function receivedAnalysisCompleted(array $notification): void
    {
        $batchId = $notification['batchId'] ?? null;
        if ($batchId === null || $this->isInCurrentPage($batchId)) {
            return;
        }
        auth()->user()->notify(
            new GeneralNotification(
                title: __('Analysis Completed'),
                message: __('Your analysis has been completed successfully.'),
                level: NotificationLevel::SUCCESS
            )
        );
    }

    public function updateUnreadCount(): void
    {
        $this->unreadCount = auth()->user()->unreadNotifications()->count();
    }

    public function render(): View
    {
        return view('livewire.components.layout.notification-button');
    }

    private function isInCurrentPage(string $batchId): bool
    {
        $currentPageAnalysisId = $this->queryAnalysisId ?? null;

        return $currentPageAnalysisId === $batchId;
    }
}
