<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\Country
 *
 * TypeScript interface:
 * ```typescript
 * interface Country {
 *     readonly id: string;
 *     readonly continentId: string | null;
 *     readonly currencyId: string;
 *     readonly languageId: string;
 *     readonly firstDayOfWeek: 'mon' | 'sun' | 'sat';
 *     readonly usesMiles: boolean;
 *     readonly name: string;
 *     readonly createdAt: string;
 *     readonly updatedAt: string;
 *     readonly currency?: Currency;
 *     readonly language?: Language;
 * }
 * ```
 */
class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'continentId' => $this->continent_id,
            'currencyId' => $this->currency_id,
            'languageId' => $this->language_id,
            'firstDayOfWeek' => $this->first_day_of_week,
            'usesMiles' => $this->uses_miles,
            'name' => $this->name,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'language' => new LanguageResource($this->whenLoaded('language')),
        ];
    }
}
