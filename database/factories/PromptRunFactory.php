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
            'status' => 'pending',
            'workflow_stage' => 'submitted',
            'selected_framework' => null,
            'framework_questions' => [],
            'clarifying_answers' => [],
            'optimized_prompt' => null,
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the prompt run is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'workflow_stage' => 'submitted',
        ]);
    }

    /**
     * Indicate that the analysis is complete (PromptBuilder).
     */
    public function analysisComplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'workflow_stage' => 'analysis_complete',
            'selected_framework' => ['code' => 'SMART', 'name' => 'SMART Goals'],
            'framework_questions' => [
                ['question' => 'What specific outcome do you want?'],
                ['question' => 'How will you measure success?'],
            ],
            'current_question_index' => 0,
        ]);
    }

    /**
     * Indicate that the prompt run is answering questions.
     */
    public function answeringQuestions(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'workflow_stage' => 'answering_questions',
            'selected_framework' => 'SMART Goals',
            'framework_questions' => [
                'What specific outcome do you want?',
                'How will you measure success?',
            ],
            'clarifying_answers' => ['Improve team productivity'],
        ]);
    }

    /**
     * Indicate that the prompt run is generating the final prompt.
     */
    public function generatingPrompt(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'workflow_stage' => 'generating_prompt',
        ]);
    }

    /**
     * Indicate that the prompt run is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'workflow_stage' => 'completed',
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
            'status' => 'failed',
            'workflow_stage' => 'failed',
            'error_message' => 'An error occurred during processing.',
        ]);
    }
}
