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

    const trackPageViewWithPath = (path: string) => {
        if (isAnalyticsBlockedPath(path)) {
            return;
        }

        analyticsService.track({
            name: 'page_view',
            page_path: path,
            referrer: document.referrer || null,
            properties: {
                title: document.title,
            },
        });
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
                trackInitialPageView();
            }
            initialFallbackTimer = null;
        }, 500);
    });

    // Track subsequent Inertia navigations
    router.on('navigate', (event) => {
        const path = getAnalyticsPathname(event.detail.page.url);
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

        trackPageViewWithPath(path);
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
