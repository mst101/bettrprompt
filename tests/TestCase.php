<?php

namespace Tests;

use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected string $testCountry = 'gb';

    /**
     * Enable seeding only active languages, currencies, and countries.
     * Reduces seeding from ~447 rows to ~52 rows, speeding up Feature tests by ~70%.
     * Set to false to seed all reference data (useful for specific tests that need all countries).
     */
    protected bool $seedActiveOnly = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Set environment variable for seeders to check
        if ($this->seedActiveOnly) {
            putenv('SEED_ACTIVE_ONLY=true');
        }

        // Clear cache before seeding
        Cache::flush();

        // Seed languages, currencies, and countries for tests that need them (in order of dependencies)
        $this->seed([
            LanguageSeeder::class,
            CurrencySeeder::class,
            CountrySeeder::class,
        ]);

        // Reset environment variable
        if ($this->seedActiveOnly) {
            putenv('SEED_ACTIVE_ONLY=false');
        }

        // Cache is cleared above, so supportedCountries() helper will repopulate on first call

        URL::defaults(['country' => $this->testCountry]);
        $this->defaultHeaders = array_merge($this->defaultHeaders, [
            'Accept-Language' => 'en-GB',
        ]);
    }

    /**
     * Override this in a test class to seed all countries/languages/currencies.
     * Useful for tests that specifically need non-active countries.
     */
    protected function seedFullData(): void
    {
        $this->seedActiveOnly = false;
    }

    /**
     * Generate a route URL with country parameter automatically injected for country-prefixed routes
     */
    protected function countryRoute($name, $parameters = [], $absolute = true)
    {
        // Ensure parameters is an array
        if (! is_array($parameters)) {
            $parameters = [];
        }

        // Add country if not already present
        if (! isset($parameters['country'])) {
            $parameters['country'] = $this->testCountry;
        }

        return route($name, $parameters, $absolute);
    }

    /**
     * Ensure a URI includes the country prefix without duplicating it.
     */
    protected function withCountryPrefix(string $uri): string
    {
        $normalized = str_starts_with($uri, '/') ? $uri : "/{$uri}";
        $prefix = "/{$this->testCountry}";

        if ($normalized === $prefix || str_starts_with($normalized, "{$prefix}/")) {
            return $normalized;
        }

        return "{$prefix}{$normalized}";
    }

    /**
     * Make a GET request to a country-prefixed route
     */
    protected function getCountry($uri, $headers = [])
    {
        return $this->get($this->withCountryPrefix($uri), $headers);
    }

    /**
     * Make a POST request to a country-prefixed route
     */
    protected function postCountry($uri, $data = [], $headers = [])
    {
        return $this->post($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a PATCH request to a country-prefixed route
     */
    protected function patchCountry($uri, $data = [], $headers = [])
    {
        return $this->patch($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a DELETE request to a country-prefixed route
     */
    protected function deleteCountry($uri, $data = [], $headers = [])
    {
        return $this->delete($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a PUT request to a country-prefixed route
     */
    protected function putCountry($uri, $data = [], $headers = [])
    {
        return $this->put($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a POST request with JSON to a country-prefixed route
     */
    protected function postJsonCountry($uri, $data = [], $headers = [])
    {
        return $this->postJson($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a PATCH request with JSON to a country-prefixed route
     */
    protected function patchJsonCountry($uri, $data = [], $headers = [])
    {
        return $this->patchJson($this->withCountryPrefix($uri), $data, $headers);
    }

    /**
     * Make a DELETE request with JSON to a country-prefixed route
     */
    protected function deleteJsonCountry($uri, $data = [], $headers = [])
    {
        return $this->deleteJson($this->withCountryPrefix($uri), $data, $headers);
    }
}
