<?php

namespace App\Http\Middleware;

use App\Services\ExperimentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AssignExperiments
{
    public function __construct(
        private ExperimentService $experimentService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get visitor_id from request (set by TrackVisitor middleware)
        $visitorId = $request->cookie('visitor_id');
        $userId = $request->user()?->id;

        if ($visitorId) {
            // Get active experiment assignments for this visitor
            $assignments = $this->experimentService->getActiveAssignments($visitorId);

            // Store in request for Inertia to access
            $request->merge([
                'experiment_assignments' => $assignments,
            ]);
        }

        return $next($request);
    }
}
