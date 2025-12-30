<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see \App\Models\Currency
 *
 * TypeScript interface:
 * ```typescript
 * interface Currency {
 *     readonly id: string;
 *     readonly symbol: string;
 *     readonly thousandsSeparator: string;
 *     readonly decimalSeparator: string;
 *     readonly symbolOnLeft: boolean;
 *     readonly spaceBetweenAmountAndSymbol: boolean;
 *     readonly roundingCoefficient: number;
 *     readonly decimalDigits: number;
 *     readonly createdAt: string;
 *     readonly updatedAt: string;
 * }
 * ```
 */
class CurrencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'thousandsSeparator' => $this->thousands_separator,
            'decimalSeparator' => $this->decimal_separator,
            'symbolOnLeft' => $this->symbol_on_left,
            'spaceBetweenAmountAndSymbol' => $this->space_between_amount_and_symbol,
            'roundingCoefficient' => $this->rounding_coefficient,
            'decimalDigits' => $this->decimal_digits,
            'createdAt' => $this->created_at?->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
