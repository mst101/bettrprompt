<?php

use App\Models\User;

describe('Subscription Feature Tests', function () {
    describe('Subscription Tiers', function () {
        it('free user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);
            expect($user->isFree())->toBeTrue();
            expect($user->isPaid())->toBeFalse();
            expect($user->isStarter())->toBeFalse();
            expect($user->isPro())->toBeFalse();
            expect($user->isPremium())->toBeFalse();
        });

        it('starter user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'starter']);
            expect($user->isPaid())->toBeTrue();
            expect($user->isStarter())->toBeTrue();
            expect($user->isPro())->toBeFalse();
            expect($user->isPremium())->toBeFalse();
            expect($user->isFree())->toBeFalse();
        });

        it('pro user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);
            expect($user->isPaid())->toBeTrue();
            expect($user->isStarter())->toBeFalse();
            expect($user->isPro())->toBeTrue();
            expect($user->isPremium())->toBeFalse();
            expect($user->isFree())->toBeFalse();
        });

        it('premium user has correct tier status', function () {
            $user = User::factory()->create(['subscription_tier' => 'premium']);
            expect($user->isPaid())->toBeTrue();
            expect($user->isStarter())->toBeFalse();
            expect($user->isPro())->toBeFalse();
            expect($user->isPremium())->toBeTrue();
            expect($user->isFree())->toBeFalse();
        });
    });

    describe('Free Tier Limits', function () {
        it('free user can create prompts within limit of 10', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 2,
            ]);

            expect($user->getPromptLimit())->toBe(10);
            expect($user->getPromptsRemaining())->toBe(8);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('free user at limit cannot create more prompts', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'free',
                'monthly_prompt_count' => 10,
            ]);

            expect($user->getPromptsRemaining())->toBe(0);
            expect($user->canCreatePrompt())->toBeFalse();
        });

        it('starter user has 25 prompts per month', function () {
            $user = User::factory()->create(['subscription_tier' => 'starter']);
            expect($user->getPromptLimit())->toBe(25);
            expect($user->getPromptsRemaining())->toBe(25);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('starter user at limit cannot create more prompts', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'starter',
                'monthly_prompt_count' => 25,
            ]);

            expect($user->getPromptsRemaining())->toBe(0);
            expect($user->canCreatePrompt())->toBeFalse();
        });

        it('pro user has 90 prompts per month', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);
            expect($user->getPromptLimit())->toBe(90);
            expect($user->getPromptsRemaining())->toBe(90);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('pro user at limit cannot create more prompts', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'pro',
                'monthly_prompt_count' => 90,
            ]);

            expect($user->getPromptsRemaining())->toBe(0);
            expect($user->canCreatePrompt())->toBeFalse();
        });

        it('premium user has unlimited prompts', function () {
            $user = User::factory()->create(['subscription_tier' => 'premium']);
            expect($user->getPromptLimit())->toBe(PHP_INT_MAX);
            expect($user->getPromptsRemaining())->toBe(PHP_INT_MAX);
            expect($user->canCreatePrompt())->toBeTrue();
        });

        it('premium user can create prompts even with high count', function () {
            $user = User::factory()->create([
                'subscription_tier' => 'premium',
                'monthly_prompt_count' => 999,
            ]);

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
            expect($status['isStarter'])->toBeFalse();
            expect($status['isPro'])->toBeFalse();
            expect($status['isPremium'])->toBeFalse();
            expect($status['promptsRemaining'])->toBe(7);
            expect($status['promptLimit'])->toBe(10);
        });

        it('returns correct subscription status for starter user', function () {
            $user = User::factory()->create(['subscription_tier' => 'starter']);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('starter');
            expect($status['isPaid'])->toBeTrue();
            expect($status['isStarter'])->toBeTrue();
            expect($status['promptLimit'])->toBe(25);
        });

        it('returns correct subscription status for pro user', function () {
            $user = User::factory()->create(['subscription_tier' => 'pro']);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('pro');
            expect($status['isPaid'])->toBeTrue();
            expect($status['isPro'])->toBeTrue();
            expect($status['promptLimit'])->toBe(90);
        });

        it('returns correct subscription status for premium user', function () {
            $user = User::factory()->create(['subscription_tier' => 'premium']);

            $status = $user->getSubscriptionStatus();

            expect($status['tier'])->toBe('premium');
            expect($status['isPaid'])->toBeTrue();
            expect($status['isPremium'])->toBeTrue();
            expect($status['promptLimit'])->toBeNull(); // Unlimited
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

        it('works for all paid tiers (starter, pro, premium)', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $starterResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'starter',
                'interval' => 'monthly',
            ]);

            $proResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'pro',
                'interval' => 'monthly',
            ]);

            $premiumResponse = $this->actingAs($user)->postJson(route('subscription.checkout'), [
                'tier' => 'premium',
                'interval' => 'monthly',
            ]);

            $starterResponse->assertSuccessful()->assertJsonStructure(['url']);
            $proResponse->assertSuccessful()->assertJsonStructure(['url']);
            $premiumResponse->assertSuccessful()->assertJsonStructure(['url']);
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
        it('sets subscription tier to starter after checkout', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success', ['tier' => 'starter']));

            $user->refresh();
            expect($user->subscription_tier)->toBe('starter');
        });

        it('sets subscription tier to pro after checkout', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success', ['tier' => 'pro']));

            $user->refresh();
            expect($user->subscription_tier)->toBe('pro');
        });

        it('sets subscription tier to premium after checkout', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success', ['tier' => 'premium']));

            $user->refresh();
            expect($user->subscription_tier)->toBe('premium');
        });

        it('defaults to pro tier if not specified', function () {
            $user = User::factory()->create(['subscription_tier' => 'free']);

            $this->actingAs($user)->get(route('subscription.success'));

            $user->refresh();
            expect($user->subscription_tier)->toBe('pro');
        });
    });

    describe('Stripe Tier Determination', function () {
        it('determines starter tier from price id', function () {
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'starter' => ['monthly' => 'price_starter_monthly'],
                        'pro' => ['monthly' => 'price_pro_monthly'],
                        'premium' => ['monthly' => 'price_premium_monthly'],
                    ],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'price_starter_monthly');
            expect($tier)->toBe('starter');
        });

        it('determines pro tier from price id', function () {
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'starter' => ['monthly' => 'price_starter_monthly'],
                        'pro' => ['monthly' => 'price_pro_monthly'],
                        'premium' => ['monthly' => 'price_premium_monthly'],
                    ],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'price_pro_monthly');
            expect($tier)->toBe('pro');
        });

        it('determines premium tier from price id', function () {
            config([
                'stripe.prices' => [
                    'GBP' => [
                        'starter' => ['monthly' => 'price_starter_monthly'],
                        'pro' => ['monthly' => 'price_pro_monthly'],
                        'premium' => ['monthly' => 'price_premium_monthly'],
                    ],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'price_premium_monthly');
            expect($tier)->toBe('premium');
        });

        it('defaults to starter if price not found', function () {
            config([
                'stripe.prices' => [
                    'GBP' => ['pro' => ['monthly' => 'price_pro_monthly']],
                ],
            ]);

            $controller = new \App\Http\Controllers\StripeWebhookController;
            $method = new \ReflectionMethod($controller, 'determineTierFromPriceId');
            $method->setAccessible(true);

            $tier = $method->invoke($controller, 'unknown_price');
            expect($tier)->toBe('starter');
        });
    });
});
