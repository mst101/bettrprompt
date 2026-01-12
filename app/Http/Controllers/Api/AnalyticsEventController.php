<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnalyticsEventsRequest;
use App\Jobs\ProcessAnalyticsEvents;
use Illuminate\Http\JsonResponse;

class AnalyticsEventController extends Controller
{
    /**
     * Store analytics events.
     * Non-blocking: validates, dispatches job, returns 200 immediately.
     */
    public function store(StoreAnalyticsEventsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Get context from request
        $visitorId = $request->cookie('visitor_id');
        $userId = $request->user()?->id;
        $sessionId = $request->header('X-Analytics-Session-Id');
        $pagePath = $request->header('Referer');
        $deviceType = $this->detectDeviceType($request);

        // Dispatch job to process events asynchronously
        ProcessAnalyticsEvents::dispatch(
            events: $validated['events'],
            visitorId: $visitorId,
            userId: $userId,
            sessionId: $sessionId,
            pageContext: [
                'page_path' => $pagePath,
                'device_type' => $deviceType,
                'referrer' => $request->header('Referer'),
                'user_agent' => $request->userAgent(),
                'country_code' => $request->route('country'),
            ]
        );

        // Return 200 immediately (non-blocking)
        return response()->json([
            'success' => true,
            'message' => 'Events queued for processing',
        ], 200);
    }

    /**
     * Detect device type from user agent
     */
    private function detectDeviceType($request): string
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        if (preg_match('/(mobile|android|iphone|ipod|blackberry|windows phone)/', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/(tablet|ipad|kindle|playbook)/', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }
}
