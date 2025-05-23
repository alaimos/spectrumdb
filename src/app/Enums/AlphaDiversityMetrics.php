<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Arr;

enum AlphaDiversityMetrics: int
{
    case FAITH = 0;
    case CHAO = 1;
    case EVENNESS = 2;
    case SHANNON = 3;

    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return Arr::mapWithKeys(
            self::cases(),
            static fn (self $metric) => [$metric->value => $metric->getName()]
        );
    }

    public function getName(): string
    {
        return match ($this) {
            self::FAITH => 'Faith\'s Phylogenetic Diversity',
            self::CHAO => 'Chao1 Diversity Index',
            self::EVENNESS => 'Evenness Index',
            self::SHANNON => 'Shannon Diversity Index',
        };
    }
}
