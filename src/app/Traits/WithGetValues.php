<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Arr;

/**
 * @template T
 *
 * @method static array cases()
 */
trait WithGetValues
{
    abstract public function getName(): string;

    /**
     * @return array<T, string>
     */
    public static function getValues(): array
    {
        return Arr::mapWithKeys(
            self::cases(),
            static fn (self $metric) => [$metric->value => $metric->getName()]
        );
    }
}
