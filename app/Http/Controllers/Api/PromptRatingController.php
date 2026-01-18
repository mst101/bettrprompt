<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePromptRatingRequest;
use App\Models\PromptRun;
use App\Services\PromptQualityService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

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
        $visitorId = $this->resolveVisitorId($request->cookie('visitor_id'));

        // Return 401 for unauthenticated users with no visitor ID
        if (! $user && ! $visitorId) {
            abort(401, __('messages.api.unauthorized'));
        }

        // Ensure user/visitor owns this prompt run or is admin
        if (! $promptRun->canBeAccessedBy($user?->id, $visitorId) && ! $user?->is_admin) {
            abort(403, __('messages.api.unauthorized'));
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

        return response()->json(['message' => __('messages.api.prompt_rating_saved')]);
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
