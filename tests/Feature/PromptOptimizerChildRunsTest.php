<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Services\N8nClient;

beforeEach(function () {
    $this->user = User::factory()->create([
        'personality_type' => 'INTJ',
        'trait_percentages' => [
            'introversion' => 75,
            'intuition' => 80,
            'thinking' => 70,
            'judging' => 65,
        ],
    ]);

    $this->actingAs($this->user);
});

test('create child with new task description successfully', function () {
    // Create parent prompt run
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Original task',
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    // Mock N8nClient to return success
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/framework-selector', \Mockery::on(function ($data) {
                return $data['task_description'] === 'New refined task description';
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Suitable for goal-oriented tasks',
                    'framework_questions' => [
                        'What is your specific objective?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => 'New refined task description',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'New prompt optimisation created successfully.');

    // Verify child was created
    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'parent_id' => $parentRun->id,
        'task_description' => 'New refined task description',
        'selected_framework' => 'SMART Goals',
        'workflow_stage' => 'framework_selected',
    ]);
});

test('create child validates task description required', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->post(route('prompt-optimizer.create-child', $parentRun), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('create child validates task description max length', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => str_repeat('a', 5001), // Over 5000 characters
    ]);

    $response->assertSessionHasErrors(['task_description']);
});

test('user cannot create child of other users prompt run', function () {
    $otherUser = User::factory()->create();
    $parentRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => 'New task',
    ]);

    $response->assertForbidden();
});

test('create child handles n8n failure', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Mock N8nClient to return failure
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Framework selection failed',
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => 'New task description',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Failed to select framework. Please try again.');

    // Verify failed child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->status)->toBe('failed');
});

test('create child from edited answers successfully', function () {
    // Create completed parent run with questions and answers
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Original task',
        'selected_framework' => 'SMART Goals',
        'framework_reasoning' => 'Framework reasoning',
        'personality_approach' => 'amplify',
        'framework_questions' => [
            'What is your specific goal?',
            'How will you measure success?',
        ],
        'clarifying_answers' => [
            'Increase sales by 20%',
            'Track monthly revenue',
        ],
        'status' => 'completed',
        'workflow_stage' => 'completed',
    ]);

    // Mock N8nClient to return success
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/final-prompt-optimizer', \Mockery::on(function ($data) {
                return $data['clarifying_answers'] === ['Updated goal: Increase sales by 30%', 'Track weekly revenue'];
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimized_prompt' => 'Your newly optimised prompt based on edited answers',
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => [
            'Updated goal: Increase sales by 30%',
            'Track weekly revenue',
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'New prompt optimisation created with edited answers.');

    // Verify child was created with edited answers
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->task_description)->toBe('Original task')
        ->and($childRun->selected_framework)->toBe('SMART Goals')
        ->and($childRun->clarifying_answers)->toBe(['Updated goal: Increase sales by 30%', 'Track weekly revenue'])
        ->and($childRun->status)->toBe('completed')
        ->and($childRun->workflow_stage)->toBe('completed')
        ->and($childRun->optimized_prompt)->toBe('Your newly optimised prompt based on edited answers');
});

test('create child from answers validates clarifying answers required', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => ['Question 1', 'Question 2'],
    ]);

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), []);

    $response->assertSessionHasErrors(['clarifying_answers']);
});

test('create child from answers validates array structure', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => ['Question 1', 'Question 2'],
    ]);

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => 'not-an-array',
    ]);

    $response->assertSessionHasErrors(['clarifying_answers']);
});

test('create child from answers allows mismatched answer count', function () {
    // The validation doesn't enforce answer count matching questions
    // This is intentional to allow flexibility
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => ['Question 1', 'Question 2'],
        'selected_framework' => 'SMART Goals',
        'framework_reasoning' => 'Reasoning',
    ]);

    // Mock N8nClient
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimized_prompt' => 'Optimised prompt',
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1'], // Only 1 answer for 2 questions - allowed
    ]);

    $response->assertRedirect();
    // Should succeed without errors
});

test('create child from answers converts empty strings to null', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => ['Question 1', 'Question 2'],
        'selected_framework' => 'SMART Goals',
        'framework_reasoning' => 'Reasoning',
    ]);

    // Mock N8nClient
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/final-prompt-optimizer', \Mockery::on(function ($data) {
                // Empty strings should be converted to null
                return $data['clarifying_answers'] === ['Answer 1', null];
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimized_prompt' => 'Optimised prompt',
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1', ''], // Empty string should become null
    ]);

    $response->assertRedirect();

    // Verify child has null for empty answer
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun->clarifying_answers)->toBe(['Answer 1', null]);
});

test('create child from answers handles n8n failure', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => ['Question 1'],
        'selected_framework' => 'SMART Goals',
    ]);

    // Mock N8nClient to return failure
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Final optimization failed',
            ]);
    });

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Failed to generate optimised prompt. Please try again.');

    // Verify failed child was created
    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun)->not->toBeNull()
        ->and($childRun->status)->toBe('failed')
        ->and($childRun->workflow_stage)->toBe('failed');
});

test('user cannot create child from answers of other users prompt run', function () {
    $otherUser = User::factory()->create();
    $parentRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'framework_questions' => ['Question 1'],
    ]);

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1'],
    ]);

    $response->assertForbidden();
});

test('create child from answers rejects parent without framework questions', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'framework_questions' => null, // No questions
    ]);

    $response = $this->post(route('prompt-optimizer.create-child-from-answers', $parentRun), [
        'clarifying_answers' => ['Answer 1'],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Parent prompt run does not have framework questions.');
});

test('parent child relationship is correctly established', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Mock N8nClient
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Reasoning',
                    'framework_questions' => ['Question 1'],
                ],
            ]);
    });

    $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => 'Child task',
    ]);

    $parentRun->refresh();
    $parentRun->load('children');

    expect($parentRun->children)->toHaveCount(1);
    expect($parentRun->children->first()->parent_id)->toBe($parentRun->id);
    expect($parentRun->children->first()->parent->id)->toBe($parentRun->id);
});

test('child prompt run inherits personality from user', function () {
    $parentRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Mock N8nClient
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/framework-selector', \Mockery::on(function ($data) {
                return $data['personality_type'] === 'INTJ' &&
                    $data['trait_percentages']['introversion'] === 75;
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Reasoning',
                    'framework_questions' => ['Question 1'],
                ],
            ]);
    });

    $this->post(route('prompt-optimizer.create-child', $parentRun), [
        'task_description' => 'Child task',
    ]);

    $childRun = PromptRun::where('parent_id', $parentRun->id)->first();
    expect($childRun->personality_type)->toBe('INTJ')
        ->and($childRun->trait_percentages['introversion'])->toBe(75);
});
