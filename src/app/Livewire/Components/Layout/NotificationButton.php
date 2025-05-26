<?php

declare(strict_types=1);

namespace App\Livewire\Components\Layout;

use App\Enums\NotificationLevel;
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
        if ($batchId === null || $this->isInCurrentPage($batchId)) {
            return;
        }
        Flux::toast(
            text: 'One of your analyses failed with an error: '.($error ?? 'No error message provided.').' (Batch ID: '.$batchId.')',
            heading: 'Analysis Error',
            variant: NotificationLevel::ERROR->variant(),
        );
    }

    public function receivedAnalysisCompleted(array $notification): void
    {
        $batchId = $notification['batchId'] ?? null;
        if ($batchId === null || $this->isInCurrentPage($batchId)) {
            return;
        }
        Flux::toast(
            text: 'Your analysis has been completed successfully.',
            heading: 'Analysis Completed',
            variant: NotificationLevel::SUCCESS->variant(),
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
