<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\WithGetValues;

enum PicrustTables: int
{
    /** @use WithGetValues<int> */
    use WithGetValues;

    case KO = 0;
    case EC = 1;
    case PATHWAYS = 2;

    public function getName(): string
    {
        return match ($this) {
            self::KO => __('KEGG Orthology (KO)'),
            self::EC => __('Enzyme Commission (EC)'),
            self::PATHWAYS => __('Pathways'),
        };
    }
}
