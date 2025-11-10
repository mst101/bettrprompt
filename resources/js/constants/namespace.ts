/**
 * AI Buddy namespace constants and utilities.
 *
 * Provides centralized management of prefixes for all frontend storage mechanisms
 * including localStorage, sessionStorage, cookies, and other identifiers.
 */

/**
 * The AI Buddy application prefix.
 * Can be overridden via environment variable for different environments.
 */
export const APP_NAMESPACE = import.meta.env.APP_NAMESPACE || 'aib';

/**
 * Generate a namespaced key with the AI Buddy prefix.
 *
 * @param key The base key to namespace
 * @returns The namespaced key
 *
 * @example
 * namespaced('consent_state') // returns 'aib_consent_state'
 * namespaced('visitor_id') // returns 'aib_visitor_id'
 */
export function namespaced(key: string): string {
    return `${APP_NAMESPACE}_${key}`;
}

/**
 * Generate a namespaced localStorage key.
 *
 * @param key The base localStorage key
 * @returns The namespaced localStorage key
 */
export function localStorageKey(key: string): string {
    return namespaced(key);
}

/**
 * Generate a namespaced sessionStorage key.
 *
 * @param key The base sessionStorage key
 * @returns The namespaced sessionStorage key
 */
export function sessionStorageKey(key: string): string {
    return namespaced(key);
}

/**
 * Generate a namespaced cookie name.
 *
 * @param name The base cookie name
 * @returns The namespaced cookie name
 */
export function cookieName(name: string): string {
    return namespaced(name);
}

/**
 * Generate a namespaced CSS class name.
 *
 * @param className The base CSS class name
 * @returns The namespaced CSS class name with hyphens
 */
export function cssClass(className: string): string {
    return `${APP_NAMESPACE}-${className}`;
}

/**
 * Generate a namespaced HTML data attribute.
 *
 * @param attribute The base data attribute name
 * @returns The namespaced data attribute
 */
export function dataAttribute(attribute: string): string {
    return `data-${APP_NAMESPACE}-${attribute}`;
}

/**
 * Commonly used namespaced keys for consistency across the application.
 */
export const STORAGE_KEYS = {
    // Consent and privacy
    CONSENT_STATE: namespaced('consent_state'),
    VISITOR_ID: namespaced('visitor_id'),
    SESSION_ID: namespaced('session_id'),

    // Analytics and tracking
    ANALYTICS: namespaced('analytics'),
    SESSION_ANALYTICS_UTM: namespaced('session_analytics_utm'),
    SESSION_ANALYTICS_FIRST_VISIT: namespaced('session_analytics_first_visit'),
    SESSION_ANALYTICS_ID: namespaced('session_analytics_id'),
    SESSION_TRACKER_FIRST_VISIT: namespaced('session_tracker_first_visit'),

    // Personalisation
    PREFERENCES: namespaced('preferences'),
    SAVED_SEARCHES: namespaced('saved_searches'),
    BEHAVIOURAL_PROFILE: namespaced('behavioural_profile'),

    // Marketing
    MARKETING: namespaced('marketing'),
    CAMPAIGN_HISTORY: namespaced('campaign_history'),
    RETARGETING: namespaced('retargeting'),
    ATTRIBUTION: namespaced('attribution'),

    // A/B Testing
    AB_TESTS: namespaced('ab_tests'),
    TEST_PROFILE: namespaced('test_profile'),
    TEST_HISTORY: namespaced('test_history'),
} as const;

/**
 * Type-safe storage key names.
 */
export type StorageKey = keyof typeof STORAGE_KEYS;

/**
 * Get a storage key by name.
 *
 * @param key The storage key name
 * @returns The namespaced storage key
 */
export function storageKey(key: StorageKey): string {
    return STORAGE_KEYS[key];
}
