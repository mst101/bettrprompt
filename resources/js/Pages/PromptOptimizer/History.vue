<script setup lang="ts">
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkButton from '@/Components/LinkButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TableHeaderSortable from '@/Components/TableHeaderSortable.vue';
import { useLocalStorage } from '@/Composables/useLocalStorage';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Paginated, PromptRunResource } from '@/types';
import { formatDate, truncateText } from '@/utils/formatters';
import { getFullPersonalityType } from '@/utils/personalityTypes';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Props {
    promptRuns: Paginated<PromptRunResource>;
    filters: {
        sort_by: string;
        sort_direction: string;
        per_page: number;
    };
}

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sort_by;
    const currentDirection = props.filters.sort_direction;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        route('prompt-optimizer.history'),
        {
            sort_by: column,
            sort_direction: newDirection,
            per_page: props.filters.per_page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

// Use localStorage for per_page preference (default 10)
const perPageStorage = useLocalStorage('history_per_page', 10);

// Use a ref for the input that syncs with props
const perPageInput = ref(props.filters.per_page.toString());

// Watch props changes and update input
watch(
    () => props.filters.per_page,
    (newValue) => {
        perPageInput.value = newValue.toString();
    },
);

const changePerPage = () => {
    const perPage = parseInt(perPageInput.value);

    // Validate: must be a number between 1 and 100
    if (isNaN(perPage) || perPage < 1 || perPage > 100) {
        // Reset to current value if invalid
        perPageInput.value = props.filters.per_page.toString();
        return;
    }

    // Only navigate if the value has actually changed
    if (perPage === props.filters.per_page) {
        return;
    }

    // Save to localStorage
    perPageStorage.value = perPage;

    router.get(
        route('prompt-optimizer.history'),
        {
            sort_by: props.filters.sort_by,
            sort_direction: props.filters.sort_direction,
            per_page: perPage,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const sortDirection = computed(() => {
    return props.filters.sort_direction;
});

// Focus management for pagination buttons
const handlePaginationClick = (direction: 'prev' | 'next') => {
    sessionStorage.setItem('pagination_focus', direction);
};

// On mount, if the per_page from server doesn't match localStorage, update it
onMounted(() => {
    const storedPerPage = perPageStorage.value;
    if (storedPerPage !== props.filters.per_page) {
        router.get(
            route('prompt-optimizer.history'),
            {
                sort_by: props.filters.sort_by,
                sort_direction: props.filters.sort_direction,
                per_page: storedPerPage,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true, // Replace history entry instead of adding new one
            },
        );
    }

    // Restore focus to pagination button if it was clicked
    const lastClickedButton = sessionStorage.getItem('pagination_focus');
    if (lastClickedButton) {
        sessionStorage.removeItem('pagination_focus');
        // Use setTimeout to ensure page is fully loaded and stable before focusing
        nextTick(() => {
            // Try desktop button first, then mobile
            let button = document.getElementById(
                `pagination-${lastClickedButton}`,
            );
            if (!button) {
                button = document.getElementById(
                    `pagination-${lastClickedButton}-mobile`,
                );
            }
            if (button) {
                button.focus();
            }
        });
    }
});
</script>

<template>
    <Head title="Prompt History" />

    <HeaderPage title="Prompt History">
        <template #actions>
            <LinkButton
                :href="route('prompt-optimizer.index')"
                variant="primary"
            >
                Create New
            </LinkButton>
        </template>
    </HeaderPage>

    <ContainerPage>
        <Card padding="none">
            <div
                v-if="promptRuns.data.length === 0"
                class="text-centre p-6 text-gray-500"
            >
                <p>No prompt history yet.</p>
                <a
                    :href="route('prompt-optimizer.index')"
                    class="mt-2 text-indigo-600 hover:text-indigo-800"
                >
                    Create your first optimised prompt
                </a>
            </div>

            <div v-else>
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-600 uppercase sm:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="personality_type"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        Personality Type
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-gray-600 uppercase"
                                >
                                    <TableHeaderSortable
                                        column="task_description"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        Task Description
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-600 uppercase lg:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="selected_framework"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        Framework
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-600 uppercase sm:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="status"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        Status
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-600 uppercase"
                                >
                                    <TableHeaderSortable
                                        column="created_at"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        Created
                                    </TableHeaderSortable>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="promptRun in promptRuns.data"
                                :key="promptRun.id"
                                class="cursor-pointer rounded-md hover:bg-gray-50 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 dark:hover:bg-gray-100"
                                tabindex="0"
                                @click="
                                    $inertia.visit(
                                        route(
                                            'prompt-optimizer.show',
                                            promptRun.id,
                                        ),
                                    )
                                "
                            >
                                <td
                                    class="hidden px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:table-cell"
                                >
                                    <span class="lg:hidden">{{
                                        promptRun.personalityType
                                    }}</span>
                                    <span class="hidden lg:inline">{{
                                        getFullPersonalityType(
                                            promptRun.personalityType,
                                        )
                                    }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{
                                        truncateText(
                                            promptRun.taskDescription,
                                            80,
                                        )
                                    }}
                                </td>
                                <td
                                    class="hidden px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 lg:table-cell"
                                >
                                    {{
                                        promptRun.selectedFramework || '\u2014'
                                    }}
                                </td>
                                <td
                                    class="hidden px-6 py-4 text-sm whitespace-nowrap sm:table-cell"
                                >
                                    <StatusBadge :status="promptRun.status" />
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                >
                                    <div>
                                        {{ formatDate(promptRun.createdAt) }}
                                    </div>
                                    <div class="mt-1 sm:hidden">
                                        <StatusBadge
                                            :status="promptRun.status"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Navigation buttons -->
                <div
                    class="mt-4 grid grid-cols-3 items-center gap-2 px-4 sm:hidden"
                >
                    <div class="flex justify-start">
                        <LinkButton
                            v-if="promptRuns.meta.prevPageUrl"
                            id="pagination-prev-mobile"
                            :href="promptRuns.meta.prevPageUrl"
                            @click="handlePaginationClick('prev')"
                        >
                            Previous
                        </LinkButton>
                    </div>

                    <p
                        v-if="promptRuns.meta.lastPage > 1"
                        class="text-center text-sm text-gray-700"
                    >
                        Page
                        {{ promptRuns.meta.currentPage }}
                        of
                        {{ promptRuns.meta.lastPage }}
                    </p>

                    <div class="flex justify-end">
                        <LinkButton
                            v-if="promptRuns.meta.nextPageUrl"
                            id="pagination-next-mobile"
                            :href="promptRuns.meta.nextPageUrl"
                            @click="handlePaginationClick('next')"
                        >
                            Next
                        </LinkButton>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                >
                    <!-- Mobile Layout -->
                    <div class="sm:hidden">
                        <!-- Results info and page info -->
                        <div
                            class="text-centre mb-3 space-y-1 text-sm text-gray-700"
                        >
                            <p
                                v-if="
                                    promptRuns.meta.from && promptRuns.meta.to
                                "
                                class="text-center"
                            >
                                Showing
                                <span class="font-medium">{{
                                    promptRuns.meta.from
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    promptRuns.meta.to
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    promptRuns.meta.total
                                }}</span>
                                results
                            </p>
                        </div>

                        <!-- Per-page selector -->
                        <div
                            class="mt-3 flex items-center justify-center-safe gap-2"
                        >
                            <label for="per-page" class="text-sm text-gray-700">
                                Show
                            </label>
                            <input
                                id="per-page"
                                v-model="perPageInput"
                                type="number"
                                min="1"
                                max="100"
                                class="w-16 rounded-md border-gray-300 bg-white px-2 py-1 pl-4 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                @blur="changePerPage"
                                @keydown.enter="changePerPage"
                            />
                            <span class="text-sm text-gray-700">per page</span>
                        </div>
                    </div>

                    <!-- Desktop Layout -->
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-between"
                    >
                        <!-- Results info -->
                        <div>
                            <p
                                v-if="
                                    promptRuns.meta.from && promptRuns.meta.to
                                "
                                class="text-sm text-gray-700"
                            >
                                Showing
                                <span class="font-medium">{{
                                    promptRuns.meta.from
                                }}</span>
                                to
                                <span class="font-medium">{{
                                    promptRuns.meta.to
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    promptRuns.meta.total
                                }}</span>
                                results
                            </p>
                        </div>

                        <!-- Per-page selector (centred) -->
                        <div class="flex items-center gap-2">
                            <label
                                for="per-page-desktop"
                                class="text-sm text-gray-700"
                            >
                                Show
                            </label>
                            <input
                                id="per-page-desktop"
                                v-model="perPageInput"
                                type="number"
                                min="1"
                                max="100"
                                class="w-16 rounded-md border-gray-300 bg-white py-1 pl-2 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                @blur="changePerPage"
                                @keydown.enter="changePerPage"
                            />
                            <span class="text-sm text-gray-700">per page</span>
                        </div>

                        <!-- Navigation -->
                        <div>
                            <nav
                                v-if="promptRuns.meta.lastPage > 1"
                                class="isolate inline-flex -space-x-px rounded-md shadow-xs"
                                aria-label="Pagination"
                            >
                                <LinkButton
                                    v-if="promptRuns.meta.prevPageUrl"
                                    id="pagination-prev"
                                    :href="promptRuns.meta.prevPageUrl"
                                    variant="rounded-left"
                                    @click="handlePaginationClick('prev')"
                                >
                                    Previous
                                </LinkButton>
                                <span
                                    class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700"
                                >
                                    Page
                                    {{ promptRuns.meta.currentPage }}
                                    of
                                    {{ promptRuns.meta.lastPage }}
                                </span>
                                <LinkButton
                                    v-if="promptRuns.meta.nextPageUrl"
                                    id="pagination-next"
                                    :href="promptRuns.meta.nextPageUrl"
                                    variant="rounded-right"
                                    @click="handlePaginationClick('next')"
                                >
                                    Next
                                </LinkButton>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
