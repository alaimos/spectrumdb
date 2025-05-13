<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sample;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SampleMetadata>
 */
final class SampleMetadataFactory extends Factory
{
    public function definition(): array
    {
        $keys = [
            'pH',
            'temperature',
            'humidity',
            'soil_type',
        ];

        $values = [
            'pH' => fn () => fake()->randomFloat(2, 0, 100),
            'temperature' => fn () => fake()->randomFloat(2, 0, 100),
            'humidity' => fn () => fake()->randomFloat(2, 0, 100),
            'soil_type' => fn () => fake()->word(),
        ];

        $key = fake()->randomElement($keys);

        return [
            'sample_id' => Sample::factory(),
            'key' => $key,
            'value' => $values[$key](),
        ];
    }
}
