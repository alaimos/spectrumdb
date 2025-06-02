<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sample extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'sample_code',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(SampleMetadata::class);
    }

    /**
     * Scope a query to only include samples visible to the given user.
     */
    #[Scope]
    protected function visibleTo($query, ?User $user = null)
    {
        $user ??= auth()->user();

        // Admin can see all samples
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('dataset', function ($query) use ($user): void {
            $query->where(function ($query) use ($user): void {
                $query
                    // Samples from datasets owned by the user
                    ->where('datasets.created_by', $user->id)
                    // OR samples from datasets where the user has been granted any permission
                    ->orWhereHas('users', function ($query) use ($user): void {
                        $query->where('users.id', $user->id);
                    });
            });
        });
    }
}
