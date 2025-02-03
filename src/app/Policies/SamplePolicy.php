<?php

namespace App\Policies;

use App\Enums\DatasetPermission;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SamplePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        // Users can view the list of samples, but the query should be filtered
        // in the controller/repository to show only accessible samples
        return true;
    }

    public function view(User $user, Sample $sample): bool
    {
        // Users can view samples if they have read access to the parent dataset
        return $sample->dataset->userHasPermission($user, DatasetPermission::READ);
    }

    public function create(User $user, ?Sample $sample = null): bool
    {
        if (! $sample) {
            // General ability to create samples
            return $user->isFarm() || $user->isResearcher();
        }

        // Can only create samples in datasets they own
        return $user->id === $sample->dataset->created_by;
    }

    public function update(User $user, Sample $sample): bool
    {
        // Only the dataset owner can update samples
        return $user->id === $sample->dataset->created_by;
    }

    public function delete(User $user, Sample $sample): bool
    {
        // Only the dataset owner can delete samples
        return $user->id === $sample->dataset->created_by;
    }

    public function downloadRaw(User $user, Sample $sample): bool
    {
        return $sample->dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_RAW);
    }

    public function downloadProcessed(User $user, Sample $sample): bool
    {
        return $sample->dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_PROCESSED);
    }
}
