<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\WithGetValues;

enum TaxonomicLevels: int
{
    /** @use WithGetValues<int> */
    use WithGetValues;

    case DOMAIN_LEVEL = 1;
    case PHYLUM_LEVEL = 2;
    case CLASS_LEVEL = 3;
    case ORDER_LEVEL = 4;
    case FAMILY_LEVEL = 5;
    case GENUS_LEVEL = 6;
    case SPECIES_LEVEL = 7;

    public function getName(): string
    {
        return match ($this) {
            self::DOMAIN_LEVEL => 'Domain',
            self::PHYLUM_LEVEL => 'Phylum',
            self::CLASS_LEVEL => 'Class',
            self::ORDER_LEVEL => 'Order',
            self::FAMILY_LEVEL => 'Family',
            self::GENUS_LEVEL => 'Genus',
            self::SPECIES_LEVEL => 'Species',
        };
    }
}
