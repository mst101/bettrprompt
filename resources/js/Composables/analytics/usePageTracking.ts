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
    const trackPageView = (url?: string) => {
        const path = getAnalyticsPathname(url);
        if (!path || isAnalyticsBlockedPath(path)) {
            return;
        }

        analyticsService.track({
            name: 'page_view',
            properties: {
                path,
                referrer: document.referrer,
                title: document.title,
            },
        });
    };

    // Track initial page view on mount
    onMounted(() => {
        trackPageView();
    });

    // Track subsequent Inertia navigations
    router.on('navigate', (event) => {
        trackPageView(event.detail.page.url);
    });

    return {
        trackPageView,
    };
}
