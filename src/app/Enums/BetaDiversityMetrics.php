<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\WithGetValues;

enum BetaDiversityMetrics: int
{
    /** @use WithGetValues<int> */
    use WithGetValues;

    case BRAY_CURTIS = 0;
    case JACCARD = 1;
    case UNWEIGHTED_UNIFRAC = 2;
    case WEIGHTED_UNIFRAC = 3;

    public function getName(): string
    {
        return match ($this) {
            self::BRAY_CURTIS => 'Bray-Curtis Distance',
            self::JACCARD => 'Jaccard Distance',
            self::UNWEIGHTED_UNIFRAC => 'Unweighted Unifrac Distance',
            self::WEIGHTED_UNIFRAC => 'Weighted Unifrac Distance',
        };
    }
}
