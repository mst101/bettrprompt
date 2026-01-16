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
        $user = auth()->user();
        $visitorId = getVisitorIdFromCookie($request);

        // Ensure user/visitor owns this prompt run or is admin
        if (! $promptRun->canBeAccessedBy($user?->id, $visitorId) && ! $user?->is_admin) {
            abort(403, __('messages.api.unauthorized'));
        }

        // Update question_analytics table
        $this->questionAnalyticsService->updateWithRating(
            promptRun: $promptRun,
            questionId: $questionId,
            rating: $request->validated('rating'),
            explanation: $request->validated('explanation')
        );

        return response()->json(['message' => __('messages.api.question_rating_saved')]);
    }
}
