<?php

declare(strict_types=1);

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\Visitor;
use Illuminate\Database\QueryException;

describe('Analytics Referential Integrity', function () {
    describe('Visitor Foreign Keys', function () {
        it('enforces FK constraint on visitor_id in analytics_sessions', function () {
            expect(function () {
                AnalyticsSession::create([
                    'id' => fake()->uuid(),
                    'visitor_id' => fake()->uuid(),
                    'user_id' => null,
                    'country_code' => 'gb',
                    'locale' => 'en-GB',
                ]);
            })->toThrow(QueryException::class);
        });

        it('enforces FK constraint on visitor_id in analytics_events', function () {
            expect(function () {
                AnalyticsEvent::create([
                    'id' => fake()->uuid(),
                    'visitor_id' => fake()->uuid(),
                    'event_name' => 'test_event',
                    'event_data' => [],
                ]);
            })->toThrow(QueryException::class);
        });

        it('allows null visitor_id in analytics_sessions', function () {
            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => null,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            expect($session->visitor_id)->toBeNull();
        });

        it('allows null visitor_id in analytics_events', function () {
            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'visitor_id' => null,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            expect($event->visitor_id)->toBeNull();
        });

        it('allows valid visitor_id in analytics_sessions', function () {
            $visitor = Visitor::create([
                'id' => fake()->uuid(),
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            expect($session->visitor_id)->toBe($visitor->id);
        });

        it('allows valid visitor_id in analytics_events', function () {
            $visitor = Visitor::create([
                'id' => fake()->uuid(),
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            expect($event->visitor_id)->toBe($visitor->id);
        });
    });

    describe('Session Foreign Key', function () {
        it('enforces FK constraint on session_id in analytics_events', function () {
            expect(function () {
                AnalyticsEvent::create([
                    'id' => fake()->uuid(),
                    'session_id' => fake()->uuid(),
                    'event_name' => 'test_event',
                    'event_data' => [],
                ]);
            })->toThrow(QueryException::class);
        });

        it('allows null session_id in analytics_events', function () {
            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'session_id' => null,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            expect($event->session_id)->toBeNull();
        });

        it('allows valid session_id in analytics_events', function () {
            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => null,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'session_id' => $session->id,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            expect($event->session_id)->toBe($session->id);
        });
    });

    describe('Cascade and Null Delete Behavior', function () {
        it('sets visitor_id to null when visitor is deleted in analytics_sessions', function () {
            $visitor = Visitor::create([
                'id' => fake()->uuid(),
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $session_id = $session->id;
            $visitor->delete();

            $session = AnalyticsSession::find($session_id);

            expect($session)->not()->toBeNull();
            expect($session->visitor_id)->toBeNull();
        });

        it('sets visitor_id to null when visitor is deleted in analytics_events', function () {
            $visitor = Visitor::create([
                'id' => fake()->uuid(),
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            $event_id = $event->id;
            $visitor->delete();

            $event = AnalyticsEvent::find($event_id);

            expect($event)->not()->toBeNull();
            expect($event->visitor_id)->toBeNull();
        });

        it('sets session_id to null when session is deleted in analytics_events', function () {
            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => null,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'session_id' => $session->id,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            $event_id = $event->id;
            $session->delete();

            $event = AnalyticsEvent::find($event_id);

            expect($event)->not()->toBeNull();
            expect($event->session_id)->toBeNull();
        });

        it('preserves analytics history when visitor is deleted', function () {
            $visitor = Visitor::create([
                'id' => fake()->uuid(),
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $session = AnalyticsSession::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'user_id' => null,
                'country_code' => 'gb',
                'locale' => 'en-GB',
            ]);

            $event = AnalyticsEvent::create([
                'id' => fake()->uuid(),
                'visitor_id' => $visitor->id,
                'session_id' => $session->id,
                'event_name' => 'test_event',
                'event_data' => [],
            ]);

            $event_count = AnalyticsEvent::count();
            $session_count = AnalyticsSession::count();

            $visitor->delete();

            // Events and sessions should still exist, just with null visitor_id
            expect(AnalyticsEvent::count())->toBe($event_count);
            expect(AnalyticsSession::count())->toBe($session_count);

            // Reload and check
            $reloadedEvent = AnalyticsEvent::find($event->id);
            $reloadedSession = AnalyticsSession::find($session->id);

            expect($reloadedEvent->visitor_id)->toBeNull();
            expect($reloadedSession->visitor_id)->toBeNull();
        });
    });
});
