<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * TypeScript interface:
 * ```typescript
 * interface LocationDataResource {
 *     readonly countryCode: string | null;
 *     readonly countryName: string | null;
 *     readonly region: string | null;
 *     readonly city: string | null;
 *     readonly timezone: string | null;
 *     readonly currencyCode: string | null;
 *     readonly languageCode: string | null;
 *     readonly detectedAt: string | null;
 *     readonly manuallySet: boolean;
 * }
 * ```
 */
class LocationDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'countryCode' => $this['countryCode'] ?? $this['country_code'] ?? $this->country_code ?? null,
            'countryName' => $this['countryName'] ?? $this['country_name'] ?? $this->country_name ?? null,
            'region' => $this['region'] ?? $this->region ?? null,
            'city' => $this['city'] ?? $this->city ?? null,
            'timezone' => $this['timezone'] ?? $this->timezone ?? null,
            'currencyCode' => $this['currencyCode'] ?? $this['currency_code'] ?? $this->currency_code ?? null,
            'languageCode' => $this['languageCode'] ?? $this['language_code'] ?? $this->language_code ?? null,
            'detectedAt' => $this['detectedAt'] ?? $this['detected_at'] ?? $this->detected_at?->toIso8601String() ?? null,
            'manuallySet' => (bool) ($this['manuallySet'] ?? $this['manually_set'] ?? $this->manually_set ?? false),
        ];
    }
}
