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
function countryRoute(string $name, mixed $parameters = [], bool $absolute = true): string
{
    if (! is_array($parameters)) {
        $parameters = [];
    }

    // Add country if not already present
    if (! isset($parameters['country'])) {
        $parameters['country'] = request()->route('country');
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
