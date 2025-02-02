<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GeneticFeatureFactory extends Factory
{
    public function definition(): array
    {
        $types = ['gene', 'promoter', 'terminator', 'CDS', 'regulatory_sequence'];

        return [
            'feature_id' => fake()->unique()->bothify('FT_####'),
            'feature_type' => fake()->randomElement($types),
            'feature_name' => fake()->word(),
        ];
    }
}
