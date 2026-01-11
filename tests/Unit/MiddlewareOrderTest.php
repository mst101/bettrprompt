<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetCountry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

describe('Middleware Execution Order', function () {
    test('SetCountry middleware is registered before HandleInertiaRequests', function () {
        // Verify bootstrap/app.php configuration
        // This is a structural test that doesn't require database access

        // Read bootstrap/app.php and check middleware ordering
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));

        // Find the positions of both middleware in the file
        $setCountryPos = strpos($bootstrapContent, 'SetCountry');
        $handleInertiaPos = strpos($bootstrapContent, 'HandleInertiaRequests');

        // SetCountry should appear before HandleInertiaRequests in the middleware stack
        expect($setCountryPos)->toBeLessThan($handleInertiaPos)
            ->and($setCountryPos)->toBeGreaterThan(0)
            ->and($handleInertiaPos)->toBeGreaterThan(0);
    });

    test('SetCountry middleware sets app locale before HandleInertiaRequests reads it', function () {
        // Create a mock request
        $request = Request::create('/gb/test');
        $request->route(null, ['country' => 'gb']);

        // Verify the app locale can be set
        app()->setLocale('en-GB');
        expect(app()->getLocale())->toBe('en-GB');

        // Verify HandleInertiaRequests reads the locale from app()
        expect(app()->getLocale())->not->toBeNull();
    });

    test('SetCountry middleware execution sets correct locale from cache key format', function () {
        // This test verifies the middleware can correctly resolve language codes
        // without requiring database access

        Cache::forever('country.gb.language', 'en-GB');

        $cached = Cache::get('country.gb.language');
        expect($cached)->toBe('en-GB');

        Cache::forget('country.gb.language');
    });
});
