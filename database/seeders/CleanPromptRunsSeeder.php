<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Database\Seeder;

class CleanPromptRunsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder removes all prompt runs for the test user.
     * Used to ensure clean state for empty history e2e tests.
     */
    public function run(): void
    {
        // Find the test user
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            $this->command->warn('Test user not found. Nothing to clean.');

            return;
        }

        // Delete all prompt runs for test user
        $count = PromptRun::where('user_id', $user->id)->delete();

        $this->command->info("Deleted {$count} prompt runs for test user");
    }
}
