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
                name: 'bettrprompt-session',
                purpose:
                    'Maintains your session state (including authentication) across requests',
                duration: '2 hours (extends with activity)',
            },
            {
                name: 'remember_web_*',
                purpose:
                    'Keeps you signed in across browser restarts when you choose “Remember me”',
                duration: 'Varies (only set if you enable “Remember me”)',
            },
            {
                name: 'XSRF-TOKEN',
                purpose:
                    'Provides security against cross-site request forgery attacks',
                duration: '2 hours',
            },
            {
                name: 'visitor_id',
                purpose:
                    'Identifies your browser so we can associate guest actions (like prompt runs) and preferences with a single visitor record',
                duration: '2 years',
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
        cookies: [],
    },
    analytics: {
        id: 'analytics',
        name: 'Analytics Cookies',
        description:
            'These cookies help us understand how visitors interact with our website by collecting and reporting information anonymously.',
        required: false,
        cookies: [
            {
                name: 'FullStory (_fs_uid, _fs_lq)',
                purpose:
                    'Helps us understand how visitors use the site so we can improve reliability and usability',
                duration: 'Varies',
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
