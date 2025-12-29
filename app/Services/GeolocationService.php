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
    private ?Reader $reader = null;

    /**
     * Maximum age of cached results (30 days)
     */
    private const CACHE_TTL = 30 * 24 * 60 * 60;

    /**
     * Country to currency mapping
     */
    private const COUNTRY_CURRENCY_MAP = [
        'US' => 'USD',
        'GB' => 'GBP',
        'CA' => 'CAD',
        'AU' => 'AUD',
        'NZ' => 'NZD',
        'EU' => 'EUR',
        'DE' => 'EUR',
        'FR' => 'EUR',
        'IT' => 'EUR',
        'ES' => 'EUR',
        'NL' => 'EUR',
        'BE' => 'EUR',
        'AT' => 'EUR',
        'CH' => 'CHF',
        'SE' => 'SEK',
        'NO' => 'NOK',
        'DK' => 'DKK',
        'JP' => 'JPY',
        'CN' => 'CNY',
        'IN' => 'INR',
        'BR' => 'BRL',
        'MX' => 'MXN',
        'ZA' => 'ZAR',
        'SG' => 'SGD',
        'HK' => 'HKD',
    ];

    /**
     * Country to language code mapping (BCP 47 locale format)
     */
    private const COUNTRY_LANGUAGE_MAP = [
        'US' => 'en-US',
        'GB' => 'en-GB',
        'CA' => 'en-CA',
        'AU' => 'en-AU',
        'NZ' => 'en-NZ',
        'DE' => 'de-DE',
        'FR' => 'fr-FR',
        'IT' => 'it-IT',
        'ES' => 'es-ES',
        'NL' => 'nl-NL',
        'BE' => 'nl-BE',
        'AT' => 'de-AT',
        'CH' => 'de-CH',
        'SE' => 'sv-SE',
        'NO' => 'nb-NO',
        'DK' => 'da-DK',
        'JP' => 'ja-JP',
        'CN' => 'zh-CN',
        'IN' => 'en-IN',
        'BR' => 'pt-BR',
        'MX' => 'es-MX',
        'ZA' => 'en-ZA',
        'SG' => 'en-SG',
        'HK' => 'zh-HK',
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
            Log::debug("Performing geolocation lookup for private IP in development: $ip");

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
            $countryCode = $record->country->isoCode;

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

        // Special case for Eurozone
        $euCountries = [
            'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'AT', 'IE', 'PT', 'GR', 'CY', 'MT', 'SK', 'SI', 'LV', 'LT', 'EE',
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
            countryCode: 'GB',
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
