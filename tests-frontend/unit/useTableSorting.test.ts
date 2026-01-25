import { useTableSorting } from '@/Composables/data/useTableSorting';
import { router } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    router: {
        get: vi.fn(),
    },
}));

describe('useTableSorting', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('should navigate with asc when clicking a new column', () => {
        const { sortBy } = useTableSorting('created_at', 'desc', {
            routePath: '/admin/visitors',
        });

        sortBy('name');

        expect(router.get).toHaveBeenCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                sort_by: 'name',
                sort_direction: 'asc',
            }),
            expect.any(Object),
        );
    });

    it('should toggle from asc to desc when clicking same column', () => {
        const { sortBy } = useTableSorting('name', 'asc', {
            routePath: '/admin/visitors',
        });

        sortBy('name');

        expect(router.get).toHaveBeenCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                sort_by: 'name',
                sort_direction: 'desc',
            }),
            expect.any(Object),
        );
    });

    it('should toggle from desc to asc when clicking same column', () => {
        const { sortBy } = useTableSorting('name', 'desc', {
            routePath: '/admin/visitors',
        });

        sortBy('name');

        expect(router.get).toHaveBeenCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                sort_by: 'name',
                sort_direction: 'asc',
            }),
            expect.any(Object),
        );
    });

    it('should work with getter functions for reactive values', () => {
        const sortBy = 'name';
        let direction = 'asc';

        const { sortBy: sortByFn } = useTableSorting(
            () => sortBy,
            () => direction,
            {
                routePath: '/admin/visitors',
            },
        );

        // First click - toggle to desc
        sortByFn('name');

        expect(router.get).toHaveBeenLastCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                sort_direction: 'desc',
            }),
            expect.any(Object),
        );

        // Simulate page re-render with updated props
        direction = 'desc';

        // Second click - should toggle back to asc
        sortByFn('name');

        expect(router.get).toHaveBeenLastCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                sort_direction: 'asc',
            }),
            expect.any(Object),
        );
    });

    it('should include additionalParams as object', () => {
        const { sortBy } = useTableSorting('name', 'asc', {
            routePath: '/admin/visitors',
            additionalParams: {
                search: 'test',
                per_page: 25,
            },
        });

        sortBy('name');

        expect(router.get).toHaveBeenCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                search: 'test',
                per_page: 25,
                sort_direction: 'desc',
            }),
            expect.any(Object),
        );
    });

    it('should include additionalParams as function', () => {
        let searchValue = 'initial';

        const { sortBy } = useTableSorting('name', 'asc', {
            routePath: '/admin/visitors',
            additionalParams: () => ({
                search: searchValue,
            }),
        });

        sortBy('name');

        expect(router.get).toHaveBeenLastCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                search: 'initial',
            }),
            expect.any(Object),
        );

        // Update search and call sortBy again
        searchValue = 'updated';
        sortBy('name');

        // Should use the new search value
        expect(router.get).toHaveBeenLastCalledWith(
            '/admin/visitors',
            expect.objectContaining({
                search: 'updated',
            }),
            expect.any(Object),
        );
    });
});
