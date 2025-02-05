<?php

namespace App\Enums;

enum DatasetPermission: string
{
    case READ = 'read';
    case DOWNLOAD_RAW = 'download_raw';
    case DOWNLOAD_PROCESSED = 'download_processed';
    case ALL = 'all';

    public static function getAllPermissions(): array
    {
        return [
            self::READ,
            self::DOWNLOAD_RAW,
            self::DOWNLOAD_PROCESSED,
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
            self::DOWNLOAD_RAW => 'Download Raw',
            self::DOWNLOAD_PROCESSED => 'Download Processed',
            self::ALL => 'All',
        };
    }
}
