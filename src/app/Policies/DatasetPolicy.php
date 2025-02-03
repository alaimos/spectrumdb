<?php

namespace App\Policies;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatasetPolicy
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
        // Users can view the list of datasets, but the query should be filtered
        // in the controller/repository to show only accessible datasets
        return true;
    }

    public function view(User $user, Dataset $dataset): bool
    {
        return $dataset->userHasPermission($user, DatasetPermission::READ);
    }

    public function create(User $user): bool
    {
        // Both Farms and Researchers can create datasets
        return $user->isFarm() || $user->isResearcher();
    }

    public function update(User $user, Dataset $dataset): bool
    {
        // Only the owner can update the dataset
        return $user->id === $dataset->created_by;
    }

    public function delete(User $user, Dataset $dataset): bool
    {
        // Only the owner can delete the dataset
        return $user->id === $dataset->created_by;
    }

    public function downloadRaw(User $user, Dataset $dataset): bool
    {
        return $dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_RAW);
    }

    public function downloadProcessed(User $user, Dataset $dataset): bool
    {
        return $dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_PROCESSED);
    }

    public function managePermissions(User $user, Dataset $dataset): bool
    {
        // Only the owner can manage permissions
        return $user->id === $dataset->created_by;
    }
}
