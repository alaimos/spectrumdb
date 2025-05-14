<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class GeneticFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_id',
        'feature_type',
        'feature_name',
    ];
}
