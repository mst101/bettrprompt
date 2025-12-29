<?php

namespace App\DTOs;

use Carbon\Carbon;

readonly class LocationData
{
    public function __construct(
        public ?string $countryCode = null,
        public ?string $countryName = null,
        public ?string $region = null,
        public ?string $city = null,
        public ?string $timezone = null,
        public ?string $currencyCode = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?string $languageCode = null,
        public ?Carbon $detectedAt = null,
    ) {}

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'region' => $this->region,
            'city' => $this->city,
            'timezone' => $this->timezone,
            'currency_code' => $this->currencyCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'language_code' => $this->languageCode,
            'location_detected_at' => $this->detectedAt,
        ];
    }

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: $data['country_code'] ?? null,
            countryName: $data['country_name'] ?? null,
            region: $data['region'] ?? null,
            city: $data['city'] ?? null,
            timezone: $data['timezone'] ?? null,
            currencyCode: $data['currency_code'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            languageCode: $data['language_code'] ?? null,
            detectedAt: isset($data['location_detected_at']) ? Carbon::parse($data['location_detected_at']) : null,
        );
    }

    /**
     * Check if location data is complete enough to be useful
     */
    public function isComplete(): bool
    {
        return ! is_null($this->countryCode) && ! is_null($this->timezone);
    }

    /**
     * Get a summary of the location
     */
    public function getSummary(): string
    {
        if (is_null($this->city)) {
            return "$this->region, $this->countryName";
        }

        return "$this->city, $this->region, $this->countryName";
    }
}
