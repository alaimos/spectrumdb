<?php

declare(strict_types=1);

namespace App\Traits;

/**
 * @template T
 */
trait Makeable
{
    /**
     * Create a new instance of the class.
     *
     * @return T
     */
    public static function make(...$parameters): static
    {
        return new static(...$parameters);
    }
}
