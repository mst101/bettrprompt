import { type CookiePreferences } from '@/Constants/cookies';
import { computed, ref } from 'vue';

const CONSENT_COOKIE_NAME = 'cookie_consent';
const CONSENT_COOKIE_DURATION = 365; // days

// Shared state across all instances
const cookiePreferences = ref<CookiePreferences | null>(null);
const hasConsent = ref<boolean>(false);

/**
 * Composable for managing GDPR cookie consent
 */
export function useCookieConsent() {
    /**
     * Get cookie value by name
     */
    const getCookie = (name: string): string | null => {
        const matches = document.cookie.match(
            new RegExp(
                '(?:^|; )' +
                    name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') +
                    '=([^;]*)',
            ),
        );
        return matches ? decodeURIComponent(matches[1]) : null;
    };

    /**
     * Set cookie with expiration
     */
    const setCookie = (
        name: string,
        value: string,
        days: number = CONSENT_COOKIE_DURATION,
    ): void => {
        const expires = new Date();
        expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
        document.cookie = `${name}=${encodeURIComponent(value)};expires=${expires.toUTCString()};path=/;SameSite=Strict`;
    };

    /**
     * Load cookie preferences from storage
     */
    const loadPreferences = (): void => {
        const stored = getCookie(CONSENT_COOKIE_NAME);
        if (stored) {
            try {
                cookiePreferences.value = JSON.parse(stored);
                hasConsent.value = true;
            } catch (e) {
                console.error('Failed to parse cookie preferences:', e);
                cookiePreferences.value = null;
                hasConsent.value = false;
            }
        } else {
            cookiePreferences.value = null;
            hasConsent.value = false;
        }
    };

    /**
     * Save cookie preferences
     */
    const savePreferences = (preferences: CookiePreferences): void => {
        // Essential cookies are always enabled
        const finalPreferences: CookiePreferences = {
            ...preferences,
            essential: true,
        };

        cookiePreferences.value = finalPreferences;
        hasConsent.value = true;

        // Store in cookie
        setCookie(CONSENT_COOKIE_NAME, JSON.stringify(finalPreferences));

        // Apply preferences (disable non-consented cookies)
        applyPreferences(finalPreferences);
    };

    /**
     * Accept all cookies
     */
    const acceptAll = (): void => {
        savePreferences({
            essential: true,
            functional: true,
            analytics: true,
        });
    };

    /**
     * Reject all non-essential cookies
     */
    const rejectAll = (): void => {
        savePreferences({
            essential: true,
            functional: false,
            analytics: false,
        });
    };

    /**
     * Apply cookie preferences (remove cookies for disabled categories)
     */
    const applyPreferences = (preferences: CookiePreferences): void => {
        // If analytics cookies are disabled, remove analytics cookies
        if (!preferences.analytics) {
            // Best-effort cleanup for known analytics cookies.
            // Note: some third-party tools may use additional cookies.
            document.cookie =
                '_fs_uid=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
            document.cookie =
                '_fs_lq=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;';
        }
    };

    /**
     * Reset consent (for testing or user request to re-choose)
     */
    const resetConsent = (): void => {
        document.cookie = `${CONSENT_COOKIE_NAME}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/;`;
        cookiePreferences.value = null;
        hasConsent.value = false;
    };

    /**
     * Check if a specific category is consented
     */
    const hasConsentFor = (category: keyof CookiePreferences): boolean => {
        if (!cookiePreferences.value) return false;
        return cookiePreferences.value[category] === true;
    };

    // Initialize on first use
    if (cookiePreferences.value === null && typeof document !== 'undefined') {
        loadPreferences();
    }

    return {
        cookiePreferences: computed(() => cookiePreferences.value),
        hasConsent: computed(() => hasConsent.value),
        acceptAll,
        rejectAll,
        savePreferences,
        resetConsent,
        hasConsentFor,
        loadPreferences,
    };
}
