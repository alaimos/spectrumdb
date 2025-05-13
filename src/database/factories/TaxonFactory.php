<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Taxon>
 */
final class TaxonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'feature_id' => Str::lower(Str::random(32)),
            'parent_taxa_id' => null,
            'kingdom' => fake()->randomElement(['Bacteria', 'Archaea']),
            'phylum' => fake()->word(),
            'class' => fake()->word(),
            'order' => fake()->word(),
            'family' => fake()->word(),
            'genus' => fake()->word(),
            'species' => fake()->word(),
        ];
    }
}
