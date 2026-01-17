<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Models\WorkflowAnalytic;
use App\Services\WorkflowAnalyticsService;

describe('Workflow Analytics Service', function () {
    test('records workflow start with required fields', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $analytic = $service->recordStart($promptRun, 0);

        expect($analytic)->toBeInstanceOf(WorkflowAnalytic::class)
            ->and($analytic->prompt_run_id)->toBe($promptRun->id)
            ->and($analytic->workflow_stage)->toBe(0)
            ->and($analytic->status)->toBe('processing')
            ->and($analytic->attempt_number)->toBe(1)
            ->and($analytic->was_retry)->toBeFalse()
            ->and($analytic->started_at)->not->toBeNull();
    });

    test('records workflow start with version', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $analytic = $service->recordStart($promptRun, 1, 'v2.0');

        expect($analytic->workflow_version)->toBe('v2.0');
    });

    test('records start for each workflow stage', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        foreach ([0, 1, 2] as $stage) {
            $analytic = $service->recordStart($promptRun, $stage);
            expect($analytic->workflow_stage)->toBe($stage);
        }
    });

    test('records workflow completion with metrics', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $start = $service->recordStart($promptRun, 0);
        sleep(1);

        $completed = $service->recordCompletion(
            $start,
            inputTokens: 1000,
            outputTokens: 500,
            estimatedCostUsd: 0.005,
            modelUsed: 'claude-3-sonnet'
        );

        expect($completed->status)->toBe('completed')
            ->and($completed->completed_at)->not->toBeNull()
            ->and($completed->duration_ms)->toBeGreaterThanOrEqual(1000)
            ->and($completed->input_tokens)->toBe(1000)
            ->and($completed->output_tokens)->toBe(500)
            ->and($completed->model_used)->toBe('claude-3-sonnet');
    });

    test('completes workflow with minimal parameters', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $start = $service->recordStart($promptRun, 1);
        $completed = $service->recordCompletion($start);

        expect($completed->status)->toBe('completed')
            ->and($completed->input_tokens)->toBeNull()
            ->and($completed->output_tokens)->toBeNull()
            ->and($completed->cost_usd)->toBeNull();
    });

    test('records workflow failure with error details', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $start = $service->recordStart($promptRun, 0);
        $failed = $service->recordFailure(
            $start,
            errorCode: 'API_RATE_LIMIT',
            errorMessage: 'Rate limit exceeded',
            inputTokens: 500,
            outputTokens: 100
        );

        expect($failed->status)->toBe('failed')
            ->and($failed->error_code)->toBe('API_RATE_LIMIT')
            ->and($failed->error_message)->toBe('Rate limit exceeded')
            ->and($failed->input_tokens)->toBe(500)
            ->and($failed->output_tokens)->toBe(100);
    });

    test('records workflow timeout', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $start = $service->recordStart($promptRun, 2);
        $timedOut = $service->recordTimeout($start);

        expect($timedOut->status)->toBe('timeout')
            ->and($timedOut->error_code)->toBe('TIMEOUT');
    });

    test('records workflow retry with attempt number', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        $retry = $service->recordRetry($promptRun, 0, 2);

        expect($retry->was_retry)->toBeTrue()
            ->and($retry->attempt_number)->toBe(2)
            ->and($retry->status)->toBe('processing');
    });

    test('records multiple retry attempts', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        for ($i = 2; $i <= 4; $i++) {
            $retry = $service->recordRetry($promptRun, 0, $i);
            expect($retry->attempt_number)->toBe($i);
        }
    });

    test('calculates success rate for workflow stage', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        // Create 7 successful and 3 failed
        for ($i = 0; $i < 7; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordCompletion($start);
        }

        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordFailure($start, 'ERROR', 'Failed');
        }

        $successRate = (float) $service->getSuccessRate(0);
        expect($successRate)->toBeGreaterThan(65.0)
            ->and($successRate)->toBeLessThan(75.0);
    });

    test('returns zero success rate when no executions', function () {
        $service = app(WorkflowAnalyticsService::class);

        $successRate = (float) $service->getSuccessRate(99);
        expect($successRate)->toBe(0.0);
    });

    test('returns 100 success rate when all successful', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 1);
            $service->recordCompletion($start);
        }

        expect((float) $service->getSuccessRate(1))->toBe(100.0);
    });

    test('calculates average duration for stage', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            sleep(1);
            $service->recordCompletion($start);
        }

        $avgDuration = $service->getAverageDuration(0);
        expect($avgDuration)->toBeGreaterThan(900);
    });

    test('returns null for no completed executions', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $service->recordStart($promptRun, 0);

        expect($service->getAverageDuration(0))->toBeNull();
    });

    test('calculates total cost for workflow stage', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordCompletion($start, estimatedCostUsd: 0.01);
        }

        $totalCost = (float) $service->getTotalCost(0);
        expect($totalCost)->toBeGreaterThan(0.02);
    });

    test('returns zero cost when no cost data', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $start = $service->recordStart($promptRun, 0);
        $service->recordCompletion($start);

        expect((float) $service->getTotalCost(0))->toBe(0.0);
    });

    test('calculates average cost per execution', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordCompletion($start, estimatedCostUsd: 0.01);
        }

        $avgCost = (float) $service->getAverageCost(0);
        expect($avgCost)->toBeGreaterThan(0.009)
            ->and($avgCost)->toBeLessThan(0.011);
    });

    test('calculates retry rate for workflow stage', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        // 3 initial + 2 retries = 40% retry rate
        $service->recordStart($promptRun, 0);
        $service->recordStart($promptRun, 0);
        $service->recordStart($promptRun, 0);
        $service->recordRetry($promptRun, 0, 2);
        $service->recordRetry($promptRun, 0, 2);

        $retryRate = (float) $service->getRetryRate(0);
        expect($retryRate)->toBeGreaterThan(35.0)
            ->and($retryRate)->toBeLessThan(45.0);
    });

    test('returns zero retry rate when no retries', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $service->recordStart($promptRun, 0);
        }

        expect((float) $service->getRetryRate(0))->toBe(0.0);
    });

    test('identifies most common error', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        // 3 TIMEOUT, 2 API_ERROR
        for ($i = 0; $i < 3; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordFailure($start, 'TIMEOUT', 'Timed out');
        }

        for ($i = 0; $i < 2; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordFailure($start, 'API_ERROR', 'API failed');
        }

        $error = $service->getMostCommonError(0);
        expect($error)->not->toBeNull()
            ->and($error['error_code'])->toBe('TIMEOUT')
            ->and($error['count'])->toBe(3);
    });

    test('returns null when no failures', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $start = $service->recordStart($promptRun, 0);
        $service->recordCompletion($start);

        expect($service->getMostCommonError(0))->toBeNull();
    });

    test('returns comprehensive stage health summary', function () {
        $service = app(WorkflowAnalyticsService::class);
        $user = User::factory()->create();

        // 6 successful, 2 failed, 1 timeout
        for ($i = 0; $i < 6; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordCompletion($start, estimatedCostUsd: 0.01);
        }

        for ($i = 0; $i < 2; $i++) {
            $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
            $start = $service->recordStart($promptRun, 0);
            $service->recordFailure($start, 'ERROR', 'Failed');
        }

        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);
        $start = $service->recordStart($promptRun, 0);
        $service->recordTimeout($start);

        $health = $service->getStageHealth(0);

        expect($health['workflow_stage'])->toBe(0)
            ->and($health['total_executions'])->toBe(9)
            ->and($health['successful'])->toBe(6)
            ->and($health['failed'])->toBe(2)
            ->and($health['timed_out'])->toBe(1);

        expect($health)->toHaveKey('success_rate')
            ->and($health)->toHaveKey('average_cost_usd')
            ->and($health)->toHaveKey('most_common_error');
    });
});
