import { analyticsService } from '@/services/analytics';
import {
    getAnalyticsPathname,
    isAnalyticsBlockedPath,
} from '@/Utils/analyticsGuard';
import { router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

/**
 * Track page views for analytics
 * Integrates with Inertia navigation
 * Call this once per layout
 */
export function usePageTracking() {
    let hasTrackedInitial = false;
    let lastPath: string | null = null;
    let navigationInProgress = false;
    const externalReferrer = (() => {
        if (typeof document === 'undefined' || !document.referrer) {
            return null;
        }

        try {
            const referrerUrl = new URL(document.referrer);
            if (referrerUrl.origin === window.location.origin) {
                return null;
            }
        } catch {
            return null;
        }

        return document.referrer;
    })();

    const buildReferrer = (): string | null => {
        if (lastPath) {
            return lastPath;
        }

        return externalReferrer;
    };

    const trackPageViewWithPath = (path: string) => {
        if (isAnalyticsBlockedPath(path)) {
            return;
        }

        if (lastPath === path) {
            return;
        }

        analyticsService.track({
            name: 'page_view',
            page_path: path,
            referrer: buildReferrer(),
            properties: {
                title: document.title,
            },
        });

        lastPath = path;
    };

    // Track initial page view on mount (before any navigation)
    onMounted(() => {
        // Only track initial view if router hasn't started yet (no navigation in progress)
        if (!navigationInProgress && !hasTrackedInitial) {
            const path = getAnalyticsPathname();
            if (path) {
                hasTrackedInitial = true;
                trackPageViewWithPath(path);
            }
        }
    });

    // Track all Inertia navigations (including initial if not already tracked)
    router.on('finish', () => {
        navigationInProgress = false;

        const path = getAnalyticsPathname();
        if (!path) {
            return;
        }

        // If initial page view hasn't been tracked yet, track it now
        if (!hasTrackedInitial) {
            hasTrackedInitial = true;
            trackPageViewWithPath(path);
            return;
        }

        // Track subsequent navigations (avoid tracking same path immediately)
        if (lastPath === path) {
            return;
        }

        trackPageViewWithPath(path);
    });

    // Set flag when navigation starts to avoid duplicate tracking
    router.on('start', () => {
        navigationInProgress = true;
    });

    return {
        trackPageView: (url?: string) => {
            const path = getAnalyticsPathname(url);
            if (!path) {
                return;
            }

            trackPageViewWithPath(path);
        },
    };
}
