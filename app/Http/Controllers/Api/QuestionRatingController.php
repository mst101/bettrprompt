<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRatingRequest;
use App\Models\PromptRun;
use App\Services\QuestionAnalyticsService;

class QuestionRatingController extends Controller
{
    public function __construct(
        private QuestionAnalyticsService $questionAnalyticsService
    ) {}

    public function store(
        StoreQuestionRatingRequest $request,
        PromptRun $promptRun,
        string $questionId
    ) {
        // Ensure user owns this prompt run or is admin
        if ($promptRun->user_id !== auth()->id() && ! auth()->user()?->is_admin) {
            abort(403, 'Unauthorized');
        }

        // Update question_analytics table
        $this->questionAnalyticsService->updateWithRating(
            promptRunId: $promptRun->id,
            questionId: $questionId,
            rating: $request->validated('rating'),
            explanation: $request->validated('explanation')
        );

        return response()->json(['message' => 'Question rating saved successfully']);
    }
}
