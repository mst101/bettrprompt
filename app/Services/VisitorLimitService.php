<?php

namespace App\Services;

use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class VisitorLimitService
{
    public function hasExceededLimit(?string $visitorId): bool
    {
        if ($visitorId === null) {
            return false;
        }

        $visitor = Visitor::find($visitorId);

        return $visitor?->hasCompletedPrompts() ?? false;
    }

    public function checkLimit(bool $isAuthenticated, ?string $visitorId): bool
    {
        if ($isAuthenticated) {
            return true;
        }

        return ! $this->hasExceededLimit($visitorId);
    }

    public function createWebErrorResponse(): RedirectResponse
    {
        return back()->with('error', __('messages.prompt_builder.visitor_limit_reached'));
    }

    public function createApiErrorResponse(): JsonResponse
    {
        return response()->json([
            'error' => __('messages.prompt_builder.visitor_limit_reached'),
        ], 403);
    }
}
