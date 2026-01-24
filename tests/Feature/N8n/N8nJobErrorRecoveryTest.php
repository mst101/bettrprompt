<?php

use App\Enums\WorkflowStage;
use App\Jobs\ProcessAnalysis;
use App\Jobs\ProcessPreAnalysis;
use App\Jobs\ProcessPromptGeneration;
use App\Models\PromptRun;
use App\Models\User;
use App\Services\N8nWorkflowClient;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

test('pre analysis job handles service failure and updates database', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock N8nWorkflowClient to throw exception
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executePreAnalysis')
            ->once()
            ->andThrow(new \Exception('N8n service unavailable'));
    });

    // Execute the job (it will throw, which is expected behaviour)
    try {
        $job = new ProcessPreAnalysis($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected to throw
    }

    // Verify database was updated with error
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::PreAnalysisFailed)
        ->and($promptRun->error_message)->toContain('N8n service unavailable');
});

test('analysis job handles service failure and updates database', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock N8nWorkflowClient to throw exception
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeAnalysis')
            ->once()
            ->andThrow(new \Exception('Analysis failed'));
    });

    // Execute the job
    try {
        $job = new ProcessAnalysis($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    // Verify database was updated
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisFailed)
        ->and($promptRun->error_message)->toContain('Analysis failed');
});

test('prompt generation job handles service failure and updates database', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::GenerationProcessing,
        'task_description' => 'Test task',
        'selected_framework' => ['name' => 'SMART', 'code' => 'smart'],
        'framework_questions' => ['Q1', 'Q2'],
        'clarifying_answers' => ['A1', 'A2'],
        'task_classification' => ['type' => 'analytical'],
        'personality_tier' => 'full',
    ]);

    // Mock N8nWorkflowClient to throw exception
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andThrow(new \Exception('Generation failed'));
    });

    // Execute the job
    try {
        $job = new ProcessPromptGeneration($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    // Verify database was updated
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationFailed)
        ->and($promptRun->error_message)->toContain('Generation failed');
});

test('pre analysis job succeeds and dispatches next job', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock N8nWorkflowClient to return success (no questions needed)
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executePreAnalysis')
            ->once()
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task is clear',
                'pre_analysis_context' => ['clarity' => 'high'],
            ]);
    });

    // Execute the job
    $job = new ProcessPreAnalysis($promptRun);
    $job->handle(app(N8nWorkflowClient::class));

    // Verify next job was dispatched
    Queue::assertPushed(ProcessAnalysis::class);

    // Verify workflow stage updated
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisProcessing)
        ->and($promptRun->pre_analysis_skipped)->toBeTrue()
        ->and($promptRun->error_message)->toBeNull();
});

test('pre analysis job with questions does not dispatch next job', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock N8nWorkflowClient to return questions
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executePreAnalysis')
            ->once()
            ->andReturn([
                'needs_clarification' => true,
                'questions' => [
                    ['id' => 'q1', 'question' => 'What is the goal?'],
                ],
                'reasoning' => 'Need more details',
            ]);
    });

    // Execute the job
    $job = new ProcessPreAnalysis($promptRun);
    $job->handle(app(N8nWorkflowClient::class));

    // Verify next job was NOT dispatched (waiting for user answers)
    Queue::assertNotPushed(ProcessAnalysis::class);

    // Verify workflow stage is 0_completed
    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::PreAnalysisCompleted)
        ->and($promptRun->pre_analysis_questions)->toHaveCount(1)
        ->and($promptRun->error_message)->toBeNull();
});

test('jobs clear previous error messages on success', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        'task_description' => 'Test task',
        'error_message' => 'Previous error',
    ]);

    // Mock successful response
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executePreAnalysis')
            ->once()
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Clear task',
            ]);
    });

    $job = new ProcessPreAnalysis($promptRun);
    $job->handle(app(N8nWorkflowClient::class));

    // Verify error message was cleared
    $promptRun->refresh();
    expect($promptRun->error_message)->toBeNull();
});

test('jobs handle n8n rate limit errors', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::AnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock rate limit response
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeAnalysis')
            ->once()
            ->andThrow(new \Exception('API rate limit reached'));
    });

    try {
        $job = new ProcessAnalysis($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::AnalysisFailed)
        ->and($promptRun->error_message)->toContain('rate limit');
});

test('jobs handle n8n timeout errors', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::GenerationProcessing,
        'task_description' => 'Test task',
        'selected_framework' => ['name' => 'SMART'],
        'framework_questions' => ['Q1'],
        'clarifying_answers' => ['A1'],
        'task_classification' => ['type' => 'analytical'],
        'personality_tier' => 'full',
    ]);

    // Mock timeout response
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andThrow(new \Exception('Request timed out'));
    });

    try {
        $job = new ProcessPromptGeneration($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationFailed)
        ->and($promptRun->error_message)->toContain('timed out');
});

test('jobs preserve previous successful data on failure', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::GenerationProcessing,
        'task_description' => 'Test task',
        'selected_framework' => ['name' => 'SMART', 'code' => 'smart'],
        'framework_questions' => ['Question 1', 'Question 2'],
        'clarifying_answers' => ['Answer 1', 'Answer 2'],
        'task_classification' => ['type' => 'analytical'],
        'personality_tier' => 'full',
    ]);

    // Mock generation failure
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andThrow(new \Exception('Generation failed'));
    });

    try {
        $job = new ProcessPromptGeneration($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    $promptRun->refresh();

    // Previous data should be preserved
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::GenerationFailed)
        ->and($promptRun->selected_framework)->toEqual(['name' => 'SMART', 'code' => 'smart'])
        ->and($promptRun->framework_questions)->toEqual(['Question 1', 'Question 2'])
        ->and($promptRun->clarifying_answers)->toEqual(['Answer 1', 'Answer 2']);
});

test('pre analysis job handles n8n circuit breaker open', function () {
    $user = User::factory()->create();
    $promptRun = PromptRun::factory()->create([
        'user_id' => $user->id,
        'workflow_stage' => WorkflowStage::PreAnalysisProcessing,
        'task_description' => 'Test task',
    ]);

    // Mock circuit breaker response
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executePreAnalysis')
            ->once()
            ->andThrow(new \Exception('N8n service is temporarily unavailable'));
    });

    try {
        $job = new ProcessPreAnalysis($promptRun);
        $job->handle(app(N8nWorkflowClient::class));
    } catch (\Exception $e) {
        // Expected
    }

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe(WorkflowStage::PreAnalysisFailed)
        ->and($promptRun->error_message)->toContain('temporarily unavailable');
});
