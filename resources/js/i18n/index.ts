import { createI18n } from 'vue-i18n';

export type LocaleCode =
    | 'en-US'
    | 'en-GB'
    | 'de-DE'
    | 'ja-JP'
    | 'ko-KR'
    | 'fr-FR'
    | 'sv-SE'
    | 'no-NO'
    | 'da-DK'
    | 'fi-FI'
    | 'nl-NL'
    | 'es-ES'
    | 'pt-BR'
    | 'it-IT'
    | 'zh-CN'
    | 'ar-SA'
    | 'he-IL';

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
        code: 'en-US',
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
        code: 'de-DE',
        name: 'German',
        nativeName: 'Deutsch',
        flag: 'de',
        direction: 'ltr',
    },
    {
        code: 'ja-JP',
        name: 'Japanese',
        nativeName: '日本語',
        flag: 'jp',
        direction: 'ltr',
    },
    {
        code: 'ko-KR',
        name: 'Korean',
        nativeName: '한국어',
        flag: 'kr',
        direction: 'ltr',
    },
    {
        code: 'fr-FR',
        name: 'French',
        nativeName: 'Français',
        flag: 'fr',
        direction: 'ltr',
    },
    // Tier 3: Nordic
    {
        code: 'sv-SE',
        name: 'Swedish',
        nativeName: 'Svenska',
        flag: 'se',
        direction: 'ltr',
    },
    {
        code: 'no-NO',
        name: 'Norwegian',
        nativeName: 'Norsk',
        flag: 'no',
        direction: 'ltr',
    },
    {
        code: 'da-DK',
        name: 'Danish',
        nativeName: 'Dansk',
        flag: 'dk',
        direction: 'ltr',
    },
    {
        code: 'fi-FI',
        name: 'Finnish',
        nativeName: 'Suomi',
        flag: 'fi',
        direction: 'ltr',
    },
    {
        code: 'nl-NL',
        name: 'Dutch',
        nativeName: 'Nederlands',
        flag: 'nl',
        direction: 'ltr',
    },
    // Tier 4: Volume
    {
        code: 'es-ES',
        name: 'Spanish',
        nativeName: 'Español',
        flag: 'es',
        direction: 'ltr',
    },
    {
        code: 'pt-BR',
        name: 'Portuguese',
        nativeName: 'Português',
        flag: 'br',
        direction: 'ltr',
    },
    {
        code: 'it-IT',
        name: 'Italian',
        nativeName: 'Italiano',
        flag: 'it',
        direction: 'ltr',
    },
    {
        code: 'zh-CN',
        name: 'Chinese',
        nativeName: '中文',
        flag: 'cn',
        direction: 'ltr',
    },
    // Tier 5: RTL
    {
        code: 'ar-SA',
        name: 'Arabic',
        nativeName: 'العربية',
        flag: 'sa',
        direction: 'rtl',
    },
    {
        code: 'he-IL',
        name: 'Hebrew',
        nativeName: 'עברית',
        flag: 'il',
        direction: 'rtl',
    },
];

// Use en-GB as default since it's the core target market
export const i18n = createI18n({
    legacy: false,
    locale: 'en-GB',
    fallbackLocale: 'en-GB',
    messages: {},
});

/**
 * Initialize i18n with default locale messages (en-GB is core target)
 */
export async function initializeI18n(): Promise<void> {
    await loadLocaleMessages('en-GB');
}

/**
 * Lazy load locale messages
 */
export async function loadLocaleMessages(locale: LocaleCode): Promise<void> {
    // Skip if already loaded
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
