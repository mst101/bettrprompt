<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\AdminUserDetailResource;
use App\Http\Resources\Admin\SessionStatsResource;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\PromptRunResource;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->withCount([
                'visitors',
                'visitors as prompt_runs_count' => function ($query) {
                    $query->join('prompt_runs', 'visitors.id', '=', 'prompt_runs.visitor_id');
                },
            ])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => [
                'data' => UserResource::collection($users->items())->resolve(),
                'links' => $users->linkCollection(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Display the specified user
     */
    public function show(Request $request, string $locale, User $user): Response
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $perPage = (int) $request->get('per_page', 15);

        // Validate sort parameters
        $validSortColumns = ['task_description', 'selected_framework', 'workflow_stage', 'created_at'];
        if (! in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }
        if (! in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        $perPage = max(1, min(100, $perPage));

        // Get all prompt runs for this user (both owned and via visitors)
        $promptRunsQuery = $user->promptRuns()
            ->with(['user', 'visitor']);

        // Apply sorting
        if ($sortBy === 'created_at') {
            $promptRunsQuery->orderBy('created_at', $sortDirection);
        } elseif ($sortBy === 'task_description') {
            $promptRunsQuery->orderBy('task_description', $sortDirection);
        } elseif ($sortBy === 'workflow_stage') {
            $promptRunsQuery->orderBy('workflow_stage', $sortDirection);
        } elseif ($sortBy === 'selected_framework') {
            // selected_framework is JSON, so we need to extract the 'name' field
            $promptRunsQuery->orderByRaw("selected_framework->>'name' {$sortDirection}");
        }

        $promptRuns = $promptRunsQuery
            ->paginate($perPage)
            ->withQueryString();

        $promptRunsCount = $user->promptRuns()->count();

        // Load visitor data with sessions for session history
        $user->load([
            'visitor.sessions' => function ($query) {
                $query->orderBy('started_at', 'desc')
                    ->limit(20)
                    ->with(['events' => function ($q) {
                        $q->orderBy('occurred_at', 'asc');
                    }]);
            },
        ]);

        // Calculate session statistics if user has a visitor
        $sessionStats = null;
        if ($user->visitor) {
            $allSessions = $user->visitor->sessions()->get();
            $sessionStats = SessionStatsResource::make([
                'total_sessions' => $allSessions->count(),
                'total_page_views' => $allSessions->sum('page_count'),
                'avg_duration' => round($allSessions->avg('duration_seconds') ?? 0),
                'last_active' => $allSessions->first()?->started_at?->toIso8601String(),
            ])->resolve();
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => AdminUserDetailResource::make($user)->resolve(),
            'promptRuns' => PromptRunResource::collection($promptRuns->items())->resolve(),
            'pagination' => [
                'currentPage' => $promptRuns->currentPage(),
                'lastPage' => $promptRuns->lastPage(),
                'perPage' => $promptRuns->perPage(),
                'total' => $promptRuns->total(),
                'from' => $promptRuns->firstItem(),
                'to' => $promptRuns->lastItem(),
                'nextPageUrl' => $promptRuns->nextPageUrl(),
                'prevPageUrl' => $promptRuns->previousPageUrl(),
                'links' => $promptRuns->linkCollection(),
            ],
            'filters' => [
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'perPage' => $perPage,
            ],
            'promptRunsCount' => $promptRunsCount,
            'sessionStats' => $sessionStats,
        ]);
    }
}
