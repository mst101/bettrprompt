/**
 * TypeScript definition for CountryResource
 * Auto-generated from Resource docblock by bp:types:generate
 */

export interface CountryResource {
    readonly id: string;
    readonly continentId: string | null;
    readonly currencyId: string;
    readonly languageId: string;
    readonly firstDayOfWeek: 'mon' | 'sun' | 'sat';
    readonly usesMiles: boolean;
    readonly name: string;
    readonly createdAt: string;
    readonly updatedAt: string;
    readonly currency?: Currency;
    readonly language?: Language;
}
