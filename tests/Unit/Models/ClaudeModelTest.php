<?php

use App\Models\ClaudeModel;

/**
 * Unit tests for ClaudeModel cost calculation logic
 */
describe('ClaudeModel input cost calculation', function () {
    test('calculateInputCost returns correct cost for standard token count', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000, // $3 per million tokens
        ]);

        $cost = $model->calculateInputCost(100_000); // 100k tokens

        expect($cost)->toEqualWithDelta(0.30, 0.0001); // $0.30
    });

    test('calculateInputCost returns zero for zero tokens', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
        ]);

        $cost = $model->calculateInputCost(0);

        expect($cost)->toBe(0.0);
    });

    test('calculateInputCost handles small token counts accurately', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
        ]);

        $cost = $model->calculateInputCost(1_000); // 1k tokens

        expect($cost)->toBe(0.003); // $0.003
    });

    test('calculateInputCost handles large token counts', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 15.0000, // Opus pricing
        ]);

        $cost = $model->calculateInputCost(5_000_000); // 5M tokens

        expect($cost)->toBe(75.0); // $75
    });

    test('calculateInputCost works with different pricing tiers', function () {
        $haiku = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 0.2500, // $0.25 per million
        ]);

        $cost = $haiku->calculateInputCost(1_000_000); // 1M tokens

        expect($cost)->toBe(0.25); // $0.25
    });
});

describe('ClaudeModel output cost calculation', function () {
    test('calculateOutputCost returns correct cost for standard token count', function () {
        $model = ClaudeModel::factory()->create([
            'output_cost_per_mtok' => 15.0000, // $15 per million tokens
        ]);

        $cost = $model->calculateOutputCost(100_000); // 100k tokens

        expect($cost)->toBe(1.50); // $1.50
    });

    test('calculateOutputCost returns zero for zero tokens', function () {
        $model = ClaudeModel::factory()->create([
            'output_cost_per_mtok' => 15.0000,
        ]);

        $cost = $model->calculateOutputCost(0);

        expect($cost)->toBe(0.0);
    });

    test('calculateOutputCost handles small token counts accurately', function () {
        $model = ClaudeModel::factory()->create([
            'output_cost_per_mtok' => 15.0000,
        ]);

        $cost = $model->calculateOutputCost(500); // 500 tokens

        expect($cost)->toBe(0.0075); // $0.0075
    });

    test('calculateOutputCost handles large token counts', function () {
        $model = ClaudeModel::factory()->create([
            'output_cost_per_mtok' => 75.0000, // Opus output pricing
        ]);

        $cost = $model->calculateOutputCost(10_000_000); // 10M tokens

        expect($cost)->toBe(750.0); // $750
    });

    test('calculateOutputCost works with different pricing tiers', function () {
        $haiku = ClaudeModel::factory()->create([
            'output_cost_per_mtok' => 1.2500, // $1.25 per million
        ]);

        $cost = $haiku->calculateOutputCost(2_000_000); // 2M tokens

        expect($cost)->toBe(2.50); // $2.50
    });
});

describe('ClaudeModel total cost calculation', function () {
    test('calculateTotalCost returns sum of input and output costs', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        $total = $model->calculateTotalCost(100_000, 50_000);

        // Input: 100k * $3/M = $0.30
        // Output: 50k * $15/M = $0.75
        // Total: $1.05
        expect($total)->toBe(1.05);
    });

    test('calculateTotalCost returns zero for zero tokens', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        $total = $model->calculateTotalCost(0, 0);

        expect($total)->toBe(0.0);
    });

    test('calculateTotalCost handles input-only scenario', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        $total = $model->calculateTotalCost(200_000, 0);

        expect($total)->toEqualWithDelta(0.60, 0.0001); // Only input cost
    });

    test('calculateTotalCost handles output-only scenario', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        $total = $model->calculateTotalCost(0, 100_000);

        expect($total)->toBe(1.50); // Only output cost
    });

    test('calculateTotalCost handles realistic API call', function () {
        $sonnet = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        // Realistic: 5k input, 2k output
        $total = $sonnet->calculateTotalCost(5_000, 2_000);

        // Input: 5k * $3/M = $0.015
        // Output: 2k * $15/M = $0.030
        // Total: $0.045
        expect($total)->toBe(0.045);
    });

    test('calculateTotalCost handles high-volume usage', function () {
        $model = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 3.0000,
            'output_cost_per_mtok' => 15.0000,
        ]);

        // High volume: 10M input, 5M output
        $total = $model->calculateTotalCost(10_000_000, 5_000_000);

        // Input: 10M * $3/M = $30
        // Output: 5M * $15/M = $75
        // Total: $105
        expect($total)->toBe(105.0);
    });

    test('calculateTotalCost works with Haiku pricing tier', function () {
        $haiku = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 0.2500,
            'output_cost_per_mtok' => 1.2500,
        ]);

        $total = $haiku->calculateTotalCost(1_000_000, 500_000);

        // Input: 1M * $0.25/M = $0.25
        // Output: 500k * $1.25/M = $0.625
        // Total: $0.875
        expect($total)->toBe(0.875);
    });

    test('calculateTotalCost works with Opus pricing tier', function () {
        $opus = ClaudeModel::factory()->create([
            'input_cost_per_mtok' => 15.0000,
            'output_cost_per_mtok' => 75.0000,
        ]);

        $total = $opus->calculateTotalCost(100_000, 50_000);

        // Input: 100k * $15/M = $1.50
        // Output: 50k * $75/M = $3.75
        // Total: $5.25
        expect($total)->toBe(5.25);
    });
});
