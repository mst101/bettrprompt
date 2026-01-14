<?php

use App\Jobs\ProcessAnalyticsEvents;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
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

it('decrypts visitor cookie before dispatch', function () {
    Bus::fake();

    $encrypted = Crypt::encryptString('visitor-real');
    $eventId = Str::uuid()->toString();

    $response = $this
        ->withHeaders([
            'X-Analytics-Session-Id' => 'session-foo',
            'Referer' => 'https://example.com/pricing',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) Mobile/15E148',
        ])
        ->withCookie('visitor_id', $encrypted)
        ->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_id' => $eventId,
                    'name' => 'prompt_completed',
                    'occurred_at_ms' => now()->getTimestampMs(),
                ],
            ],
        ]);

    $response->assertOk();

    Bus::assertDispatched(ProcessAnalyticsEvents::class);
    Bus::assertDispatched(ProcessAnalyticsEvents::class, function (ProcessAnalyticsEvents $job) {
        return getJobProperty($job, 'visitorId') === 'visitor-real';
    });
});

it('extracts uuid segment from pipe-delimited visitor cookie', function () {
    Bus::fake();

    $visitorId = Str::uuid()->toString();
    $encrypted = Crypt::encryptString("tracking|{$visitorId}");
    $eventId = Str::uuid()->toString();

    $response = $this
        ->withHeaders([
            'X-Analytics-Session-Id' => 'session-foo',
            'Referer' => 'https://example.com/pricing',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) Mobile/15E148',
        ])
        ->withCookie('visitor_id', $encrypted)
        ->postJson(route('analytics.events.store'), [
            'events' => [
                [
                    'event_id' => $eventId,
                    'name' => 'prompt_completed',
                    'occurred_at_ms' => now()->getTimestampMs(),
                ],
            ],
        ]);

    $response->assertOk();

    Bus::assertDispatched(ProcessAnalyticsEvents::class);
    Bus::assertDispatched(ProcessAnalyticsEvents::class, function (ProcessAnalyticsEvents $job) use ($visitorId) {
        return getJobProperty($job, 'visitorId') === $visitorId;
    });
});

it('validates analytics payload structure before queuing', function () {
    $response = $this->postJson(route('analytics.events.store'), [
        'events' => [],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['events']);
});

function getJobProperty(ProcessAnalyticsEvents $job, string $property)
{
    return Closure::bind(fn () => $this->{$property}, $job, ProcessAnalyticsEvents::class)();
}
