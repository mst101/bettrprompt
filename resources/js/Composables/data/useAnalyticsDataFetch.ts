import { logger } from '@/Utils/logger';
import { ref } from 'vue';

/**
 * Generic composable for fetching analytics data with date range support
 * Handles loading state, error logging, and automatic date parameter injection
 */
export const useAnalyticsDataFetch = (endpoint: string) => {
    const loading = ref(false);

    const fetchData = async (dateFrom: string, dateTo: string) => {
        loading.value = true;

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('start_date', dateFrom);
            url.searchParams.set('end_date', dateTo);

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(
                    `Failed to fetch analytics: HTTP ${response.status}`,
                );
            }

            return await response.json();
        } catch (error) {
            logger.error(`Error fetching ${endpoint}:`, error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        fetchData,
    };
};
