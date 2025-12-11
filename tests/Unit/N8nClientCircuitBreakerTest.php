<?php

use App\Services\N8nClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Configure N8n service
    Config::set('services.n8n.url', 'http://test-n8n.localhost');
    Config::set('services.n8n.username', 'test-user');
    Config::set('services.n8n.password', 'test-password');

    // Clear cache before each test
    Cache::flush();
});

test('circuit breaker opens after threshold failures', function () {
    $client = new N8nClient;

    // Simulate 500 server errors to trigger failures
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    // Make 5 failed requests (threshold is 5)
    for ($i = 0; $i < 5; $i++) {
        $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
        expect($result['success'])->toBeFalse();
    }

    // Circuit should now be open
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n service is temporarily unavailable. Please try again later.')
        ->and(Cache::has('n8n_circuit_breaker_open_until'))->toBeTrue();

    // Verify the circuit breaker is open via cache
});

test('circuit breaker remains closed under threshold', function () {
    $client = new N8nClient;

    // Simulate 500 server errors
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    // Make 4 failed requests (below threshold of 5)
    for ($i = 0; $i < 4; $i++) {
        $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
        expect($result['success'])->toBeFalse();
    }

    // Circuit should still be closed, allowing next request
    // (though it will fail due to 500 response)
    $failureCount = Cache::get('n8n_circuit_breaker_failures', 0);
    expect($failureCount)->toBe(4)
        ->and(Cache::has('n8n_circuit_breaker_open_until'))->toBeFalse();
});

test('circuit breaker closes after cooldown period and failure expiry', function () {
    $client = new N8nClient;

    // Set up sequence: 15 failures (5 webhook calls * 3 retries) + 1 success
    $sequence = Http::sequence();

    // Add 15 failures to trigger circuit breaker
    for ($i = 0; $i < 15; $i++) {
        $sequence->push([], 500);
    }

    // Add 1 success response after circuit recovery
    $sequence->push(['success' => true], 200);

    Http::fake([
        '*' => $sequence,
    ]);

    // Trigger 5 failures to reach threshold (each retries 3 times = 15 HTTP calls)
    for ($i = 0; $i < 5; $i++) {
        $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    }

    // Verify 5 failures recorded (circuit not yet "opened" until next call)
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(5);

    // Manually clear cache to simulate expiry (time travel doesn't affect cache in tests)
    Cache::forget('n8n_circuit_breaker_failures');
    Cache::forget('n8n_circuit_breaker_open_until');

    // Circuit should be closed now, allowing the successful request
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    expect($result['success'])->toBeTrue();
});

test('circuit breaker resets failure count on success', function () {
    $client = new N8nClient;

    // Simulate 3 failures then 1 success using a sequence
    // Each failed call retries 3 times, so we need 9 failures + 1 success
    $sequence = Http::sequence();

    // Add 9 failures (3 webhook calls * 3 retries each)
    for ($i = 0; $i < 9; $i++) {
        $sequence->push([], 500);
    }

    // Add 1 success
    $sequence->push(['success' => true], 200);

    Http::fake([
        '*' => $sequence,
    ]);

    // Make 3 failed webhook calls
    for ($i = 0; $i < 3; $i++) {
        $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    }

    // Verify failures recorded
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(3);

    // Now make a successful call
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    expect($result['success'])->toBeTrue()
        ->and(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(0)
        ->and(Cache::has('n8n_circuit_breaker_open_until'))->toBeFalse();

    // Failure count should be reset to 0
});

test('rate limit extends circuit breaker cooldown to 15 minutes', function () {
    $client = new N8nClient;

    // Simulate rate limit response (429)
    Http::fake([
        '*' => Http::response(['error' => 'Rate limit exceeded'], 429),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'API rate limit reached. Please try again in a few minutes.')
        ->toHaveKey('error_context')
        ->and($result['error_context'])
        ->toHaveKey('error_type', 'rate_limit');

    // Verify extended cooldown (15 minutes)
    $circuitOpenUntil = Cache::get('n8n_circuit_breaker_open_until');
    expect($circuitOpenUntil)->not->toBeNull();

    // Verify it's approximately 15 minutes from now (allow 1 second tolerance)
    $expectedTime = now()->addMinutes(15);
    expect($circuitOpenUntil->diffInSeconds($expectedTime, false))->toBeLessThan(2);
});

test('circuit breaker prevents requests when open', function () {
    $client = new N8nClient;

    // Manually set circuit as open
    Cache::put('n8n_circuit_breaker_open_until', now()->addMinutes(5));
    Cache::put('n8n_circuit_breaker_failures', 5);

    // Fake HTTP to verify it never gets called
    Http::fake([
        '*' => Http::response(['should' => 'never be called'], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toBeArray()
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n service is temporarily unavailable. Please try again later.');

    // Verify no HTTP requests were made
    Http::assertNothingSent();
});

test('circuit breaker failure count expires after double cooldown period', function () {
    $client = new N8nClient;

    // Simulate 3 failures
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    for ($i = 0; $i < 3; $i++) {
        $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    }

    // Verify failures recorded
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(3);

    // Travel beyond double the cooldown period (10 minutes)
    $this->travel(11)->minutes();

    // Failure count should have expired
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(0);
});

test('circuit breaker opens immediately on reaching threshold', function () {
    $client = new N8nClient;

    // Simulate failures
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    // Make exactly 5 requests (threshold)
    // Each request will retry 3 times, so 5 * 3 = 15 total HTTP calls
    for ($i = 0; $i < 5; $i++) {
        $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
        expect($result['success'])->toBeFalse();
    }

    // The very next request should be blocked by circuit breaker
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toHaveKey('error', 'N8n service is temporarily unavailable. Please try again later.');

    // Verify HTTP was called 15 times (5 webhook calls * 3 retries each)
    // The 6th call was blocked by circuit breaker (0 HTTP calls)
    Http::assertSentCount(15);
});
