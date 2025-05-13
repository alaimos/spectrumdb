<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()
            ->admin()
            ->create(
                [
                    'name' => 'Admin User',
                    'email' => 'admin@test.com',
                ]
            );

        // Create farm user
        User::factory()
            ->farm()
            ->create(
                [
                    'name' => 'Farm User',
                    'email' => 'farm@test.com',
                ]
            );

        // Create researcher user
        User::factory()
            ->researcher()
            ->create(
                [
                    'name' => 'Researcher User',
                    'email' => 'researcher@test.com',
                ]
            );
    }
}
