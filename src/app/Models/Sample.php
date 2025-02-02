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
}
