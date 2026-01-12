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
 * Generate a country-prefixed route URL.
 * Automatically injects the current country code from the route parameter.
 *
 * @param  string  $name  Route name
 * @param  array<string, mixed>  $parameters  Additional route parameters
 * @param  bool  $absolute  Whether to generate absolute URL
 * @return string Route URL
 */
function countryRoute(string $name, array $parameters = [], bool $absolute = true): string
{
    $country = \Illuminate\Support\Facades\Route::current()?->parameter('country') ?? 'gb';

    return route($name, ['country' => $country, ...$parameters], $absolute);
}
