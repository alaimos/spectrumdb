<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\WithGetValues;

enum AlphaDiversityMetrics: int
{
    /** @use WithGetValues<int> */
    use WithGetValues;

    case FAITH = 0;
    case CHAO = 1;
    case EVENNESS = 2;
    case SHANNON = 3;

    public function getName(): string
    {
        return match ($this) {
            self::FAITH => __('Faith\'s Phylogenetic Diversity Index'),
            self::CHAO => __('Chao1 Diversity Index'),
            self::EVENNESS => __('Evenness Index'),
            self::SHANNON => __('Shannon Diversity Index'),
        };
    }
}
