/**
 * TypeScript definition for the Currency model
 * Auto-generated from app/Http/Resources/CurrencyResource.php
 */

export interface Currency {
    readonly id: string;
    readonly symbol: string;
    readonly thousandsSeparator: string;
    readonly decimalSeparator: string;
    readonly symbolOnLeft: boolean;
    readonly spaceBetweenAmountAndSymbol: boolean;
    readonly roundingCoefficient: number;
    readonly decimalDigits: number;
    readonly createdAt: string;
    readonly updatedAt: string;
}
