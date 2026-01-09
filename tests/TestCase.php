<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    protected string $testLocale = 'en-US';

    protected function setUp(): void
    {
        parent::setUp();

        URL::defaults(['locale' => $this->testLocale]);
        $this->defaultHeaders = array_merge($this->defaultHeaders, [
            'Accept-Language' => $this->testLocale,
        ]);
        $this->withSession(['locale' => $this->testLocale]);
    }

    /**
     * Generate a route URL with locale parameter automatically injected for locale-prefixed routes
     */
    protected function localeRoute($name, $parameters = [], $absolute = true)
    {
        // Ensure parameters is an array
        if (! is_array($parameters)) {
            $parameters = [];
        }

        // Add locale if not already present
        if (! isset($parameters['locale'])) {
            $parameters['locale'] = $this->testLocale;
        }

        return route($name, $parameters, $absolute);
    }

    /**
     * Ensure a URI includes the locale prefix without duplicating it.
     */
    protected function withLocalePrefix(string $uri): string
    {
        $normalized = str_starts_with($uri, '/') ? $uri : "/{$uri}";
        $prefix = "/{$this->testLocale}";

        if ($normalized === $prefix || str_starts_with($normalized, "{$prefix}/")) {
            return $normalized;
        }

        return "{$prefix}{$normalized}";
    }

    /**
     * Make a GET request to a locale-prefixed route
     */
    protected function getLocale($uri, $headers = [])
    {
        return $this->get($this->withLocalePrefix($uri), $headers);
    }

    /**
     * Make a POST request to a locale-prefixed route
     */
    protected function postLocale($uri, $data = [], $headers = [])
    {
        return $this->post($this->withLocalePrefix($uri), $data, $headers);
    }

    /**
     * Make a PATCH request to a locale-prefixed route
     */
    protected function patchLocale($uri, $data = [], $headers = [])
    {
        return $this->patch($this->withLocalePrefix($uri), $data, $headers);
    }

    /**
     * Make a DELETE request to a locale-prefixed route
     */
    protected function deleteLocale($uri, $data = [], $headers = [])
    {
        return $this->delete($this->withLocalePrefix($uri), $data, $headers);
    }

    /**
     * Make a PUT request to a locale-prefixed route
     */
    protected function putLocale($uri, $data = [], $headers = [])
    {
        return $this->put($this->withLocalePrefix($uri), $data, $headers);
    }

    /**
     * Make a POST request with JSON to a locale-prefixed route
     */
    protected function postJsonLocale($uri, $data = [], $headers = [])
    {
        return $this->postJson($this->withLocalePrefix($uri), $data, $headers);
    }
}
