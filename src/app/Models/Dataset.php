<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsDatasetFilesDataObject;
use App\Enums\AlphaDiversityMetrics;
use App\Enums\BetaDiversityMetrics;
use App\Enums\DatasetPermission;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property \App\DataObjects\DatasetFilesDataObject $files
 */
final class Dataset extends Model
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
            ->contains(
                function ($userWithPivot) use ($permission) {
                    return $userWithPivot->pivot->permission->includes($permission); // @phpstan-ignore-line
                }
            );
    }

    public function grantPermission(User $user, DatasetPermission $permission): void
    {
        $this->users()->attach(
            $user->id,
            [
                'permission' => $permission->value,
            ]
        );
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

    public function getAlphaDiversityFile(AlphaDiversityMetrics $metrics): ?string
    {
        return match ($metrics) {
            AlphaDiversityMetrics::SHANNON => $this->dataset->files->alphaDiversity->shannon,
            AlphaDiversityMetrics::CHAO => $this->dataset->files->alphaDiversity->chao,
            AlphaDiversityMetrics::EVENNESS => $this->dataset->files->alphaDiversity->evenness,
            AlphaDiversityMetrics::FAITH => $this->dataset->files->alphaDiversity->faith,
        };
    }

    public function getBetaDiversityFile(BetaDiversityMetrics $metrics): ?string
    {
        return match ($metrics) {
            BetaDiversityMetrics::BRAY_CURTIS => $this->dataset->files->betaDiversity->brayCurtis,
            BetaDiversityMetrics::JACCARD => $this->dataset->files->betaDiversity->jaccard,
            BetaDiversityMetrics::UNWEIGHTED_UNIFRAC => $this->dataset->files->betaDiversity->unweightedUnifrac,
            BetaDiversityMetrics::WEIGHTED_UNIFRAC => $this->dataset->files->betaDiversity->weightedUnifrac,
        };
    }

    /**
     * Scope a query to only include datasets visible to the given user.
     */
    #[Scope]
    protected function visibleTo($query, ?User $user = null)
    {
        $user ??= auth()->user();

        // Admin can see all datasets
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(
            function ($query) use ($user): void {
                $query
                    // Datasets owned by the user
                    ->where('created_by', $user->id)
                    // OR datasets where the user has been granted any permission
                    ->orWhereHas(
                        'users',
                        function ($query) use ($user): void {
                            $query->where('users.id', $user->id);
                        }
                    );
            }
        );
    }

    protected function casts(): array
    {
        return [
            'files' => AsDatasetFilesDataObject::class,
        ];
    }
}
