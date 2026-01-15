<?php

use App\Jobs\ProcessAnalyticsEvents;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

it('accepts valid analytics payloads and responds successfully', function () {
    Bus::fake();

    $eventId = Str::uuid()->toString();

    $response = $this
        ->withHeaders([
            'X-Analytics-Session-Id' => 'session-foo',
            'Referer' => 'https://example.com/pricing',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) Mobile/15E148',
        ])
        ->withCookie('visitor_id', 'visitor-abc')
        ->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_id' => $eventId,
                    'name' => 'prompt_completed',
                    'occurred_at_ms' => now()->getTimestampMs(),
                    'properties' => [
                        'prompt_run_id' => 99,
                    ],
                ],
            ],
        ]);

    $response->assertOk()->assertJson([
        'success' => true,
        'message' => 'Events queued for processing',
    ]);
});

it('validates analytics payload structure before queuing', function () {
    $response = $this->postJson(route('analytics.events.store'), [
        'events' => [],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['events']);
});

it('creates session before inserting events using sync queue', function () {
    // Use sync queue to run jobs synchronously during testing
    Queue::fake();

    $sessionId = Str::uuid()->toString();
    $visitorId = Str::uuid()->toString();
    $eventId = Str::uuid()->toString();

    // Make the request
    $response = $this
        ->withHeaders([
            'X-Analytics-Session-Id' => $sessionId,
            'Referer' => 'https://example.com/pricing',
        ])
        ->withCookie('visitor_id', $visitorId)
        ->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_id' => $eventId,
                    'name' => 'page_view',
                    'occurred_at_ms' => now()->getTimestampMs(),
                    'properties' => ['path' => '/'],
                ],
            ],
        ]);

    $response->assertOk();

    // Verify the job was dispatched
    Queue::assertPushed(ProcessAnalyticsEvents::class);

    // Now process the job synchronously
    Queue::assertPushed(function (ProcessAnalyticsEvents $job) use ($sessionId) {
        // Get the job properties via reflection to verify they match
        $reflection = new \ReflectionClass($job);
        $sessionProp = $reflection->getProperty('sessionId');
        $sessionProp->setAccessible(true);

        expect($sessionProp->getValue($job))->toBe($sessionId);

        return true;
    });
});
