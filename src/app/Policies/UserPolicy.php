<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
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
        return Response::deny('Only administrators can view the list of users.');
    }

    public function view(User $user, User $model): Response
    {
        return $user->id === $model->id
            ? Response::allow()
            : Response::deny('You can only view your own profile.');
    }

    public function create(User $user): Response
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

    public function restore(User $user, User $model): Response
    {
        return Response::deny('Only administrators can restore deleted users.');
    }

    public function forceDelete(User $user, User $model): Response
    {
        return Response::deny('Only administrators can permanently delete users.');
    }
}
