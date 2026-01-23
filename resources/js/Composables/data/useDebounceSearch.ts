import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface DebounceSearchOptions {
    routePath?: string;
    additionalParams?: Record<string, unknown>;
    debounceMs?: number;
    preserveState?: boolean;
    preserveScroll?: boolean;
}

/**
 * Composable for debounced search with Inertia router integration
 * Handles search input and navigation with automatic debouncing
 */
export const useDebounceSearch = (
    initialSearch: string = '',
    options: DebounceSearchOptions = {},
) => {
    const {
        debounceMs = 300,
        preserveState = true,
        preserveScroll = true,
        additionalParams = {},
    } = options;

    const search = ref(initialSearch);

    const routePath = options.routePath || window.location.pathname;

    const performSearch = useDebounceFn(() => {
        router.get(
            routePath,
            {
                search: search.value,
                ...additionalParams,
            },
            { preserveState, preserveScroll, replace: true },
        );
    }, debounceMs);

    watch(search, performSearch);

    return {
        search,
        performSearch,
    };
};
