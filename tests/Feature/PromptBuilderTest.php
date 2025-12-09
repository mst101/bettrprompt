<?php

use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use App\Services\PromptFrameworkService;

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

test('index displays form for authenticated users', function () {
    $this->actingAs($this->user);

    $response = $this->get(route('prompt-builder.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Index')
        ->has('auth.user')
    );
});

test('index allows guests to access prompt builder', function () {
    $response = $this->get(route('prompt-builder.index'));

    // Guests can now access prompt builder
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('PromptBuilder/Index'));
});

test('analyse validates task description required', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-builder.analyse'), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('analyse validates task description min length', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'short', // Too short (min 10)
    ]);

    $response->assertSessionHasErrors(['task_description']);
});

test('analyse creates prompt run successfully', function () {
    $this->actingAs($this->user);

    Queue::fake();

    // Mock PromptFrameworkService to return success
    $this->mock(PromptFrameworkService::class, function ($mock) {
        // First call: preAnalyseTask (returns no clarification needed)
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Create a detailed project plan for launching a new product',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'task_description' => 'Create a detailed project plan for launching a new product',
        'personality_type' => 'INTJ-A',

        'workflow_stage' => '0_processing',
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('analyse includes user personality traits', function () {
    $this->actingAs($this->user);

    Queue::fake();

    // Mock PromptFrameworkService
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task that is long enough to pass validation',
    ]);

    $response->assertRedirect();

    // Verify personality traits were saved
    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
        'personality_type' => 'INTJ-A',
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('show displays prompt run details', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'My test task',
        'personality_type' => 'INTJ',
        'selected_framework' => ['code' => 'SMART', 'name' => 'SMART Goals'],
        'workflow_stage' => '1_completed',
    ]);

    $response = $this->get(route('prompt-builder.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/Show')
        ->where('promptRun.id', $promptRun->id)
        ->where('promptRun.taskDescription', 'My test task')
    );
});

test('show displays current question', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'What is your goal?'],
            ['question' => 'How will you measure success?'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->get(route('prompt-builder.show', $promptRun));

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
        'workflow_stage' => '2_processing',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => [
            'Answer 1',
            'Answer 2',
        ],
        'current_question_index' => 2,
    ]);

    $response = $this->get(route('prompt-builder.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('currentQuestion', null)
        ->where('progress.answered', 2)
        ->where('progress.total', 2)
    );
});

test('history displays only user prompt runs with task classification', function () {
    $this->actingAs($this->user);

    // Create prompt runs for this user with task_classification (PromptBuilder runs)
    PromptRun::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    // Create prompt runs for another user
    $otherUser = User::factory()->create();
    PromptRun::factory()->count(3)->create([
        'user_id' => $otherUser->id,
        'task_classification' => ['category' => 'planning'],
    ]);

    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('PromptBuilder/History')
        ->has('promptRuns.data', 2) // Should only see own PromptBuilder runs
    );
});

test('history orders by created at desc', function () {
    $this->actingAs($this->user);

    // Create runs with specific timestamps
    $oldRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'created_at' => now()->subDays(2),
    ]);
    $newRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_classification' => ['category' => 'planning'],
        'created_at' => now(),
    ]);

    $response = $this->get(route('prompt-builder.history'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRuns.data.0.id', $newRun->id)
        ->where('promptRuns.data.1.id', $oldRun->id)
    );
});

test('answer question saves answer successfully', function () {
    $this->actingAs($this->user);

    $promptRun = promptRunBuilder()
        ->withUser($this->user)
        ->analysisComplete()
        ->withAnswers([]) // Clear any default answers
        ->build();

    $response = $this->post(route('prompt-builder.answer', $promptRun), [
        'question_index' => 0,
        'answer' => 'This is my detailed answer to the first question',
    ]);

    $response->assertOk();
    $response->assertJson([
        'clarifying_answers' => [
            'This is my detailed answer to the first question',
            null,
        ],
    ]);

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(2)
        ->and($promptRun->clarifying_answers[0])->toBe('This is my detailed answer to the first question')
        ->and($promptRun->current_question_index)->toBe(1)
        ->and($promptRun->workflow_stage)->toBe('1_completed');
});

test('skip question records null answer', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.skip', $promptRun), [
        'question_index' => 0,
    ]);

    $response->assertOk();

    $promptRun->refresh();
    expect($promptRun->clarifying_answers)->toHaveCount(2)
        ->and($promptRun->clarifying_answers[0])->toBeNull()
        ->and($promptRun->current_question_index)->toBe(1);
});

test('generate creates optimised prompt', function () {
    $this->actingAs($this->user);

    // Mock PromptFrameworkService to return successful generation
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('generatePrompt')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'optimised_prompt' => 'Your optimised prompt here',
                    'framework_used' => ['code' => 'SMART'],
                    'personality_adjustments_summary' => [],
                    'model_recommendations' => [],
                    'iteration_suggestions' => [],
                ],
            ]);
    });

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Answer 1', 'Answer 2'],
        'current_question_index' => 2,
        'task_classification' => ['category' => 'planning'],
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
    ]);

    $response = $this->post(route('prompt-builder.generate', $promptRun), [
        'question_answers' => ['Answer 1', 'Answer 2'],
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Your optimised prompt here')
        ->and($promptRun->workflow_stage)->toBe('2_completed')
        ->and($promptRun->isCompleted())->toBeTrue();
});

test('guests can create prompt runs as visitors', function () {
    Queue::fake();

    // Mock PromptFrameworkService to return success
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    // Create a visitor first (simulating middleware)
    $visitor = Visitor::factory()->create();
    $this->withCookie('visitor_id', (string) $visitor->id);

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Test task for visitor',
    ]);

    // Should create successfully and redirect to show page
    $response->assertRedirect();
    expect(PromptRun::where('visitor_id', $visitor->id)->count())->toBe(1);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('user cannot view other users prompt runs', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->get(route('prompt-builder.show', $otherRun));

    $response->assertForbidden();
});

test('user cannot answer other users questions', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRun = PromptRun::factory()->create([
        'user_id' => $otherUser->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [['question' => 'Question 1']],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.answer', $otherRun), [
        'question_index' => 0,
        'answer' => 'My answer',
    ]);

    $response->assertForbidden();
});

test('completed prompt run displays optimized prompt', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',

        'optimized_prompt' => 'This is your personalised, optimised prompt based on your INTJ personality.',
    ]);

    $response = $this->get(route('prompt-builder.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.optimizedPrompt',
            'This is your personalised, optimised prompt based on your INTJ personality.')
        ->where('promptRun.workflowStage', '2_completed')
    );
});

test('analyse saves personality tier', function () {
    $this->actingAs($this->user);

    Queue::fake();

    // Mock PromptFrameworkService to return personality tier
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Lead a team brainstorming session for new product ideas',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('show includes personality tier in response', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'task_description' => 'Test task',
        'personality_type' => 'INTJ',
        'selected_framework' => ['code' => 'SMART'],
        'personality_tier' => 'full',
        'workflow_stage' => '1_completed',
    ]);

    $response = $this->get(route('prompt-builder.show', $promptRun));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('promptRun.personalityTier', 'full')
    );
});

test('user without personality type can create prompt run', function () {
    // Create user without personality type
    $userWithoutPersonality = User::factory()->create([
        'personality_type' => null,
        'trait_percentages' => null,
    ]);

    $this->actingAs($userWithoutPersonality);

    Queue::fake();

    // Mock PromptFrameworkService
    $this->mock(PromptFrameworkService::class, function ($mock) {
        $mock->shouldReceive('preAnalyseTask')
            ->andReturn([
                'needs_clarification' => false,
                'reasoning' => 'Task description is clear',
                'questions' => [],
            ]);
    });

    $response = $this->post(route('prompt-builder.analyse'), [
        'task_description' => 'Help me write a professional email',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $userWithoutPersonality->id,
        'personality_type' => null,
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('go back to previous question updates index', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
            ['question' => 'Question 2'],
        ],
        'clarifying_answers' => ['Answer 1', null],
        'current_question_index' => 1,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect(route('prompt-builder.show', $promptRun));

    $promptRun->refresh();
    expect($promptRun->current_question_index)->toBe(0)
        ->and($promptRun->workflow_stage)->toBe('1_completed');
});

test('cannot go back from first question', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',
        'framework_questions' => [
            ['question' => 'Question 1'],
        ],
        'clarifying_answers' => [],
        'current_question_index' => 0,
    ]);

    $response = $this->post(route('prompt-builder.go-back', $promptRun));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Already at first question.');
});

test('update optimized prompt updates successfully', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '2_completed',

        'optimized_prompt' => 'Original prompt',
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'Updated prompt text',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Prompt updated successfully.');

    $promptRun->refresh();
    expect($promptRun->optimized_prompt)->toBe('Updated prompt text');
});

test('cannot update prompt for non-completed runs', function () {
    $this->actingAs($this->user);

    $promptRun = PromptRun::factory()->create([
        'user_id' => $this->user->id,
        'workflow_stage' => '1_completed',

        'optimized_prompt' => null,
    ]);

    $response = $this->patch(route('prompt-builder.update-prompt', $promptRun), [
        'optimized_prompt' => 'Trying to update',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Can only edit completed prompt runs.');
});
