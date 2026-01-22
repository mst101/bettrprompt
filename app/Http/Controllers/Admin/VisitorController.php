<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionStatsResource;
use App\Http\Resources\VisitorDetailResource;
use App\Http\Resources\VisitorListResource;
use App\Models\AnalyticsEvent;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VisitorController extends Controller
{
    /**
     * Display a listing of visitors with search functionality
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Validate sort parameters
        $validSortColumns = ['id', 'country_code', 'sessions_count', 'user_name', 'created_at'];
        if (! in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }

        $query = Visitor::query()
            ->when($search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->withCount('sessions')
            ->with('user:id,name,email');

        // Apply sorting
        if ($sortBy === 'sessions_count') {
            $query->orderBy('sessions_count', $sortDirection);
        } elseif ($sortBy === 'user_name') {
            $query->join('users', 'visitors.user_id', '=', 'users.id')
                ->select('visitors.*')
                ->orderBy('users.name', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $visitors = $query
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/Visitors/Index', [
            'visitors' => [
                'data' => VisitorListResource::collection($visitors->items())->resolve(),
                'meta' => [
                    'currentPage' => $visitors->currentPage(),
                    'lastPage' => $visitors->lastPage(),
                    'from' => $visitors->firstItem(),
                    'to' => $visitors->lastItem(),
                    'perPage' => $visitors->perPage(),
                    'path' => $visitors->path(),
                    'total' => $visitors->total(),
                    'hasMorePages' => $visitors->hasMorePages(),
                    'nextPageUrl' => $visitors->nextPageUrl(),
                    'prevPageUrl' => $visitors->previousPageUrl(),
                ],
            ],
            'search' => $search,
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
            ],
        ]);
    }

    /**
     * Display the specified visitor with session history
     */
    public function show(Request $request, string $locale, Visitor $visitor): Response
    {
        $visitor->load([
            'user:id,name,email,created_at,subscription_tier',
            'sessions' => function ($query) {
                $query->orderBy('started_at', 'desc')
                    ->limit(50)
                    ->with(['events' => function ($q) {
                        $q->orderBy('occurred_at', 'asc');
                    }]);
            },
        ]);

        // Calculate session statistics
        $allSessions = $visitor->sessions()->get();
        // Derive total page views from analytics_events (page_view events in visitor's sessions)
        $totalPageViews = AnalyticsEvent::where('name', 'page_view')
            ->whereIn('session_id', $allSessions->pluck('id'))
            ->count();
        // Calculate bounce rate from sessions with ≤1 page view
        $bouncedCount = 0;
        foreach ($allSessions as $session) {
            if ($session->isBounce()) {
                $bouncedCount++;
            }
        }
        $bounceRate = $allSessions->count() > 0
            ? round(($bouncedCount / $allSessions->count()) * 100, 1)
            : 0;

        // Count sessions with conversion events
        $conversions = AnalyticsEvent::where('type', 'conversion')
            ->whereIn('session_id', $allSessions->pluck('id'))
            ->distinct('session_id')
            ->count('session_id');

        $sessionStats = [
            'total_sessions' => $allSessions->count(),
            'total_page_views' => $totalPageViews,
            'avg_duration' => round($allSessions->avg('duration_seconds') ?? 0),
            'bounce_rate' => $bounceRate,
            'converted' => $conversions,
        ];

        return Inertia::render('Admin/Visitors/Show', [
            'visitor' => VisitorDetailResource::make($visitor)->resolve(),
            'sessionStats' => SessionStatsResource::make($sessionStats)->resolve(),
        ]);
    }
}
