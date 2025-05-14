<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DatasetPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DatasetUserPermission extends Model
{
    protected $fillable = [
        'dataset_id',
        'user_id',
        'permission',
    ];

    protected $casts = [
        'permission' => DatasetPermission::class,
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
