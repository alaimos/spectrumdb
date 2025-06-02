<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sample>
 */
final class SampleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'dataset_id' => Dataset::factory(),
            'sample_code' => fake()->unique()->bothify('TOM-####-??'),
        ];
    }
}
