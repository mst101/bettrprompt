<?php

use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use Carbon\Carbon;

it('enriches events with visitor context and derived type metadata', function () {
    $timestamp = Carbon::now()->subMinutes(5)->getTimestampMs();

    $job = new ProcessAnalyticsEvents(
        events: [],
        visitorId: 'visitor-xyz',
        userId: 99,
        sessionId: 'session-xyz',
        pageContext: [
            'page_path' => '/features',
            'referrer' => 'https://ref.example.com',
            'device_type' => 'desktop',
            'country_code' => 'gb',
        ],
    );

    $event = [
        'event_id' => '11111111-1111-1111-1111-111111111111',
        'name' => 'prompt_started',
        'occurred_at_ms' => $timestamp,
        'properties' => [
            'prompt_run_id' => 42,
        ],
    ];

    $enriched = callJobMethod($job, 'enrichEvent', [$event]);

    expect($enriched)->not->toBeNull();
    expect($enriched['visitor_id'])->toBe('visitor-xyz');
    expect($enriched['user_id'])->toBe(99);
    expect($enriched['session_id'])->toBe('session-xyz');
    expect($enriched['type'])->toBe('engagement');
    expect($enriched['page_path'])->toBe('/features');
    expect($enriched['referrer'])->toBe('https://ref.example.com');
    expect($enriched['device_type'])->toBe('desktop');
    expect($enriched['country_code'])->toBe('gb');
    expect($enriched['prompt_run_id'])->toBe(42);
    expect($enriched['source'])->toBe('client');
    expect($enriched['occurred_at'])->toBeInstanceOf(Carbon::class);
});

it('rejects events missing required identifiers', function () {
    $job = new ProcessAnalyticsEvents(events: [], visitorId: 'visitor-xyz');

    $event = [
        'name' => 'prompt_started',
        'occurred_at_ms' => Carbon::now()->getTimestampMs(),
    ];

    $enriched = callJobMethod($job, 'enrichEvent', [$event]);

    expect($enriched)->toBeNull();
});

it('derives event types from naming conventions', function () {
    expect(AnalyticsEvent::deriveType('subscription_completed'))->toBe('conversion');
    expect(AnalyticsEvent::deriveType('workflow_failed'))->toBe('error');
    expect(AnalyticsEvent::deriveType('session_expired'))->toBe('system');
    expect(AnalyticsEvent::deriveType('prompt_started'))->toBe('engagement');
});

it('serializes properties before inserting', function () {
    $job = new ProcessAnalyticsEvents(events: []);

    $arrayProperties = ['a' => 'b'];
    $json = callJobMethod($job, 'jsonEncodeProperties', [$arrayProperties]);

    expect($json)->toBe(json_encode($arrayProperties, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    expect(callJobMethod($job, 'jsonEncodeProperties', [null]))->toBeNull();
    expect(callJobMethod($job, 'jsonEncodeProperties', ['raw']))->toBe('raw');
});

function callJobMethod(ProcessAnalyticsEvents $job, string $method, array $arguments = [])
{
    $reflection = new \ReflectionMethod(ProcessAnalyticsEvents::class, $method);
    $reflection->setAccessible(true);

    return $reflection->invokeArgs($job, $arguments);
}
