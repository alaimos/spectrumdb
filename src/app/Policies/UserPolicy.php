<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

final class UserPolicy
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
        return Response::deny('Only administrators can view the list of users.');
    }

    public function view(User $user, User $model): Response
    {
        return $user->id === $model->id
            ? Response::allow()
            : Response::deny('You can only view your own profile.');
    }

    public function create(): Response
    {
        return Response::deny('Only administrators can create new users.');
    }

    public function update(User $user, User $model): Response
    {
        return $user->id === $model->id
            ? Response::allow()
            : Response::deny('You can only update your own profile.');
    }

    public function delete(User $user, User $model): Response
    {
        return $user->id === $model->id
            ? Response::allow()
            : Response::deny('You can only delete your own account.');
    }

    public function restore(): Response
    {
        return Response::deny('Only administrators can restore deleted users.');
    }

    public function forceDelete(): Response
    {
        return Response::deny('Only administrators can permanently delete users.');
    }
}
