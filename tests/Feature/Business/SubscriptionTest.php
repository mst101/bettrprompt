<?php

use App\Models\User;

describe('Subscription Feature Tests', function () {
    describe('Subscription Tiers', function () {
        it('free user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);
            expect($user->isFree())->toBeTrue();
            expect($user->isPaid())->toBeFalse();
            expect($user->isPro())->toBeFalse();
            expect($user->isPrivate())->toBeFalse();
        });

        it('pro user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);
            expect($user->isPaid())->toBeTrue();
            expect($user->isPro())->toBeTrue();
            expect($user->isPrivate())->toBeFalse();
            expect($user->isFree())->toBeFalse();
        });

        it('private user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'private']);
            expect($user->isPaid())->toBeTrue();
            expect($user->isPro())->toBeFalse();
            expect($user->isPrivate())->toBeTrue();
            expect($user->isFree())->toBeFalse();
        });
    });

    describe('Free Tier Limits', function () {
        it('free user can create prompts within limit', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 2,
            ]);

            expect($user->getPromptsRemaining())->toBe(3);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('free user at limit cannot create more prompts', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 5,
            ]);

            expect($user->getPromptsRemaining())->toBe(0);
            expect($user->canCreatePrompt())->toBeFalse();
        });

        it('pro user has unlimited prompts', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);
            expect($user->getPromptsRemaining())->toBe(PHP_INT_MAX);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('private user has unlimited prompts', function () {
            $user = User::factory()->create(['subscription_tier' => 'private']);
            expect($user->getPromptsRemaining())->toBe(PHP_INT_MAX);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('increments monthly prompt count', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 0,
            ]);

            $user->incrementPromptCount();
            $user->refresh();

            expect($user->monthly_prompt_count)->toBe(1);
        });
    });

    describe('Prompt Reset', function () {
        it('getDaysUntilPromptReset returns correct number of days', function () {
            $user = User::factory()->create([
                'monthly_prompt_count' => 1,
                'prompt_count_reset_at' => now()->subDays(15),
            ]);

            $days = $user->getDaysUntilPromptReset();
            // Should reset in approximately 15 days (30 day month - 15 days elapsed)
            expect($days)->toBeGreaterThanOrEqual(14);
            expect($days)->toBeLessThanOrEqual(16);
        });

        it('getDaysUntilPromptReset returns 0 when no reset date set', function () {
            $user = User::factory()->create([
                'monthly_prompt_count' => 0,
                'prompt_count_reset_at' => null,
            ]);

            expect($user->getDaysUntilPromptReset())->toBe(0);
        });

        it('getSubscriptionStatus includes daysUntilReset', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 1,
                'prompt_count_reset_at' => now(),
            ]);

            $status = $user->getSubscriptionStatus();
            expect($status)->toHaveKey('daysUntilReset');
            expect($status['daysUntilReset'])->toBeInt();
        });
    });

    describe('Subscription Status', function () {
        it('returns correct subscription status for free user', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 3,
            ]);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('free');
            expect($status['isPaid'])->toBeFalse();
            expect($status['isFree'])->toBeTrue();
            expect($status['promptsRemaining'])->toBe(2);
        });

        it('returns correct subscription status for pro user', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('pro');
            expect($status['isPaid'])->toBeTrue();
            expect($status['isPro'])->toBeTrue();
        });

        it('returns correct subscription status for private user', function () {
            $user = User::factory()->create(['subscription_tier' => 'private']);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('private');
            expect($status['isPaid'])->toBeTrue();
            expect($status['isPrivate'])->toBeTrue();
        });
    });

    describe('Price ID Lookup', function () {
        it('returns correct price ID for user currency', function () {
            $user = User::factory()->create(['currency_code' => 'EUR']);
            config([
                'stripe.prices' => [
                    'EUR' => [
                        'pro' => [
                            'monthly' => 'price_eur_pro_monthly',
                            'yearly' => 'price_eur_pro_yearly',
                        ],
                    ],
                ],
            ]);

            $priceId = $user->getCheckoutPriceId('pro', 'monthly');
            expect($priceId)->toBe('price_eur_pro_monthly');
        });

        it('defaults to GBP if user has no currency', function () {
            $user = User::factory()->create(['currency_code' => null]);
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'pro' => ['monthly' => 'price_gbp_pro_monthly'],
                    ],
                ],
            ]);

            $priceId = $user->getCheckoutPriceId('pro', 'monthly');
            expect($priceId)->toBe('price_gbp_pro_monthly');
        });

        it('returns different prices for different intervals', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'pro' => [
                            'monthly' => 'price_gbp_pro_monthly',
                            'yearly' => 'price_gbp_pro_yearly',
                        ],
                    ],
                ],
            ]);

            $monthlyId = $user->getCheckoutPriceId('pro', 'monthly');
            $yearlyId = $user->getCheckoutPriceId('pro', 'yearly');

            expect($monthlyId)->toBe('price_gbp_pro_monthly');
            expect($yearlyId)->toBe('price_gbp_pro_yearly');
        });
    });

    describe('Pricing Page', function () {
        it('displays pricing page to unauthenticated user', function () {
            $response = $this->get(route('pricing'));
            $response->assertStatus(200);
        });

        it('renders pricing page for authenticated user', function () {
            $user = User::factory()->create();
            $response = $this->actingAs($user)->get(route('pricing'));
            $response->assertStatus(200);
        });
    });

    describe('Checkout', function () {
        it('requires authentication', function () {
            $response = $this->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ]);

            $response->assertUnauthorized();
        });

        it('returns checkout URL for authenticated user', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ])->assertSuccessful()
                ->assertJsonStructure(['url']);
        });

        it('validates required parameters', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->postJson(route('subscription.checkout'), [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['tier', 'interval']);
        });

        it('validates tier parameter', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'invalid',
                'interval' => 'monthly',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['tier']);
        });

        it('validates interval parameter', function () {
            $user = User::factory()->create();

            $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'invalid',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['interval']);
        });

        it('returns error if price not found', function () {
            $user = User::factory()->create(['currency_code' => 'ZZZ']);

            $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ])->assertUnprocessable();
        });

        it('works for both pro and private tiers', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $proResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ]);

            $privateResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'private',
                'interval' => 'monthly',
            ]);

            $proResponse->assertSuccessful()->assertJsonStructure(['url']);
            $privateResponse->assertSuccessful()->assertJsonStructure(['url']);
        });

        it('works for both monthly and yearly intervals', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $monthlyResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ]);

            $yearlyResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'yearly',
            ]);

            $monthlyResponse->assertSuccessful()->assertJsonStructure(['url']);
            $yearlyResponse->assertSuccessful()->assertJsonStructure(['url']);
        });
    });

    describe('Subscription Success', function () {
        it('sets subscription tier to pro after checkout', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success', ['tier' => 'pro']));

            $user->refresh();
            expect($user->subscription_tier)->toBe('pro');
        });

        it('sets subscription tier to private after checkout', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success', ['tier' => 'private']));

            $user->refresh();
            expect($user->subscription_tier)->toBe('private');
        });

        it('defaults to pro tier if not specified', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success'));

            $user->refresh();
            expect($user->subscription_tier)->toBe('pro');
        });
    });

    describe('Stripe Tier Determination', function () {
        it('determines pro tier from price id', function () {
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'pro' => ['monthly' => 'price_pro_monthly'],
                        'private' => ['monthly' => 'price_private_monthly'],
                    ],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'price_pro_monthly');
            expect($tier)->toBe('pro');
        });

        it('determines private tier from price id', function () {
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'pro' => ['monthly' => 'price_pro_monthly'],
                        'private' => ['monthly' => 'price_private_monthly'],
                    ],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'price_private_monthly');
            expect($tier)->toBe('private');
        });

        it('defaults to pro if price not found', function () {
            config([
                'stripe.prices' => [
                    'GBP' => ['pro' => ['monthly' => 'price_pro_monthly']],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'unknown_price');
            expect($tier)->toBe('pro');
        });
    });
});
