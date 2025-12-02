<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestPromptRunsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates test prompt runs for the test user for e2e history tests.
     * It can be configured with count and status parameters via environment variables.
     *
     * Usage:
     * SEED_COUNT=10 SEED_STATUS=completed php artisan db:seed --class=TestPromptRunsSeeder
     */
    public function run(): void
    {
        // Find the test user (use test@example.com for e2e tests)
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            $this->command->error('Test user not found. Please run E2eTestSeeder or TestUserSeeder first.');

            return;
        }

        // Get parameters from environment variables
        $count = (int) (env('SEED_COUNT', 5));
        $status = env('SEED_STATUS');

        // Create prompt runs with varied data
        $frameworks = ['SMART Goals', 'Brainstorming', '5 Whys', 'SWOT Analysis', null];
        $personalityTypes = ['INTJ', 'INTP', 'ENTJ', 'ENTP', 'INFJ', 'INFP', 'ENFJ', 'ENFP'];

        for ($i = 0; $i < $count; $i++) {
            $promptRun = PromptRun::factory()
                ->for($user)
                ->create([
                    'created_at' => now()->subDays($i),
                    'personality_type' => $personalityTypes[$i % count($personalityTypes)],
                    'task_classification' => ['type' => 'prompt_builder', 'source' => 'web'],
                ]);

            // Apply status-specific state if provided
            if ($status) {
                match ($status) {
                    'completed' => $promptRun->update([
                        'status' => 'completed',
                        'workflow_stage' => 'completed',
                        'selected_framework' => $frameworks[$i % count($frameworks)] ?? 'SMART Goals',
                        'optimized_prompt' => 'Here is your optimised prompt for: '.$promptRun->task_description,
                        'completed_at' => now(),
                    ]),
                    'processing' => $promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'submitted',
                        'selected_framework' => $frameworks[$i % count($frameworks)],
                    ]),
                    'failed' => $promptRun->update([
                        'status' => 'failed',
                        'workflow_stage' => 'failed',
                        'error_message' => 'An error occurred during processing.',
                    ]),
                    'pending' => $promptRun->update([
                        'status' => 'pending',
                        'workflow_stage' => 'submitted',
                    ]),
                    default => null,
                };
            } else {
                // Mix of different statuses for realistic testing
                $statuses = ['completed', 'processing', 'pending'];
                $randomStatus = $statuses[$i % count($statuses)];

                match ($randomStatus) {
                    'completed' => $promptRun->update([
                        'status' => 'completed',
                        'workflow_stage' => 'completed',
                        'selected_framework' => $frameworks[$i % count($frameworks)] ?? 'SMART Goals',
                        'optimized_prompt' => 'Here is your optimised prompt for: '.$promptRun->task_description,
                        'completed_at' => now()->subHours($i),
                    ]),
                    'processing' => $promptRun->update([
                        'status' => 'processing',
                        'workflow_stage' => 'framework_selected',
                        'selected_framework' => $frameworks[$i % count($frameworks)],
                    ]),
                    default => null,
                };
            }
        }

        $this->command->info("Created {$count} prompt runs for test user".($status ? " with status: {$status}" : ' with mixed statuses'));
    }
}
