<?php

namespace Database\Seeders;

use App\Models\ClaudeModel;
use Illuminate\Database\Seeder;

class ClaudeModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'id' => 'claude-haiku-4-5-20251001',
                'name' => 'Claude Haiku 4.5',
                'tier' => 'haiku',
                'version' => 45,
                'input_cost_per_mtok' => 1.00,
                'output_cost_per_mtok' => 5.00,
                'release_date' => '2025-10-15',
                'active' => true,
                'positioning' => 'Fastest and most cost-efficient model; near-frontier intelligence at low latency.',
                'context_window_input' => 200000,
                'context_window_output' => 64000,
            ],
            [
                'id' => 'claude-sonnet-4-5-20250929',
                'name' => 'Claude Sonnet 4.5',
                'tier' => 'sonnet',
                'version' => 45,
                'input_cost_per_mtok' => 3.00,
                'output_cost_per_mtok' => 15.00,
                'release_date' => '2025-09-29',
                'active' => true,
                'positioning' => 'High-capability model for complex reasoning, coding, and multi-step tasks.',
                'context_window_input' => 1000000,
                'context_window_output' => 1000000,
            ],
            [
                'id' => 'claude-opus-4-1-20250805',
                'name' => 'Claude Opus 4.1',
                'tier' => 'opus',
                'version' => 41,
                'input_cost_per_mtok' => 15.00,
                'output_cost_per_mtok' => 75.00,
                'release_date' => '2025-08-05',
                'active' => true,
                'positioning' => 'Flagship model for highest reasoning depth and agentic capabilities.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-opus-4-20250514',
                'name' => 'Claude Opus 4',
                'tier' => 'opus',
                'version' => 40,
                'input_cost_per_mtok' => 15.00,
                'output_cost_per_mtok' => 75.00,
                'release_date' => '2025-05-22',
                'active' => true,
                'positioning' => 'Top-tier reasoning and creativity; predecessor to Opus 4.1.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-sonnet-4-20250514',
                'name' => 'Claude Sonnet 4',
                'tier' => 'sonnet',
                'version' => 40,
                'input_cost_per_mtok' => 3.00,
                'output_cost_per_mtok' => 15.00,
                'release_date' => '2025-05-22',
                'active' => true,
                'positioning' => 'Versatile mid-tier model; balance of speed, reasoning, and cost.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-3-7-sonnet-20250219',
                'name' => 'Claude Sonnet 3.7',
                'tier' => 'sonnet',
                'version' => 37,
                'input_cost_per_mtok' => 3.00,
                'output_cost_per_mtok' => 15.00,
                'release_date' => '2025-02-24',
                'active' => true,
                'positioning' => 'Refinement of Claude 3.5; improved stability and accuracy.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-3-5-haiku-20241022',
                'name' => 'Claude Haiku 3.5',
                'tier' => 'haiku',
                'version' => 35,
                'input_cost_per_mtok' => 1.00,
                'output_cost_per_mtok' => 5.00,
                'release_date' => '2024-10-22',
                'active' => true,
                'positioning' => 'Lightweight fast model optimised for cost and latency.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-3-haiku-20240307',
                'name' => 'Claude Haiku 3',
                'tier' => 'haiku',
                'version' => 30,
                'input_cost_per_mtok' => 1.00,
                'output_cost_per_mtok' => 5.00,
                'release_date' => '2024-03-07',
                'active' => true,
                'positioning' => 'Compact, efficient model for everyday tasks.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
            [
                'id' => 'claude-3-opus-20240229',
                'name' => 'Claude Opus 3',
                'tier' => 'opus',
                'version' => 30,
                'input_cost_per_mtok' => 15.00,
                'output_cost_per_mtok' => 75.00,
                'release_date' => '2024-02-29',
                'active' => true,
                'positioning' => 'Most capable Claude 3 generation model for reasoning and coding.',
                'context_window_input' => 200000,
                'context_window_output' => 32000,
            ],
        ];

        foreach ($models as $model) {
            ClaudeModel::insertOrIgnore($model);
        }
    }
}
