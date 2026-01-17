<?php

namespace Database\Factories;

use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionAnalytic>
 */
class QuestionAnalyticFactory extends Factory
{
    protected $model = QuestionAnalytic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prompt_run_id' => PromptRun::factory(),
            'visitor_id' => null,
            'user_id' => null,
            'question_id' => 'q-'.fake()->numberBetween(1, 10),
            'question_category' => fake()->randomElement(['personality', 'task', 'context']),
            'personality_variant' => fake()->randomElement(['INTJ', 'INTP', 'ENTJ', 'ENTP', 'INFJ', 'INFP', 'ENFJ', 'ENFP', 'ISTJ', 'ISFJ', 'ESTJ', 'ESFJ', 'ISTP', 'ISFP', 'ESTP', 'ESFP']),
            'display_order' => fake()->numberBetween(1, 20),
            'was_required' => fake()->boolean(70),
            'display_mode' => fake()->randomElement(['one-at-a-time', 'show-all']),
            'response_status' => fake()->randomElement(['answered', 'skipped', 'not_shown']),
            'response_length' => fake()->numberBetween(0, 500),
            'time_to_answer_ms' => fake()->numberBetween(100, 30000),
            'prompt_rating' => fake()->optional(0.7)->numberBetween(1, 5),
            'user_rating' => null,
            'rating_explanation' => null,
            'prompt_copied' => fake()->boolean(30),
            'presented_at' => fake()->dateTimeThisMonth(),
        ];
    }

    /**
     * Configure the factory to set visitor_id and user_id from the PromptRun before creation.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (QuestionAnalytic $analytic) {
            // Ensure the PromptRun is loaded
            if (! $analytic->relationLoaded('promptRun')) {
                $analytic->load('promptRun');
            }

            // Set visitor_id and user_id from the PromptRun if not already set
            if ($analytic->promptRun && ! $analytic->visitor_id) {
                $analytic->setAttribute('visitor_id', $analytic->promptRun->visitor_id);
                $analytic->setAttribute('user_id', $analytic->promptRun->user_id);
            }
        });
    }

    /**
     * Indicate that the question was answered.
     */
    public function answered(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 'answered',
            'response_length' => fake()->numberBetween(10, 500),
            'time_to_answer_ms' => fake()->numberBetween(500, 30000),
        ]);
    }

    /**
     * Indicate that the question was skipped.
     */
    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 'skipped',
            'response_length' => 0,
            'time_to_answer_ms' => fake()->numberBetween(100, 1000),
        ]);
    }

    /**
     * Indicate that the question was not shown.
     */
    public function notShown(): static
    {
        return $this->state(fn (array $attributes) => [
            'response_status' => 'not_shown',
            'response_length' => 0,
            'time_to_answer_ms' => 0,
        ]);
    }

    /**
     * Indicate that the question has a user rating.
     */
    public function withUserRating(?int $rating = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_rating' => $rating ?? fake()->numberBetween(1, 5),
            'rating_explanation' => fake()->optional(0.7)->sentence(),
        ]);
    }
}
