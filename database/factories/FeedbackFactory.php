<?php

namespace Database\Factories;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'experience_level' => $this->faker->numberBetween(1, 7),
            'usefulness' => $this->faker->numberBetween(1, 7),
            'usage_intent' => $this->faker->numberBetween(1, 7),
            'suggestions' => $this->faker->optional(0.6)->sentence(),
            'desired_features' => $this->faker->randomElements(['templates', 'api-integration', 'advanced-analytics', 'team-collaboration'], 1),
            'desired_features_other' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate the feedback is from a beginner.
     */
    public function beginner(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate the feedback is from an advanced user.
     */
    public function advanced(): static
    {
        return $this->state(fn (array $attributes) => [
            'experience_level' => $this->faker->numberBetween(6, 7),
        ]);
    }

    /**
     * Indicate feedback with high usefulness rating.
     */
    public function useful(): static
    {
        return $this->state(fn (array $attributes) => [
            'usefulness' => $this->faker->numberBetween(5, 7),
        ]);
    }

    /**
     * Indicate feedback with low usefulness rating.
     */
    public function notUseful(): static
    {
        return $this->state(fn (array $attributes) => [
            'usefulness' => $this->faker->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate feedback from a guest (no user_id).
     */
    public function fromGuest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate feedback with suggestions.
     */
    public function withSuggestions(): static
    {
        return $this->state(fn (array $attributes) => [
            'suggestions' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate feedback with custom feature requests.
     */
    public function withCustomFeatures(): static
    {
        return $this->state(fn (array $attributes) => [
            'desired_features_other' => $this->faker->sentence(),
        ]);
    }
}
