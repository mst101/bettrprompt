<?php

namespace App\Http\Middleware;

use App\Models\Country;
use App\Models\Visitor;
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
        // 1. Check authenticated user preference (with Redis cache)
        if ($user = $request->user()) {
            return Cache::remember(
                "user.{$user->id}.language",
                3600, // 1 hour
                function () use ($user, $countryCode) {
                    return $user->language_code ?? $this->getCountryDefaultLanguage($countryCode);
                }
            );
        }

        // 2. Check visitor preference (with Redis cache)
        if ($visitorId = $request->cookie('visitor_id')) {
            return Cache::remember(
                "visitor.{$visitorId}.language",
                3600, // 1 hour
                function () use ($visitorId, $countryCode) {
                    $visitor = Visitor::find($visitorId);

                    return $visitor?->language_code ?? $this->getCountryDefaultLanguage($countryCode);
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

                // Check if language directory exists
                $languageDir = lang_path($country->language->id);
                if (! is_dir($languageDir)) {
                    Log::info("Language directory {$country->language->id}/ not found, using fallback");

                    return config('app.fallback_locale', 'en-US');
                }

                return $country->language->id;
            }
        );
    }

    /**
     * Resolve country code to currency code with user/visitor preferences.
     * Uses Redis caching for performance.
     */
    public function resolveCurrencyCode(string $countryCode, Request $request): string
    {
        // 1. Check authenticated user preference (with Redis cache)
        if ($user = $request->user()) {
            return Cache::remember(
                "user.{$user->id}.currency",
                3600, // 1 hour
                function () use ($user, $countryCode) {
                    return $user->currency_code ?? $this->getCountryDefaultCurrency($countryCode);
                }
            );
        }

        // 2. Check visitor preference (with Redis cache)
        if ($visitorId = $request->cookie('visitor_id')) {
            return Cache::remember(
                "visitor.{$visitorId}.currency",
                3600, // 1 hour
                function () use ($visitorId, $countryCode) {
                    $visitor = Visitor::find($visitorId);

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
        // TODO: Implement geolocation detection

        // 4. Fallback to default
        return config('app.fallback_country', 'gb');
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
