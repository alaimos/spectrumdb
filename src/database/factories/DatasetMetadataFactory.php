<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DatasetMetadata>
 */
final class DatasetMetadataFactory extends Factory
{
    public function definition(): array
    {
        $keys = [
            'sequencing_platform',
            'sequencing_method',
            'read_length',
            'coverage',
        ];

        $values = [
            'sequencing_platform' => fn () => fake()->word(),
            'sequencing_method' => fn () => fake()->word(),
            'read_length' => fn () => fake()->numberBetween(100, 1000),
            'coverage' => fn () => fake()->numberBetween(10, 100),
        ];
        $key = fake()->randomElement($keys);

        return [
            'dataset_id' => Dataset::factory(),
            'key' => $key,
            'value' => $values[$key](),
        ];
    }
}
