<?php

namespace Database\Factories;

use App\Models\ClaudeModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClaudeModel>
 */
class ClaudeModelFactory extends Factory
{
    protected $model = ClaudeModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'claude-sonnet-3-5-20241022',
            'name' => 'Claude 3.5 Sonnet',
            'tier' => 'sonnet',
            'version' => 35,
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
            'release_date' => '2024-10-22',
            'active' => true,
            'positioning' => 'Intelligence and speed',
            'context_window_input' => 200000,
            'context_window_output' => 8192,
        ];
    }

    /**
     * Create a Haiku model variant
     */
    public function haiku(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => 'claude-3-5-haiku-20241022',
            'name' => 'Claude 3.5 Haiku',
            'tier' => 'haiku',
            'version' => 35,
            'input_cost_per_mtok' => 0.2500,
            'output_cost_per_mtok' => 1.2500,
            'positioning' => 'Speed and affordability',
        ]);
    }

    /**
     * Create an Opus model variant
     */
    public function opus(): static
    {
        return $this->state(fn (array $attributes) => [
            'id' => 'claude-opus-4-20250514',
            'name' => 'Claude Opus 4',
            'tier' => 'opus',
            'version' => 40,
            'input_cost_per_mtok' => 15.0000,
            'output_cost_per_mtok' => 75.0000,
            'positioning' => 'Top-level intelligence',
        ]);
    }

    /**
     * Create an inactive model
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
