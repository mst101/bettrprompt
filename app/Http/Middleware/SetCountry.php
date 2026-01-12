<?php

namespace App\Http\Middleware;

use App\Models\Country;
use App\Models\Visitor;
use App\Services\GeolocationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetCountry
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $countryCode = $request->route('country');

        // Validate country code exists in database
        if ($countryCode && ! Country::where('id', $countryCode)->exists()) {
            abort(404);
        }

        // Resolve country code to full language locale (with caching)
        if ($countryCode) {
            $languageCode = $this->resolveLanguageCode($countryCode, $request);
            app()->setLocale($languageCode);
        }

        return $next($request);
    }

    /**
     * Resolve country code to full language code with user/visitor preferences.
     * Uses Redis caching for performance.
     */
    protected function resolveLanguageCode(string $countryCode, Request $request): string
    {
        $routeCountry = $request->route('country');

        // 1. Check authenticated user preference (with Redis cache)
        if ($user = $request->user()) {
            $userCountry = $user->country_code;
            $cacheKey = $routeCountry
                ? "user.{$user->id}.language.{$routeCountry}.{$userCountry}"
                : "user.{$user->id}.language";

            return Cache::remember(
                $cacheKey,
                3600, // 1 hour
                function () use ($user, $countryCode, $routeCountry, $userCountry) {
                    // Refresh user from database to get latest language_code
                    $freshUser = $user->fresh();
                    $userCountry = $freshUser?->country_code ?? $userCountry;

                    if ($routeCountry) {
                        if (! $userCountry || strtolower($userCountry) !== strtolower($routeCountry)) {
                            return $this->getCountryDefaultLanguage($countryCode);
                        }
                    }

                    $normalized = self::normalizeLocaleToSupported($freshUser?->language_code);

                    return $normalized ?? $this->getCountryDefaultLanguage($countryCode);
                }
            );
        }

        // 2. Check visitor preference (with Redis cache)
        if ($visitorId = $request->cookie('visitor_id')) {
            $visitorCountry = Visitor::where('id', $visitorId)->value('country_code');
            $cacheKey = $routeCountry
                ? "visitor.{$visitorId}.language.{$routeCountry}.{$visitorCountry}"
                : "visitor.{$visitorId}.language";

            return Cache::remember(
                $cacheKey,
                3600, // 1 hour
                function () use ($visitorId, $countryCode, $routeCountry, $visitorCountry) {
                    // Always fetch fresh visitor from database to get latest language_code
                    $visitor = Visitor::find($visitorId);
                    $visitorCountry = $visitor?->country_code ?? $visitorCountry;

                    if ($routeCountry) {
                        if (! $visitorCountry || strtolower($visitorCountry) !== strtolower($routeCountry)) {
                            return $this->getCountryDefaultLanguage($countryCode);
                        }
                    }

                    $normalized = self::normalizeLocaleToSupported($visitor?->language_code);

                    return $normalized ?? $this->getCountryDefaultLanguage($countryCode);
                }
            );
        }

        // 3. Fallback to country default
        return $this->getCountryDefaultLanguage($countryCode);
    }

    /**
     * Get the default language for a country.
     * Caches country language mappings indefinitely.
     */
    protected function getCountryDefaultLanguage(string $countryCode): string
    {
        // Cache country defaults indefinitely (rarely change)
        return Cache::rememberForever(
            "country.{$countryCode}.language",
            function () use ($countryCode) {
                $country = Country::with('language')->find($countryCode);

                if (! $country || ! $country->language) {
                    Log::info("Country {$countryCode} has no language mapping, using fallback");

                    return config('app.fallback_locale', 'en-US');
                }

                $normalized = self::normalizeLocaleToSupported($country->language->id);
                if (! $normalized) {
                    Log::info("Country {$countryCode} language {$country->language->id} not supported, using fallback");

                    return config('app.fallback_locale', 'en-US');
                }

                // Check if language directory exists
                $languageDir = lang_path($normalized);
                if (! is_dir($languageDir)) {
                    Log::info("Language directory {$normalized}/ not found, using fallback");

                    return config('app.fallback_locale', 'en-US');
                }

                return $normalized;
            }
        );
    }

    /**
     * Resolve country code to currency code with user/visitor preferences.
     * Uses Redis caching for performance.
     */
    public function resolveCurrencyCode(string $countryCode, Request $request): string
    {
        $routeCountry = $request->route('country');

        // 1. Check authenticated user preference (with Redis cache)
        if ($user = $request->user()) {
            $userCountry = $user->country_code;
            $cacheKey = $routeCountry
                ? "user.{$user->id}.currency.{$routeCountry}.{$userCountry}"
                : "user.{$user->id}.currency";

            return Cache::remember(
                $cacheKey,
                3600, // 1 hour
                function () use ($user, $countryCode, $routeCountry, $userCountry) {
                    $userCountry = $user->country_code ?? $userCountry;
                    if ($routeCountry) {
                        if (! $userCountry || strtolower($userCountry) !== strtolower($routeCountry)) {
                            return $this->getCountryDefaultCurrency($countryCode);
                        }
                    }

                    return $user->currency_code ?? $this->getCountryDefaultCurrency($countryCode);
                }
            );
        }

        // 2. Check visitor preference (with Redis cache)
        if ($visitorId = $request->cookie('visitor_id')) {
            $visitorCountry = Visitor::where('id', $visitorId)->value('country_code');
            $cacheKey = $routeCountry
                ? "visitor.{$visitorId}.currency.{$routeCountry}.{$visitorCountry}"
                : "visitor.{$visitorId}.currency";

            return Cache::remember(
                $cacheKey,
                3600, // 1 hour
                function () use ($visitorId, $countryCode, $routeCountry, $visitorCountry) {
                    $visitor = Visitor::find($visitorId);
                    $visitorCountry = $visitor?->country_code ?? $visitorCountry;
                    if ($routeCountry) {
                        if (! $visitorCountry || strtolower($visitorCountry) !== strtolower($routeCountry)) {
                            return $this->getCountryDefaultCurrency($countryCode);
                        }
                    }

                    return $visitor?->currency_code ?? $this->getCountryDefaultCurrency($countryCode);
                }
            );
        }

        // 3. Fallback to country default
        return $this->getCountryDefaultCurrency($countryCode);
    }

    /**
     * Get the default currency for a country.
     * Caches country currency mappings indefinitely.
     */
    protected function getCountryDefaultCurrency(string $countryCode): string
    {
        // Cache country defaults indefinitely (rarely change)
        return Cache::rememberForever(
            "country.{$countryCode}.currency",
            function () use ($countryCode) {
                $country = Country::with('currency')->find($countryCode);

                if (! $country || ! $country->currency_id) {
                    return config('app.fallback_currency', 'USD');
                }

                // Check if pricing exists for this currency
                $hasPricing = \App\Models\Price::where('currency_code', $country->currency_id)->exists();

                if (! $hasPricing) {
                    Log::info("No pricing for currency {$country->currency_id}, using fallback");

                    return config('app.fallback_currency', 'USD');
                }

                return $country->currency_id;
            }
        );
    }

    /**
     * Detect the preferred country from the request.
     */
    public static function detectCountry(Request $request): string
    {
        // 1. Check authenticated user preference
        if ($user = $request->user()) {
            if ($user->country_code && Country::where('id', $user->country_code)->exists()) {
                return $user->country_code;
            }
        }

        // 2. Check visitor preference (via cookie, NOT session)
        if ($visitorId = $request->cookie('visitor_id')) {
            $visitor = Visitor::find($visitorId);
            if ($visitor && $visitor->country_code) {
                return $visitor->country_code;
            }
        }

        // 3. Geolocate IP to country code
        try {
            $geolocationService = new GeolocationService;
            $locationData = $geolocationService->lookupIp($request->ip());

            if ($locationData && $locationData->countryCode) {
                $countryCode = $locationData->countryCode;

                // Verify country exists in database
                if (Country::where('id', $countryCode)->exists()) {
                    return $countryCode;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Geolocation detection failed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
        }

        // 4. Fallback to default
        return config('app.fallback_country', 'gb');
    }

    /**
     * Normalize a locale to a supported one (e.g. de -> de-DE, es-MX -> es-ES).
     */
    public static function normalizeLocaleToSupported(?string $locale): ?string
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

        $language = strtolower(explode('-', $normalized)[0]);
        foreach ($supported as $supportedLocale) {
            if (strtolower(explode('-', $supportedLocale)[0]) === $language) {
                return $supportedLocale;
            }
        }

        return null;
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
