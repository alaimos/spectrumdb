<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationLevel: string
{
    case INFO = 'info';
    case ERROR = 'error';
    case SUCCESS = 'success';
    case WARNING = 'warning';

    public function icon(): string
    {
        return match ($this) {
            self::INFO => 'information-circle',
            self::ERROR => 'x-circle',
            self::SUCCESS => 'check-circle',
            self::WARNING => 'exclamation-triangle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'text-blue-500 dark:text-blue-400',
            self::ERROR => 'text-red-500 dark:text-red-400',
            self::SUCCESS => 'text-green-500 dark:text-green-400',
            self::WARNING => 'text-yellow-500 dark:text-yellow-400',
        };
    }

    public function variant(): ?string
    {
        return match ($this) {
            self::INFO => null,
            self::ERROR => 'danger',
            self::SUCCESS => 'success',
            self::WARNING => 'warning',
        };
    }
}
