<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'sample_code',
        'variety',
        'plant_stage',
        'biological_replica',
        'sample_conditions',
        'plant_section',
        'sampling_date',
        'location',
    ];

    protected $casts = [
        'sampling_date' => 'date',
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
    public function scopeVisibleTo($query, ?User $user = null)
    {
        $user = $user ?? auth()->user();

        // Admin can see all samples
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('dataset', function ($query) use ($user) {
            $query->where(function ($query) use ($user) {
                $query
                    // Samples from datasets owned by the user
                    ->where('datasets.created_by', $user->id)
                    // OR samples from datasets where the user has been granted any permission
                    ->orWhereHas('users', function ($query) use ($user) {
                        $query->where('users.id', $user->id);
                    });
            });
        });
    }
}
