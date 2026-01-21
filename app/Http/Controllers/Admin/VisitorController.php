<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SessionStatsResource;
use App\Http\Resources\VisitorDetailResource;
use App\Http\Resources\VisitorListResource;
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

        $visitors = Visitor::query()
            ->when($search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhere('country_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->withCount('sessions')
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
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
        $sessionStats = [
            'total_sessions' => $allSessions->count(),
            'total_page_views' => $allSessions->sum('page_count'),
            'avg_duration' => round($allSessions->avg('duration_seconds') ?? 0),
            'bounce_rate' => $allSessions->count() > 0
                ? round(($allSessions->where('is_bounce', true)->count() / $allSessions->count()) * 100, 1)
                : 0,
            'converted' => $allSessions->where('converted', true)->count(),
        ];

        return Inertia::render('Admin/Visitors/Show', [
            'visitor' => VisitorDetailResource::make($visitor)->resolve(),
            'sessionStats' => SessionStatsResource::make($sessionStats)->resolve(),
        ]);
    }
}
