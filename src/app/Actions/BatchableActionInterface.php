<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

interface BatchableActionInterface extends ActionInterface
{
    public string $batchId {
        set;
    }

    public User $user {
        set;
    }

    /**
     * @param  array<string, string>  $queryParams
     */
    public function url(array $queryParams = []): ?string;
}
