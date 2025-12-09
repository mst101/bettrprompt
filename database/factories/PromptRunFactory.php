<?php

namespace Database\Factories;

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromptRun>
 */
class PromptRunFactory extends Factory
{
    protected $model = PromptRun::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visitor_id' => Visitor::factory(),
            'user_id' => User::factory(),
            'personality_type' => fake()->randomElement([
                'INTJ-A', 'INTJ-T', 'INTP-A', 'INTP-T', 'ENTJ-A', 'ENTJ-T', 'ENTP-A', 'ENTP-T',
                'INFJ-A', 'INFJ-T', 'INFP-A', 'INFP-T', 'ENFJ-A', 'ENFJ-T', 'ENFP-A', 'ENFP-T',
                'ISTJ-A', 'ISTJ-T', 'ISFJ-A', 'ISFJ-T', 'ESTJ-A', 'ESTJ-T', 'ESFJ-A', 'ESFJ-T',
                'ISTP-A', 'ISTP-T', 'ISFP-A', 'ISFP-T', 'ESTP-A', 'ESTP-T', 'ESFP-A', 'ESFP-T',
            ]),
            'trait_percentages' => [
                'mind' => fake()->numberBetween(50, 100),
                'energy' => fake()->numberBetween(50, 100),
                'nature' => fake()->numberBetween(50, 100),
                'tactics' => fake()->numberBetween(50, 100),
                'identity' => fake()->numberBetween(50, 100),
            ],
            'task_description' => fake()->sentence(),
            'workflow_stage' => '0_processing',
            'selected_framework' => null,
            'framework_questions' => [],
            'clarifying_answers' => [],
            'optimized_prompt' => null,
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the prompt run is processing (Workflow 1).
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => '1_processing',
        ]);
    }

    /**
     * Indicate that the analysis is complete (PromptBuilder).
     */
    public function analysisComplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => '1_completed',
            'selected_framework' => ['code' => 'SMART', 'name' => 'SMART Goals'],
            'framework_questions' => [
                ['question' => 'What specific outcome do you want?'],
                ['question' => 'How will you measure success?'],
            ],
            'current_question_index' => 0,
        ]);
    }

    /**
     * Indicate that the prompt run is generating the final prompt.
     */
    public function generatingPrompt(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => '2_processing',
        ]);
    }

    /**
     * Indicate that the prompt run is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => '2_completed',
            'optimized_prompt' => 'Here is your optimised prompt...',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the prompt run has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_stage' => '0_failed',
            'error_message' => 'An error occurred during processing.',
        ]);
    }
}
