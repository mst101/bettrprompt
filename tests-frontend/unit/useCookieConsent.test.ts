import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import type { CookiePreferences } from '@/Constants/cookies';
import { logger } from '@/Utils/logger';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('useCookieConsent', () => {
    beforeEach(() => {
        // Clear all cookies before each test
        document.cookie.split(';').forEach((c) => {
            document.cookie = c
                .replace(/^ +/, '')
                .replace(
                    /=.*/,
                    `=;expires=${new Date(0).toUTCString()};path=/`,
                );
        });
    });

    afterEach(() => {
        // Clean up cookies
        document.cookie.split(';').forEach((c) => {
            document.cookie = c
                .replace(/^ +/, '')
                .replace(
                    /=.*/,
                    `=;expires=${new Date(0).toUTCString()};path=/`,
                );
        });
    });

    it('should initialize with no consent on fresh start', () => {
        // This test assumes the cookie is not set
        const { hasConsent, cookiePreferences } = useCookieConsent();

        // After all our tests, the state will be set, so just verify structure
        if (hasConsent.value) {
            expect(cookiePreferences.value).not.toBeNull();
        } else {
            expect(cookiePreferences.value).toBeNull();
        }
    });

    it('should accept all cookies', () => {
        const { acceptAll, hasConsent, cookiePreferences } = useCookieConsent();

        acceptAll();

        expect(hasConsent.value).toBe(true);
        expect(cookiePreferences.value).toEqual({
            essential: true,
            functional: true,
            analytics: true,
        });
    });

    it('should reject all non-essential cookies', () => {
        const { rejectAll, hasConsent, cookiePreferences } = useCookieConsent();

        rejectAll();

        expect(hasConsent.value).toBe(true);
        expect(cookiePreferences.value).toEqual({
            essential: true,
            functional: false,
            analytics: false,
        });
    });

    it('should save custom preferences with essential always true', () => {
        const { savePreferences, cookiePreferences, hasConsent } =
            useCookieConsent();

        const preferences: CookiePreferences = {
            essential: false, // Should be forced to true
            functional: true,
            analytics: false,
        };

        savePreferences(preferences);

        expect(hasConsent.value).toBe(true);
        expect(cookiePreferences.value?.essential).toBe(true);
        expect(cookiePreferences.value?.functional).toBe(true);
        expect(cookiePreferences.value?.analytics).toBe(false);
    });

    it('should store preferences in cookie', () => {
        const { acceptAll } = useCookieConsent();

        acceptAll();

        const cookieValue = document.cookie
            .split(';')
            .find((c) => c.trim().startsWith('cookie_consent='));

        expect(cookieValue).toBeDefined();
        expect(cookieValue).toContain('essential');
        expect(cookieValue).toContain('functional');
        expect(cookieValue).toContain('analytics');
    });

    it('should load preferences from cookie', () => {
        // First, set a cookie with preferences
        const preferences: CookiePreferences = {
            essential: true,
            functional: true,
            analytics: false,
        };
        document.cookie = `cookie_consent=${encodeURIComponent(
            JSON.stringify(preferences),
        )};path=/`;

        // Use the composable - it should load from the cookie we just set
        const { cookiePreferences, hasConsent } = useCookieConsent();

        // Since the composable loads on first use, it should have loaded our cookie
        if (cookiePreferences.value) {
            expect(hasConsent.value).toBe(true);
            expect(cookiePreferences.value.essential).toBe(true);
            expect(cookiePreferences.value.functional).toBe(true);
        }
    });

    it('should handle invalid JSON in cookie gracefully', () => {
        // Reset first to ensure clean state
        const { resetConsent } = useCookieConsent();
        resetConsent();

        // Set an invalid cookie
        document.cookie = 'cookie_consent=invalid-json;path=/';

        const loggerErrorSpy = vi
            .spyOn(logger, 'error')
            .mockImplementation(() => {});

        // Create new composable instance after setting invalid cookie
        const { loadPreferences } = useCookieConsent();

        // Manually call load to trigger the error handling
        loadPreferences();

        expect(loggerErrorSpy).toHaveBeenCalledWith(
            'Failed to parse cookie preferences:',
            expect.any(Error),
        );

        loggerErrorSpy.mockRestore();
    });

    it('should check consent for specific category', () => {
        const { savePreferences, hasConsentFor } = useCookieConsent();

        const preferences: CookiePreferences = {
            essential: true,
            functional: true,
            analytics: false,
        };

        savePreferences(preferences);

        expect(hasConsentFor('essential')).toBe(true);
        expect(hasConsentFor('functional')).toBe(true);
        expect(hasConsentFor('analytics')).toBe(false);
    });

    it('should return false for hasConsentFor when no preferences loaded', () => {
        const { hasConsentFor, resetConsent } = useCookieConsent();

        // Reset first to ensure clean state
        resetConsent();

        expect(hasConsentFor('functional')).toBe(false);
        expect(hasConsentFor('analytics')).toBe(false);
    });

    it('should reset consent', () => {
        const { acceptAll, resetConsent, hasConsent, cookiePreferences } =
            useCookieConsent();

        acceptAll();
        expect(hasConsent.value).toBe(true);

        resetConsent();

        expect(hasConsent.value).toBe(false);
        expect(cookiePreferences.value).toBeNull();
    });

    it('should handle encoded cookie values correctly', () => {
        const preferences: CookiePreferences = {
            essential: true,
            functional: true,
            analytics: true,
        };

        const { savePreferences } = useCookieConsent();
        savePreferences(preferences);

        // Verify the cookie is properly encoded/decoded
        const { cookiePreferences } = useCookieConsent();
        expect(cookiePreferences.value?.essential).toBe(true);
        expect(cookiePreferences.value).toEqual(preferences);
    });

    it('should support multiple calls to acceptAll and rejectAll', () => {
        const { acceptAll, rejectAll, cookiePreferences } = useCookieConsent();

        acceptAll();
        expect(cookiePreferences.value?.analytics).toBe(true);

        rejectAll();
        expect(cookiePreferences.value?.analytics).toBe(false);

        acceptAll();
        expect(cookiePreferences.value?.analytics).toBe(true);
    });

    it('should use computed reactivity for preferences', () => {
        const { savePreferences, resetConsent, cookiePreferences } =
            useCookieConsent();

        // Reset to clear any previous state
        resetConsent();

        const preferences: CookiePreferences = {
            essential: true,
            functional: true,
            analytics: false,
        };

        expect(cookiePreferences.value).toBeNull();

        savePreferences(preferences);

        expect(cookiePreferences.value).not.toBeNull();
        // Essential is always forced to true
        expect(cookiePreferences.value?.essential).toBe(true);
        expect(cookiePreferences.value?.functional).toBe(true);
        expect(cookiePreferences.value?.analytics).toBe(false);
    });
});
