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
    const { hasConsentFor } = useCookieConsent();
    // Track the previous consent state to detect actual changes
    // (not the immediate watcher callback on first mount)
    const previousConsentState = ref<boolean | undefined>(undefined);

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
                    });
                }

                analyticsService.flushPending();

                console.log(
                    '[Analytics] Consent granted, session started:',
                    sessionId,
                );
            }
            // Fire consent_denied when consent transitions from true→false
            else if (!currentConsent && wasConsented) {
                // Track consent denied before stopping analytics
                if (
                    typeof window === 'undefined' ||
                    !isAnalyticsBlockedPath(window.location.pathname)
                ) {
                    analyticsService.track({
                        name: 'consent_denied',
                        page_path: window.location.pathname,
                        referrer: null,
                    });
                }

                // Flush the consent_denied event before clearing
                analyticsService.flushPending();

                console.log('[Analytics] Consent denied, analytics disabled');
            }
        },
        { immediate: true },
    );
}
