<?php

namespace App\Http\Middleware;

use App\Models\Visitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');

        // Validate locale is supported
        if ($locale && ! in_array($locale, config('app.supported_locales'))) {
            abort(404);
        }

        // Set the application locale
        if ($locale) {
            app()->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Detect the preferred country from the request
     */
    public static function detectCountry(Request $request): string
    {
        // 1. Check authenticated user preference
        $user = $request->user();
        if ($user && $user->country_code) {
            return $user->country_code;
        }

        // 2. Check visitor preference (via cookie, NOT session)
        if ($visitorId = $request->cookie('visitor_id')) {
            $visitor = Visitor::find($visitorId);
            if ($visitor && $visitor->country_code) {
                return $visitor->country_code;
            }
        }

        // 3. Geolocate IP to country code
        // TODO: Implement geolocation detection in Phase 7

        // 4. Fallback to default
        return config('app.fallback_country', 'gb');
    }

    /**
     * Detect the preferred locale from the request
     */
    public static function detectLocale(Request $request): string
    {
        // 0. Prefer explicit route locale
        $routeLocale = $request->route('locale');
        if ($routeLocale && in_array($routeLocale, config('app.supported_locales'))) {
            return $routeLocale;
        }

        // 1. Check authenticated user preference
        $user = $request->user();
        if ($user && $user->language_code) {
            $userLocale = self::normalizeLocale($user->language_code);
            if ($userLocale && in_array($userLocale, config('app.supported_locales'))) {
                return $userLocale;
            }
        }

        // 2. Check session preference
        $sessionLocale = self::normalizeLocale(session('locale'));
        if ($sessionLocale && in_array($sessionLocale, config('app.supported_locales'))) {
            return $sessionLocale;
        }

        // 3. Check browser Accept-Language header
        $browserLocale = self::normalizeLocale(
            $request->getPreferredLanguage(config('app.supported_locales'))
        );
        if ($browserLocale) {
            return $browserLocale;
        }

        // 4. Fallback to default
        return config('app.locale', 'en');
    }

    /**
     * Normalize locale formats (e.g. en_GB -> en-GB) and casing.
     */
    private static function normalizeLocale(?string $locale): ?string
    {
        if (! $locale) {
            return null;
        }

        $normalized = str_replace('_', '-', $locale);
        $supported = config('app.supported_locales', []);

        foreach ($supported as $supportedLocale) {
            if (strtolower($supportedLocale) === strtolower($normalized)) {
                return $supportedLocale;
            }
        }

        return $normalized;
    }

    /**
     * Check if a locale uses RTL text direction
     */
    public static function isRtl(?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();

        return in_array($locale, config('app.rtl_locales', []));
    }

    /**
     * Get the text direction for a locale
     */
    public static function getDirection(?string $locale = null): string
    {
        return self::isRtl($locale) ? 'rtl' : 'ltr';
    }
}
