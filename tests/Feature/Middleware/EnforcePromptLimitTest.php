<?php

use App\Models\User;

test('free user with prompts remaining can create prompt', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 3,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'This is a test task description that is long enough to pass validation requirements for the prompt builder form',
    ]);

    expect($response->status())->not->toBe(403);
});

test('free user at limit is blocked', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 10,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('prompt-builder.pre-analyse'), [
            'task_description' => 'This is a test task description that is long enough to pass validation requirements for the prompt builder form',
        ]);

    expect($response->status())->toBe(403);
});

test('free user at limit receives correct error data', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 10,
        'prompt_count_reset_at' => now()->subDays(10),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('prompt-builder.pre-analyse'), [
            'task_description' => 'Test task description',
        ]);

    $response->assertStatus(403);
    $response->assertJson([
        'error' => 'prompt_limit_reached',
        'promptsUsed' => 10,
        'promptLimit' => 10,
    ]);
    expect($response->json('daysUntilReset'))->toBeGreaterThan(0);
    expect($response->json('suggestedTier'))->toBe('starter');
});

test('starter user with prompts remaining can create prompt', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'starter',
        'monthly_prompt_count' => 10,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'This is a test task description that is long enough to pass validation requirements',
    ]);

    expect($response->status())->not->toBe(403);
});

test('starter user at limit is blocked', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'starter',
        'monthly_prompt_count' => 25,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('prompt-builder.pre-analyse'), [
            'task_description' => 'This is a test task description that is long enough to pass validation requirements',
        ]);

    expect($response->status())->toBe(403);
    expect($response->json('promptLimit'))->toBe(25);
    expect($response->json('suggestedTier'))->toBe('pro');
});

test('pro user with prompts remaining can create prompt', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'pro',
        'monthly_prompt_count' => 50,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'This is a test task description that is long enough to pass validation requirements',
    ]);

    expect($response->status())->not->toBe(403);
});

test('pro user at limit is blocked', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'pro',
        'monthly_prompt_count' => 90,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('prompt-builder.pre-analyse'), [
            'task_description' => 'This is a test task description that is long enough to pass validation requirements',
        ]);

    expect($response->status())->toBe(403);
    expect($response->json('promptLimit'))->toBe(90);
    expect($response->json('suggestedTier'))->toBe('premium');
});

test('premium user is never blocked by limit', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'premium',
        'monthly_prompt_count' => 999,
        'prompt_count_reset_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'This is a test task description that is long enough to pass validation requirements',
    ]);

    expect($response->status())->not->toBe(403);
});

test('blocks free user at limit on create-child-from-task route', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 5,
        'prompt_count_reset_at' => now(),
    ]);

    $parentPromptRun = \App\Models\PromptRun::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(
            route('prompt-builder.create-child-from-task', ['parentPromptRun' => $parentPromptRun->id]),
            [
                'task_description' => 'Modified task description that is long enough',
            ]
        );

    expect($response->status())->toBe(403);
});

test('blocks free user at limit on create-child-from-answers route', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 5,
        'prompt_count_reset_at' => now(),
    ]);

    $parentPromptRun = \App\Models\PromptRun::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(
            route('prompt-builder.create-child-from-answers', ['parentPromptRun' => $parentPromptRun->id]),
            []
        );

    expect($response->status())->toBe(403);
});

test('blocks free user at limit on create-child-with-framework route', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 5,
        'prompt_count_reset_at' => now(),
    ]);

    $promptRun = \App\Models\PromptRun::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(
            route('prompt-builder.create-child-with-framework', ['promptRun' => $promptRun->id]),
            ['framework' => 'test']
        );

    expect($response->status())->toBe(403);
});

test('allows unauthenticated users to pass through middleware', function () {
    $response = $this->post(route('prompt-builder.pre-analyse'), [
        'task_description' => 'Test',
    ]);

    // Should not be 403 from middleware (may be 401 or 422 from other validation)
    expect($response->status())->not->toBe(403);
});
