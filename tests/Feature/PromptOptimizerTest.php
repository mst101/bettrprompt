<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
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

test('index allows guests to access prompt optimizer', function () {
    $response = $this->get(route('prompt-optimizer.index'));

    // Guests can now access prompt optimizer
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('PromptOptimizer/Index'));
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

test('guests can create prompt runs as visitors', function () {
    // Mock N8nClient to return success
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
            ]);
    });

    // Create a visitor first (simulating middleware)
    $visitor = Visitor::factory()->create();
    $this->withCookie('visitor_id', (string) $visitor->id);

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Test task for visitor',
    ]);

    // Should create successfully and redirect to show page
    $response->assertRedirect();
    expect(PromptRun::where('visitor_id', $visitor->id)->count())->toBe(1);
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

test('store saves personality approach from framework selector', function () {
    $this->actingAs($this->user);

    // Mock N8nClient to return personality approach
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'Brainstorming',
                    'framework_reasoning' => 'Suitable for creative tasks',
                    'personality_approach' => 'amplify',
                    'framework_questions' => [
                        'What creative ideas do you want to explore?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Lead a team brainstorming session for new product ideas',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'personality_approach' => 'amplify',
        'selected_framework' => 'Brainstorming',
    ]);
});

test('store saves counterbalance personality approach', function () {
    $this->actingAs($this->user);

    // Mock N8nClient to return counterbalance approach
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Provides structure for planning',
                    'personality_approach' => 'counterbalance',
                    'framework_questions' => [
                        'What specific goal do you want to achieve?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Create a detailed project plan with clear milestones',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'personality_approach' => 'counterbalance',
        'selected_framework' => 'SMART Goals',
    ]);
});

test('show includes personality approach in response', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Test task',
        'personality_type' => 'INTJ',
        'selected_framework' => 'SMART Goals',
        'personality_approach' => 'amplify',
        'workflow_stage' => 'framework_selected',
    ]);

    $response = $this->get(route('prompt-optimizer.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.personalityApproach', 'amplify')
        ->where('promptRun.selectedFramework', 'SMART Goals')
    );
});

test('personality approach can be null for users without personality type', function () {
    $this->actingAs($this->user);

    // Mock N8nClient to return no personality approach
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'CRISPE',
                    'framework_reasoning' => 'Generic task-based selection',
                    'personality_approach' => null,
                    'framework_questions' => [
                        'What is the context?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Help me write a professional email to my team',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'personality_approach' => null,
        'selected_framework' => 'CRISPE',
    ]);
});

test('user without personality type can create prompt run', function () {
    // Create user without personality type
    $userWithoutPersonality = User::factory()->create([
        'personality_type' => null,
        'trait_percentages' => null,
    ]);

    $this->actingAs($userWithoutPersonality);

    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/framework-selector', \Mockery::on(function ($data) {
                // Should not include personality_type or trait_percentages
                return ! isset($data['personality_type']) &&
                    ! isset($data['trait_percentages']);
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'CRISPE',
                    'framework_reasoning' => 'Task-based framework selection',
                    'personality_approach' => null,
                    'framework_questions' => [
                        'What is the context?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Help me write a professional email',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $userWithoutPersonality->id,
        'personality_type' => null,
        'personality_approach' => null,
    ]);
});

test('user without personality type receives framework selection', function () {
    $userWithoutPersonality = User::factory()->create([
        'personality_type' => null,
        'trait_percentages' => null,
    ]);

    $this->actingAs($userWithoutPersonality);

    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'SMART Goals',
                    'framework_reasoning' => 'Suitable for structured planning tasks',
                    'personality_approach' => null,
                    'framework_questions' => [
                        'What specific goal do you want to achieve?',
                        'How will you measure success?',
                    ],
                ],
            ]);
    });

    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Create a quarterly sales target plan',
    ]);

    $response->assertRedirect();

    // Should still get framework selection and questions
    $promptRun = PromptRun::where('user_id', $userWithoutPersonality->id)->first();
    expect($promptRun)->not->toBeNull()
        ->and($promptRun->selected_framework)->toBe('SMART Goals')
        ->and($promptRun->framework_questions)->toHaveCount(2);
});

test('user without personality type can complete full flow', function () {
    $userWithoutPersonality = User::factory()->create([
        'personality_type' => null,
        'trait_percentages' => null,
    ]);

    $this->actingAs($userWithoutPersonality);

    // Mock framework selector
    $this->mock(N8nClient::class, function ($mock) {
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/framework-selector', \Mockery::any())
            ->andReturn([
                'success' => true,
                'data' => [
                    'selected_framework' => 'CRISPE',
                    'framework_reasoning' => 'Generic framework',
                    'personality_approach' => null,
                    'framework_questions' => ['What is the context?'],
                ],
            ]);

        // Mock final optimizer
        $mock->shouldReceive('triggerWebhook')
            ->once()
            ->with('/webhook/final-prompt-optimizer', \Mockery::on(function ($data) {
                // Should not include personality fields
                return ! isset($data['personality_type']) &&
                    ! isset($data['personality_approach']);
            }))
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimized_prompt' => 'Your optimised prompt without personality customisation',
                ],
            ]);
    });

    // Create prompt run
    $response = $this->post(route('prompt-optimizer.store'), [
        'task_description' => 'Write a business proposal',
    ]);

    $promptRun = PromptRun::where('user_id', $userWithoutPersonality->id)->first();

    // Answer the question
    $response = $this->post(route('prompt-optimizer.answer', $promptRun), [
        'answer' => 'The context is...',
    ]);

    $response->assertRedirect();

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->not->toBeNull()
        ->and($promptRun->status)->toBe('completed');
});
