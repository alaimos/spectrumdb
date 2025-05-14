<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatasetMetadata extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'dataset_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}
