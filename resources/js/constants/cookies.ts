/**
 * Cookie categories and descriptions for GDPR compliance
 */

export const COOKIE_CATEGORIES = {
    essential: {
        id: 'essential',
        name: 'Essential Cookies',
        description:
            'These cookies are necessary for the website to function and cannot be disabled. They enable core functionality such as security, network management, and accessibility.',
        required: true,
        cookies: [
            {
                name: 'Laravel Session Cookie',
                purpose:
                    'Maintains your session state and authentication across pages',
                duration: '2 hours (extends with activity)',
            },
            {
                name: 'XSRF-TOKEN',
                purpose: 'Provides security against cross-site request forgery attacks',
                duration: '2 hours',
            },
            {
                name: 'cookie_consent',
                purpose: 'Stores your cookie preferences',
                duration: '1 year',
            },
        ],
    },
    functional: {
        id: 'functional',
        name: 'Functional Cookies',
        description:
            'These cookies enable enhanced functionality and personalisation, such as remembering your preferences and settings.',
        required: false,
        cookies: [
            {
                name: 'returning_visitor',
                purpose:
                    'Identifies returning visitors to show personalised welcome messages',
                duration: '1 year',
            },
            {
                name: 'voice_input_preference',
                purpose: 'Remembers your voice input method preference (Browser vs Whisper API)',
                duration: '1 year',
            },
        ],
    },
    analytics: {
        id: 'analytics',
        name: 'Analytics Cookies',
        description:
            'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
        required: false,
        cookies: [
            {
                name: 'Analytics Cookies',
                purpose:
                    'Track page visits, user interactions, and performance metrics to improve the service',
                duration: 'Up to 2 years',
            },
        ],
    },
} as const;

export type CookieCategoryId = keyof typeof COOKIE_CATEGORIES;

export interface CookiePreferences {
    essential: boolean; // Always true, cannot be disabled
    functional: boolean;
    analytics: boolean;
}

export const DEFAULT_COOKIE_PREFERENCES: CookiePreferences = {
    essential: true,
    functional: false,
    analytics: false,
};
