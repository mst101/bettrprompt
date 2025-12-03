<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class E2eTestSeeder extends Seeder
{
    /**
     * Seed data for E2E tests.
     *
     * This seeder creates a test user account that's used by all E2E tests.
     * Test-specific data (prompt runs) are seeded on-demand by individual tests
     * using the TestPromptRunsSeeder to ensure proper test isolation.
     */
    public function run(): void
    {
        // Ensure the default connection reflects the current env (config cache-safe)
        $connection = env('DB_CONNECTION', config('database.default', 'pgsql'));
        Config::set('database.default', $connection);
        DB::setDefaultConnection($connection);

        $this->command?->info(sprintf(
            'Seeding using connection "%s" (%s)',
            $connection,
            DB::connection($connection)->getDatabaseName()
        ));

        DB::connection($connection)->transaction(function () use ($connection) {
            // Create or update test user
            $testUser = User::on($connection)->updateOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => Hash::make('password'),
                    'personality_type' => 'INTJ-A',
                    'trait_percentages' => [
                        'mind' => 75,
                        'energy' => 60,
                        'nature' => 70,
                        'tactics' => 80,
                        'identity' => 65,
                    ],
                    'ui_complexity' => 'advanced',
                ],
            );

            // Clean existing prompt runs for this user to ensure fresh state
            // (This allows E2eTestSeeder to be re-run without duplicate data)
            PromptRun::on($connection)
                ->where('user_id', $testUser->id)
                ->delete();
        });

        $this->command->info('E2E test data seeded successfully.');
        $this->command->info('Test user email: test@example.com');
        $this->command->info('Test user password: password');
        $this->command->info('Note: Prompt run data is seeded on-demand by individual tests for better isolation.');
    }
}
