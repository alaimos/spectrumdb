<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsDatasetFilesDataObject;
use App\Enums\AlphaDiversityMetrics;
use App\Enums\BetaDiversityMetrics;
use App\Enums\DatasetPermission;
use App\Enums\PicrustTables;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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

    public function hasAlphaDiversity(): bool
    {
        return $this->files->alphaDiversity->shannon !== null ||
            $this->files->alphaDiversity->chao !== null ||
            $this->files->alphaDiversity->evenness !== null ||
            $this->files->alphaDiversity->faith !== null;
    }

    public function getAlphaDiversityFile(AlphaDiversityMetrics $metrics): ?string
    {
        return match ($metrics) {
            AlphaDiversityMetrics::SHANNON => $this->files->alphaDiversity->shannon,
            AlphaDiversityMetrics::CHAO => $this->files->alphaDiversity->chao,
            AlphaDiversityMetrics::EVENNESS => $this->files->alphaDiversity->evenness,
            AlphaDiversityMetrics::FAITH => $this->files->alphaDiversity->faith,
        };
    }

    public function hasBetaDiversity(): bool
    {
        return $this->files->betaDiversity->brayCurtis !== null ||
            $this->files->betaDiversity->jaccard !== null ||
            $this->files->betaDiversity->unweightedUnifrac !== null ||
            $this->files->betaDiversity->weightedUnifrac !== null;
    }

    public function getBetaDiversityFile(BetaDiversityMetrics $metrics): ?string
    {
        return match ($metrics) {
            BetaDiversityMetrics::BRAY_CURTIS => $this->files->betaDiversity->brayCurtis,
            BetaDiversityMetrics::JACCARD => $this->files->betaDiversity->jaccard,
            BetaDiversityMetrics::UNWEIGHTED_UNIFRAC => $this->files->betaDiversity->unweightedUnifrac,
            BetaDiversityMetrics::WEIGHTED_UNIFRAC => $this->files->betaDiversity->weightedUnifrac,
        };
    }

    public function hasPicrustTables(): bool
    {
        return $this->files->picrust->ec !== null ||
            $this->files->picrust->ko !== null ||
            $this->files->picrust->pathways !== null;
    }

    public function getPicrustTableFile(PicrustTables $table): ?string
    {
        return match ($table) {
            PicrustTables::EC => $this->files->picrust->ec,
            PicrustTables::KO => $this->files->picrust->ko,
            PicrustTables::PATHWAYS => $this->files->picrust->pathways,
        };
    }

    public function deleteDatasetDirectory(): bool
    {
        return Storage::deleteDirectory("datasets/{$this->id}");
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
