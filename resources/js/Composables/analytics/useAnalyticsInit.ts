import { useCookieConsent } from '@/Composables/features/useCookieConsent';
import { analyticsService } from '@/services/analytics';
import { analyticsSessionService } from '@/services/analyticsSession';
import { isAnalyticsBlockedPath } from '@/Utils/analyticsGuard';
import { watch } from 'vue';

/**
 * Initialize analytics tracking after consent
 * Call this once in app.ts after Inertia app is mounted
 */
export function useAnalyticsInit() {
    const { hasConsentFor } = useCookieConsent();
    // Track consent granted event when analytics consent is given
    watch(
        () => hasConsentFor('analytics'),
        (hasConsent, wasConsented) => {
            // Only fire once when consent transitions from false→true
            if (hasConsent && !wasConsented) {
                // Initialize session (creates session ID)
                const sessionId = analyticsSessionService.getSessionId();

                // Track consent granted
                if (
                    typeof window === 'undefined' ||
                    !isAnalyticsBlockedPath(window.location.pathname)
                ) {
                    analyticsService.track({
                        name: 'consent_granted',
                        properties: {
                            initial_page_path: window.location.pathname,
                        },
                    });
                }

                analyticsService.flushPending();

                console.log(
                    '[Analytics] Consent granted, session started:',
                    sessionId,
                );
            }
        },
        { immediate: true },
    );
}
