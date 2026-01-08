<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $testLocale = 'en';

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
     * Make a GET request to a locale-prefixed route
     */
    protected function getLocale($uri, $headers = [])
    {
        return $this->get("/{$this->testLocale}{$uri}", $headers);
    }

    /**
     * Make a POST request to a locale-prefixed route
     */
    protected function postLocale($uri, $data = [], $headers = [])
    {
        return $this->post("/{$this->testLocale}{$uri}", $data, $headers);
    }

    /**
     * Make a PATCH request to a locale-prefixed route
     */
    protected function patchLocale($uri, $data = [], $headers = [])
    {
        return $this->patch("/{$this->testLocale}{$uri}", $data, $headers);
    }

    /**
     * Make a DELETE request to a locale-prefixed route
     */
    protected function deleteLocale($uri, $data = [], $headers = [])
    {
        return $this->delete("/{$this->testLocale}{$uri}", $data, $headers);
    }

    /**
     * Make a PUT request to a locale-prefixed route
     */
    protected function putLocale($uri, $data = [], $headers = [])
    {
        return $this->put("/{$this->testLocale}{$uri}", $data, $headers);
    }

    /**
     * Make a POST request with JSON to a locale-prefixed route
     */
    protected function postJsonLocale($uri, $data = [], $headers = [])
    {
        return $this->postJson("/{$this->testLocale}{$uri}", $data, $headers);
    }
}
