<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GeneticFeature;
use Illuminate\Database\Seeder;

final class GeneticFeatureSeeder extends Seeder
{
    public function run(): void
    {
        GeneticFeature::factory()
            ->count(50)
            ->create();
    }
}
