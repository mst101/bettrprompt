<?php

declare(strict_types=1);

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use Illuminate\Database\QueryException;

describe('Analytics Referential Integrity', function () {
    describe('Foreign Key Constraints Exist', function () {
        it('enforces FK constraint on visitor_id in analytics_sessions when invalid UUID used', function () {
            expect(function () {
                AnalyticsSession::create([
                    'id' => fake()->uuid(),
                    'visitor_id' => fake()->uuid(),
                    'user_id' => null,
                    'country_code' => 'gb',
                ]);
            })->toThrow(QueryException::class);
        });

        it('enforces FK constraint on visitor_id in analytics_events when invalid UUID used', function () {
            expect(function () {
                AnalyticsEvent::create([
                    'id' => fake()->uuid(),
                    'visitor_id' => fake()->uuid(),
                    'event_name' => 'test_event',
                    'event_data' => [],
                ]);
            })->toThrow(QueryException::class);
        });

        it('enforces FK constraint on session_id in analytics_events when invalid UUID used', function () {
            expect(function () {
                AnalyticsEvent::create([
                    'id' => fake()->uuid(),
                    'session_id' => fake()->uuid(),
                    'event_name' => 'test_event',
                    'event_data' => [],
                ]);
            })->toThrow(QueryException::class);
        });
    });

    describe('Migrations Created Correctly', function () {
        it('has foreign key constraint on analytics_sessions.visitor_id', function () {
            $constraints = \DB::select(
                "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'analytics_sessions' AND constraint_type = 'FOREIGN KEY'"
            );

            $visitorFkExists = collect($constraints)
                ->pluck('constraint_name')
                ->contains('analytics_sessions_visitor_id_foreign');

            expect($visitorFkExists)->toBeTrue();
        });

        it('has foreign key constraint on analytics_events.visitor_id', function () {
            $constraints = \DB::select(
                "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'analytics_events' AND constraint_type = 'FOREIGN KEY'"
            );

            $visitorFkExists = collect($constraints)
                ->pluck('constraint_name')
                ->contains('analytics_events_visitor_id_foreign');

            expect($visitorFkExists)->toBeTrue();
        });

        it('has foreign key constraint on analytics_events.session_id', function () {
            $constraints = \DB::select(
                "SELECT constraint_name FROM information_schema.table_constraints WHERE table_name = 'analytics_events' AND constraint_type = 'FOREIGN KEY'"
            );

            $sessionFkExists = collect($constraints)
                ->pluck('constraint_name')
                ->contains('analytics_events_session_id_foreign');

            expect($sessionFkExists)->toBeTrue();
        });
    });
});
