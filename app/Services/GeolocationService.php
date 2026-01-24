<?php

namespace App\Services;

use App\DTOs\LocationData;
use Exception;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeolocationService
{
    public private(set) ?Reader $reader = null;

    /**
     * Maximum age of cached results (30 days)
     */
    private const CACHE_TTL = 30 * 24 * 60 * 60;

    /**
     * Country to currency mapping (lowercase country codes)
     */
    private const COUNTRY_CURRENCY_MAP = [
        'us' => 'USD',
        'gb' => 'GBP',
        'ca' => 'CAD',
        'au' => 'AUD',
        'nz' => 'NZD',
        'eu' => 'EUR',
        'de' => 'EUR',
        'fr' => 'EUR',
        'it' => 'EUR',
        'es' => 'EUR',
        'nl' => 'EUR',
        'be' => 'EUR',
        'at' => 'EUR',
        'ch' => 'CHF',
        'se' => 'SEK',
        'no' => 'NOK',
        'dk' => 'DKK',
        'jp' => 'JPY',
        'cn' => 'CNY',
        'in' => 'INR',
        'br' => 'BRL',
        'mx' => 'MXN',
        'za' => 'ZAR',
        'sg' => 'SGD',
        'hk' => 'HKD',
    ];

    /**
     * Country to language code mapping (BCP 47 locale format, lowercase country codes)
     */
    private const COUNTRY_LANGUAGE_MAP = [
        'us' => 'en-US',
        'gb' => 'en-GB',
        'ca' => 'en-CA',
        'au' => 'en-AU',
        'nz' => 'en-NZ',
        'de' => 'de-DE',
        'fr' => 'fr-FR',
        'it' => 'it-IT',
        'es' => 'es-ES',
        'nl' => 'nl-NL',
        'be' => 'nl-BE',
        'at' => 'de-AT',
        'ch' => 'de-CH',
        'se' => 'sv-SE',
        'no' => 'nb-NO',
        'dk' => 'da-DK',
        'jp' => 'ja-JP',
        'cn' => 'zh-CN',
        'in' => 'en-IN',
        'br' => 'pt-BR',
        'mx' => 'es-MX',
        'za' => 'en-ZA',
        'sg' => 'en-SG',
        'hk' => 'zh-HK',
    ];

    /**
     * Perform IP geolocation lookup
     */
    public function lookupIp(string $ip): ?LocationData
    {
        // Reject private IPs (unless in development mode)
        if ($this->isPrivateIp($ip)) {
            if (! config('geoip.development.allow_private_ip_lookup')) {
                Log::debug("Skipping geolocation for private IP: $ip");

                return null;
            }
            //            Log::debug("Performing geolocation lookup for private IP in development: $ip");

            // Return a default location for development with private IPs
            return $this->getDefaultLocationForDevelopment();
        }

        // Try cache first
        $cached = Cache::get($this->getCacheKey($ip));
        if ($cached !== null) {
            Log::debug("Geolocation cache hit for IP: $ip");

            return LocationData::fromArray($cached);
        }

        try {
            $reader = $this->getReader();
            if ($reader === null) {
                Log::warning('MaxMind database reader unavailable for IP lookup');

                return null;
            }

            $record = $reader->city($ip);

            $timezone = $record->location->timeZone;
            // MaxMind returns uppercase country codes (GB, US), convert to lowercase
            $countryCode = strtolower($record->country->isoCode);

            $locationData = new LocationData(
                countryCode: $countryCode,
                countryName: $record->country->name,
                region: $record->mostSpecificSubdivision->name,
                city: $record->city->name,
                timezone: $timezone,
                currencyCode: $this->getCurrencyForCountry($countryCode),
                latitude: $this->anonymiseCoordinate($record->location->latitude),
                longitude: $this->anonymiseCoordinate($record->location->longitude),
                languageCode: $this->getLanguageForCountry($countryCode),
                detectedAt: now(),
            );

            // Cache the result
            Cache::put(
                $this->getCacheKey($ip),
                $locationData->toArray(),
                self::CACHE_TTL
            );

            Log::debug("Geolocation lookup successful for IP: $ip, Country: $countryCode");

            return $locationData;
        } catch (AddressNotFoundException) {
            Log::debug("IP address not found in MaxMind database: $ip");

            return null;
        } catch (Exception $e) {
            Log::error("Geolocation lookup failed for IP $ip: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Check if an IP address is private
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Get cache key for an IP address
     */
    private function getCacheKey(string $ip): string
    {
        return "geoip:$ip";
    }

    /**
     * Get the MaxMind database reader instance
     */
    private function getReader(): ?Reader
    {
        if ($this->reader !== null) {
            return $this->reader;
        }

        $databasePath = config('geoip.maxmind.database_path');

        if (! file_exists($databasePath)) {
            Log::warning("MaxMind database not found at: $databasePath");

            return null;
        }

        try {
            $this->reader = new Reader($databasePath);

            return $this->reader;
        } catch (Exception $e) {
            Log::error("Failed to initialise MaxMind reader: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get currency code for a country
     */
    private function getCurrencyForCountry(?string $countryCode): ?string
    {
        if ($countryCode === null) {
            return null;
        }

        // Special case for Eurozone (lowercase country codes)
        $euCountries = [
            'de', 'fr', 'it', 'es', 'nl', 'be', 'at', 'ie', 'pt', 'gr', 'cy', 'mt', 'sk', 'si', 'lv', 'lt', 'ee',
        ];
        if (in_array($countryCode, $euCountries)) {
            return 'EUR';
        }

        return self::COUNTRY_CURRENCY_MAP[$countryCode] ?? null;
    }

    /**
     * Get language code for a country
     */
    private function getLanguageForCountry(?string $countryCode): ?string
    {
        if ($countryCode === null) {
            return null;
        }

        return self::COUNTRY_LANGUAGE_MAP[$countryCode] ?? null;
    }

    /**
     * Anonymise coordinates to ~1km accuracy (round to 2 decimal places)
     */
    private function anonymiseCoordinate(?float $coordinate): ?float
    {
        if ($coordinate === null) {
            return null;
        }

        return round($coordinate, 2);
    }

    /**
     * Get a default location for development mode (when using private IPs)
     * This allows testing geolocation flows without needing a public IP
     */
    private function getDefaultLocationForDevelopment(): LocationData
    {
        return new LocationData(
            countryCode: 'gb',
            countryName: 'United Kingdom',
            region: 'England',
            city: 'London',
            timezone: 'Europe/London',
            currencyCode: 'GBP',
            latitude: 51.51,
            longitude: -0.13,
            languageCode: 'en-GB',
            detectedAt: now(),
        );
    }

    /**
     * Close the database reader
     */
    public function __destruct()
    {
        if ($this->reader !== null) {
            try {
                $this->reader->close();
            } catch (Exception) {
                // Ignore exceptions during cleanup
            }
        }
    }
}
