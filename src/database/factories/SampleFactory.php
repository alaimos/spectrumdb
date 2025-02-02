<?php

namespace Database\Factories;

use App\Models\Dataset;
use Illuminate\Database\Eloquent\Factories\Factory;

class SampleFactory extends Factory
{
    public function definition(): array
    {
        $plantStages = ['seedling', 'vegetative', 'flowering', 'fruiting'];
        $plantSections = ['root', 'stem', 'leaf', 'flower', 'fruit'];
        $varieties = ['Moneymaker', 'Better Boy', 'Roma', 'San Marzano', 'Cherry'];

        return [
            'dataset_id' => Dataset::factory(),
            'sample_code' => fake()->unique()->bothify('TOM-####-??'),
            'variety' => fake()->randomElement($varieties),
            'plant_stage' => fake()->randomElement($plantStages),
            'biological_replica' => fake()->numberBetween(1, 5),
            'sample_conditions' => fake()->sentence(),
            'plant_section' => fake()->randomElement($plantSections),
            'sampling_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'location' => fake()->city().', '.fake()->country(),
        ];
    }
}
