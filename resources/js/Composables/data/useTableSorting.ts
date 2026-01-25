import { router } from '@inertiajs/vue3';
import { computed, isRef, type ComputedRef, type Ref } from 'vue';

export interface UseTableSortingOptions {
    routePath?: string;
    additionalParams?:
        | Record<string, unknown>
        | (() => Record<string, unknown>);
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
 *     () => props.filters.sortBy,
 *     () => props.filters.sortDirection,
 *     { additionalParams: () => ({ search: search.value, per_page: 25 }) }
 * );
 *
 * // In template
 * <button @click="sortBy('created_at')">Sort by Date</button>
 * ```
 */
export function useTableSorting(
    currentSortBy: string | (() => string) | ComputedRef<string> | Ref<string>,
    currentDirection:
        | string
        | (() => string)
        | ComputedRef<string>
        | Ref<string>,
    options?: UseTableSortingOptions,
) {
    // Convert to getters for consistent access
    const getSortBy = () => {
        if (typeof currentSortBy === 'function') return currentSortBy();
        if (isRef(currentSortBy)) return currentSortBy.value;
        return currentSortBy;
    };

    const getDirection = () => {
        if (typeof currentDirection === 'function') return currentDirection();
        if (isRef(currentDirection)) return currentDirection.value;
        return currentDirection;
    };

    const sortDirection = computed(() => getDirection());

    const sortBy = (column: string) => {
        const currentSortByValue = getSortBy();
        const currentDirectionValue = getDirection();

        let newDirection = 'asc';
        if (currentSortByValue === column) {
            // Toggle direction if clicking the same column
            newDirection = currentDirectionValue === 'asc' ? 'desc' : 'asc';
        }

        // Get additionalParams - can be a function (for reactive values) or an object
        const baseParams =
            typeof options?.additionalParams === 'function'
                ? options.additionalParams()
                : options?.additionalParams || {};

        const params: Record<string, unknown> = {
            ...baseParams,
            sort_by: column,
            sort_direction: newDirection,
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
