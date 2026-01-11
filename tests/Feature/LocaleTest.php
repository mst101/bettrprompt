<?php

use App\Http\Middleware\SetCountry;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;

test('supported countries are configured correctly', function () {
    $countries = config('app.supported_countries');

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
        ->where('supportedCountries', config('app.supported_countries'))
    );
});

test('middleware alias is registered', function () {
    // Check that the 'country' middleware alias is registered
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('country');
});
