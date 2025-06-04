<?php

declare(strict_types=1);

namespace App\Enums;

enum DatasetPermission: string
{
    case READ = 'read';
    case ANALYZE = 'download_raw';
    case DOWNLOAD = 'download_processed';
    case ALL = 'all';

    public static function getAllPermissions(): array
    {
        return [
            self::READ,
            self::ANALYZE,
            self::DOWNLOAD,
            self::ALL,
        ];
    }

    public function includes(self $permission): bool
    {
        if ($this === self::ALL) {
            return true;
        }

        return $this === $permission;
    }

    public function label(): string
    {
        return match ($this) {
            self::READ => 'Read',
            self::ANALYZE => 'Analyze',
            self::DOWNLOAD => 'Download',
            self::ALL => 'All',
        };
    }
}
