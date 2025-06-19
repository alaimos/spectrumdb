<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case FARM = 'farm';
    case RESEARCHER = 'researcher';

    public static function default(): self
    {
        return self::RESEARCHER;
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => __('Admin'),
            self::FARM => __('Farm'),
            self::RESEARCHER => __('Researcher'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::FARM => 'green',
            self::RESEARCHER => 'blue',
        };
    }
}
