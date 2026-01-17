<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRatingRequest;
use App\Models\PromptRun;
use App\Services\QuestionAnalyticsService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

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
        $visitorId = $this->resolveVisitorId($request->cookie('visitor_id'));

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

    /**
     * Resolve visitor ID from cookie, handling decryption and UUID extraction.
     */
    private function resolveVisitorId(?string $cookieValue): ?string
    {
        if (! $cookieValue) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($cookieValue);
        } catch (DecryptException) {
            $decrypted = $cookieValue;
        }

        return $this->extractUuidFromCookieValue($decrypted);
    }

    /**
     * Extract UUID from cookie value, handling pipe-separated format.
     */
    private function extractUuidFromCookieValue(string $value): ?string
    {
        $segments = array_filter(explode('|', $value));

        foreach (array_reverse($segments) as $segment) {
            if (Str::isUuid($segment)) {
                return $segment;
            }
        }

        // Fallback: return the value if it's a valid UUID
        if (Str::isUuid($value)) {
            return $value;
        }

        return null;
    }
}
