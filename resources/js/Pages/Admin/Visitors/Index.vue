<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import TableHeaderSortable from '@/Components/Base/TableHeaderSortable.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { Paginated, VisitorListResource } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    visitors: Paginated<VisitorListResource>;
    search: string | null;
    filters: {
        sortBy: string;
        sortDirection: string;
    };
}

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();

const searchQuery = ref(props.search || '');
let debounceTimeout: ReturnType<typeof setTimeout>;

const debouncedSearch = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get(
            window.location.pathname,
            {
                search: searchQuery.value,
                sort_by: props.filters.sortBy,
                sort_direction: props.filters.sortDirection,
            },
            { preserveState: true, preserveScroll: true },
        );
    }, 300);
};

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sortBy;
    const currentDirection = props.filters.sortDirection;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        window.location.pathname,
        {
            search: searchQuery.value,
            sort_by: column,
            sort_direction: newDirection,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const sortDirection = computed(() => {
    return props.filters.sortDirection;
});

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Visitors - Admin" />

    <HeaderPage title="Visitors" />

    <ContainerPage>
        <!-- Search bar -->
        <div class="mb-6">
            <input
                id="visitor-search"
                v-model="searchQuery"
                type="text"
                placeholder="Search by visitor ID, country, or linked user..."
                class="w-full rounded-lg border border-indigo-200 px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                data-testid="visitor-search"
                @input="debouncedSearch"
            />
        </div>

        <!-- Visitors table -->
        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-indigo-200">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="id"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    Visitor ID
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="user_name"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    Linked User
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="country_code"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    Country
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="sessions_count"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    Sessions
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="created_at"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    First Seen
                                </TableHeaderSortable>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                            >
                                <TableHeaderSortable
                                    column="last_visit_at"
                                    :current-sort="filters.sortBy"
                                    :sort-direction="sortDirection"
                                    @sort="sortBy"
                                >
                                    Last Seen
                                </TableHeaderSortable>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-indigo-100 bg-white">
                        <Link
                            v-for="visitor in visitors.data"
                            :key="visitor.id"
                            :href="
                                countryRoute('admin.visitors.show', {
                                    visitor: visitor.id,
                                })
                            "
                            as="tr"
                            class="cursor-pointer transition hover:bg-indigo-50"
                            data-testid="visitor-row"
                        >
                            <td
                                class="px-6 py-4 font-mono text-sm whitespace-nowrap text-indigo-900"
                            >
                                {{ visitor.id }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                <span
                                    v-if="visitor.user"
                                    class="text-indigo-900"
                                >
                                    {{ visitor.user.name }}
                                </span>
                                <span v-else class="text-indigo-400">
                                    Anonymous
                                </span>
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ visitor.countryCode || 'N/A' }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ visitor.sessionsCount }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{ formatDate(visitor.createdAt) }}
                            </td>
                            <td
                                class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                            >
                                {{
                                    visitor.lastSeenAt
                                        ? formatDate(visitor.lastSeenAt)
                                        : 'N/A'
                                }}
                            </td>
                        </Link>
                        <tr v-if="visitors.data.length === 0">
                            <td
                                colspan="6"
                                class="px-6 py-4 text-center text-sm text-indigo-500"
                            >
                                No visitors found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </Card>

        <!-- Pagination -->
        <div v-if="visitors.meta.lastPage > 1" class="mt-6 flex justify-center">
            <Pagination :meta="visitors.meta" />
        </div>
    </ContainerPage>
</template>
