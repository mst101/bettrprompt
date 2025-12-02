<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class E2eTestSeeder extends Seeder
{
    /**
     * Seed data for E2E tests.
     *
     * This seeder creates:
     * - A test user account
     * - Multiple prompt runs with various states
     * - Different personality types and frameworks for testing
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

            // Create or update a persistent visitor for history runs
            $visitor = Visitor::on($connection)->updateOrCreate(
                ['user_id' => $testUser->id],
                [
                    'first_visit_at' => now()->subDays(30),
                    'last_visit_at' => now()->subDay(),
                    'visit_count' => 3,
                    'personality_type' => 'INTJ-A',
                    'trait_percentages' => [
                        'mind' => 70,
                        'energy' => 55,
                        'nature' => 65,
                        'tactics' => 75,
                        'identity' => 60,
                    ],
                    'ui_complexity' => 'advanced',
                ],
            );

            // Clean existing runs for this user/visitor to avoid duplicates
            PromptRun::on($connection)
                ->where('user_id', $testUser->id)
                ->orWhere('visitor_id', $visitor->id)
                ->delete();

            // Data pools
            $frameworks = new BaseCollection([
                ['name' => 'STAR Method', 'code' => 'STAR'],
                ['name' => 'Problem-Solution-Benefit', 'code' => 'PSB'],
                ['name' => 'Feature-Advantage-Benefit', 'code' => 'FAB'],
                ['name' => 'Before-After-Bridge', 'code' => 'BAB'],
                ['name' => '4Cs Framework', 'code' => '4CS'],
                ['name' => 'AIDA Model', 'code' => 'AIDA'],
                null, // Some without frameworks
            ]);

            $personalityTypes = [
                'INTJ-A',
                'ENTJ-A',
                'INFJ-A',
                'ENFJ-A',
                'ISTJ-A',
                'ESTJ-A',
            ];

            $statuses = [
                'submitted' => 'submitted',
                'framework_selected' => 'analysis_complete',
                'completed' => 'completed',
                'failed' => 'failed',
            ];

            $tasks = [
                'Help me create a comprehensive marketing strategy for a new SaaS product targeting small business owners',
                'Write a technical blog post about microservices architecture for a developer audience',
                'Create a social media campaign for launching an eco-friendly product line',
                'Draft an email sequence for customer onboarding in a B2B context',
                'Develop a content strategy for increasing organic traffic to our website',
                'Write product descriptions for an e-commerce fashion store',
                'Create a pitch deck for investors for a fintech startup',
                'Design a customer feedback survey for improving our mobile app',
            ];

            // Create exactly 25 prompt runs with varied data
            BaseCollection::times(25, function () use (
                $connection,
                $visitor,
                $testUser,
                $statuses,
                $frameworks,
                $personalityTypes,
                $tasks
            ) {
                $status = array_rand($statuses);
                $workflowStage = $statuses[$status];
                $framework = $frameworks->random();
                $personalityType = $personalityTypes[array_rand($personalityTypes)];
                $completed = $status === 'completed';

                PromptRun::on($connection)->create([
                    'visitor_id' => $visitor->id,
                    'user_id' => $testUser->id,
                    'task_description' => $tasks[array_rand($tasks)],
                    'task_classification' => ['type' => 'prompt_builder', 'source' => 'web'],
                    'status' => $status,
                    'workflow_stage' => $workflowStage,
                    'personality_type' => $personalityType,
                    'selected_framework' => $framework ? [
                        'name' => $framework['name'],
                        'code' => $framework['code'],
                        'rationale' => 'Selected based on task analysis and personality type.',
                    ] : null,
                    'current_question_index' => $completed ? 2 : 0,
                    'framework_questions' => $completed ? [
                        [
                            'id' => 1,
                            'question' => 'What is the main goal you want to achieve?',
                            'purpose' => 'Understanding the primary objective',
                            'required' => true,
                        ],
                        [
                            'id' => 2,
                            'question' => 'Who is your target audience?',
                            'purpose' => 'Identifying the audience',
                            'required' => true,
                        ],
                    ] : null,
                    'clarifying_answers' => $completed ? [
                        '1' => 'Increase brand awareness and generate leads',
                        '2' => 'Small to medium-sized business owners in tech sector',
                    ] : null,
                    'optimized_prompt' => $completed
                        ? "As an {$personalityType} with a strategic and analytical approach, here's an optimised prompt:\n\n[Detailed prompt content would go here based on the task and framework]"
                        : null,
                    'created_at' => now()->subDays(rand(0, 30))->startOfDay(),
                    'updated_at' => now()->subDays(rand(0, 30))->startOfDay(),
                ]);
            });
        });

        $this->command->info('E2E test data seeded successfully.');
        $this->command->info('Test user email: test@example.com');
        $this->command->info('Test user password: password');
        $this->command->info('Created 25 prompt runs for testing.');
    }
}
