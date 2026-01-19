<?php

namespace Tests\Feature\Console;

use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillDailyAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_date_range(): void
    {
        $from = now()->subDays(5)->startOfDay();
        $to = now()->subDays(1)->startOfDay();

        // Create sessions for each day in the range
        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            AnalyticsSession::factory()->count(3)->create([
                'started_at' => $date->copy()->addHours(2),
            ]);
        }

        $this->artisan('analytics:backfill-daily', [
            '--from' => $from->toDateString(),
            '--to' => $to->toDateString(),
        ])->assertExitCode(0);

        // Verify 5 records created
        $stats = AnalyticsDailyStat::whereBetween('date', [$from->toDateString(), $to->toDateString()])->get();

        $this->assertCount(5, $stats);

        // Verify each day has a record
        for ($date = $from->copy(); $date <= $to; $date->addDay()) {
            $stat = AnalyticsDailyStat::where('date', $date->toDateString())->first();
            $this->assertNotNull($stat);
            $this->assertEquals(3, $stat->total_sessions);
        }
    }

    public function test_it_validates_date_format(): void
    {
        $this->artisan('analytics:backfill-daily', [
            '--from' => 'invalid-date',
        ])->assertExitCode(1);
    }

    public function test_it_validates_date_range(): void
    {
        $from = now()->toDateString();
        $to = now()->subDays(5)->toDateString();

        $this->artisan('analytics:backfill-daily', [
            '--from' => $from,
            '--to' => $to,
        ])->assertExitCode(1);
    }

    public function test_it_defaults_to_yesterday(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        // Create sessions for yesterday
        AnalyticsSession::factory()->count(2)->create([
            'started_at' => $yesterday->addHours(2),
        ]);

        $this->artisan('analytics:backfill-daily')->assertExitCode(0);

        // Verify record created for yesterday
        $stat = AnalyticsDailyStat::where('date', $yesterday->toDateString())->first();
        $this->assertNotNull($stat);
    }

    public function test_it_processes_single_day(): void
    {
        $date = now()->subDays(3)->startOfDay();

        AnalyticsSession::factory()->count(5)->create([
            'started_at' => $date->addHours(2),
        ]);

        $this->artisan('analytics:backfill-daily', [
            '--from' => $date->toDateString(),
            '--to' => $date->toDateString(),
        ])->assertExitCode(0);

        $stat = AnalyticsDailyStat::where('date', $date->toDateString())->first();

        $this->assertNotNull($stat);
        $this->assertEquals(5, $stat->total_sessions);
    }

    public function test_it_processes_large_date_range(): void
    {
        $from = now()->subDays(30)->startOfDay();
        $to = now()->subDay()->startOfDay();

        // Create sessions for some days (not all)
        for ($i = 0; $i < 20; $i++) {
            $date = $from->clone()->addDays($i);
            AnalyticsSession::factory()->count(2)->create([
                'started_at' => $date->addHours(2),
            ]);
        }

        $this->artisan('analytics:backfill-daily', [
            '--from' => $from->toDateString(),
            '--to' => $to->toDateString(),
        ])->assertExitCode(0);

        // Should have records only for days with sessions
        $stats = AnalyticsDailyStat::whereBetween('date', [$from->toDateString(), $to->toDateString()])->get();

        $this->assertCount(20, $stats);
    }
}
