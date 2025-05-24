<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class SampleMetadata extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sample_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function dataset(): HasManyThrough
    {
        return $this->hasManyThrough(Dataset::class, Sample::class);
    }
}
