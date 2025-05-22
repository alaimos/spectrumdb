<?php

declare(strict_types=1);

namespace App\Actions;

interface ActionInterface
{
    /**
     * Run the action.
     */
    public function handle(): void;
}
