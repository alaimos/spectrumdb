<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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

    public function viewAny(User $user): bool
    {
        // Only admins can list users (handled by before())
        return false;
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        return $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        // Only admins can create users (handled by before())
        return false;
    }

    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        // Users can delete their own profile
        return $user->id === $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        // Only admins can restore users (handled by before())
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Only admins can force delete users (handled by before())
        return false;
    }
}
