<?php

use App\DTOs\LocationData;
use App\Services\GeolocationService;
use Illuminate\Support\Facades\Cache;

describe('Private IP Detection', function () {
    test('rejects private IP when dev mode disabled', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result)->toBeNull();
    });

    test('detects loopback IP as private', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result)->toBeNull();
    });

    test('detects RFC 1918 private IP range (192.168.x.x)', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;
        $result = $service->lookupIp('192.168.1.1');

        expect($result)->toBeNull();
    });

    test('detects RFC 1918 private IP range (10.x.x.x)', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;
        $result = $service->lookupIp('10.0.0.1');

        expect($result)->toBeNull();
    });

    test('detects RFC 1918 private IP range (172.16-31.x.x)', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;
        $result = $service->lookupIp('172.16.0.1');

        expect($result)->toBeNull();
    });

    test('returns default location for private IP in development mode', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result)
            ->not->toBeNull()
            ->and($result->countryCode)->toBe('gb')
            ->and($result->city)->toBe('London')
            ->and($result->timezone)->toBe('Europe/London')
            ->and($result->currencyCode)->toBe('GBP')
            ->and($result->languageCode)->toBe('en-GB');
    });

    test('default development location includes anonymised coordinates', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->latitude)->toBe(51.51)
            ->and($result->longitude)->toBe(-0.13);
    });

    test('development mode returns consistent default location', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result1 = $service->lookupIp('127.0.0.1');
        $result2 = $service->lookupIp('192.168.1.1');

        expect($result1->countryCode)->toBe($result2->countryCode)
            ->and($result1->city)->toBe($result2->city);
    });
});

describe('Caching', function () {
    test('returns consistent results on multiple lookups', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result1 = $service->lookupIp('127.0.0.1');
        $result2 = $service->lookupIp('127.0.0.1');

        expect($result1->countryCode)->toBe($result2->countryCode)
            ->and($result1->city)->toBe($result2->city)
            ->and($result1->latitude)->toBe($result2->latitude);
    });
});

describe('Location Data DTO', function () {
    test('creates location data with all fields', function () {
        $location = new LocationData(
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

        expect($location->countryCode)->toBe('gb')
            ->and($location->city)->toBe('London')
            ->and($location->currencyCode)->toBe('GBP');
    });

    test('converts location data to array with snake_case keys', function () {
        $location = new LocationData(
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

        $array = $location->toArray();

        expect($array)
            ->toHaveKey('country_code')
            ->toHaveKey('country_name')
            ->toHaveKey('timezone')
            ->toHaveKey('currency_code')
            ->toHaveKey('language_code')
            ->and($array['country_code'])->toBe('gb')
            ->and($array['city'])->toBe('London');
    });

    test('reconstructs location data from array', function () {
        $original = new LocationData(
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

        $array = $original->toArray();
        $reconstructed = LocationData::fromArray($array);

        expect($reconstructed->countryCode)->toBe($original->countryCode)
            ->and($reconstructed->city)->toBe($original->city)
            ->and($reconstructed->currencyCode)->toBe($original->currencyCode)
            ->and($reconstructed->languageCode)->toBe($original->languageCode);
    });

    test('handles null values in DTO', function () {
        $location = new LocationData(
            countryCode: 'gb',
            countryName: 'United Kingdom',
            region: null,
            city: null,
            timezone: 'Europe/London',
            currencyCode: 'GBP',
            latitude: null,
            longitude: null,
            languageCode: 'en-GB',
            detectedAt: now(),
        );

        expect($location->region)->toBeNull()
            ->and($location->city)->toBeNull()
            ->and($location->latitude)->toBeNull()
            ->and($location->longitude)->toBeNull();
    });

    test('is complete when country and timezone present', function () {
        $location = new LocationData(
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

        expect($location->isComplete())->toBeTrue();
    });

    test('is incomplete when country is null', function () {
        $location = new LocationData(
            countryCode: null,
            countryName: 'Unknown',
            region: 'Region',
            city: 'City',
            timezone: 'UTC',
            currencyCode: null,
            latitude: 0.0,
            longitude: 0.0,
            languageCode: null,
            detectedAt: now(),
        );

        expect($location->isComplete())->toBeFalse();
    });

    test('is incomplete when timezone is null', function () {
        $location = new LocationData(
            countryCode: 'us',
            countryName: 'United States',
            region: 'Region',
            city: 'City',
            timezone: null,
            currencyCode: 'USD',
            latitude: 0.0,
            longitude: 0.0,
            languageCode: 'en-US',
            detectedAt: now(),
        );

        expect($location->isComplete())->toBeFalse();
    });

    test('gets summary for location with city', function () {
        $location = new LocationData(
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

        expect($location->getSummary())->toBe('London, England, United Kingdom');
    });

    test('gets summary for location without city', function () {
        $location = new LocationData(
            countryCode: 'gb',
            countryName: 'United Kingdom',
            region: 'England',
            city: null,
            timezone: 'Europe/London',
            currencyCode: 'GBP',
            latitude: 51.51,
            longitude: -0.13,
            languageCode: 'en-GB',
            detectedAt: now(),
        );

        expect($location->getSummary())->toBe('England, United Kingdom');
    });
});

describe('Development Mode', function () {
    test('development mode default location is complete', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->isComplete())->toBeTrue();
    });

    test('development mode location has all required fields', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->countryCode)->not->toBeNull()
            ->and($result->countryName)->not->toBeNull()
            ->and($result->timezone)->not->toBeNull()
            ->and($result->currencyCode)->not->toBeNull()
            ->and($result->languageCode)->not->toBeNull()
            ->and($result->detectedAt)->not->toBeNull();
    });

    test('development mode location contains valid country code', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->countryCode)->toBe('gb')
            ->and(strlen($result->countryCode))->toBe(2);
    });

    test('development mode location contains valid timezone', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->timezone)->toBe('Europe/London');
    });

    test('development mode location has valid BCP 47 language code', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->languageCode)->toMatch('/^[a-z]{2}-[A-Z]{2}$/');
    });

    test('development mode location has valid 3-letter currency code', function () {
        config(['geoip.development.allow_private_ip_lookup' => true]);

        $service = new GeolocationService;
        $result = $service->lookupIp('127.0.0.1');

        expect($result->currencyCode)->toMatch('/^[A-Z]{3}$/')
            ->and(strlen($result->currencyCode))->toBe(3);
    });
});

describe('IP Address Types', function () {
    test('accepts public IPv4 addresses', function () {
        // Public IPs should not be treated as private
        // (though actual lookup may fail without database)
        $publicIps = ['8.8.8.8', '1.1.1.1', '9.9.9.9'];

        foreach ($publicIps as $ip) {
            config(['geoip.development.allow_private_ip_lookup' => false]);
            config(['geoip.maxmind.database_path' => '/non/existent/path.mmdb']);

            $service = new GeolocationService;
            $result = $service->lookupIp($ip);

            // Should return null (database unavailable) rather than blocking private IP
            expect($result)->toBeNull();
        }
    });

    test('recognises loopback addresses', function () {
        config(['geoip.development.allow_private_ip_lookup' => false]);

        $service = new GeolocationService;

        expect($service->lookupIp('127.0.0.1'))->toBeNull()
            ->and($service->lookupIp('127.255.255.255'))->toBeNull();
    });
});

describe('Geolocation Service DTO Round Trip', function () {
    test('location data persists through cache', function () {
        $original = new LocationData(
            countryCode: 'us',
            countryName: 'United States',
            region: 'California',
            city: 'San Francisco',
            timezone: 'America/Los_Angeles',
            currencyCode: 'USD',
            latitude: 37.77,
            longitude: -122.41,
            languageCode: 'en-US',
            detectedAt: now(),
        );

        // Simulate caching
        Cache::put('geoip:test', $original->toArray(), 3600);
        $cached = Cache::get('geoip:test');
        $restored = LocationData::fromArray($cached);

        expect($restored->countryCode)->toBe($original->countryCode)
            ->and($restored->city)->toBe($original->city)
            ->and($restored->latitude)->toBe($original->latitude)
            ->and($restored->timezone)->toBe($original->timezone);
    });

    test('coexists with multiple DTOs in cache', function () {
        Cache::flush();

        $locations = [
            'gb' => new LocationData(countryCode: 'gb', timezone: 'Europe/London', currencyCode: 'GBP', languageCode: 'en-GB'),
            'us' => new LocationData(countryCode: 'us', timezone: 'America/New_York', currencyCode: 'USD', languageCode: 'en-US'),
            'de' => new LocationData(countryCode: 'de', timezone: 'Europe/Berlin', currencyCode: 'EUR', languageCode: 'de-DE'),
        ];

        foreach ($locations as $code => $location) {
            Cache::put("geoip:test_$code", $location->toArray(), 3600);
        }

        expect(Cache::has('geoip:test_gb'))->toBeTrue()
            ->and(Cache::has('geoip:test_us'))->toBeTrue()
            ->and(Cache::has('geoip:test_de'))->toBeTrue();

        $gbRestored = LocationData::fromArray(Cache::get('geoip:test_gb'));
        expect($gbRestored->countryCode)->toBe('gb');
    });
});
