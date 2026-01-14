<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionVariant>
 */
class QuestionVariantFactory extends Factory
{
    protected $model = QuestionVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'personality_pattern' => $this->faker->randomElement([
                'high_t_high_j',
                'high_f_high_j',
                'high_t_high_p',
                'high_f_high_p',
                'high_a',
                'high_t_identity',
                'neutral',
            ]),
            'phrasing' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the variant is inactive.
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
