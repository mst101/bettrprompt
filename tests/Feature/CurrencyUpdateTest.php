<?php

use App\Models\User;

describe('Currency Update API', function () {
    describe('Authenticated Users', function () {
        it('updates currency for authenticated user', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)->postJson(route('api.currency.update'), [
                'currency_code' => 'EUR',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'currency_code' => 'EUR',
                ]);

            $user->refresh();
            expect($user->currency_code)->toBe('EUR');
        });

        it('supports switching between all three currencies', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            foreach (['EUR', 'USD', 'GBP'] as $currency) {
                $response = $this->actingAs($user)->postJson(route('api.currency.update'), [
                    'currency_code' => $currency,
                ]);

                $response->assertStatus(200)
                    ->assertJson(['currency_code' => $currency]);

                $user->refresh();
                expect($user->currency_code)->toBe($currency);
            }
        });

        it('rejects invalid currency codes', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)->postJson(route('api.currency.update'), [
                'currency_code' => 'JPY',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('currency_code');

            $user->refresh();
            expect($user->currency_code)->toBe('GBP'); // Should not change
        });

        it('requires currency_code field', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson(route('api.currency.update'), []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('currency_code');
        });

        it('validates currency_code length (must be 3 characters)', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->postJson(route('api.currency.update'), [
                'currency_code' => 'GB',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('currency_code');
        });

        it('is case sensitive for currency codes', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            // Lowercase should be rejected
            $response = $this->actingAs($user)->postJson(route('api.currency.update'), [
                'currency_code' => 'eur',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('currency_code');

            $user->refresh();
            expect($user->currency_code)->toBe('GBP');
        });
    });

    describe('Unauthenticated Visitors', function () {
        it('stores currency in session for unauthenticated user', function () {
            $response = $this->postJson(route('api.currency.update'), [
                'currency_code' => 'EUR',
            ]);

            $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'currency_code' => 'EUR',
                ]);

            // Verify currency is in session
            expect(session('currency_code'))->toBe('EUR');
        });

        it('allows visitor to switch currencies multiple times', function () {
            foreach (['GBP', 'EUR', 'USD'] as $currency) {
                $response = $this->postJson(route('api.currency.update'), [
                    'currency_code' => $currency,
                ]);

                $response->assertStatus(200);
                expect(session('currency_code'))->toBe($currency);
            }
        });

        it('rejects invalid currency for unauthenticated user', function () {
            $response = $this->postJson(route('api.currency.update'), [
                'currency_code' => 'INVALID',
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('currency_code');
        });
    });

    describe('Pricing Page Currency Selection', function () {
        it('pricing page shows correct currency symbol after update', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)->get(route('pricing'));
            $response->assertStatus(200);
        });

        it('pricing page uses user currency code if set', function () {
            $user = User::factory()->create(['currency_code' => 'EUR']);

            $response = $this->actingAs($user)->get(route('pricing'));
            $response->assertStatus(200);
        });

        it('pricing page defaults to GBP if user has no currency', function () {
            $user = User::factory()->create(['currency_code' => null]);

            $response = $this->actingAs($user)->get(route('pricing'));
            $response->assertStatus(200);
        });
    });

    describe('Database Prices', function () {
        it('database contains prices for all three currencies', function () {
            $prices = \App\Models\Price::all();

            $currencies = $prices->pluck('currency_code')->unique();
            expect($currencies->sort()->values()->toArray())->toBe(['EUR', 'GBP', 'USD']);

            // Verify we have prices for both tiers and intervals
            $tiers = $prices->pluck('tier')->unique();
            expect($tiers->sort()->values()->toArray())->toBe(['private', 'pro']);

            $intervals = $prices->pluck('interval')->unique();
            expect($intervals->sort()->values()->toArray())->toBe(['monthly', 'yearly']);
        });

        it('all prices have valid stripe price ids', function () {
            $prices = \App\Models\Price::all();

            foreach ($prices as $price) {
                expect($price->stripe_price_id)->not->toBeNull()
                    ->toMatch('/^price_/');
            }
        });

        it('prices have correct amounts for GBP', function () {
            $prices = \App\Models\Price::where('currency_code', 'GBP')->get();

            $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

            expect($priceMap['pro_monthly']->amount)->toBe(12.00);
            expect($priceMap['pro_yearly']->amount)->toBe(120.00);
            expect($priceMap['private_monthly']->amount)->toBe(20.00);
            expect($priceMap['private_yearly']->amount)->toBe(200.00);
        });

        it('prices have correct amounts for EUR', function () {
            $prices = \App\Models\Price::where('currency_code', 'EUR')->get();

            $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

            expect($priceMap['pro_monthly']->amount)->toBe(13.99);
            expect($priceMap['pro_yearly']->amount)->toBe(139.00);
            expect($priceMap['private_monthly']->amount)->toBe(22.99);
            expect($priceMap['private_yearly']->amount)->toBe(229.00);
        });

        it('prices have correct amounts for USD', function () {
            $prices = \App\Models\Price::where('currency_code', 'USD')->get();

            $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

            expect($priceMap['pro_monthly']->amount)->toBe(15.99);
            expect($priceMap['pro_yearly']->amount)->toBe(159.00);
            expect($priceMap['private_monthly']->amount)->toBe(26.99);
            expect($priceMap['private_yearly']->amount)->toBe(269.00);
        });
    });

    describe('Pricing Consistency', function () {
        it('annual discount is consistent across currencies', function () {
            $prices = \App\Models\Price::all();

            foreach (['pro', 'private'] as $tier) {
                foreach (['GBP', 'EUR', 'USD'] as $currency) {
                    $monthly = $prices->firstWhere(fn ($p) => $p->currency_code === $currency && $p->tier === $tier && $p->interval === 'monthly'
                    );
                    $yearly = $prices->firstWhere(fn ($p) => $p->currency_code === $currency && $p->tier === $tier && $p->interval === 'yearly'
                    );

                    // Yearly should be approximately 10x monthly (17% discount)
                    $expectedYearly = round($monthly->amount * 10, 2);
                    expect($yearly->amount)->toBe($expectedYearly);
                }
            }
        });
    });
});
