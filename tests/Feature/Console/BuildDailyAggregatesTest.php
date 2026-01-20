<?php

namespace Tests\Feature\Console;

use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildDailyAggregatesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test build daily aggregates command dispatches orchestrator
     */
    public function test_build_daily_aggregates_dispatches_orchestrator(): void
    {
        // Create analytics session data for a specific date
        $date = '2026-01-20';
        AnalyticsSession::factory(2)->create([
            'started_at' => now()->parse($date)->setHour(12),
        ]);

        // Run the command
        $this->artisan('analytics:build-daily-aggregates', [
            '--date' => $date,
        ])->assertExitCode(0);

        // Verify analytics_daily_stats was created
        $stat = AnalyticsDailyStat::where('date', $date)->first();
        $this->assertNotNull($stat);
        $this->assertGreaterThan(0, $stat->unique_visitors);
    }

    /**
     * Test build daily aggregates for specific date
     */
    public function test_build_daily_aggregates_for_specific_date(): void
    {
        $date = '2026-01-15';
        AnalyticsSession::factory(3)->create([
            'started_at' => now()->parse($date)->setHour(12),
        ]);

        $this->artisan('analytics:build-daily-aggregates', [
            '--date' => $date,
        ])->assertExitCode(0);

        $stat = AnalyticsDailyStat::where('date', $date)->first();
        $this->assertNotNull($stat);
        $this->assertEquals(3, $stat->total_sessions);
    }

    /**
     * Test build daily aggregates defaults to yesterday
     */
    public function test_build_daily_aggregates_defaults_to_yesterday(): void
    {
        $yesterday = now()->subDay();
        AnalyticsSession::factory(2)->create([
            'started_at' => $yesterday->setHour(12),
        ]);

        // Run without date argument
        $this->artisan('analytics:build-daily-aggregates')->assertExitCode(0);

        // Verify records were created for yesterday
        $stat = AnalyticsDailyStat::where('date', $yesterday->toDateString())->first();
        $this->assertNotNull($stat);
    }

    /**
     * Test build daily aggregates skips dates with no data
     */
    public function test_build_daily_aggregates_skips_empty_dates(): void
    {
        // Don't create any analytics session data
        // But try to aggregate anyway
        $this->artisan('analytics:build-daily-aggregates', [
            '--date' => '2025-01-01',
        ])->assertExitCode(0);

        // Verify no records were created
        $stat = AnalyticsDailyStat::where('date', '2025-01-01')->first();
        $this->assertNull($stat);
    }

    /**
     * Test build daily aggregates is idempotent
     */
    public function test_build_daily_aggregates_is_idempotent(): void
    {
        $date = '2026-01-10';
        AnalyticsSession::factory(2)->create([
            'started_at' => now()->parse($date)->setHour(12),
        ]);

        // Run once
        $this->artisan('analytics:build-daily-aggregates', [
            '--date' => $date,
        ])->assertExitCode(0);

        $firstStat = AnalyticsDailyStat::where('date', $date)->first();

        // Run again
        $this->artisan('analytics:build-daily-aggregates', [
            '--date' => $date,
        ])->assertExitCode(0);

        $secondStat = AnalyticsDailyStat::where('date', $date)->first();

        // Verify same data (idempotent)
        $this->assertEquals($firstStat->total_sessions, $secondStat->total_sessions);
        $this->assertEquals($firstStat->unique_visitors, $secondStat->unique_visitors);
    }
}
