<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->unique()->regexify('[A-Z]{1,3}[0-9]{1,2}'),
            'question_text' => $this->faker->sentence(10),
            'purpose' => $this->faker->sentence(5),
            'cognitive_requirements' => ['STRUCTURE', 'DETAIL'],
            'priority' => $this->faker->randomElement(['high', 'medium', 'low']),
            'category' => $this->faker->randomElement(['universal', 'decision', 'strategy', 'analysis']),
            'framework' => $this->faker->optional()->randomElement(['co_star', 'react', 'self_refine']),
            'is_universal' => false,
            'is_conditional' => false,
            'condition_text' => null,
            'display_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the question is universal.
     */
    public function universal(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_universal' => true,
                'category' => 'universal',
                'framework' => null,
            ];
        });
    }

    /**
     * Indicate that the question is conditional.
     */
    public function conditional(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_conditional' => true,
                'condition_text' => 'research task',
            ];
        });
    }

    /**
     * Indicate that the question is inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
