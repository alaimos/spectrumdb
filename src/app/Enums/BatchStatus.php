<?php

declare(strict_types=1);

namespace App\Enums;

enum BatchStatus: string
{
    case PENDING = 'pending';
    case FINISHED = 'finished';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
