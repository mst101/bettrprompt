import { createI18n } from 'vue-i18n';
import en from './locales/en.json';

export type LocaleCode =
    | 'en'
    | 'en-GB'
    | 'de'
    | 'ja'
    | 'ko'
    | 'fr'
    | 'sv'
    | 'no'
    | 'da'
    | 'fi'
    | 'nl'
    | 'es'
    | 'pt'
    | 'it'
    | 'zh'
    | 'ar'
    | 'he';

export interface LocaleInfo {
    code: LocaleCode;
    name: string;
    nativeName: string;
    flag: string;
    direction: 'ltr' | 'rtl';
}

export const locales: LocaleInfo[] = [
    // Tier 1: Essential
    {
        code: 'en',
        name: 'English (US)',
        nativeName: 'English',
        flag: 'us',
        direction: 'ltr',
    },
    {
        code: 'en-GB',
        name: 'English (UK)',
        nativeName: 'English',
        flag: 'gb',
        direction: 'ltr',
    },
    // Tier 2: High Value
    {
        code: 'de',
        name: 'German',
        nativeName: 'Deutsch',
        flag: 'de',
        direction: 'ltr',
    },
    {
        code: 'ja',
        name: 'Japanese',
        nativeName: '日本語',
        flag: 'jp',
        direction: 'ltr',
    },
    {
        code: 'ko',
        name: 'Korean',
        nativeName: '한국어',
        flag: 'kr',
        direction: 'ltr',
    },
    {
        code: 'fr',
        name: 'French',
        nativeName: 'Français',
        flag: 'fr',
        direction: 'ltr',
    },
    // Tier 3: Nordic
    {
        code: 'sv',
        name: 'Swedish',
        nativeName: 'Svenska',
        flag: 'se',
        direction: 'ltr',
    },
    {
        code: 'no',
        name: 'Norwegian',
        nativeName: 'Norsk',
        flag: 'no',
        direction: 'ltr',
    },
    {
        code: 'da',
        name: 'Danish',
        nativeName: 'Dansk',
        flag: 'dk',
        direction: 'ltr',
    },
    {
        code: 'fi',
        name: 'Finnish',
        nativeName: 'Suomi',
        flag: 'fi',
        direction: 'ltr',
    },
    {
        code: 'nl',
        name: 'Dutch',
        nativeName: 'Nederlands',
        flag: 'nl',
        direction: 'ltr',
    },
    // Tier 4: Volume
    {
        code: 'es',
        name: 'Spanish',
        nativeName: 'Español',
        flag: 'es',
        direction: 'ltr',
    },
    {
        code: 'pt',
        name: 'Portuguese',
        nativeName: 'Português',
        flag: 'br',
        direction: 'ltr',
    },
    {
        code: 'it',
        name: 'Italian',
        nativeName: 'Italiano',
        flag: 'it',
        direction: 'ltr',
    },
    {
        code: 'zh',
        name: 'Chinese',
        nativeName: '中文',
        flag: 'cn',
        direction: 'ltr',
    },
    // Tier 5: RTL
    {
        code: 'ar',
        name: 'Arabic',
        nativeName: 'العربية',
        flag: 'sa',
        direction: 'rtl',
    },
    {
        code: 'he',
        name: 'Hebrew',
        nativeName: 'עברית',
        flag: 'il',
        direction: 'rtl',
    },
];

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: {
        en,
    },
});

/**
 * Lazy load locale messages
 */
export async function loadLocaleMessages(locale: LocaleCode): Promise<void> {
    // Skip if already loaded or is the default
    if (i18n.global.availableLocales.includes(locale)) {
        return;
    }

    try {
        const messages = await import(`./locales/${locale}.json`);
        i18n.global.setLocaleMessage(locale, messages.default);
    } catch (error) {
        console.warn(
            `Failed to load locale ${locale}, falling back to English`,
            error,
        );
    }
}

/**
 * Set the current locale and load messages if needed
 */
export async function setLocale(locale: LocaleCode): Promise<void> {
    await loadLocaleMessages(locale);
    i18n.global.locale.value = locale;

    // Update document direction for RTL languages
    const localeInfo = locales.find((l) => l.code === locale);
    if (localeInfo) {
        document.documentElement.dir = localeInfo.direction;
        document.documentElement.lang = locale;
    }
}

/**
 * Get locale info by code
 */
export function getLocaleInfo(code: LocaleCode): LocaleInfo | undefined {
    return locales.find((l) => l.code === code);
}

/**
 * Check if a locale is RTL
 */
export function isRtlLocale(locale: LocaleCode): boolean {
    return getLocaleInfo(locale)?.direction === 'rtl';
}

export default i18n;
