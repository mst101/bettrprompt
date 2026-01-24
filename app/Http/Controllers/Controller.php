<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Detect device type from user agent.
     */
    protected function detectDeviceType(Request $request): string
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
     * Extract analytics context from request (session_id, page_path, referrer, device_type).
     * Useful for server-side analytics events.
     */
    protected function getAnalyticsContext(Request $request): array
    {
        // Normalise page path to always have leading slash
        $pagePath = $request->path();
        if (! str_starts_with($pagePath, '/')) {
            $pagePath = '/'.$pagePath;
        }

        // Extract path from referrer URL
        $referrer = null;
        $refererHeader = $request->header('Referer');
        if ($refererHeader) {
            $parsed = parse_url($refererHeader);
            $path = $parsed['path'] ?? '/';
            if (! empty($parsed['query'])) {
                $path .= '?'.$parsed['query'];
            }
            $referrer = $path;
        }

        return [
            'session_id' => $request->header('X-Analytics-Session-Id'),
            'page_path' => $pagePath,
            'referrer' => $referrer,
            'device_type' => $this->detectDeviceType($request),
        ];
    }
}
