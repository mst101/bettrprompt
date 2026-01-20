<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrafficAnalyticsController extends Controller
{
    /**
     * Display traffic analytics dashboard with sources, countries, devices, and pages
     */
    public function index(Request $request): Response
    {
        // Date range (default: last 30 days)
        $endDate = $request->has('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : now()->endOfDay();
        $startDate = $request->has('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        return Inertia::render('Admin/TrafficAnalytics/Index', [
            'sources' => $this->getSourceAnalytics($startDate, $endDate),
            'countries' => $this->getCountryAnalytics($startDate, $endDate),
            'devices' => $this->getDeviceAnalytics($startDate, $endDate),
            'topPages' => $this->getTopPages($startDate, $endDate),
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * Get source analytics (UTM sources breakdown)
     */
    private function getSourceAnalytics($startDate, $endDate): array
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
                    $sources[$source] = [
                        'sessions' => 0,
                        'conversions' => 0,
                        'visitors' => 0,
                    ];
                }
                $sources[$source]['sessions'] += $data['sessions'] ?? 0;
                $sources[$source]['conversions'] += $data['conversions'] ?? 0;
                $sources[$source]['visitors'] += $data['visitors'] ?? 0;
            }
        }

        // Calculate conversion rates and sort by sessions
        $sourcesList = [];
        foreach ($sources as $source => $data) {
            $sourcesList[] = [
                'source' => $source,
                'sessions' => $data['sessions'],
                'conversions' => $data['conversions'],
                'visitors' => $data['visitors'],
                'conversion_rate' => $data['visitors'] > 0
                    ? round(($data['conversions'] / $data['visitors']) * 100, 1)
                    : 0,
            ];
        }

        // Sort by sessions descending
        usort($sourcesList, fn ($a, $b) => $b['sessions'] <=> $a['sessions']);

        return $sourcesList;
    }

    /**
     * Get country analytics breakdown
     */
    private function getCountryAnalytics($startDate, $endDate): array
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
                    $countries[$country] = [
                        'sessions' => 0,
                        'conversions' => 0,
                        'visitors' => 0,
                    ];
                }
                $countries[$country]['sessions'] += $data['sessions'] ?? 0;
                $countries[$country]['conversions'] += $data['conversions'] ?? 0;
                $countries[$country]['visitors'] += $data['visitors'] ?? 0;
            }
        }

        // Calculate conversion rates and sort by sessions
        $countriesList = [];
        foreach ($countries as $country => $data) {
            $countriesList[] = [
                'country' => $country,
                'sessions' => $data['sessions'],
                'conversions' => $data['conversions'],
                'visitors' => $data['visitors'],
                'conversion_rate' => $data['visitors'] > 0
                    ? round(($data['conversions'] / $data['visitors']) * 100, 1)
                    : 0,
            ];
        }

        // Sort by sessions descending
        usort($countriesList, fn ($a, $b) => $b['sessions'] <=> $a['sessions']);

        return $countriesList;
    }

    /**
     * Get device type analytics breakdown
     */
    private function getDeviceAnalytics($startDate, $endDate): array
    {
        $stats = AnalyticsDailyStat::whereBetween('date', [
            $startDate->toDateString(),
            $endDate->toDateString(),
        ])->whereNotNull('by_device_type')->get();

        // Aggregate JSON data
        $devices = [];
        foreach ($stats as $stat) {
            $byDevice = $stat->by_device_type ?? [];
            foreach ($byDevice as $device => $data) {
                if (! isset($devices[$device])) {
                    $devices[$device] = [
                        'sessions' => 0,
                        'avg_duration' => 0,
                        'bounce_rate' => 0,
                        'count' => 0,
                    ];
                }
                $devices[$device]['sessions'] += $data['sessions'] ?? 0;
                $devices[$device]['avg_duration'] += $data['avg_duration'] ?? 0;
                $devices[$device]['bounce_rate'] += $data['bounce_rate'] ?? 0;
                $devices[$device]['count']++;
            }
        }

        // Calculate averages and format
        $devicesList = [];
        foreach ($devices as $device => $data) {
            $devicesList[] = [
                'device' => ucfirst($device),
                'sessions' => $data['sessions'],
                'avg_duration' => $data['count'] > 0
                    ? round($data['avg_duration'] / $data['count'])
                    : 0,
                'bounce_rate' => $data['count'] > 0
                    ? round($data['bounce_rate'] / $data['count'], 1)
                    : 0,
            ];
        }

        // Sort by sessions descending
        usort($devicesList, fn ($a, $b) => $b['sessions'] <=> $a['sessions']);

        return $devicesList;
    }

    /**
     * Get top pages by view count
     */
    private function getTopPages($startDate, $endDate): array
    {
        $pages = AnalyticsEvent::where('name', 'page_view')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->select('page_path', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT visitor_id) as unique_visitors'))
            ->groupBy('page_path')
            ->orderByDesc('views')
            ->limit(50)
            ->get();

        return $pages->map(function ($page) {
            return [
                'path' => $page->page_path,
                'views' => $page->views,
                'unique_visitors' => $page->unique_visitors,
            ];
        })->toArray();
    }
}
