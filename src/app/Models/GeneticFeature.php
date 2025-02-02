<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneticFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'feature_id',
        'feature_type',
        'feature_name',
    ];
}
