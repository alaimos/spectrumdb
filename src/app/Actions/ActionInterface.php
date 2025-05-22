<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * @template T
 */
interface ActionInterface
{
    public function getCacheFile(): string;

    /**
     * @return T
     */
    public function handle();
}
