<?php

namespace App\Models;

use App\Enums\DatasetPermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(DatasetMetadata::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'dataset_user_permissions')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function userHasPermission(User $user, DatasetPermission $permission): bool
    {
        // Admin and creator always have all permissions
        if ($user->id === $this->created_by || $user->isAdmin()) {
            return true;
        }

        // Farm users have all permissions on their datasets
        if ($user->id === $this->created_by && $user->isFarm()) {
            return true;
        }

        return $this->users()
            ->wherePivot('user_id', $user->id)
            ->get()
            ->contains(function ($userWithPivot) use ($permission) {
                return $userWithPivot->pivot->permission->includes($permission); // @phpstan-ignore-line
            });
    }

    public function grantPermission(User $user, DatasetPermission $permission): void
    {
        $this->users()->attach($user->id, [
            'permission' => $permission->value,
        ]);
    }

    public function revokePermission(User $user, DatasetPermission $permission): void
    {
        $this->users()->wherePivot('user_id', $user->id)
            ->wherePivot('permission', $permission->value)
            ->detach();
    }

    public function revokeAllPermissions(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Scope a query to only include datasets visible to the given user.
     */
    public function scopeVisibleTo($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        // Admin can see all datasets
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function ($query) use ($user) {
            $query
                // Datasets owned by the user
                ->where('created_by', $user->id)
                // OR datasets where the user has been granted any permission
                ->orWhereHas('users', function ($query) use ($user) {
                    $query->where('users.id', $user->id);
                });
        });
    }
}
