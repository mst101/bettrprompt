<?php

use App\Enums\WorkflowStage;
use App\Events\WorkflowFailed;
use App\Models\PromptRun;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->validSecret = setupN8nWebhookAuth();
});

test('webhook accepts and stores error context on failure', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $errorContext = [
        'error_type' => 'api_error',
        'failed_node' => 'Claude API Call',
        'execution_id' => 'exec_12345',
        'timestamp' => now()->toIso8601String(),
    ];

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'Claude API returned an error',
        'error_context' => $errorContext,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    // Verify error context was stored
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisFailed)
        ->and($promptRun->error_message)->toBe('Claude API returned an error')
        ->and($promptRun->error_context)->toEqual($errorContext)
        ->and($promptRun->last_error_at)->not->toBeNull();
});

test('webhook validates error context structure', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Invalid error_context (string instead of array)
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'Test error',
        'error_context' => 'invalid string',
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
        'error' => 'Invalid payload',
    ]);
    $response->assertJsonStructure(['details' => ['error_context']]);
});

test('webhook accepts and stores retry count', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'retry_count' => 2,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->retry_count)->toBe(2);
});

test('webhook validates retry count limits', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Test negative retry count
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'retry_count' => -1,
    ]);

    $response->assertStatus(422);
    $response->assertJsonStructure(['details' => ['retry_count']]);

    // Test exceeding max retry count
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'retry_count' => 11, // Max is 10
    ]);

    $response->assertStatus(422);
    $response->assertJsonStructure(['details' => ['retry_count']]);
});

test('webhook sets last error at timestamp on failed stages', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'last_error_at' => null,
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::GenerationFailed,
        'error_message' => 'Generation failed',
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->last_error_at)->not->toBeNull()
        ->and($promptRun->last_error_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('webhook does not set last error at on successful stages', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'last_error_at' => null,
    ]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->last_error_at)->toBeNull();
});

test('webhook broadcasts workflow failed event on failed stages', function () {
    Event::fake([WorkflowFailed::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::PreAnalysisFailed,
        'error_message' => 'Pre-analysis failed',
    ]);

    $response->assertOk();

    // Verify WorkflowFailed event was broadcast
    Event::assertDispatched(WorkflowFailed::class, function ($event) use ($promptRun) {
        return $event->promptRun->id === $promptRun->id;
    });
});

test('webhook broadcasts workflow failed event for all failed stages', function () {
    Event::fake([WorkflowFailed::class]);

    $user = User::factory()->create();

    // Test each failed stage
    $failedStages = [WorkflowStage::PreAnalysisFailed, WorkflowStage::AnalysisFailed, WorkflowStage::GenerationFailed];

    foreach ($failedStages as $stage) {
        $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

        webhookPost([
            'prompt_run_id' => $promptRun->id,
            'workflow_stage' => $stage,
            'error_message' => "Stage {$stage->value} failed",
        ]);
    }

    // Verify WorkflowFailed was broadcast 3 times (once per stage)
    Event::assertDispatched(WorkflowFailed::class, 3);
});

test('webhook does not broadcast workflow failed event on processing stages', function () {
    Event::fake([WorkflowFailed::class]);

    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
    ]);

    // Verify WorkflowFailed was NOT broadcast
    Event::assertNotDispatched(WorkflowFailed::class);
});

test('webhook stores comprehensive error details', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $errorContext = [
        'error_type' => 'timeout',
        'failed_node' => 'Generate Prompt',
        'execution_id' => 'exec_xyz789',
        'timestamp' => '2025-12-10T15:30:00Z',
        'additional_info' => [
            'duration_ms' => 90000,
            'model' => 'claude-sonnet-4',
        ],
    ];

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::GenerationFailed,
        'error_message' => 'Request timed out after 90 seconds',
        'error_context' => $errorContext,
        'retry_count' => 3,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->error_message)->toBe('Request timed out after 90 seconds')
        ->and($promptRun->error_context)->toEqual($errorContext)
        ->and($promptRun->retry_count)->toBe(3)
        ->and($promptRun->last_error_at)->not->toBeNull();
});

test('webhook validates error message max length', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Create error message exceeding 1000 characters
    $longErrorMessage = str_repeat('Error! ', 200); // ~1400 characters

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => $longErrorMessage,
    ]);

    $response->assertStatus(422);
    $response->assertJsonStructure(['details' => ['error_message']]);
});

test('webhook handles rate limit error type', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $errorContext = [
        'error_type' => 'rate_limit',
        'timestamp' => now()->toIso8601String(),
    ];

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'API rate limit exceeded',
        'error_context' => $errorContext,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->error_context['error_type'])->toBe('rate_limit');
});

test('webhook updates existing prompt run error details', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'error_message' => 'Old error',
        'retry_count' => 1,
    ]);

    // Update with new error details
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'New error after retry',
        'retry_count' => 2,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->error_message)->toBe('New error after retry')
        ->and($promptRun->retry_count)->toBe(2)
        ->and($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisFailed);
});

test('webhook handles malformed error context gracefully', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    // Send error_context with unexpected structure (but still valid array)
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'Test error',
        'error_context' => [
            'unexpected_key' => 'value',
            'nested' => ['deeply' => ['nested' => 'value']],
        ],
    ]);

    // Should still accept it (validation only checks it's an array)
    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->error_context)->toBeArray();
});

test('webhook preserves previous successful data when recording error', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisCompleted,
        'selected_framework' => ['name' => 'SMART', 'code' => 'smart'],
        'framework_questions' => ['Question 1', 'Question 2'],
    ]);

    // Update to failed stage with error
    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::GenerationFailed,
        'error_message' => 'Generation failed',
    ]);

    $response->assertOk();

    $promptRun->refresh();

    // Previous successful data should be preserved
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationFailed)
        ->and($promptRun->selected_framework)->toEqual(['name' => 'SMART', 'code' => 'smart'])
        ->and($promptRun->framework_questions)->toEqual(['Question 1', 'Question 2'])
        ->and($promptRun->error_message)->toBe('Generation failed');
});

test('webhook handles zero retry count', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'Failed on first attempt',
        'retry_count' => 0,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->retry_count)->toBe(0);
});

test('webhook handles max retry count', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create(['user_id' => $user->id]);

    $response = webhookPost([
        'prompt_run_id' => $promptRun->id,
        'workflow_stage' => WorkflowStage::AnalysisFailed,
        'error_message' => 'Failed after maximum retries',
        'retry_count' => 10, // Max allowed
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->retry_count)->toBe(10);
});
