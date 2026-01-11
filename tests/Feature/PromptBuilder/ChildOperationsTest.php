<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Services\N8nWorkflowClient;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ-A',
        'trait_percentages' => [
            'mind' => 75,
            'energy' => 55,
            'nature' => 80,
            'tactics' => 70,
            'identity' => 65,
        ],
    ]);
});

test('create child from task description creates child successfully', function () {
    Queue::fake();

    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'workflow_stage' => '2_completed',
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-task', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'task_description' => 'Updated task description for child run',
    ]);

    $response->assertRedirect();

    // Verify child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->task_description)->toBe('Updated task description for child run')
        ->and($childRun->parent_id)->toBe($parentRun->id)
        ->and($childRun->workflow_stage)->toBe('0_processing');

    // Verify job was dispatched (ProcessPreAnalysis for Workflow 0)
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('create child from task description validates task description required', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-task', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('user cannot create child of other users prompt run', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-task', [
        'parentPromptRun' => $otherRun,
    ], absolute: false), [
        'task_description' => 'New task description',
    ]);

    $response->assertForbidden();
});

test('create child from answers creates child successfully', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Old answer 1', 'Old answer 2'],
        'personality_tier' => 'full',
        'workflow_stage' => '2_completed',
    ]);

    // Mock N8nWorkflowClient
    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'New optimised prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['New answer 1', 'New answer 2'],
    ]);

    $response->assertRedirect();

    // Verify child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->clarifying_answers)->toBe(['New answer 1', 'New answer 2'])
        ->and($childRun->parent_id)->toBe($parentRun->id);
});

test('create child from answers validates clarifying answers required', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => [['question' => 'Question 1']],
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), []);

    $response->assertSessionHasErrors(['clarifying_answers']);
});

test('create child from answers converts empty strings to null', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'personality_tier' => 'full',
    ]);

    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['Answer 1', ''], // Empty string should become null
    ]);

    $response->assertRedirect();

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun->clarifying_answers)->toBe(['Answer 1', null]);
});

test('user cannot create child from answers of other users prompt run', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'framework_questions' => [['question' => 'Question 1']],
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $otherRun,
    ], absolute: false), [
        'clarifying_answers' => ['Answer'],
    ]);

    $response->assertForbidden();
});

test('create child from answers rejects parent without framework questions', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => [], // No questions
    ]);

    $response = $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['Answer'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('parent child relationship is correctly established', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [['question' => 'Q1']],
        'personality_tier' => 'full',
    ]);

    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['Answer'],
    ]);

    $parentRun->refresh();
    $childRun = $parentRun->children()->first();

    expect($childRun->parent_id)->toBe($parentRun->id)
        ->and($childRun->parent->id)->toBe($parentRun->id)
        ->and($parentRun->children)->toHaveCount(1);
});

test('child prompt run inherits personality from user', function () {
    $this->actingAs($this->user);

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'personality_type' => 'ENFP-A', // Different from user
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [['question' => 'Q1']],
        'personality_tier' => 'full',
    ]);

    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['Answer'],
    ]);

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();

    // Child should use current user's personality
    expect($childRun->personality_type)->toBe($this->user->personality_type)
        ->and($childRun->trait_percentages)->toBe($this->user->trait_percentages);
});

test('child from answers inherits pre-analysis information from parent', function () {
    $this->actingAs($this->user);

    $preAnalysisQuestions = [
        ['id' => 'q1', 'question' => 'What is your goal?', 'type' => 'text'],
        ['id' => 'q2', 'question' => 'What is your deadline?', 'type' => 'text'],
    ];

    $preAnalysisAnswers = [
        'q1' => 'To build a website',
        'q2' => 'Next month',
    ];

    $preAnalysisContext = [
        'q1' => ['question' => 'What is your goal?', 'answer' => 'To build a website'],
        'q2' => ['question' => 'What is your deadline?', 'answer' => 'Next month'],
    ];

    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'framework_questions' => [['question' => 'Q1']],
        'personality_tier' => 'full',
        'pre_analysis_questions' => $preAnalysisQuestions,
        'pre_analysis_answers' => $preAnalysisAnswers,
        'pre_analysis_context' => $preAnalysisContext,
        'pre_analysis_reasoning' => 'User needs structured guidance',
    ]);

    $this->mock(N8nWorkflowClient::class, function ($mock) {
        $mock->shouldReceive('executeGeneration')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Prompt',
                    'framework_used' => ['code' => 'SMART'],
                ],
            ]);
    });

    $this->post($this->countryRoute('prompt-builder.create-child-from-answers', [
        'parentPromptRun' => $parentRun,
    ], absolute: false), [
        'clarifying_answers' => ['New answer'],
    ]);

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();

    // Child should inherit all pre-analysis information
    expect($childRun->pre_analysis_questions)->toBe($preAnalysisQuestions)
        ->and($childRun->pre_analysis_answers)->toBe($preAnalysisAnswers)
        ->and($childRun->pre_analysis_context)->toBe($preAnalysisContext)
        ->and($childRun->pre_analysis_reasoning)->toBe('User needs structured guidance');
});
