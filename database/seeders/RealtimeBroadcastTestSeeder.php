<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class RealtimeBroadcastTestSeeder extends Seeder
{
    /**
     * Seed the database with test data for real-time broadcast testing.
     *
     * This seeder creates specific prompt runs in known states for E2E testing:
     * - One submitted prompt (no framework)
     * - One with framework selected (no optimised prompt)
     * - One completed (for reference)
     */
    public function run(): void
    {
        $testUser = User::where('email', 'test@example.com')->first();
        $visitor = Visitor::first();

        if (! $testUser || ! $visitor) {
            return; // Skip if test user doesn't exist
        }

        // Create a submitted prompt (no framework yet)
        PromptRun::create([
            'visitor_id' => $visitor->id,
            'user_id' => $testUser->id,
            'task_description' => 'Real-time test: Framework selection pending',
            'task_classification' => ['type' => 'prompt_builder', 'source' => 'web'],
            'status' => 'submitted',
            'workflow_stage' => 'submitted',
            'personality_type' => 'INTJ-A',
        ]);

        // Create a prompt with framework selected (awaiting optimisation)
        // Note: NO optimised_prompt set, so PromptOptimizationCompleted can add it
        PromptRun::create([
            'visitor_id' => $visitor->id,
            'user_id' => $testUser->id,
            'task_description' => 'Real-time test: Waiting for prompt optimisation',
            'task_classification' => ['type' => 'prompt_builder', 'source' => 'web'],
            'status' => 'analysis_complete',
            'workflow_stage' => 'answering_questions',
            'personality_type' => 'ENTJ-A',
            'selected_framework' => [
                'name' => 'STAR Method',
                'code' => 'star',
                'rationale' => 'Selected for testing real-time broadcasts',
            ],
            'framework_questions' => [
                ['id' => 1, 'question' => 'What was the specific Situation or Task?'],
                ['id' => 2, 'question' => 'What Action did you take?'],
                ['id' => 3, 'question' => 'What was the Result?'],
            ],
            // Intentionally NOT setting optimized_prompt so tests can trigger it
        ]);

        // Create a completed prompt (for reference in full workflow test)
        PromptRun::create([
            'visitor_id' => $visitor->id,
            'user_id' => $testUser->id,
            'task_description' => 'Real-time test: Completed workflow example',
            'task_classification' => ['type' => 'prompt_builder', 'source' => 'web'],
            'status' => 'completed',
            'workflow_stage' => 'completed',
            'personality_type' => 'INFJ-A',
            'selected_framework' => [
                'name' => 'STAR Method',
                'code' => 'star',
                'rationale' => 'Selected for testing real-time broadcasts',
            ],
            'framework_questions' => [
                ['id' => 1, 'question' => 'What was the specific Situation or Task?'],
                ['id' => 2, 'question' => 'What Action did you take?'],
                ['id' => 3, 'question' => 'What was the Result?'],
            ],
            'optimized_prompt' => 'This is a test optimised prompt for demonstration purposes.',
            'completed_at' => now(),
        ]);
    }
}
