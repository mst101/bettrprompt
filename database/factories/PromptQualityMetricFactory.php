<?php

namespace Database\Factories;

use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromptQualityMetricFactory extends Factory
{
    protected $model = PromptQualityMetric::class;

    public function definition(): array
    {
        return [
            'prompt_run_id' => PromptRun::factory(),
            'user_rating' => $this->faker->randomElement([1, 2, 3, 4, 5, null]),
            'rating_explanation' => $this->faker->sentence(),
            'was_copied' => $this->faker->boolean(50),
            'copy_count' => $this->faker->numberBetween(0, 5),
            'was_edited' => $this->faker->boolean(40),
            'edit_percentage' => $this->faker->numberBetween(0, 100),
            'prompt_length' => $this->faker->numberBetween(100, 1000),
            'questions_answered' => $this->faker->numberBetween(0, 10),
            'questions_skipped' => $this->faker->numberBetween(0, 5),
            'time_to_complete_ms' => $this->faker->numberBetween(5000, 300000),
            'task_category' => $this->faker->randomElement(['writing', 'coding', 'analysis', 'brainstorming']),
            'framework_used' => $this->faker->randomElement(['SCAMPER', 'Jobs to be Done', 'Design Thinking']),
            'personality_type' => $this->faker->randomElement(['INTJ', 'ENTJ', 'INTP', 'ENTP', 'ENFJ', 'INFJ']),
            'engagement_score' => $this->faker->numberBetween(0, 100),
            'quality_score' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function withRating(int $rating): self
    {
        return $this->state(fn (array $attributes) => [
            'user_rating' => $rating,
        ]);
    }

    public function copied(): self
    {
        return $this->state(fn (array $attributes) => [
            'was_copied' => true,
            'copy_count' => $this->faker->numberBetween(1, 5),
        ]);
    }

    public function edited(): self
    {
        return $this->state(fn (array $attributes) => [
            'was_edited' => true,
            'edit_percentage' => $this->faker->numberBetween(10, 100),
        ]);
    }
}
