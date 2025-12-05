/**
 * TypeScript definition for the Country model
 * Auto-generated from app/Http/Resources/CountryResource.php
 */

import type { Currency, Language } from '@/types/models';

export interface Country {
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
