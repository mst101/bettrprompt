<?php

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Http\Request;

test('supported locales are configured correctly', function () {
    $locales = config('app.supported_locales');

    expect($locales)->toBeArray();
    expect($locales)->toContain('en');
    expect($locales)->toContain('en-GB');
    expect($locales)->toContain('de');
    expect($locales)->toContain('fr');
    expect($locales)->toContain('ar');
    expect($locales)->toContain('he');
});

test('rtl locales are configured correctly', function () {
    $rtlLocales = config('app.rtl_locales');

    expect($rtlLocales)->toBeArray();
    expect($rtlLocales)->toContain('ar');
    expect($rtlLocales)->toContain('he');
    expect($rtlLocales)->not->toContain('en');
    expect($rtlLocales)->not->toContain('de');
});

test('SetLocale middleware detects locale from request', function () {
    $request = Request::create('/de/test');

    expect(SetLocale::detectLocale($request))->toBeIn(config('app.supported_locales'));
});

test('SetLocale middleware defaults to en when no locale detected', function () {
    $request = Request::create('/test');

    expect(SetLocale::detectLocale($request))->toBe('en');
});

test('SetLocale middleware uses user language preference when authenticated', function () {
    $user = User::factory()->create([
        'language_code' => 'de',
    ]);

    $request = Request::create('/test');
    $request->setUserResolver(fn () => $user);

    expect(SetLocale::detectLocale($request))->toBe('de');
});

test('SetLocale isRtl returns true for rtl locales', function () {
    expect(SetLocale::isRtl('ar'))->toBeTrue();
    expect(SetLocale::isRtl('he'))->toBeTrue();
});

test('SetLocale isRtl returns false for ltr locales', function () {
    expect(SetLocale::isRtl('en'))->toBeFalse();
    expect(SetLocale::isRtl('de'))->toBeFalse();
    expect(SetLocale::isRtl('fr'))->toBeFalse();
});

test('SetLocale getDirection returns correct direction', function () {
    expect(SetLocale::getDirection('ar'))->toBe('rtl');
    expect(SetLocale::getDirection('he'))->toBe('rtl');
    expect(SetLocale::getDirection('en'))->toBe('ltr');
    expect(SetLocale::getDirection('de'))->toBe('ltr');
});

test('locale is shared with inertia pages', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('locale')
        ->has('direction')
        ->has('supportedLocales')
    );
});

test('locale shared data has correct default values', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('locale', 'en')
        ->where('direction', 'ltr')
    );
});

test('supported locales shared data contains expected locales', function () {
    $response = $this->getLocale('/');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->where('supportedLocales', config('app.supported_locales'))
    );
});

test('middleware alias is registered', function () {
    // Check that the 'locale' middleware alias is registered
    $aliases = app('router')->getMiddleware();

    expect($aliases)->toHaveKey('locale');
});
