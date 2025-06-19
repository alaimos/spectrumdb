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
            self::BRAY_CURTIS => __('Bray-Curtis Distance'),
            self::JACCARD => __('Jaccard Distance'),
            self::UNWEIGHTED_UNIFRAC => __('Unweighted UniFrac Distance'),
            self::WEIGHTED_UNIFRAC => __('Weighted UniFrac Distance'),
        };
    }
}
