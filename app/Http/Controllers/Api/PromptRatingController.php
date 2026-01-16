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
        // Ensure user owns this prompt run or is admin
        if ($promptRun->user_id !== auth()->id() && ! auth()->user()?->is_admin) {
            abort(403, 'Unauthorized');
        }

        // Update prompt_quality_metrics table
        $this->promptQualityService->recordMetrics([
            'prompt_run_id' => $promptRun->id,
            'user_rating' => $request->validated('rating'),
            'rating_explanation' => $request->validated('explanation'),
        ]);

        return response()->json(['message' => 'Rating saved successfully']);
    }
}
