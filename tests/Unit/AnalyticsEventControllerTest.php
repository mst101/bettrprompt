<?php

use App\Http\Controllers\Api\AnalyticsEventController;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

it('returns null when visitor cookie is absent', function () {
    expect(invokeResolveVisitorId(null))->toBeNull();
});

it('decrypts the cookie and returns the UUID segment', function () {
    $visitorId = Str::uuid()->toString();
    $encrypted = Crypt::encryptString($visitorId);

    expect(invokeResolveVisitorId($encrypted))->toBe($visitorId);
});

it('picks the UUID portion when metadata is added', function () {
    $visitorId = Str::uuid()->toString();
    $encrypted = Crypt::encryptString("fingerprint|{$visitorId}|meta");

    expect(invokeResolveVisitorId($encrypted))->toBe($visitorId);
});

it('falls back to the raw value when no UUID is present', function () {
    $encrypted = Crypt::encryptString('non-uuid-value');

    expect(invokeResolveVisitorId($encrypted))->toBe('non-uuid-value');
});

function invokeResolveVisitorId(?string $cookie): ?string
{
    $controller = new AnalyticsEventController;
    $method = new \ReflectionMethod(AnalyticsEventController::class, 'resolveVisitorId');
    $method->setAccessible(true);

    return $method->invoke($controller, $cookie);
}
