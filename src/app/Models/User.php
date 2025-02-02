<?php

namespace App\Models;

use App\Enums\DatasetPermission;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property \App\Enums\Role $role
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function datasets(): BelongsToMany
    {
        return $this->belongsToMany(Dataset::class, 'dataset_user_permissions')
            ->withPivot('permission')
            ->withTimestamps();
    }

    public function hasDatasetPermission(Dataset $dataset, DatasetPermission $permission): bool
    {
        return $dataset->userHasPermission($this, $permission);
    }

    public function grantDatasetPermission(Dataset $dataset, DatasetPermission $permission): void
    {
        $dataset->grantPermission($this, $permission);
    }

    public function revokeDatasetPermission(Dataset $dataset, DatasetPermission $permission): void
    {
        $dataset->revokePermission($this, $permission);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::ADMIN;
    }

    public function isFarm(): bool
    {
        return $this->role === Role::FARM;
    }

    public function isResearcher(): bool
    {
        return $this->role === Role::RESEARCHER;
    }
}
