<?php

namespace App\Http\Middleware;

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
     * Detect the preferred locale from the request
     */
    public static function detectLocale(Request $request): string
    {
        // 1. Check authenticated user preference
        $user = $request->user();
        if ($user && $user->language_code) {
            $userLocale = $user->language_code;
            if (in_array($userLocale, config('app.supported_locales'))) {
                return $userLocale;
            }
        }

        // 2. Check session preference
        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, config('app.supported_locales'))) {
            return $sessionLocale;
        }

        // 3. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage(config('app.supported_locales'));
        if ($browserLocale) {
            return $browserLocale;
        }

        // 4. Fallback to default
        return config('app.locale', 'en');
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
