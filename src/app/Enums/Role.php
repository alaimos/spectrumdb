<?php

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
}
