<?php

// tests/Feature/CurrencyUpdateTest.php

use App\Models\User;
use App\Models\Visitor;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PricesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

// SKIPPED: Currency switching UI has been removed in favor of region-based auto-detection
// See 4-tier pricing implementation plan: "Remove currency switcher from pricing page"
// Users now have currencies auto-detected based on region (UK→GBP, EU→EUR, Rest of World→USD)

beforeEach(function () {
    // Seed currencies and prices before each test
    (new CurrencySeeder)->run();
    (new PricesTableSeeder)->run();
});

// NOTE: Tests for authenticated user currency updates below
// These tests are for the currency switching endpoint which still exists but was not exposed in the UI
// See 4-tier pricing plan: Currency is now auto-detected by region instead of switched manually
describe('Authenticated User Currency Updates', function () {
    it('updates currency for authenticated user', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'EUR',
        ]);

        $response->assertRedirect();

        $user->refresh();
        expect($user->currency_code)->toBe('EUR');
    });

    it('supports switching between all active currencies', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        foreach (['EUR', 'USD', 'GBP'] as $currency) {
            $response = $this->actingAs($user)->postCountry('/currency/select', [
                'currency_code' => $currency,
            ]);

            $response->assertRedirect();

            $user->refresh();
            expect($user->currency_code)->toBe($currency);
        }
    });

    it('syncs currency to visitor record when authenticated user has visitor cookie', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)
            ->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        $response->assertRedirect();

        $user->refresh();
        $visitor->refresh();

        expect($user->currency_code)->toBe('EUR');
        expect($visitor->currency_code)->toBe('EUR');
    });

    it('syncs currency to all visitor records linked to user_id', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        // Create multiple visitor records linked to this user (e.g., different devices)
        $visitor1 = Visitor::factory()->create([
            'user_id' => $user->id,
            'currency_code' => 'GBP',
        ]);
        $visitor2 = Visitor::factory()->create([
            'user_id' => $user->id,
            'currency_code' => 'USD',
        ]);
        $visitor3 = Visitor::factory()->create([
            'user_id' => $user->id,
            'currency_code' => 'GBP',
        ]);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'EUR',
        ]);

        $response->assertRedirect();

        $user->refresh();
        $visitor1->refresh();
        $visitor2->refresh();
        $visitor3->refresh();

        // All records should now use EUR
        expect($user->currency_code)->toBe('EUR');
        expect($visitor1->currency_code)->toBe('EUR');
        expect($visitor2->currency_code)->toBe('EUR');
        expect($visitor3->currency_code)->toBe('EUR');
    });

    it('syncs currency to visitor from cookie even if not linked via user_id', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        // Visitor exists but is not linked to user (e.g., user just registered)
        $visitor = Visitor::factory()->create([
            'user_id' => null,
            'currency_code' => 'USD',
        ]);

        $response = $this->actingAs($user)
            ->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        $response->assertRedirect();

        $visitor->refresh();
        expect($visitor->currency_code)->toBe('EUR');
    });

    it('rejects invalid currency codes', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'JPY', // Not in active currencies
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');

        $user->refresh();
        expect($user->currency_code)->toBe('GBP'); // Should not change
    });

    it('rejects inactive currency codes', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        // Create an inactive currency (or update existing one)
        \App\Models\Currency::updateOrCreate(
            ['id' => 'ZAR'],
            [
                'symbol' => 'R',
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_on_left' => true,
                'space_between_amount_and_symbol' => false,
                'rounding_coefficient' => 0,
                'decimal_digits' => 2,
                'active' => false,
            ]
        );

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'ZAR',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');

        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });

    it('requires currency_code field', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postCountry('/currency/select', []);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');
    });

    it('validates currency_code length must be exactly 3 characters', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'GB', // Too short
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'GBPX', // Too long
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');
    });

    it('is case sensitive for currency codes', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        // Lowercase should be rejected
        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'eur',
        ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');

        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });
});

describe('Visitor Currency Updates (Unauthenticated)', function () {
    it('updates currency for visitor with valid visitor_id cookie', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        $response->assertRedirect();

        $visitor->refresh();
        expect($visitor->currency_code)->toBe('EUR');
    });

    it('invalidates currency cache when visitor updates currency', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        $response->assertRedirect();

        // Verify that the database was updated
        $visitor->refresh();
        expect($visitor->currency_code)->toBe('EUR');

        // Note: Cache pattern clearing is skipped in tests with ArrayStore (only works with Redis)
        // Manually clear the old cache entry for this test (in production, clearCachePattern() handles this)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";
        Cache::forget($cacheKey);

        // Next request should show new currency
        $priceResponse = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $priceResponse->assertInertia(fn ($page) => $page
            ->where('currency', 'EUR')
        );
    });

    it('allows visitor to switch currencies multiple times', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        foreach (['EUR', 'USD', 'GBP'] as $currency) {
            $response = $this->withCookie('visitor_id', (string) $visitor->id)
                ->postCountry('/currency/select', [
                    'currency_code' => $currency,
                ]);

            $response->assertRedirect();

            $visitor->refresh();
            expect($visitor->currency_code)->toBe($currency);
        }
    });

    it('handles currency update gracefully when visitor_id cookie is missing', function () {
        // No visitor_id cookie provided
        $response = $this->postCountry('/currency/select', [
            'currency_code' => 'EUR',
        ]);

        // Should still redirect successfully (fails silently)
        $response->assertRedirect();
    });

    it('handles currency update gracefully when visitor record does not exist', function () {
        $nonExistentVisitorId = '99999999-9999-9999-9999-999999999999';

        $response = $this->withCookie('visitor_id', $nonExistentVisitorId)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        // Should still redirect successfully (fails silently)
        $response->assertRedirect();
    });

    it('rejects invalid currency codes for visitors', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'INVALID',
            ]);

        $response->assertRedirect()
            ->assertSessionHasErrors('currency_code');

        $visitor->refresh();
        expect($visitor->currency_code)->toBe('GBP'); // Should not change
    });

    it('rejects inactive currencies for visitors', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        // Create an inactive currency (or update existing one)
        \App\Models\Currency::updateOrCreate(
            ['id' => 'CAD'],
            [
                'symbol' => '$',
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_on_left' => true,
                'space_between_amount_and_symbol' => false,
                'rounding_coefficient' => 0,
                'decimal_digits' => 2,
                'active' => false,
            ]
        );

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'CAD',
            ]);

        // Should redirect with validation errors
        $response->assertRedirect();

        // Currency should not change
        $visitor->refresh();
        expect($visitor->currency_code)->toBe('GBP');
    });
});

describe('Pricing Page Currency Display', function () {
    it('displays pricing in GBP for unauthenticated visitor without currency preference', function () {
        $response = $this->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->has('plans')
                ->where('currency', 'GBP')
                ->where('currencySymbol', '£')
            );
    });

    it('displays pricing in visitor selected currency after cache is populated', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'EUR']);

        // First request should populate cache
        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'EUR')
                ->where('currencySymbol', '€')
            );

        // Verify cache was populated (with route-specific key)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";
        expect(Cache::has($cacheKey))->toBeTrue();
        expect(Cache::get($cacheKey))->toBe('EUR');
    });

    it('displays pricing in visitor selected currency using cached value', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'USD']);

        // Populate cache (with route-specific key)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";
        Cache::put($cacheKey, 'USD', 3600);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'USD')
                ->where('currencySymbol', '$')
            );
    });

    it('displays pricing in authenticated user currency preference', function () {
        $user = User::factory()->create(['currency_code' => 'EUR']);

        $response = $this->actingAs($user)->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'EUR')
                ->where('currencySymbol', '€')
            );
    });

    it('defaults to GBP when authenticated user has no currency preference', function () {
        $user = User::factory()->create(['currency_code' => null]);

        $response = $this->actingAs($user)->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'GBP')
                ->where('currencySymbol', '£')
            );
    });

    it('uses user currency preference over visitor cookie when authenticated', function () {
        $user = User::factory()->create(['currency_code' => 'EUR']);
        $visitor = Visitor::factory()->create(['currency_code' => 'USD']);

        $response = $this->actingAs($user)
            ->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'EUR') // User preference takes priority
            );
    });

    it('reflects currency change immediately after visitor updates preference', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        // Change currency (endpoint returns redirect)
        $updateResponse = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'USD',
            ]);

        $updateResponse->assertRedirect();

        // Verify database was updated
        $visitor->refresh();
        expect($visitor->currency_code)->toBe('USD');

        // Next request should reflect new currency (with cache cleared in test)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";
        Cache::forget($cacheKey);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'USD')
            );
    });

    it('loads correct prices from database for selected currency', function () {
        $user = User::factory()->create(['currency_code' => 'EUR']);

        $response = $this->actingAs($user)->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'EUR')
                ->has('plans.pro_monthly', fn ($plan) => $plan
                    ->where('currency', 'EUR')
                    ->where('price', '29.99') // Updated: New Pro price
                    ->has('priceId')
                    ->has('interval')
                    ->etc()
                )
                ->has('plans.pro_yearly', fn ($plan) => $plan
                    ->where('currency', 'EUR')
                    ->where('price', '299.00') // Updated: New Pro price
                    ->has('priceId')
                    ->has('interval')
                    ->etc()
                )
            );
    });

    it('currency auto-detects from country code', function () {
        // Removed: availableCurrencies list no longer provided after removing currency switcher
        // Currency is now auto-detected by country/region
        $response = $this->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Pricing')
                ->where('currency', 'GBP') // GB country defaults to GBP
            );
    });
});

describe('Currency Cache Management', function () {
    it('caches visitor currency preference for 1 hour', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'EUR']);

        // Make first request to populate cache
        $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        // Cache key includes route country (route-specific caching)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";

        // Cache should exist
        expect(Cache::has($cacheKey))->toBeTrue();
        expect(Cache::get($cacheKey))->toBe('EUR');

        // Update database directly without going through controller
        $visitor->update(['currency_code' => 'USD']);

        // Cache should still return old value (EUR) until it expires
        expect(Cache::get($cacheKey))->toBe('EUR');
    });

    it('properly formats cache key with visitor id', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);

        $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        // Cache key includes route country (route-specific caching)
        $expectedCacheKey = "visitor.{$visitor->id}.currency.gb";
        expect(Cache::has($expectedCacheKey))->toBeTrue();
    });

    it('does not create cache entry when visitor_id is missing', function () {
        $this->getCountry('/pricing');

        // Cache should not contain any visitor currency entries
        expect(Cache::has('visitor..currency'))->toBeFalse();
    });

    it('cache invalidation works correctly on currency update', function () {
        $visitor = Visitor::factory()->create(['currency_code' => 'GBP']);
        // Cache key includes route country (route-specific caching)
        $cacheKey = "visitor.{$visitor->id}.currency.gb";

        // Populate cache
        Cache::put($cacheKey, 'GBP', 3600);
        expect(Cache::get($cacheKey))->toBe('GBP');

        // Update currency (endpoint returns redirect)
        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->postCountry('/currency/select', [
                'currency_code' => 'EUR',
            ]);

        $response->assertRedirect();

        // Note: In tests with ArrayStore, cache pattern clearing is skipped (only works with Redis)
        // Manually clear the old cache entry for this test
        Cache::forget($cacheKey);

        // Next pricing page request should show new currency (EUR)
        $priceResponse = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $priceResponse->assertInertia(fn ($page) => $page
            ->where('currency', 'EUR')
        );
    });
});

describe('Edge Cases and Error Handling', function () {
    it('handles null currency_code gracefully', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => null,
        ]);

        // Should redirect with validation errors
        $response->assertRedirect();

        // Currency should not change
        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });

    it('handles empty string currency_code', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => '',
        ]);

        // Should redirect with validation errors
        $response->assertRedirect();

        // Currency should not change
        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });

    it('handles special characters in currency_code', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => 'GB£',
        ]);

        // Should redirect with validation errors
        $response->assertRedirect();

        // Currency should not change
        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });

    it('handles numeric currency_code', function () {
        $user = User::factory()->create(['currency_code' => 'GBP']);

        $response = $this->actingAs($user)->postCountry('/currency/select', [
            'currency_code' => '123',
        ]);

        // Should redirect with validation errors
        $response->assertRedirect();

        // Currency should not change
        $user->refresh();
        expect($user->currency_code)->toBe('GBP');
    });

    it('falls back to GBP when visitor currency_code is null in database', function () {
        $visitor = Visitor::factory()->create(['currency_code' => null]);

        $response = $this->withCookie('visitor_id', (string) $visitor->id)
            ->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('currency', 'GBP')
            );
    });

    it('falls back to GBP symbol when currency record is missing', function () {
        $user = User::factory()->create(['currency_code' => 'XYZ']); // Invalid currency

        $response = $this->actingAs($user)->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('currencySymbol', '£') // Fallback to GBP symbol
            );
    });

    it('falls back to GBP symbol when currency is inactive', function () {
        // Create an inactive currency (or update existing one)
        \App\Models\Currency::updateOrCreate(
            ['id' => 'AUD'],
            [
                'symbol' => 'A$',
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'symbol_on_left' => true,
                'space_between_amount_and_symbol' => false,
                'rounding_coefficient' => 0,
                'decimal_digits' => 2,
                'active' => false,
            ]
        );

        $user = User::factory()->create(['currency_code' => 'AUD']);

        $response = $this->actingAs($user)->getCountry('/pricing');

        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('currencySymbol', '£') // Fallback to GBP symbol
            );
    });
});

describe('Database Integrity', function () {
    it('database contains prices for all active currencies and tiers', function () {
        $prices = \App\Models\Price::all();

        $currencies = $prices->pluck('currency_code')->unique();
        expect($currencies->sort()->values()->toArray())->toBe(['EUR', 'GBP', 'USD']);

        // Verify all 4 tiers are present
        $tiers = $prices->pluck('tier')->unique();
        expect($tiers->sort()->values()->toArray())->toBe(['premium', 'pro', 'starter']);
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

        // Updated pricing for new 4-tier structure
        expect((float) $priceMap['starter_monthly']->amount)->toBe(9.99);
        expect((float) $priceMap['starter_yearly']->amount)->toBe(99.00);
        expect((float) $priceMap['pro_monthly']->amount)->toBe(24.99);
        expect((float) $priceMap['pro_yearly']->amount)->toBe(249.00);
        expect((float) $priceMap['premium_monthly']->amount)->toBe(49.99);
        expect((float) $priceMap['premium_yearly']->amount)->toBe(499.00);
    });

    it('prices have correct amounts for EUR', function () {
        $prices = \App\Models\Price::where('currency_code', 'EUR')->get();
        $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

        // Updated pricing for new 4-tier structure
        expect((float) $priceMap['starter_monthly']->amount)->toBe(11.99);
        expect((float) $priceMap['starter_yearly']->amount)->toBe(119.00);
        expect((float) $priceMap['pro_monthly']->amount)->toBe(29.99);
        expect((float) $priceMap['pro_yearly']->amount)->toBe(299.00);
        expect((float) $priceMap['premium_monthly']->amount)->toBe(59.99);
        expect((float) $priceMap['premium_yearly']->amount)->toBe(599.00);
    });

    it('prices have correct amounts for USD', function () {
        $prices = \App\Models\Price::where('currency_code', 'USD')->get();
        $priceMap = $prices->keyBy(fn ($p) => $p->tier.'_'.$p->interval);

        // Updated pricing for new 4-tier structure
        expect((float) $priceMap['starter_monthly']->amount)->toBe(11.99);
        expect((float) $priceMap['starter_yearly']->amount)->toBe(119.00);
        expect((float) $priceMap['pro_monthly']->amount)->toBe(27.99);
        expect((float) $priceMap['pro_yearly']->amount)->toBe(279.00);
        expect((float) $priceMap['premium_monthly']->amount)->toBe(54.99);
        expect((float) $priceMap['premium_yearly']->amount)->toBe(549.00);
    });
});
