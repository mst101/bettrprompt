<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnalyticsEventsRequest;
use App\Jobs\ProcessAnalyticsEvents;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $visitorId = $this->resolveVisitorId($request->cookie('visitor_id'));
        $userId = $request->user()?->id;
        $sessionId = $request->header('X-Analytics-Session-Id');
        $pagePath = $request->header('Referer');
        $deviceType = $this->detectDeviceType($request);

        // Extract UTM parameters from the referrer (page URL) first
        $referrer = $request->header('Referer');
        $utmParams = $this->extractUtmFromUrl($referrer);

        // Fall back to utm_params cookie if not found in referrer
        // (cookie is set by TrackVisitor middleware and survives redirects)
        if (! array_filter($utmParams)) {
            $cookieValue = $request->cookie('utm_params');
            $utmParams = $this->extractUtmFromCookie($cookieValue);
            Log::info('AnalyticsEventController: using utm_params from cookie', [
                'cookie_value' => $cookieValue,
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
            ]);
        } else {
            Log::info('AnalyticsEventController: using utm_params from referrer', [
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
            ]);
        }

        // Dispatch job to process events asynchronously
        ProcessAnalyticsEvents::dispatch(
            events: $validated['events'],
            visitorId: $visitorId,
            userId: $userId,
            sessionId: $sessionId,
            pageContext: [
                'page_path' => $pagePath,
                'device_type' => $deviceType,
                'referrer' => $referrer,
                'user_agent' => $request->userAgent(),
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
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

    /**
     * Extract UTM parameters from the utm_params cookie
     */
    private function extractUtmFromCookie(?string $cookieValue): array
    {
        $params = ['utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null];

        if (! $cookieValue) {
            return $params;
        }

        try {
            $decoded = json_decode($cookieValue, true);
            if (is_array($decoded)) {
                return [
                    'utm_source' => $decoded['utm_source'] ?? null,
                    'utm_medium' => $decoded['utm_medium'] ?? null,
                    'utm_campaign' => $decoded['utm_campaign'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // Invalid JSON in cookie, return defaults
        }

        return $params;
    }

    /**
     * Extract UTM parameters from a URL
     */
    private function extractUtmFromUrl(?string $url): array
    {
        $params = ['utm_source' => null, 'utm_medium' => null, 'utm_campaign' => null];

        if (! $url) {
            return $params;
        }

        $parsed = parse_url($url);
        if (! isset($parsed['query'])) {
            return $params;
        }

        parse_str($parsed['query'], $query);

        return [
            'utm_source' => $query['utm_source'] ?? null,
            'utm_medium' => $query['utm_medium'] ?? null,
            'utm_campaign' => $query['utm_campaign'] ?? null,
        ];
    }

    /**
     * Normalize the visitor ID cookie so we never pass a pipe-delimited value to the job.
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
     * Prefer the UUID segment if the cookie includes any metadata.
     */
    private function extractUuidFromCookieValue(string $value): string
    {
        $segments = array_filter(explode('|', $value));

        foreach (array_reverse($segments) as $segment) {
            if (Str::isUuid($segment)) {
                return $segment;
            }
        }

        return $value;
    }
}
