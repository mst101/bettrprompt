<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromptRatingRequest;
use App\Models\PromptRun;
use App\Services\PromptQualityService;

class PromptRatingController extends Controller
{
    public function __construct(
        private PromptQualityService $promptQualityService
    ) {}

    public function store(StorePromptRatingRequest $request, PromptRun $promptRun)
    {
        // Authorization is checked here rather than via middleware to allow flexibility
        // in different client contexts (browser sessions, API tokens, tests)
        $user = auth()->user();

        // Return 401 for unauthenticated users, 403 for insufficient permissions
        if (! $user) {
            abort(401, 'Unauthenticated');
        }

        if ($promptRun->user_id !== $user->id && ! $user->is_admin) {
            abort(403, 'Unauthorized');
        }

        // Update prompt_quality_metrics table
        // Check if explanation was explicitly provided in the request (even if empty)
        $validated = $request->validated();
        $hasExplanation = array_key_exists('explanation', $validated);

        $this->promptQualityService->recordMetrics(
            promptRun: $promptRun,
            userRating: $validated['rating'],
            ratingExplanation: $validated['explanation'] ?? null,
            shouldUpdateExplanation: $hasExplanation,
        );

        return response()->json(['message' => 'Rating saved successfully']);
    }
}
