<?php

namespace Database\Seeders;

use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Database\Seeder;
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
        // Create test user
        $testUser = User::firstOrCreate(
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

        // Create prompt runs with different statuses for history tests
        $frameworks = [
            ['name' => 'STAR Method', 'code' => 'STAR'],
            ['name' => 'Problem-Solution-Benefit', 'code' => 'PSB'],
            ['name' => 'Feature-Advantage-Benefit', 'code' => 'FAB'],
            ['name' => 'Before-After-Bridge', 'code' => 'BAB'],
            ['name' => '4Cs Framework', 'code' => '4CS'],
            ['name' => 'AIDA Model', 'code' => 'AIDA'],
            null, // Some without frameworks
        ];

        $personalityTypes = [
            'INTJ-A',
            'ENTJ-A',
            'INFJ-A',
            'ENFJ-A',
            'ISTJ-A',
            'ESTJ-A',
        ];

        $statuses = ['submitted', 'framework_selected', 'completed', 'failed'];

        // Generate visitor ID for prompt runs
        $visitor = \App\Models\Visitor::create([
            'personality_type' => 'INTJ-A',
            'first_visit_at' => now()->subDays(30),
            'last_visit_at' => now()->subDays(5),
        ]);

        // Create 25 prompt runs with varied data
        for ($i = 0; $i < 25; $i++) {
            $status = $statuses[array_rand($statuses)];
            $framework = $frameworks[array_rand($frameworks)];
            $personalityType = $personalityTypes[array_rand($personalityTypes)];

            // Generate more varied task descriptions
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

            $promptRun = PromptRun::create([
                'visitor_id' => $visitor->id,
                'user_id' => $testUser->id,
                'task_description' => $tasks[array_rand($tasks)],
                'status' => $status,
                'personality_type' => $personalityType,
                'selected_framework' => $framework ? [
                    'name' => $framework['name'],
                    'code' => $framework['code'],
                    'rationale' => 'Selected based on task analysis and personality type.',
                ] : null,
                'current_question_index' => 0,
                'framework_questions' => $status === 'completed' ? [
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
                'clarifying_answers' => $status === 'completed' ? [
                    '1' => 'Increase brand awareness and generate leads',
                    '2' => 'Small to medium-sized business owners in tech sector',
                ] : null,
                'optimized_prompt' => $status === 'completed'
                    ? "As an {$personalityType} with a strategic and analytical approach, here's an optimised prompt:\n\n[Detailed prompt content would go here based on the task and framework]"
                    : null,
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now()->subDays(rand(0, 30)),
            ]);
        }

        $this->command->info('E2E test data seeded successfully.');
        $this->command->info('Test user email: test@example.com');
        $this->command->info('Test user password: password');
        $this->command->info('Created 25 prompt runs for testing.');
    }
}
