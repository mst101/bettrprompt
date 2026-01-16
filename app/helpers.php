<?php

function inertiaPaginated($paginator, $resource): array
{
    return [
        'data' => $resource::collection($paginator->items())->resolve(),
        'meta' => [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'perPage' => $paginator->perPage(),
            'path' => $paginator->path(),
            'total' => $paginator->total(),
            'hasMorePages' => $paginator->hasMorePages(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'prevPageUrl' => $paginator->previousPageUrl(),
        ],
    ];
}

/**
 * Generate a route URL with country parameter automatically injected for country-prefixed routes.
 * Mirrors the frontend's useCountryRoute() composable.
 *
 * @param  string  $name  The route name
 * @param  mixed  $parameters  Route parameters (array or model)
 * @param  bool  $absolute  Whether to return absolute URL
 * @return string The generated route URL
 */
use App\Http\Middleware\SetCountry;

function countryRoute(string $name, mixed $parameters = [], bool $absolute = true): string
{
    if (! is_array($parameters)) {
        $parameters = [];
    }

    // Add country if not already present
    if (! isset($parameters['country'])) {
        $parameters['country'] = request()->route('country')
            ?? SetCountry::detectCountry(request());
    }

    return route($name, $parameters, $absolute);
}

/**
 * Get all supported country codes from the database with caching.
 * Supports all 247 countries in the countries table.
 *
 * @return array<string> List of lowercase ISO country codes (e.g., 'gb', 'us', 'mx')
 */
function supportedCountries(): array
{
    return \Illuminate\Support\Facades\Cache::rememberForever(
        'app.supported_countries_list',
        fn () => \App\Models\Country::pluck('id')->all()
    );
}

/**
 * Extract visitor ID from the request cookie.
 * Handles encrypted cookies (API routes) and pipe-separated format (hash|id).
 *
 * @param  \Illuminate\Http\Request  $request
 * @return string|null The extracted visitor ID, or null if not present
 */
function getVisitorIdFromCookie($request): ?string
{
    $cookieValue = $request->cookie('visitor_id');

    if (! $cookieValue) {
        return null;
    }

    // Try to decrypt if the cookie value looks encrypted (starts with base64-like pattern)
    if (preg_match('/^eyJ/', $cookieValue)) {
        try {
            $cookieValue = \Illuminate\Support\Facades\Crypt::decryptString($cookieValue);
        } catch (\Exception $e) {
            \Log::debug('Failed to decrypt visitor_id cookie', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // Extract UUID from pipe-separated format (finds the segment that is a valid UUID)
    $segments = array_filter(explode('|', $cookieValue));
    foreach (array_reverse($segments) as $segment) {
        if (\Illuminate\Support\Str::isUuid($segment)) {
            return $segment;
        }
    }

    // Fallback: return the value as-is if it looks like a UUID
    if (\Illuminate\Support\Str::isUuid($cookieValue)) {
        return $cookieValue;
    }

    return null;
}
