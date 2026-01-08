<?php

use App\Models\User;

test('pricing page is publicly accessible', function () {
    $response = $this->getLocale('/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Pricing')
        ->has('plans.monthly')
        ->has('plans.yearly')
        ->has('features.free')
        ->has('features.pro')
    );
});

test('checkout requires authentication', function () {
    $response = $this->postLocale('/subscription/checkout', [
        'plan' => 'monthly',
    ]);

    $response->assertRedirect(route('login'));
});

test('checkout validates plan selection', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->postLocale('/subscription/checkout', [
            'plan' => 'invalid',
        ]);

    $response->assertSessionHasErrors(['plan']);
});

test('subscription settings page requires authentication', function () {
    $response = $this->getLocale('/settings/subscription');

    $response->assertRedirect(route('login'));
});

test('subscription settings page is displayed for authenticated users', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->getLocale('/settings/subscription');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Settings/Subscription')
        ->has('subscription')
    );
});

test('free user has correct subscription status', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 3,
    ]);

    expect($user->isPro())->toBeFalse();
    expect($user->isFree())->toBeTrue();
    expect($user->getPromptsRemaining())->toBe(7); // 10 - 3
    expect($user->canCreatePrompt())->toBeTrue();
});

test('free user with exhausted prompts cannot create more', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 10,
    ]);

    expect($user->getPromptsRemaining())->toBe(0);
    expect($user->canCreatePrompt())->toBeFalse();
});

test('pro user can always create prompts', function () {
    $user = User::factory()
        ->create(['subscription_tier' => 'pro']);

    // Create a real subscription for testing
    $user->update(['stripe_id' => 'cus_test']);

    // Create the subscription record
    $user->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
    ]);

    expect($user->isPro())->toBeTrue();
    expect($user->canCreatePrompt())->toBeTrue();
    expect($user->getPromptsRemaining())->toBe(PHP_INT_MAX);
});

test('prompt count increments correctly', function () {
    $user = User::factory()->create([
        'monthly_prompt_count' => 5,
        'prompt_count_reset_at' => now(),
    ]);

    $user->incrementPromptCount();

    $user->refresh();
    expect($user->monthly_prompt_count)->toBe(6);
});

test('prompt count resets at new month', function () {
    $user = User::factory()->create([
        'monthly_prompt_count' => 10,
        'prompt_count_reset_at' => now()->subMonth(),
    ]);

    $user->incrementPromptCount();

    $user->refresh();
    expect($user->monthly_prompt_count)->toBe(1);
});

test('subscription status includes all required fields', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 5,
    ]);

    $status = $user->getSubscriptionStatus();

    expect($status)->toHaveKeys([
        'tier',
        'isPro',
        'promptsUsed',
        'promptsRemaining',
        'promptLimit',
        'subscriptionEndsAt',
        'onGracePeriod',
    ]);

    expect($status['tier'])->toBe('free');
    expect($status['isPro'])->toBeFalse();
    expect($status['promptsUsed'])->toBe(5);
    expect($status['promptsRemaining'])->toBe(5);
    expect($status['promptLimit'])->toBe(10);
});

test('enforce prompt limit middleware allows requests when under limit', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 5,
    ]);

    $response = $this
        ->actingAs($user)
        ->getLocale('/prompt-builder');

    $response->assertOk();
});

test('subscription status is shared with all pages', function () {
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 3,
    ]);

    $response = $this
        ->actingAs($user)
        ->getLocale('/pricing');

    $response->assertInertia(fn ($page) => $page
        ->has('subscription')
        ->where('subscription.tier', 'free')
        ->where('subscription.promptsUsed', 3)
    );
});
