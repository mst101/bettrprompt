<?php

namespace App\Http\Middleware;

use App\DTOs\LocationData;
use App\Models\Country;
use App\Models\User;
use App\Models\Visitor;
use App\Services\GeolocationService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip visitor tracking for known bots/crawlers
        if ($this->isKnownBot($request)) {
            return $next($request);
        }

        $visitorId = $request->cookie('visitor_id');
        $isNewVisitor = false;

        //        Log::info('TrackVisitor middleware executing', [
        //            'has_cookie' => $visitorId !== null,
        //            'cookie_value' => $visitorId,
        //            'url' => $request->fullUrl(),
        //        ]);

        if ($visitorId) {
            // Check if visitor exists in database
            $visitorExists = Visitor::where('id', $visitorId)->exists();

            if ($visitorExists) {
                // Existing visitor - update their record
                //                Log::info('Updating existing visitor', ['visitor_id' => $visitorId]);
                $this->updateVisitor($visitorId);
            } else {
                // Cookie exists but visitor not in DB (e.g., database was cleared)
                // Create new visitor with new ID
                //                Log::info('Visitor cookie exists but not in database, creating new visitor', [
                //                    'old_visitor_id' => $visitorId,
                //                ]);
                $visitorId = $this->createVisitor($request);
                $isNewVisitor = true;
                //                Log::info('Created new visitor to replace missing one', ['visitor_id' => $visitorId]);
            }
        } else {
            // New visitor - create record BEFORE processing request
            $visitorId = $this->createVisitor($request);
            $isNewVisitor = true;
            //            Log::info('Created new visitor', ['visitor_id' => $visitorId]);
        }

        // Make visitor_id available to the request (merge into cookies for this request)
        // This allows controllers to read the visitor_id even for new visitors
        $request->cookies->set('visitor_id', $visitorId);
        //        Log::info('Set visitor_id in request cookies', ['visitor_id' => $visitorId]);

        // Store current utm params in a cookie (refreshed on each visit)
        // This preserves utm params through redirects
        $utmParams = [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_term' => $request->query('utm_term'),
            'utm_content' => $request->query('utm_content'),
        ];
        // Only set cookie if there are utm params present
        if (array_filter($utmParams)) {
            Cookie::queue('utm_params', json_encode($utmParams), 60); // 1 hour
            Log::info('TrackVisitor: queued utm_params cookie', [
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
            ]);
        }

        // Process the request
        $response = $next($request);

        // Set cookie for new visitors (cookie will be sent with response)
        if ($isNewVisitor) {
            //            Log::info('Queueing visitor_id cookie', ['visitor_id' => $visitorId]);
            Cookie::queue(
                'visitor_id',
                $visitorId,
                1051200, // 2 years in minutes
                '/',
                null,
                true, // secure (HTTPS only in production)
                true, // httpOnly
                false,
                'lax' // sameSite
            );
        }

        return $response;
    }

    /**
     * Create a new visitor record.
     *
     * @throws Throwable
     */
    protected function createVisitor(Request $request): string
    {
        //        Log::info('Creating visitor record', [
        //            'url' => $request->fullUrl(),
        //        ]);

        // Look up referrer by referral code if present
        $referredByUserId = null;
        if ($referralCode = $request->query('ref')) {
            $referrer = User::where('referral_code', $referralCode)->first();
            $referredByUserId = $referrer?->id;
        }

        // Create visitor in a separate transaction that commits immediately
        // This ensures the visitor persists even if the main request fails
        $routeCountryCode = $request->route('country');
        $routeCountry = $routeCountryCode
            ? Country::with(['language', 'currency'])->find($routeCountryCode)
            : null;

        $visitor = DB::transaction(function () use ($request, $referredByUserId, $routeCountry) {
            $locationData = null;

            if ($routeCountry) {
                $languageCode = SetCountry::normalizeLocaleToSupported($routeCountry->language?->id)
                    ?? config('app.fallback_locale', 'en-US');

                $locationData = [
                    'country_code' => $routeCountry->id,
                    'country_name' => $routeCountry->name,
                    'currency_code' => $routeCountry->currency_id,
                    'language_code' => $languageCode,
                    'location_detected_at' => now(),
                ];
            } elseif (config('geoip.enabled') && config('geoip.features.lookup_on_visitor_creation')) {
                try {
                    $geolocationService = new GeolocationService;
                    $locationData = $geolocationService->lookupIp($request->ip());
                } catch (Exception $e) {
                    Log::error('Geolocation lookup failed', [
                        'ip' => $request->ip(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $visitorData = [
                // Don't set 'id' - let HasUuids trait generate it
                'utm_source' => $request->query('utm_source'),
                'utm_medium' => $request->query('utm_medium'),
                'utm_campaign' => $request->query('utm_campaign'),
                'utm_term' => $request->query('utm_term'),
                'utm_content' => $request->query('utm_content'),
                'referrer' => $request->header('referer'),
                'landing_page' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'first_visit_at' => now(),
                'last_visit_at' => now(),
                'referred_by_user_id' => $referredByUserId,
            ];

            // Add location data if available
            if ($locationData !== null) {
                $visitorData = array_merge(
                    $visitorData,
                    $locationData instanceof LocationData ? $locationData->toArray() : $locationData
                );
            }

            return Visitor::create($visitorData);
        });

        return (string) $visitor->id;
    }

    /**
     * Update an existing visitor's last visit time.
     */
    protected function updateVisitor(string $visitorId): void
    {
        $visitor = Visitor::find($visitorId);

        $visitor?->update([
            'last_visit_at' => now(),
        ]);
    }

    /**
     * Determine if the request is from a known bot/crawler.
     * Uses a conservative allowlist of well-known search engines only.
     */
    protected function isKnownBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // Only well-known search engine crawlers
        $knownBots = [
            'googlebot',
            'bingbot',
            'duckduckbot',
            'baiduspider',
            'yandexbot',
            'slurp',       // Yahoo
            'applebot',    // Apple/Siri
        ];

        foreach ($knownBots as $bot) {
            if (str_contains($userAgent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
