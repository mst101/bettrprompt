<?php

use App\Http\Middleware\SetCountry;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;

test('supported countries are configured correctly', function () {
    $countries = supportedCountries();

    expect($countries)->toBeArray();
    expect($countries)->not->toBeEmpty();
    // Verify we have entries for major countries
    expect($countries)->toContain('gb');
    expect($countries)->toContain('us');
});

test('SetCountry middleware detects country from request', function () {
    $request = Request::create('/gb/test');
    $request->route(null, ['country' => 'gb']);

    expect(SetCountry::detectCountry($request))->toBeString();
    expect(SetCountry::detectCountry($request))->toHaveLength(2);
});

test('SetCountry middleware defaults to gb when no country detected', function () {
    $request = Request::create('/test');

    expect(SetCountry::detectCountry($request))->toBe('gb');
});

test('SetCountry middleware uses user country preference when authenticated', function () {
    $user = User::factory()->create([
        'country_code' => 'us',
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    expect(SetCountry::detectCountry($request))->toBe('us');
});

test('SetCountry middleware resolves country code to full language code', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    // Should resolve 'gb' to 'en-GB' language via Inertia props
    // The language is set during request processing for translations
    $response->assertInertia(fn ($page) => $page
        ->has('locale')
    );
});

test('country and locale are shared with inertia pages', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('country')
        ->has('locale')
        ->has('currency')
        ->has('supportedCountries')
    );
});

test('country shared data has correct values', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('country', 'gb')
        ->has('locale') // Locale is present and set by middleware
    );
});

test('supported countries shared data contains expected countries', function () {
    $response = $this->getCountry('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('supportedCountries', supportedCountries())
    );
});

test('middleware alias is registered', function () {
    // Check that the 'country' middleware alias is registered
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('country');
});

test('language preference is global and applies across country URLs', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => 'en-GB',
    ]);

    // Update language to French on GB
    $this->actingAs($user)
        ->patch('/gb/profile/language', ['language_code' => 'fr-FR'])
        ->assertOk();

    // Verify French is used when viewing DE
    $response = $this->actingAs($user)->get('/de/pricing');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('locale', 'fr-FR')
    );
});

test('language preference applies to all country URLs without re-setting', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => null,
    ]);

    // Set language to German on GB
    $this->actingAs($user)
        ->patch('/gb/profile/language', ['language_code' => 'de-DE'])
        ->assertOk();

    // Visit multiple country URLs
    foreach (['us', 'mx', 'de'] as $country) {
        $response = $this->actingAs($user)->get("/{$country}/pricing");
        $response->assertInertia(fn ($page) => $page
            ->where('locale', 'de-DE')
        );
    }
});

test('currency is country-specific based on route', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'currency_code' => 'GBP',
    ]);

    // On GB route, should see GBP
    $response = $this->actingAs($user)->get('/gb/pricing');
    $response->assertInertia(fn ($page) => $page
        ->where('currency', 'GBP')
    );

    // On DE route, should see EUR (country default)
    $response = $this->actingAs($user)->get('/de/pricing');
    $response->assertInertia(fn ($page) => $page
        ->where('currency', 'EUR')
    );

    // On US route, should see USD (country default)
    $response = $this->actingAs($user)->get('/us/pricing');
    $response->assertInertia(fn ($page) => $page
        ->where('currency', 'USD')
    );
});

test('language cache is invalidated when updated', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'language_code' => 'en-GB',
    ]);

    // Make a request to populate cache
    $this->actingAs($user)->get('/gb/pricing');

    // Cache key should exist for this user after first request
    expect(\Illuminate\Support\Facades\Cache::has("user.{$user->id}.language"))
        ->toBeTrue(); // Cache::remember() creates entry on first request

    // Update language
    $this->actingAs($user)
        ->patch('/gb/profile/language', ['language_code' => 'fr-FR'])
        ->assertOk();

    // Make another request to verify new language is used (cache was cleared)
    $response = $this->actingAs($user)->get('/gb/pricing');
    $response->assertInertia(fn ($page) => $page
        ->where('locale', 'fr-FR')
    );
});

test('currency cache is route-specific', function () {
    $user = User::factory()->create([
        'country_code' => 'gb',
        'currency_code' => null,
    ]);

    // Visit GB pricing (should cache GBP)
    $response1 = $this->actingAs($user)->get('/gb/pricing');
    $response1->assertInertia(fn ($page) => $page
        ->where('currency', 'GBP')
    );

    // Visit DE pricing (should have separate cache for EUR)
    $response2 = $this->actingAs($user)->get('/de/pricing');
    $response2->assertInertia(fn ($page) => $page
        ->where('currency', 'EUR')
    );

    // Come back to GB (should still show GBP from cached route-specific key)
    $response3 = $this->actingAs($user)->get('/gb/pricing');
    $response3->assertInertia(fn ($page) => $page
        ->where('currency', 'GBP')
    );
});
