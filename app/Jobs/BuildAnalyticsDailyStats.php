<?php

namespace App\Jobs;

use App\Enums\WorkflowStage;
use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\PromptQualityMetric;
use App\Models\PromptRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BuildAnalyticsDailyStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  Carbon|null  $date  Date to aggregate for (defaults to yesterday)
     */
    public function __construct(
        private ?Carbon $date = null,
    ) {
        $this->date = $this->date ?? now()->subDay()->startOfDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Building analytics daily stats', [
                'date' => $this->date->toDateString(),
            ]);

            $dayStart = $this->date->clone()->startOfDay();
            $dayEnd = $this->date->clone()->endOfDay();

            // Check if any sessions exist for the day
            $sessions = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])->get();

            if ($sessions->isEmpty()) {
                Log::info('No analytics sessions for date', ['date' => $this->date->toDateString()]);

                return;
            }

            // Calculate all metric categories
            $trafficMetrics = $this->calculateTrafficMetrics($dayStart, $dayEnd);
            $conversionMetrics = $this->calculateConversionMetrics($dayStart, $dayEnd);
            $promptMetrics = $this->calculatePromptMetrics($dayStart, $dayEnd);

            // Calculate dimensional aggregations
            $byUtmSource = $this->aggregateByUtmSource($dayStart, $dayEnd);
            $byCountry = $this->aggregateByCountry($dayStart, $dayEnd);
            $byDeviceType = $this->aggregateByDeviceType($dayStart, $dayEnd);

            // Persist with updateOrCreate for idempotency
            AnalyticsDailyStat::updateOrCreate(
                ['date' => $this->date->toDateString()],
                array_merge($trafficMetrics, $conversionMetrics, $promptMetrics, [
                    'by_utm_source' => $byUtmSource,
                    'by_country' => $byCountry,
                    'by_device_type' => $byDeviceType,
                ])
            );

            Log::info('Analytics daily stat created/updated', [
                'date' => $this->date->toDateString(),
                'unique_visitors' => $trafficMetrics['unique_visitors'],
                'registrations' => $conversionMetrics['registrations'],
                'prompts_started' => $promptMetrics['prompts_started'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to build analytics daily stats', [
                'date' => $this->date->toDateString(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Calculate traffic metrics from analytics sessions and events
     */
    private function calculateTrafficMetrics($dayStart, $dayEnd): array
    {
        $uniqueVisitors = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])
            ->distinct('visitor_id')
            ->count('visitor_id');

        $sessions = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])->get();

        $totalSessions = $sessions->count();
        // Derive page views from analytics_events (page_view events in sessions from this day)
        $totalPageViews = $this->countPageViews($dayStart, $dayEnd);
        $avgDuration = $sessions->whereNotNull('duration_seconds')->avg('duration_seconds');
        $bounceRate = $this->calculateBounceRate($sessions);

        return [
            'unique_visitors' => $uniqueVisitors,
            'total_sessions' => $totalSessions,
            'total_page_views' => $totalPageViews,
            'avg_session_duration_seconds' => $avgDuration,
            'bounce_rate' => $bounceRate,
        ];
    }

    /**
     * Calculate bounce rate from sessions
     * Bounce rate = sessions with ≤1 page view / total sessions
     */
    private function calculateBounceRate($sessions): ?float
    {
        $totalSessions = $sessions->count();
        if ($totalSessions === 0) {
            return null;
        }

        // Count sessions with ≤1 page view
        $bouncedCount = 0;
        foreach ($sessions as $session) {
            if ($session->isBounce()) {
                $bouncedCount++;
            }
        }

        return $bouncedCount / $totalSessions;
    }

    /**
     * Count page views from analytics_events for sessions in the given date range
     * Page views are analytics_events where name = 'page_view' and session_id references
     * a session that started within the date range
     */
    private function countPageViews($dayStart, $dayEnd): int
    {
        return AnalyticsEvent::where('name', 'page_view')
            ->whereIn('session_id', function ($query) use ($dayStart, $dayEnd) {
                $query->select('id')
                    ->from('analytics_sessions')
                    ->whereBetween('started_at', [$dayStart, $dayEnd]);
            })
            ->count();
    }

    /**
     * Calculate conversion metrics from user registrations and subscriptions
     */
    private function calculateConversionMetrics($dayStart, $dayEnd): array
    {
        $users = User::whereBetween('created_at', [$dayStart, $dayEnd])->get();

        $registrations = $users->count();
        $subscriptionsFree = $users->where(fn ($u) => $u->subscription_tier === 'free' || $u->subscription_tier === null)->count();
        $subscriptionsPro = $users->where('subscription_tier', 'pro')->count();
        $subscriptionsPrivate = $users->where('subscription_tier', 'private')->count();
        $totalRevenue = $this->calculateRevenue($users);

        return [
            'registrations' => $registrations,
            'subscriptions_free' => $subscriptionsFree,
            'subscriptions_pro' => $subscriptionsPro,
            'subscriptions_private' => $subscriptionsPrivate,
            'total_revenue_usd' => $totalRevenue,
        ];
    }

    /**
     * Calculate revenue based on subscription tier and pricing config
     */
    private function calculateRevenue($users): float
    {
        $pricing = config('subscriptions.prices');
        if (! $pricing) {
            return 0;
        }

        $revenue = 0;

        foreach ($users as $user) {
            $tier = $user->subscription_tier;

            if ($tier === 'pro' && isset($pricing['pro'])) {
                $revenue += $pricing['pro']['monthly'] ?? 0;
            } elseif ($tier === 'private' && isset($pricing['private'])) {
                $revenue += $pricing['private']['monthly'] ?? 0;
            }
        }

        return $revenue;
    }

    /**
     * Calculate prompt metrics from prompt runs and quality metrics
     */
    private function calculatePromptMetrics($dayStart, $dayEnd): array
    {
        // Only count parent runs (exclude iterations where parent_id IS NOT NULL)
        $started = PromptRun::whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereNull('parent_id')
            ->count();

        $completed = PromptRun::whereBetween('completed_at', [$dayStart, $dayEnd])
            ->where('workflow_stage', WorkflowStage::GenerationCompleted->value)
            ->whereNull('parent_id')
            ->count();

        $completionRate = $started > 0 ? ($completed / $started) : null;

        $avgRating = PromptQualityMetric::whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereNotNull('user_rating')
            ->avg('user_rating');

        return [
            'prompts_started' => $started,
            'prompts_completed' => $completed,
            'prompt_completion_rate' => $completionRate,
            'avg_prompt_rating' => $avgRating,
        ];
    }

    /**
     * Aggregate sessions by UTM source
     * Returns JSON object with UTM source as key and metrics as values
     */
    private function aggregateByUtmSource($dayStart, $dayEnd): ?object
    {
        $sessions = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])
            ->select('id', 'utm_source')
            ->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $grouped = $sessions->groupBy(fn ($session) => $session->utm_source ?? 'direct')
            ->mapWithKeys(function ($group, $source) {
                // Count sessions with conversion events
                $sessionIds = $group->pluck('id')->toArray();
                $conversions = AnalyticsEvent::where('type', 'conversion')
                    ->whereIn('session_id', $sessionIds)
                    ->distinct('session_id')
                    ->count('session_id');

                return [
                    $source => [
                        'sessions' => $group->count(),
                        'conversions' => $conversions,
                    ],
                ];
            });

        return (object) $grouped->all();
    }

    /**
     * Aggregate sessions and registrations by country
     * Returns JSON object with country code as key and metrics as values
     */
    private function aggregateByCountry($dayStart, $dayEnd): ?object
    {
        $sessions = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])
            ->with('visitor:id,country_code')
            ->select('id', 'visitor_id')
            ->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $grouped = $sessions->groupBy(fn ($session) => $session->visitor?->country_code ?? 'unknown')
            ->mapWithKeys(function ($group, $country) use ($dayStart, $dayEnd) {
                // Get registrations for this country
                $countryCodes = $group->map(fn ($session) => $session->visitor?->country_code)
                    ->unique()
                    ->filter()
                    ->all();
                $registrations = 0;

                if ($countryCodes) {
                    $registrations = User::whereBetween('created_at', [$dayStart, $dayEnd])
                        ->whereIn('country_code', $countryCodes)
                        ->count();
                }

                // Count sessions with conversion events
                $sessionIds = $group->pluck('id')->toArray();
                $conversions = AnalyticsEvent::where('type', 'conversion')
                    ->whereIn('session_id', $sessionIds)
                    ->distinct('session_id')
                    ->count('session_id');

                return [
                    $country => [
                        'sessions' => $group->count(),
                        'conversions' => $conversions,
                        'registrations' => $registrations,
                    ],
                ];
            });

        return (object) $grouped->all();
    }

    /**
     * Aggregate sessions and prompt completions by device type
     * Returns JSON object with device type as key and metrics as values
     */
    private function aggregateByDeviceType($dayStart, $dayEnd): ?object
    {
        $sessions = AnalyticsSession::whereBetween('started_at', [$dayStart, $dayEnd])
            ->select('id', 'device_type', 'duration_seconds')
            ->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $grouped = $sessions->groupBy(fn ($session) => $session->device_type ?? 'unknown')
            ->mapWithKeys(function ($group) {
                $avgDuration = $group->whereNotNull('duration_seconds')->avg('duration_seconds');

                // Count prompt_completed events across sessions
                $sessionIds = $group->pluck('id')->toArray();
                $promptsCompleted = AnalyticsEvent::where('name', 'prompt_completed')
                    ->whereIn('session_id', $sessionIds)
                    ->count();

                return [
                    $group->first()->device_type ?? 'unknown' => [
                        'sessions' => $group->count(),
                        'avg_duration_seconds' => $avgDuration,
                        'prompts_completed' => $promptsCompleted,
                    ],
                ];
            });

        return (object) $grouped->all();
    }
}
