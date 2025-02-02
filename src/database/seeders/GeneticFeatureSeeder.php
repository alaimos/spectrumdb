<?php

namespace Database\Seeders;

use App\Models\GeneticFeature;
use Illuminate\Database\Seeder;

class GeneticFeatureSeeder extends Seeder
{
    public function run(): void
    {
        GeneticFeature::factory()
            ->count(50)
            ->create();
    }
}
