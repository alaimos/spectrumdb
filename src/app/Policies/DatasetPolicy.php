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
        // Public datasets are viewable by all users
        if ($dataset->is_public) {
            return Response::allow();
        }

        return $dataset->userHasAnyPermission(
            $user,
            [
                DatasetPermission::READ,
                DatasetPermission::ANALYZE,
                DatasetPermission::DOWNLOAD,
                DatasetPermission::ALL,
            ]
        )
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

    public function analyze(User $user, Dataset $dataset): Response
    {
        // Public datasets can be analyzed by all users
        if ($dataset->is_public) {
            return Response::allow();
        }

        return $dataset->userHasAnyPermission($user, [DatasetPermission::ANALYZE, DatasetPermission::ALL])
            ? Response::allow()
            : Response::deny('You do not have permission to analyze data from this dataset.');
    }

    public function download(User $user, Dataset $dataset): Response
    {
        // Public datasets can be downloaded by all users
        if ($dataset->is_public) {
            return Response::allow();
        }

        return $dataset->userHasAnyPermission($user, [DatasetPermission::DOWNLOAD, DatasetPermission::ALL])
            ? Response::allow()
            : Response::deny('You do not have permission to download data from this dataset.');
    }
}
