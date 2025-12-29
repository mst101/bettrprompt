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

test('create prompt run validates task description required', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-builder.pre-analyse'), []);

    $response->assertSessionHasErrors(['task_description']);
});

test('create prompt run validates task description min length', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'short', // Too short (min 10)
    ]);

    $response->assertSessionHasErrors(['task_description']);
});

test('create prompt run creates prompt run successfully', function () {
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

    $response = $this->post(route('prompt-builder.pre-analyse'), [
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

test('create prompt run includes user personality traits', function () {
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

    $response = $this->post(route('prompt-builder.pre-analyse'), [
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

test('create prompt run saves personality tier', function () {
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

    $response = $this->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'Lead a team brainstorming session for new product ideas',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('prompt_runs', [
        'user_id' => $this->user->id,
    ]);

    // Verify pre-analysis job was dispatched
    Queue::assertPushed(\App\Jobs\ProcessPreAnalysis::class);
});

test('create prompt run allows guests as visitors', function () {
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

    $response = $this->post(route('prompt-builder.pre-analyse'), [
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

    $response = $this->get(route('prompt-builder.analyse', $otherRun));

    $response->assertForbidden();
});

test('create prompt run allows users without personality type', function () {
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

    $response = $this->post(route('prompt-builder.pre-analyse'), [
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
