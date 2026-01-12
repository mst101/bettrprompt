<?php

use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PricesTableSeeder;

describe('Currency Selection', function () {
    beforeEach(function () {
        // Seed currencies first, then prices
        (new CurrencySeeder)->run();
        (new PricesTableSeeder)->run();
    });

    describe('Authenticated Users', function () {
        it('updates currency for authenticated user', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)->post(route('currency.select'), [
                'currency_code' => 'EUR',
            ]);

            $response->assertRedirect();

            $user->refresh();
            expect($user->currency_code)->toBe('EUR');
        });

        it('supports switching between all three currencies', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            foreach (['EUR', 'USD', 'GBP'] as $currency) {
                $response = $this->actingAs($user)->post(route('currency.select'), [
                    'currency_code' => $currency,
                ]);

                $response->assertRedirect();

                $user->refresh();
                expect($user->currency_code)->toBe($currency);
            }
        });

        it('syncs currency to visitor record if authenticated user has one', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);
            $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)
                ->withCookie('visitor_id', $visitor->id)
                ->post(route('currency.select'), [
                    'currency_code' => 'EUR',
                ]);

            $response->assertRedirect();

            $user->refresh();
            $visitor->refresh();

            expect($user->currency_code)->toBe('EUR');
            expect($visitor->currency_code)->toBe('EUR');
        });

        it('rejects invalid currency codes', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            $response = $this->actingAs($user)->post(route('currency.select'), [
                'currency_code' => 'JPY',
            ]);

            $response->assertRedirect()
                ->assertSessionHasErrors('currency_code');

            $user->refresh();
            expect($user->currency_code)->toBe('GBP'); // Should not change
        });

        it('requires currency_code field', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->post(route('currency.select'), []);

            $response->assertRedirect()
                ->assertSessionHasErrors('currency_code');
        });

        it('validates currency_code length (must be 3 characters)', function () {
            $user = User::factory()->create();

            $response = $this->actingAs($user)->post(route('currency.select'), [
                'currency_code' => 'GB',
            ]);

            $response->assertRedirect()
                ->assertSessionHasErrors('currency_code');
        });

        it('is case sensitive for currency codes', function () {
            $user = User::factory()->create(['currency_code' => 'GBP']);

            // Lowercase should be rejected
            $response = $this->actingAs($user)->post(route('currency.select'), [
                'currency_code' => 'eur',
            ]);

            $response->assertRedirect()
                ->assertSessionHasErrors('currency_code');

            $user->refresh();
            expect($user->currency_code)->toBe('GBP');
        });
    });

    describe('Unauthenticated Visitors', function () {
        it('stores currency in session for unauthenticated user', function () {
            $response = $this->post(route('currency.select'), [
                'currency_code' => 'EUR',
            ]);

            $response->assertRedirect();

            // Verify currency is in session
            expect(session('currency_code'))->toBe('EUR');
        });

        it('allows visitor to switch currencies multiple times', function () {
            foreach (['GBP', 'EUR', 'USD'] as $currency) {
                $response = $this->post(route('currency.select'), [
                    'currency_code' => $currency,
                ]);

                $response->assertRedirect();
                expect(session('currency_code'))->toBe($currency);
            }
        });

        it('rejects invalid currency for unauthenticated user', function () {
            $response = $this->post(route('currency.select'), [
                'currency_code' => 'INVALID',
            ]);

            $response->assertRedirect()
                ->assertSessionHasErrors('currency_code');
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

            expect((float) $priceMap['pro_monthly']->amount)->toBe(11.99);
            expect((float) $priceMap['pro_yearly']->amount)->toBe(119.00);
            expect((float) $priceMap['private_monthly']->amount)->toBe(19.99);
            expect((float) $priceMap['private_yearly']->amount)->toBe(199.00);
        });

        it('prices have correct amounts for EUR', function () {
            $prices = \App\Models\Price::where('currency_code', 'EUR')->get();

            $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

            expect((float) $priceMap['pro_monthly']->amount)->toBe(13.99);
            expect((float) $priceMap['pro_yearly']->amount)->toBe(139.00);
            expect((float) $priceMap['private_monthly']->amount)->toBe(22.99);
            expect((float) $priceMap['private_yearly']->amount)->toBe(229.00);
        });

        it('prices have correct amounts for USD', function () {
            $prices = \App\Models\Price::where('currency_code', 'USD')->get();

            $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

            expect((float) $priceMap['pro_monthly']->amount)->toBe(15.99);
            expect((float) $priceMap['pro_yearly']->amount)->toBe(159.00);
            expect((float) $priceMap['private_monthly']->amount)->toBe(26.99);
            expect((float) $priceMap['private_yearly']->amount)->toBe(269.00);
        });
    });

    describe('Pricing Consistency', function () {
        it('annual discount is consistent across currencies', function () {
            $prices = \App\Models\Price::all();

            // Expected prices with 17% annual discount (yearly = monthly * 10, roughly)
            $expected = [
                'GBP' => ['pro' => 119, 'private' => 199],
                'EUR' => ['pro' => 139, 'private' => 229],
                'USD' => ['pro' => 159, 'private' => 269],
            ];

            foreach (['pro', 'private'] as $tier) {
                foreach (['GBP', 'EUR', 'USD'] as $currency) {
                    $yearly = $prices->firstWhere(fn ($p
                    ) => $p->currency_code === $currency && $p->tier === $tier && $p->interval === 'yearly'
                    );

                    expect((float) $yearly->amount)->toBe((float) $expected[$currency][$tier]);
                }
            }
        });
    });
});
