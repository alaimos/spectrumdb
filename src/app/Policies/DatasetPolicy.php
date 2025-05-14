<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DatasetPermission;
use App\Models\Dataset;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class DatasetPolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(): Response
    {
        // Users can view the list of datasets, but the query should be filtered
        // in the controller/repository to show only accessible datasets
        return Response::allow();
    }

    public function view(User $user, Dataset $dataset): Response
    {
        return $dataset->userHasPermission($user, DatasetPermission::READ)
            ? Response::allow()
            : Response::deny('You do not have permission to view this dataset.');
    }

    public function create(User $user): Response
    {
        // Both Farms and Researchers can create datasets
        return $user->isFarm() || $user->isResearcher()
            ? Response::allow()
            : Response::deny('Only farms and researchers can create datasets.');
    }

    public function update(User $user, Dataset $dataset): Response
    {
        // Only the owner can update the dataset
        return $user->id === $dataset->created_by
            ? Response::allow()
            : Response::deny('Only the dataset owner can make changes to it.');
    }

    public function delete(User $user, Dataset $dataset): Response
    {
        // Only the owner can delete the dataset
        return $user->id === $dataset->created_by
            ? Response::allow()
            : Response::deny('Only the dataset owner can delete it.');
    }

    public function downloadRaw(User $user, Dataset $dataset): Response
    {
        return $dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_RAW)
            ? Response::allow()
            : Response::deny('You do not have permission to download raw data from this dataset.');
    }

    public function downloadProcessed(User $user, Dataset $dataset): Response
    {
        return $dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_PROCESSED)
            ? Response::allow()
            : Response::deny('You do not have permission to download processed data from this dataset.');
    }

    public function managePermissions(User $user, Dataset $dataset): Response
    {
        // Only the owner can manage permissions
        return $user->id === $dataset->created_by
            ? Response::allow()
            : Response::deny('Only the dataset owner can manage permissions.');
    }
}
