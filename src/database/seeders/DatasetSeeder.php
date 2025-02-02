<?php

namespace Database\Seeders;

use App\Models\Dataset;
use App\Models\DatasetMetadata;
use App\Models\Sample;
use App\Models\SampleMetadata;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatasetSeeder extends Seeder
{
    public function run(): void
    {
        $farmUser = User::where('email', 'farm@test.com')->first();

        Dataset::factory()
            ->count(5)
            ->has(
                DatasetMetadata::factory()
                    ->count(4),
                'metadata'
            )
            ->has(
                Sample::factory()
                    ->count(10)
                    ->has(
                        SampleMetadata::factory()
                            ->count(4),
                        'metadata'
                    )
            )
            ->create([
                'created_by' => $farmUser->id,
            ]);
    }
}
