<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxon extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'feature_id',
        'parent_taxa_id',
        'kingdom',
        'phylum',
        'class',
        'order',
        'family',
        'genus',
        'species',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_taxa_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_taxa_id');
    }
}
