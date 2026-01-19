import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { analyticsService } from '@/services/analytics';
import { analyticsSessionService } from '@/services/analyticsSession';
import { isAnalyticsBlockedPath } from '@/Utils/analyticsGuard';
import { ref, watch } from 'vue';

/**
 * Initialize analytics tracking after consent
 * Call this once in app.ts after Inertia app is mounted
 */
export function useAnalyticsInit() {
    const { hasConsentFor, cookiePreferences } = useCookieConsent();
    // Track the previous consent state to detect actual changes
    // (not the immediate watcher callback on first mount)
    const previousConsentState = ref<boolean | undefined>(undefined);

    /**
     * Get array of granted consent categories
     */
    const getConsentCategories = (): string[] => {
        if (!cookiePreferences.value) return [];
        const categories: string[] = [];
        if (cookiePreferences.value.essential) categories.push('essential');
        if (cookiePreferences.value.functional) categories.push('functional');
        if (cookiePreferences.value.analytics) categories.push('analytics');
        return categories;
    };

    // Track consent state changes (but NOT on initial mount)
    watch(
        () => hasConsentFor('analytics'),
        (currentConsent) => {
            // Skip on first mount (when previousConsentState is undefined)
            if (previousConsentState.value === undefined) {
                previousConsentState.value = currentConsent;
                return;
            }

            const wasConsented = previousConsentState.value;
            previousConsentState.value = currentConsent;

            // Only fire when consent transitions from false→true
            if (currentConsent && !wasConsented) {
                // Initialize session (creates session ID)
                const sessionId = analyticsSessionService.getSessionId();

                // Track consent granted
                if (
                    typeof window === 'undefined' ||
                    !isAnalyticsBlockedPath(window.location.pathname)
                ) {
                    analyticsService.track({
                        name: 'consent_granted',
                        page_path: window.location.pathname,
                        referrer: null,
                        properties: {
                            categories: getConsentCategories(),
                        },
                    });
                }

                analyticsService.flushPending();

                console.log(
                    '[Analytics] Consent granted, session started:',
                    sessionId,
                );
            }
            // Fire consent_revoked when consent transitions from true→false
            else if (!currentConsent && wasConsented) {
                // Track consent revoked before stopping analytics
                if (
                    typeof window === 'undefined' ||
                    !isAnalyticsBlockedPath(window.location.pathname)
                ) {
                    analyticsService.track({
                        name: 'consent_revoked',
                        page_path: window.location.pathname,
                        referrer: null,
                        properties: {
                            categories: [],
                        },
                    });
                }

                // Flush the consent_revoked event before clearing
                analyticsService.flushPending();

                console.log('[Analytics] Consent revoked, analytics disabled');
            }
        },
        { immediate: true },
    );
}
