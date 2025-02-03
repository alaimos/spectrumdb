<?php

namespace App\Policies;

use App\Enums\DatasetPermission;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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

    public function viewAny(User $user): Response
    {
        // Users can view the list of samples, but the query should be filtered
        // in the controller/repository to show only accessible samples
        return Response::allow();
    }

    public function view(User $user, Sample $sample): Response
    {
        return $sample->dataset->userHasPermission($user, DatasetPermission::READ)
            ? Response::allow()
            : Response::deny('You do not have permission to view this sample.');
    }

    public function create(User $user, ?Sample $sample = null): Response
    {
        if (! $sample) {
            return $user->isFarm() || $user->isResearcher()
                ? Response::allow()
                : Response::deny('Only farms and researchers can create samples.');
        }

        return $user->id === $sample->dataset->created_by
            ? Response::allow()
            : Response::deny('You can only create samples in datasets you own.');
    }

    public function update(User $user, Sample $sample): Response
    {
        return $user->id === $sample->dataset->created_by
            ? Response::allow()
            : Response::deny('Only the dataset owner can update samples.');
    }

    public function delete(User $user, Sample $sample): Response
    {
        return $user->id === $sample->dataset->created_by
            ? Response::allow()
            : Response::deny('Only the dataset owner can delete samples.');
    }

    public function downloadRaw(User $user, Sample $sample): Response
    {
        return $sample->dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_RAW)
            ? Response::allow()
            : Response::deny('You do not have permission to download raw data from this sample.');
    }

    public function downloadProcessed(User $user, Sample $sample): Response
    {
        return $sample->dataset->userHasPermission($user, DatasetPermission::DOWNLOAD_PROCESSED)
            ? Response::allow()
            : Response::deny('You do not have permission to download processed data from this sample.');
    }
}
