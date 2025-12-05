<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GeoIP Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for MaxMind GeoLite2 IP geolocation service
    |
    */

    'enabled' => env('GEOIP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | MaxMind Settings
    |--------------------------------------------------------------------------
    */

    'maxmind' => [
        'account_id' => env('MAXMIND_ACCOUNT_ID'),
        'license_key' => env('MAXMIND_LICENSE_KEY'),
        // Path to the local GeoLite2 City database file
        'database_path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache geolocation lookups to avoid repeated database queries
    |
    */

    'cache' => [
        'ttl' => env('GEOIP_CACHE_TTL', 30 * 24 * 60 * 60), // 30 days
        'prefix' => 'geoip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy Settings
    |--------------------------------------------------------------------------
    |
    | Coordinates are automatically anonymised to ~1km accuracy
    | by rounding to 2 decimal places
    |
    */

    'privacy' => [
        'anonymise_coordinates' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */

    'features' => [
        // Lookup geolocation on visitor creation
        'lookup_on_visitor_creation' => true,
        // Lookup geolocation on user registration
        'lookup_on_registration' => true,
    ],
];
