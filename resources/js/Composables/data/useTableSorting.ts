import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface UseTableSortingOptions {
    routePath?: string;
    additionalParams?: Record<string, unknown>;
}

/**
 * Composable for managing table sorting state and navigation
 *
 * Consolidates duplicate sorting logic used across admin pages.
 * Handles toggling between ascending/descending order and updating URL parameters.
 *
 * @example
 * ```typescript
 * const { sortBy, sortDirection } = useTableSorting(
 *     props.filters.sortBy,
 *     props.filters.sortDirection,
 *     { additionalParams: { search: searchQuery.value, per_page: 25 } }
 * );
 *
 * // In template
 * <button @click="sortBy('created_at')">Sort by Date</button>
 * ```
 */
export function useTableSorting(
    currentSortBy: string,
    currentDirection: string,
    options?: UseTableSortingOptions,
) {
    const sortDirection = computed(() => currentDirection);

    const sortBy = (column: string) => {
        let newDirection = 'asc';
        if (currentSortBy === column && currentDirection === 'asc') {
            newDirection = 'desc';
        }

        const params: Record<string, unknown> = {
            sort_by: column,
            sort_direction: newDirection,
            ...(options?.additionalParams || {}),
        };

        const path = options?.routePath || window.location.pathname;

        router.get(path, params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return {
        sortBy,
        sortDirection,
    };
}
