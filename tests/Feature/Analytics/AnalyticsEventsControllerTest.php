<?php

use Illuminate\Support\Facades\Bus;
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
