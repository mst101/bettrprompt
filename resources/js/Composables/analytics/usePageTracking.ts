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
    let initialPath: string | null = null;
    let initialTrackedAt = 0;
    let initialFallbackTimer: number | null = null;
    let lastPath: string | null = null;
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

    const trackInitialPageView = (url?: string) => {
        if (hasTrackedInitial) {
            return;
        }

        const path = getAnalyticsPathname(url);
        if (!path) {
            return;
        }

        hasTrackedInitial = true;
        initialPath = path;
        initialTrackedAt = Date.now();
        trackPageViewWithPath(path);
    };

    // Track initial page view on mount
    onMounted(() => {
        initialFallbackTimer = window.setTimeout(() => {
            if (!hasTrackedInitial) {
                requestAnimationFrame(() => {
                    trackInitialPageView();
                });
            }
            initialFallbackTimer = null;
        }, 500);
    });

    // Track the initial page view before the first navigation starts.
    router.on('start', () => {
        if (!hasTrackedInitial) {
            if (initialFallbackTimer !== null) {
                clearTimeout(initialFallbackTimer);
                initialFallbackTimer = null;
            }

            trackInitialPageView();
        }
    });

    // Track subsequent Inertia navigations
    router.on('finish', () => {
        const path = getAnalyticsPathname();
        if (!path) {
            return;
        }

        if (initialFallbackTimer !== null) {
            clearTimeout(initialFallbackTimer);
            initialFallbackTimer = null;
        }

        if (!hasTrackedInitial) {
            trackInitialPageView(path);
            return;
        }

        if (initialPath === path && Date.now() - initialTrackedAt < 2000) {
            return;
        }

        requestAnimationFrame(() => {
            window.setTimeout(() => {
                trackPageViewWithPath(path);
            }, 0);
        });
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
