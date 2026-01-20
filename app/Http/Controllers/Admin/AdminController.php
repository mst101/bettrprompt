<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index(Request $request): Response
    {
        // Date range (default: last 30 days)
        $endDate = $request->input('end_date', now()->endOfDay());
        $startDate = $request->input('start_date', now()->subDays(29)->startOfDay());

        // Get metrics from aggregation tables + today's real-time data
        $trafficMetrics = $this->getTrafficMetrics($startDate, $endDate);
        $conversionMetrics = $this->getConversionMetrics($startDate, $endDate);
        $promptMetrics = $this->getPromptMetrics($startDate, $endDate);
        $topSources = $this->getTopSources($startDate, $endDate);
        $topCountries = $this->getTopCountries($startDate, $endDate);

        return Inertia::render('Admin/Dashboard', [
            'traffic' => $trafficMetrics,
            'conversions' => $conversionMetrics,
            'prompts' => $promptMetrics,
            'topSources' => $topSources,
            'topCountries' => $topCountries,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * Display domain analytics dashboard
     */
    public function domainAnalytics(): Response
    {
        return Inertia::render('Admin/DomainAnalytics/Index');
    }

    /**
     * Get traffic metrics (visitors, sessions, page views, etc.)
     */
    private function getTrafficMetrics($startDate, $endDate): array
    {
        $today = now()->startOfDay();

        // Get historical data from aggregates (yesterday and earlier)
        $historical = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $today->copy()->subDay()->toDateString(),
        ])->get();

        // Get today's data from raw sessions (real-time)
        $todaySessions = AnalyticsSession::where('started_at', '>=', $today)->get();

        $totalVisitors = $historical->sum('unique_visitors') + $todaySessions->unique('visitor_id')->count();
        $totalSessions = $historical->sum('total_sessions') + $todaySessions->count();
        $totalPageViews = $historical->sum('total_page_views') + $todaySessions->sum('page_count');
        $avgDuration = $historical->avg('avg_session_duration_seconds');
        if ($todaySessions->count() > 0) {
            $avgDuration = (($avgDuration ?? 0) + $todaySessions->avg('duration_seconds')) / 2;
        }
        $avgBounceRate = $historical->avg('bounce_rate');
        if ($todaySessions->count() > 0) {
            $todayBounceRate = $todaySessions->where('is_bounce', true)->count() / $todaySessions->count();
            $avgBounceRate = (($avgBounceRate ?? 0) + $todayBounceRate) / 2;
        }

        // Build daily trend data
        $dailyTrend = $historical->map(function ($stat) {
            return [
                'date' => $stat->date->toDateString(),
                'visitors' => $stat->unique_visitors,
                'sessions' => $stat->total_sessions,
            ];
        })->toArray();

        // Add today's data to trend
        if ($todaySessions->count() > 0) {
            $dailyTrend[] = [
                'date' => $today->toDateString(),
                'visitors' => $todaySessions->unique('visitor_id')->count(),
                'sessions' => $todaySessions->count(),
            ];
        }

        return [
            'unique_visitors' => $totalVisitors,
            'total_sessions' => $totalSessions,
            'total_page_views' => $totalPageViews,
            'avg_session_duration' => round($avgDuration ?? 0),
            'avg_bounce_rate' => round(($avgBounceRate ?? 0) * 100, 1),
            'daily_trend' => $dailyTrend,
        ];
    }

    /**
     * Get conversion metrics (registrations, subscriptions, etc.)
     */
    private function getConversionMetrics($startDate, $endDate): array
    {
        $stats = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->get();

        $totalRegistrations = $stats->sum('registrations');
        $totalProSubs = $stats->sum('subscriptions_pro');
        $totalPrivateSubs = $stats->sum('subscriptions_private');
        $totalVisitors = $stats->sum('unique_visitors');

        return [
            'registrations' => $totalRegistrations,
            'pro_subscriptions' => $totalProSubs,
            'private_subscriptions' => $totalPrivateSubs,
            'conversion_rate' => $totalVisitors > 0
                ? round(($totalRegistrations / $totalVisitors) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get prompt metrics (started, completed, completion rate, rating)
     */
    private function getPromptMetrics($startDate, $endDate): array
    {
        $stats = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->get();

        $promptsStarted = $stats->sum('prompts_started');
        $promptsCompleted = $stats->sum('prompts_completed');

        return [
            'prompts_started' => $promptsStarted,
            'prompts_completed' => $promptsCompleted,
            'completion_rate' => $promptsStarted > 0
                ? round(($promptsCompleted / $promptsStarted) * 100, 1)
                : 0,
            'avg_rating' => round($stats->avg('avg_prompt_rating') ?? 0, 1),
        ];
    }

    /**
     * Get top UTM sources by session count
     */
    private function getTopSources($startDate, $endDate): array
    {
        $stats = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->whereNotNull('by_utm_source')->get();

        // Aggregate JSON data
        $sources = [];
        foreach ($stats as $stat) {
            $bySource = $stat->by_utm_source ?? [];
            foreach ($bySource as $source => $data) {
                if (! isset($sources[$source])) {
                    $sources[$source] = ['sessions' => 0, 'conversions' => 0];
                }
                $sources[$source]['sessions'] += $data['sessions'] ?? 0;
                $sources[$source]['conversions'] += $data['conversions'] ?? 0;
            }
        }

        // Sort by sessions desc, take top 10
        uasort($sources, fn ($a, $b) => $b['sessions'] <=> $a['sessions']);

        return array_slice($sources, 0, 10, true);
    }

    /**
     * Get top countries by session count
     */
    private function getTopCountries($startDate, $endDate): array
    {
        $stats = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->whereNotNull('by_country')->get();

        // Aggregate JSON data
        $countries = [];
        foreach ($stats as $stat) {
            $byCountry = $stat->by_country ?? [];
            foreach ($byCountry as $country => $data) {
                if (! isset($countries[$country])) {
                    $countries[$country] = ['sessions' => 0, 'conversions' => 0];
                }
                $countries[$country]['sessions'] += $data['sessions'] ?? 0;
                $countries[$country]['conversions'] += $data['conversions'] ?? 0;
            }
        }

        // Sort by sessions desc, take top 10
        uasort($countries, fn ($a, $b) => $b['sessions'] <=> $a['sessions']);

        return array_slice($countries, 0, 10, true);
    }
}
