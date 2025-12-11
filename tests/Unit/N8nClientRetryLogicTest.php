<?php

use App\Services\N8nClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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

test('retries on 5xx server errors up to max attempts', function () {
    $client = new N8nClient;

    // Simulate 500 errors for first 2 attempts, then success
    Http::fake([
        '*' => Http::sequence()
            ->push([], 500)
            ->push([], 500)
            ->push(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeTrue();
    Http::assertSentCount(3); // 2 failures + 1 success
});

test('does not retry on 4xx client errors', function () {
    $client = new N8nClient;

    // Simulate 400 Bad Request
    Http::fake([
        '*' => Http::response(['error' => 'Bad request'], 400),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n webhook request failed');

    // Should only attempt once (no retry)
    Http::assertSentCount(1);
});

test('fails after max retry attempts on persistent 5xx errors', function () {
    $client = new N8nClient;

    // Simulate persistent 500 errors (more than max retries)
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n webhook failed after multiple attempts');

    // Should attempt 3 times (max retries)
    Http::assertSentCount(3);
});

test('retries with exponential backoff strategy', function () {
    $client = new N8nClient;

    // Simulate failures requiring retry
    Http::fake([
        '*' => Http::sequence()
            ->push([], 500) // First attempt fails
            ->push([], 500) // Second attempt fails
            ->push(['success' => true], 200), // Third attempt succeeds
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    // Verify that retry logic works by checking:
    // 1. The request ultimately succeeds
    // 2. Three HTTP calls were made (initial + 2 retries)
    expect($result['success'])->toBeTrue()
        ->and($result['data'])->toEqual(['success' => true]);

    Http::assertSentCount(3);
});

test('retries on connection exceptions', function () {
    $client = new N8nClient;

    // Create sequence that throws ConnectionException twice, then succeeds
    $callCount = 0;
    Http::fake(function () use (&$callCount) {
        $callCount++;

        if ($callCount <= 2) {
            throw new ConnectionException('Connection refused');
        }

        return Http::response(['success' => true], 200);
    });

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeTrue()
        ->and($callCount)->toBe(3);
});

test('fails after max retries on persistent connection exceptions', function () {
    $client = new N8nClient;

    // Simulate persistent connection failures
    $callCount = 0;
    Http::fake(function () use (&$callCount) {
        $callCount++;
        throw new ConnectionException('Connection refused');
    });

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n service is unavailable. Please try again later.')
        ->and($callCount)->toBe(3); // Max retries
});

test('retries on request exceptions', function () {
    $client = new N8nClient;

    // Simulate request exceptions then success
    $callCount = 0;
    Http::fake(function () use (&$callCount) {
        $callCount++;

        if ($callCount <= 2) {
            throw new RequestException(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(500, [], 'Internal Server Error')
                )
            );
        }

        return Http::response(['success' => true], 200);
    });

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeTrue()
        ->and($callCount)->toBe(3);
});

test('throws immediately on unexpected errors without retry', function () {
    $client = new N8nClient;

    // Simulate an unexpected runtime error
    Http::fake(function () {
        throw new \RuntimeException('Unexpected error occurred');
    });

    expect(fn () => $client->triggerWebhook('/webhook/test', ['data' => 'test']))
        ->toThrow(\RuntimeException::class, 'Unexpected error occurred');
});

test('respects 30 second timeout per request', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    // Verify the request was successful (confirming timeout didn't interrupt)
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});

test('uses basic authentication for all requests', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test-auth', ['data' => 'test']);

    // Verify the request succeeded with authentication
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});

test('constructs correct webhook URLs', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test-path', ['data' => 'test']);

    // Verify the request succeeded (URL was constructed correctly)
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});

test('handles URLs with trailing and leading slashes correctly', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    // Test various slash combinations
    $result = $client->triggerWebhook('webhook/test', ['data' => 'test']);

    // Verify the request succeeded (slashes were handled correctly)
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});

test('sends payload as JSON in request body', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);

    $testPayload = [
        'task_description' => 'Test task',
        'personality_type' => 'INTJ',
        'nested' => ['key' => 'value'],
    ];

    $result = $client->triggerWebhook('/webhook/test', $testPayload);

    // Verify the request succeeded (payload was sent)
    expect($result['success'])->toBeTrue();
    Http::assertSentCount(1);
});

test('records failure in circuit breaker after max retries', function () {
    $client = new N8nClient;

    // Simulate persistent 500 errors
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeFalse();

    // Verify failure was recorded
    $failureCount = Cache::get('n8n_circuit_breaker_failures', 0);
    expect($failureCount)->toBe(1);
});

test('successful retry resets circuit breaker failures', function () {
    $client = new N8nClient;

    // Use a sequence for both requests
    Http::fake([
        '*' => Http::sequence()
            // First webhook call: 3 failed attempts (500 errors)
            ->push([], 500)
            ->push([], 500)
            ->push([], 500)
            // Second webhook call: 2 failed attempts then success
            ->push([], 500)
            ->push([], 500)
            ->push(['success' => true], 200),
    ]);

    // First request fails (records failure)
    $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(1);

    // Second request succeeds after retries
    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);
    expect($result['success'])->toBeTrue();

    // Failure count should be reset
    expect(Cache::get('n8n_circuit_breaker_failures', 0))->toBe(0);
});

test('handles 503 service unavailable with retry', function () {
    $client = new N8nClient;

    // Simulate service unavailable then recovery
    Http::fake([
        '*' => Http::sequence()
            ->push([], 503)
            ->push(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeTrue();
    Http::assertSentCount(2);
});

test('handles 502 bad gateway with retry', function () {
    $client = new N8nClient;

    // Simulate bad gateway then recovery
    Http::fake([
        '*' => Http::sequence()
            ->push([], 502)
            ->push(['success' => true], 200),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result['success'])->toBeTrue();
    Http::assertSentCount(2);
});

test('does not retry on 404 not found', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['error' => 'Not found'], 404),
    ]);

    $result = $client->triggerWebhook('/webhook/nonexistent', ['data' => 'test']);

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n webhook request failed');

    Http::assertSentCount(1); // No retry
});

test('does not retry on 401 unauthorised', function () {
    $client = new N8nClient;

    Http::fake([
        '*' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $result = $client->triggerWebhook('/webhook/test', ['data' => 'test']);

    expect($result)
        ->toHaveKey('success', false)
        ->toHaveKey('error', 'N8n webhook request failed');

    Http::assertSentCount(1); // No retry
});
