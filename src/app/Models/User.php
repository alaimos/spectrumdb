<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatasetPermission;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property Role $role
 */
final class User extends Authenticatable
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

    public function datasets(): HasMany
    {
        return $this->hasMany(Dataset::class, 'created_by');
    }

    public function sharedDatasets(): BelongsToMany
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

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

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
}
