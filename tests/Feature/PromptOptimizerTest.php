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
});

test('index displays form for authenticated users', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('prompt-optimizer.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptOptimizer/Index')
        ->has('auth.user')
    );
});

test('index redirects guests to login', function () {
    $response = $this->get(route('prompt-optimizer.index'));

    $response->assertRedirect(route('login'));
});

test('store validates task description required', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-optimizer.store'), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('store validates task description min length', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'short', // Too short (min 10)
    ]);

    $response->assertSessionHasErrors(['task_description']);
});

test('store creates prompt run successfully', function () {
    $this->actingAs($this->user);

    // Mock N8nClient to return success
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
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

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Create a detailed project plan for launching a new product',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'task_description' => 'Create a detailed project plan for launching a new product',
        'personality_type' => 'INTJ',
        'status' => 'processing',
    ]);
});

test('store includes user personality traits', function () {
    $this->actingAs($this->user);

    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/framework-selector', \Mockery::on(function ($data) {
                return $data['personality_type'] === 'INTJ' &&
                    isset($data['trait_percentages']) &&
                    $data['trait_percentages']['introversion'] === 75;
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Test reasoning',
                    'framework_questions' => [],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Test task that is long enough to pass validation',
    ]);

    $response->assertRedirect();
});

test('show displays prompt run details', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'My test task',
        'personality_type' => 'INTJ',
        'selected_framework' => 'SMART Goals',
        'workflow_stage' => 'framework_selected',
    ]);

    $response = $this->get(route('prompt-optimizer.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptOptimizer/Show')
        ->where('promptRun.id', $promptRun->id)
        ->where('promptRun.taskDescription', 'My test task')
        ->where('promptRun.selectedFramework', 'SMART Goals')
    );
});

test('show displays current question', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => [
            'What is your goal?',
            'How will you measure success?',
        ],
        'clarifying_answers' => [],
    ]);

    $response = $this->get(route('prompt-optimizer.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentQuestion', 'What is your goal?')
        ->where('progress.answered', 0)
        ->where('progress.total', 2)
    );
});

test('show returns null when all questions answered', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'generating_prompt',
        'framework_questions' => [
            'Question 1',
            'Question 2',
        ],
        'clarifying_answers' => [
            'Answer 1',
            'Answer 2',
        ],
    ]);

    $response = $this->get(route('prompt-optimizer.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentQuestion', null)
        ->where('progress.answered', 2)
        ->where('progress.total', 2)
    );
});

test('history displays only user prompt runs', function () {
    $this->actingAs($this->user);

    // Create prompt runs for this user
    PromptRun::factory()->count(2)->create(['user_id' => $this->user->id]);

    // Create prompt runs for another user
    $otherUser = User::factory()->create();
    PromptRun::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptOptimizer/History')
        ->has('promptRuns.data', 2) // Should only see own runs
    );
});

test('history orders by created at desc', function () {
    $this->actingAs($this->user);

    // Create runs with specific timestamps
    $oldRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);
    $newRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->get(route('prompt-optimizer.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRuns.data.0.id', $newRun->id)
        ->where('promptRuns.data.1.id', $oldRun->id)
    );
});

test('answer question saves answer successfully', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1', 'Question 2'],
        'clarifying_answers' => [],
    ]);

    $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
        'answer' => 'This is my detailed answer to the first question',
    ]);

    $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(1)
        ->and($promptRun->clarifying_answers[0])->toBe('This is my detailed answer to the first question');
});

test('answer question validates required answer', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1'],
        'clarifying_answers' => [],
    ]);

    $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
        'answer' => '',
    ]);

    $response->assertSessionHasErrors(['answer']);
});

test('answer question validates answer length', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1'],
        'clarifying_answers' => [],
    ]);

    $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
        'answer' => str_repeat('a', 2001), // Too long (max 2000)
    ]);

    $response->assertSessionHasErrors(['answer']);
});

test('completing all questions triggers generation', function () {
    $this->actingAs($this->user);

    // Mock N8nClient to return successful optimization
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/final-prompt-optimizer', \Mockery::any())
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimized_prompt' => 'Your optimised prompt here',
                ],
            ]);
    });

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1', 'Question 2'],
        'clarifying_answers' => ['Answer 1'], // One already answered
    ]);

    // Answer the last question
    $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
        'answer' => 'Answer 2',
    ]);

    $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

    $promptRun->refresh();
    expect($promptRun->workflow_stage)->toBe('completed')
        ->and($promptRun->status)->toBe('completed');
});

test('skip question records null answer', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1', 'Question 2'],
        'clarifying_answers' => [],
    ]);

    $response = $this->post(route('prompt-optimizer.skip', $promptRun));

    $response->assertRedirect(route('prompt-optimizer.show', $promptRun));

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(1)
        ->and($promptRun->clarifying_answers[0])->toBeNull();
});

test('guests cannot access prompt optimizer', function () {
    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Test task',
    ]);

    $response->assertRedirect(route('login'));
});

test('user cannot view other users prompt runs', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->get(route('prompt-optimizer.show', $otherRun));

    $response->assertForbidden();
});

test('user cannot answer other users questions', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => 'answering_questions',
        'framework_questions' => ['Question 1'],
        'clarifying_answers' => [],
    ]);

    $response = $this->post(route('prompt-optimizer.answer', $otherRun), [
        'answer' => 'My answer',
    ]);

    $response->assertForbidden();
});

test('completed prompt run displays optimized prompt', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => 'completed',
        'status' => 'completed',
        'optimized_prompt' => 'This is your personalised, optimised prompt based on your INTJ personality.',
    ]);

    $response = $this->get(route('prompt-optimizer.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.optimizedPrompt', 'This is your personalised, optimised prompt based on your INTJ personality.')
        ->where('promptRun.workflowStage', 'completed')
    );
});
