<?php

namespace Tests\Feature\Jobs;

use App\Jobs\BuildAnalyticsDailyStats;
use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuildAnalyticsDailyStatsTest extends TestCase
{
    use RefreshDatabase;

    protected Carbon $testDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDate = now()->subDay()->startOfDay();
    }

    public function test_it_aggregates_traffic_metrics_correctly(): void
    {
        // Create test sessions
        $dayStart = $this->testDate->clone()->startOfDay();
        $dayEnd = $this->testDate->clone()->endOfDay();

        $sessions5 = AnalyticsSession::factory()->count(5)->create([
            'started_at' => $dayStart->addHours(2),
            'duration_seconds' => 120,
        ]);

        // Create 3 page_view events per session for first group
        foreach ($sessions5 as $session) {
            for ($i = 0; $i < 3; $i++) {
                AnalyticsEvent::factory()->create([
                    'session_id' => $session->id,
                    'name' => 'page_view',
                    'type' => 'engagement',
                ]);
            }
        }

        $sessions2 = AnalyticsSession::factory()->count(2)->create([
            'started_at' => $dayStart->addHours(4),
            'duration_seconds' => null,
        ]);

        // Create 1 page_view event per session for second group
        foreach ($sessions2 as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'name' => 'page_view',
                'type' => 'engagement',
            ]);
        }

        // Execute job
        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        // Verify metrics
        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat);
        $this->assertEquals(7, $stat->total_sessions);
        $this->assertGreaterThan(0, $stat->unique_visitors);
        $this->assertEquals(17, $stat->total_page_views); // (5 × 3) + (2 × 1) = 17
        $this->assertNotNull($stat->avg_session_duration_seconds);
        $this->assertNotNull($stat->bounce_rate);
    }

    public function test_it_handles_no_data_gracefully(): void
    {
        // No sessions created for this date
        $dayStart = $this->testDate->clone()->startOfDay();
        $dayEnd = $this->testDate->clone()->endOfDay();

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        // Should not create a record
        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();
        $this->assertNull($stat);
    }

    public function test_it_calculates_bounce_rate_correctly(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        // Create 10 sessions: 3 bounces (≤1 page view), 7 non-bounces (>1 page view)
        $bounceSessions = AnalyticsSession::factory()->count(3)->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        // Create 1 page_view per bounce session
        foreach ($bounceSessions as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'name' => 'page_view',
                'type' => 'engagement',
            ]);
        }

        $nonBounceSessions = AnalyticsSession::factory()->count(7)->create([
            'started_at' => $dayStart->addHours(2),
        ]);

        // Create 2 page_view per non-bounce session
        foreach ($nonBounceSessions as $session) {
            AnalyticsEvent::factory()->count(2)->create([
                'session_id' => $session->id,
                'name' => 'page_view',
                'type' => 'engagement',
            ]);
        }

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat->bounce_rate);
        $this->assertEquals(0.3, $stat->bounce_rate); // 3/10
    }

    public function test_it_counts_registrations_by_tier(): void
    {
        User::factory()->count(5)->create([
            'created_at' => $this->testDate->addHours(2),
            'subscription_tier' => 'free',
        ]);

        User::factory()->count(3)->create([
            'created_at' => $this->testDate->addHours(3),
            'subscription_tier' => 'pro',
        ]);

        User::factory()->count(2)->create([
            'created_at' => $this->testDate->addHours(4),
            'subscription_tier' => 'private',
        ]);

        // Also create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $this->testDate->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat);
        $this->assertEquals(10, $stat->registrations);
        $this->assertEquals(5, $stat->subscriptions_free);
        $this->assertEquals(3, $stat->subscriptions_pro);
        $this->assertEquals(2, $stat->subscriptions_private);
    }

    public function test_it_calculates_revenue_from_config(): void
    {
        config(['subscriptions.prices' => [
            'pro' => ['monthly' => 29.00],
            'private' => ['monthly' => 99.00],
        ]]);

        User::factory()->count(2)->create([
            'created_at' => $this->testDate->addHours(2),
            'subscription_tier' => 'pro',
        ]);

        User::factory()->count(1)->create([
            'created_at' => $this->testDate->addHours(3),
            'subscription_tier' => 'private',
        ]);

        // Create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $this->testDate->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $expectedRevenue = (2 * 29.00) + (1 * 99.00); // 58 + 99 = 157
        $this->assertEquals($expectedRevenue, $stat->total_revenue_usd);
    }

    public function test_it_aggregates_by_utm_source(): void
    {
        // Google sessions - all converted
        $googleSessions = AnalyticsSession::factory()->count(10)->create([
            'started_at' => $this->testDate->addHours(1),
            'utm_source' => 'google',
        ]);
        foreach ($googleSessions as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'type' => 'conversion',
            ]);
        }

        // Facebook sessions - none converted
        AnalyticsSession::factory()->count(5)->create([
            'started_at' => $this->testDate->addHours(2),
            'utm_source' => 'facebook',
        ]);

        // Direct traffic sessions - all converted
        $directSessions = AnalyticsSession::factory()->count(15)->create([
            'started_at' => $this->testDate->addHours(3),
            'utm_source' => null,
        ]);
        foreach ($directSessions as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'type' => 'conversion',
            ]);
        }

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat->by_utm_source);
        $this->assertEquals(10, $stat->by_utm_source['google']['sessions']);
        $this->assertEquals(10, $stat->by_utm_source['google']['conversions']);
        $this->assertEquals(5, $stat->by_utm_source['facebook']['sessions']);
        $this->assertEquals(0, $stat->by_utm_source['facebook']['conversions']);
        $this->assertEquals(15, $stat->by_utm_source['direct']['sessions']);
        $this->assertEquals(15, $stat->by_utm_source['direct']['conversions']);
    }

    public function test_it_aggregates_by_country(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        // Create visitors for different countries
        $gbVisitor = \App\Models\Visitor::factory()->create(['country_code' => 'gb']);
        $usVisitor = \App\Models\Visitor::factory()->create(['country_code' => 'us']);

        // GB sessions - all converted
        $gbSessions = AnalyticsSession::factory()->count(8)->create([
            'started_at' => $dayStart->addHours(1),
            'visitor_id' => $gbVisitor->id,
        ]);
        foreach ($gbSessions as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'type' => 'conversion',
            ]);
        }

        // US sessions - none converted
        AnalyticsSession::factory()->count(5)->create([
            'started_at' => $dayStart->addHours(2),
            'visitor_id' => $usVisitor->id,
        ]);

        // Create registrations from these countries
        User::factory()->count(3)->create([
            'created_at' => $dayStart->addHours(3),
            'country_code' => 'gb',
        ]);

        User::factory()->count(1)->create([
            'created_at' => $dayStart->addHours(4),
            'country_code' => 'us',
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat->by_country);
        $this->assertEquals(8, $stat->by_country['gb']['sessions']);
        $this->assertEquals(5, $stat->by_country['us']['sessions']);
    }

    public function test_it_aggregates_by_device_type(): void
    {
        // Mobile sessions with 2 prompt_completed events each
        $mobileSessions = AnalyticsSession::factory()->count(6)->create([
            'started_at' => $this->testDate->addHours(1),
            'device_type' => 'mobile',
            'duration_seconds' => 60,
        ]);
        foreach ($mobileSessions as $session) {
            AnalyticsEvent::factory()->count(2)->create([
                'session_id' => $session->id,
                'name' => 'prompt_completed',
                'type' => 'engagement',
            ]);
        }

        // Desktop sessions with 1 prompt_completed event each
        $desktopSessions = AnalyticsSession::factory()->count(4)->create([
            'started_at' => $this->testDate->addHours(2),
            'device_type' => 'desktop',
            'duration_seconds' => 180,
        ]);
        foreach ($desktopSessions as $session) {
            AnalyticsEvent::factory()->create([
                'session_id' => $session->id,
                'name' => 'prompt_completed',
                'type' => 'engagement',
            ]);
        }

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat->by_device_type);
        $this->assertEquals(6, $stat->by_device_type['mobile']['sessions']);
        $this->assertEquals(4, $stat->by_device_type['desktop']['sessions']);
    }

    public function test_it_counts_only_parent_prompts(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();
        $dayEnd = $this->testDate->clone()->endOfDay();

        // Create parent prompts
        PromptRun::factory()->count(5)->create([
            'created_at' => $dayStart->addHours(1),
            'parent_id' => null,
        ]);

        // Create iteration prompts (should be excluded from count)
        $parentPrompt = PromptRun::factory()->create([
            'created_at' => $dayStart->addHours(2),
            'parent_id' => null,
        ]);

        PromptRun::factory()->count(3)->create([
            'created_at' => $dayStart->addHours(3),
            'parent_id' => $parentPrompt->id,
        ]);

        // Create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertEquals(6, $stat->prompts_started); // 5 + 1 parent, not the 3 iterations
    }

    public function test_it_calculates_prompt_completion_rate(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        // Create prompts that started but didn't complete
        PromptRun::factory()->count(3)->create([
            'created_at' => $dayStart->addHours(1),
            'parent_id' => null,
            'workflow_stage' => '1_completed', // Not fully completed
        ]);

        // Create completed prompts
        PromptRun::factory()->count(7)->create([
            'created_at' => $dayStart->addHours(2),
            'completed_at' => $dayStart->addHours(3),
            'parent_id' => null,
            'workflow_stage' => '2_completed',
        ]);

        // Create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertEquals(10, $stat->prompts_started);
        $this->assertEquals(7, $stat->prompts_completed);
        $this->assertEquals(0.7, $stat->prompt_completion_rate); // 7/10
    }

    public function test_it_calculates_average_prompt_rating(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        // Create quality metrics with various ratings
        PromptQualityMetric::factory()->count(4)->create([
            'created_at' => $dayStart->addHours(1),
            'user_rating' => 5,
        ]);

        PromptQualityMetric::factory()->count(2)->create([
            'created_at' => $dayStart->addHours(2),
            'user_rating' => 3,
        ]);

        // These should be excluded (null ratings)
        PromptQualityMetric::factory()->count(3)->create([
            'created_at' => $dayStart->addHours(3),
            'user_rating' => null,
        ]);

        // Create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $expectedRating = ((4 * 5) + (2 * 3)) / 6; // (20 + 6) / 6 = 4.33
        $this->assertNotNull($stat->avg_prompt_rating);
        $this->assertEqualsWithDelta($expectedRating, $stat->avg_prompt_rating, 0.01);
    }

    public function test_it_handles_division_by_zero(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        // Create sessions but no prompts started
        AnalyticsSession::factory()->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertEquals(0, $stat->prompts_started);
        $this->assertNull($stat->prompt_completion_rate);
    }

    public function test_it_is_idempotent(): void
    {
        AnalyticsSession::factory()->count(5)->create([
            'started_at' => $this->testDate->addHours(1),
            'duration_seconds' => 120,
        ]);

        // Run the job twice
        BuildAnalyticsDailyStats::dispatchSync($this->testDate);
        $firstStat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);
        $secondStat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        // Should have same values and only one record
        $this->assertEquals(1, AnalyticsDailyStat::where('date', $this->testDate->toDateString())->count());
        $this->assertEquals($firstStat->unique_visitors, $secondStat->unique_visitors);
        $this->assertEquals($firstStat->total_sessions, $secondStat->total_sessions);
    }

    public function test_it_excludes_null_ratings_from_average(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();

        PromptQualityMetric::factory()->count(2)->create([
            'created_at' => $dayStart->addHours(1),
            'user_rating' => 5,
        ]);

        PromptQualityMetric::factory()->count(5)->create([
            'created_at' => $dayStart->addHours(2),
            'user_rating' => null,
        ]);

        // Create a session to trigger aggregation
        AnalyticsSession::factory()->create([
            'started_at' => $dayStart->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertEquals(5, $stat->avg_prompt_rating); // Only 2 ratings with value 5
    }

    public function test_it_attributes_sessions_by_started_at(): void
    {
        $dayStart = $this->testDate->clone()->startOfDay();
        $dayEnd = $this->testDate->clone()->endOfDay();
        $nextDay = $this->testDate->clone()->addDay()->startOfDay();

        // Session started today but ended tomorrow - should count for today
        AnalyticsSession::factory()->create([
            'started_at' => $dayEnd->subHours(1),
            'ended_at' => $nextDay->addHours(1),
        ]);

        BuildAnalyticsDailyStats::dispatchSync($this->testDate);

        $stat = AnalyticsDailyStat::where('date', $this->testDate->toDateString())->first();

        $this->assertNotNull($stat);
        $this->assertEquals(1, $stat->total_sessions);
    }
}
