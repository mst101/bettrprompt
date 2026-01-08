<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import TableHeaderSortable from '@/Components/Base/TableHeaderSortable.vue';
import CompactMetadataCard, {
    type MetadataItem,
} from '@/Components/Common/CompactMetadataCard.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { useLocalStorage } from '@/Composables/data/useLocalStorage';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/Types';
import { truncateText } from '@/Utils/formatting/formatters';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    personalityType: string | null;
    isAdmin: boolean;
    createdAt: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: Array<Record<string, unknown>>;
}

interface Filters {
    sort_by: string;
    sort_direction: string;
    per_page: number;
}

interface Props {
    user: User;
    promptRuns: PromptRunResource[];
    pagination: Pagination;
    filters: Filters;
    promptRunsCount: number;
}

const props = defineProps<Props>();

const { localeRoute } = useLocaleRoute();

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sort_by;
    const currentDirection = props.filters.sort_direction;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        localeRoute('admin.users.show', { id: props.user.id }),
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

// Use localStorage for per_page preference (default 15)
const perPageStorage = useLocalStorage('admin_users_per_page', 15);

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
        localeRoute('admin.users.show', { id: props.user.id }),
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
            localeRoute('admin.users.show', { id: props.user.id }),
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

const handleRowClick = (event: MouseEvent, runId: number): void => {
    // Allow default behavior for right-click and middle-click
    if (event.button === 2 || event.button === 1) {
        return;
    }

    // Allow Ctrl/Cmd + click to open in new tab
    if (event.ctrlKey || event.metaKey) {
        globalThis.window.open(
            localeRoute('admin.prompt-runs.show', { id: runId }),
            '_blank',
        );
        return;
    }

    // Normal left click - use Inertia navigation
    router.visit(localeRoute('admin.prompt-runs.show', { id: runId }));
};

const handleMiddleClick = (event: MouseEvent, runId: number): void => {
    if (event.button === 1) {
        globalThis.window.open(
            localeRoute('admin.prompt-runs.show', { id: runId }),
            '_blank',
        );
    }
};

const userMetadataItems = computed<MetadataItem[]>(() => {
    const items: MetadataItem[] = [];

    items.push({
        label: 'Name',
        value: props.user.name,
    });

    items.push({
        label: 'Email',
        value: props.user.email,
    });

    if (props.user.personalityType) {
        items.push({
            label: 'Personality',
            value: props.user.personalityType,
            badge: true,
            badgeColor: 'purple',
        });
    }

    if (props.user.isAdmin) {
        items.push({
            label: 'Role',
            value: 'Admin',
            badge: true,
            badgeColor: 'indigo',
        });
    }

    items.push({
        label: 'Joined',
        value: new Date(props.user.createdAt).toLocaleDateString(),
    });

    return items;
});
</script>

<template>
    <Head :title="`Admin - User: ${props.user.name}`" />
    <AppLayout>
        <HeaderPage title="User Details">
            <template #actions>
                <Link
                    :href="localeRoute('admin.users.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Back to Users
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage spacing>
            <!-- Compact User Metadata Card -->
            <CompactMetadataCard :items="userMetadataItems" />

            <!-- Prompts Heading -->
            <div class="mt-6 flex items-baseline gap-2">
                <h2 class="text-lg font-semibold text-indigo-900">Prompts</h2>
                <span class="text-sm text-indigo-500">
                    ({{ props.promptRunsCount }})
                </span>
            </div>

            <!-- Prompt Runs Table -->
            <Card padding="none">
                <div
                    v-if="props.promptRuns.length === 0"
                    class="p-6 text-center text-indigo-500"
                >
                    <p>No prompts yet.</p>
                </div>

                <div v-else>
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-indigo-200">
                            <thead class="bg-white dark:bg-indigo-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase sm:table-cell"
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
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase lg:table-cell"
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
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase sm:table-cell"
                                    >
                                        <TableHeaderSortable
                                            column="workflow_stage"
                                            :current-sort="filters.sort_by"
                                            :sort-direction="sortDirection"
                                            @sort="sortBy"
                                        >
                                            Status
                                        </TableHeaderSortable>
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
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
                            <tbody
                                class="divide-y divide-indigo-200 bg-white text-indigo-900 dark:bg-indigo-50"
                            >
                                <tr
                                    v-for="run in props.promptRuns"
                                    :key="run.id"
                                    class="group cursor-pointer transition-colors duration-150 hover:bg-indigo-50 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 dark:hover:bg-indigo-100"
                                    tabindex="0"
                                    @click="handleRowClick($event, run.id)"
                                    @auxclick.prevent="
                                        handleMiddleClick($event, run.id)
                                    "
                                    @keydown.enter="
                                        $inertia.visit(
                                            localeRoute(
                                                'admin.prompt-runs.show',
                                                { id: run.id },
                                            ),
                                        )
                                    "
                                >
                                    <td
                                        class="hidden px-6 py-4 text-sm transition-colors duration-150 group-hover:text-indigo-950 sm:table-cell"
                                    >
                                        {{
                                            truncateText(
                                                run.taskDescription || '',
                                                80,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="hidden px-6 py-4 text-sm font-medium whitespace-nowrap text-indigo-900 lg:table-cell"
                                    >
                                        {{ run.selectedFramework?.name || '—' }}
                                    </td>
                                    <td
                                        class="hidden px-6 py-4 text-sm whitespace-nowrap sm:table-cell"
                                    >
                                        <StatusBadge
                                            :workflow-stage="run.workflowStage"
                                        />
                                    </td>
                                    <td
                                        class="px-6 py-4 text-xs whitespace-nowrap text-indigo-700 transition-colors duration-150 group-hover:text-indigo-800"
                                    >
                                        <div>
                                            {{
                                                new Date(
                                                    run.createdAt,
                                                ).toLocaleString()
                                            }}
                                        </div>
                                        <div class="mt-1 sm:hidden">
                                            <StatusBadge
                                                :workflow-stage="
                                                    run.workflowStage
                                                "
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Navigation buttons (mobile) -->
                    <div
                        class="mt-4 grid grid-cols-3 items-center gap-2 px-4 sm:hidden"
                    >
                        <div class="flex justify-start">
                            <LinkButton
                                v-if="pagination.prev_page_url"
                                id="pagination-prev-mobile"
                                :href="pagination.prev_page_url"
                                @click="handlePaginationClick('prev')"
                            >
                                Previous
                            </LinkButton>
                        </div>

                        <p
                            v-if="pagination.last_page > 1"
                            class="text-center text-sm text-indigo-700"
                        >
                            Page
                            {{ pagination.current_page }}
                            of
                            {{ pagination.last_page }}
                        </p>

                        <div class="flex justify-end">
                            <LinkButton
                                v-if="pagination.next_page_url"
                                id="pagination-next-mobile"
                                :href="pagination.next_page_url"
                                @click="handlePaginationClick('next')"
                            >
                                Next
                            </LinkButton>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        class="border-t border-indigo-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <!-- Mobile Layout -->
                        <div class="sm:hidden">
                            <!-- Results info and page info -->
                            <div
                                class="mb-3 space-y-1 text-center text-sm text-indigo-700"
                            >
                                <p
                                    v-if="pagination.from && pagination.to"
                                    class="text-center"
                                >
                                    Showing
                                    <span class="font-medium">{{
                                        pagination.from
                                    }}</span>
                                    to
                                    <span class="font-medium">{{
                                        pagination.to
                                    }}</span>
                                    of
                                    <span class="font-medium">{{
                                        pagination.total
                                    }}</span>
                                    results
                                </p>
                            </div>

                            <!-- Per-page selector -->
                            <div
                                class="mt-3 flex items-center justify-center gap-2"
                            >
                                <label
                                    for="per-page"
                                    class="text-sm text-indigo-700"
                                >
                                    Show
                                </label>
                                <input
                                    id="per-page"
                                    v-model="perPageInput"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-16 rounded-md border-indigo-100 bg-white px-2 py-1 pl-4 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                    @blur="changePerPage"
                                    @keydown.enter="changePerPage"
                                />
                                <span class="text-sm text-indigo-700"
                                    >per page</span
                                >
                            </div>
                        </div>

                        <!-- Desktop Layout -->
                        <div
                            class="hidden sm:flex sm:items-center sm:justify-between"
                        >
                            <!-- Results info -->
                            <div>
                                <p
                                    v-if="pagination.from && pagination.to"
                                    class="text-sm text-indigo-700"
                                >
                                    Showing
                                    <span class="font-medium">{{
                                        pagination.from
                                    }}</span>
                                    to
                                    <span class="font-medium">{{
                                        pagination.to
                                    }}</span>
                                    of
                                    <span class="font-medium">{{
                                        pagination.total
                                    }}</span>
                                    results
                                </p>
                            </div>

                            <!-- Per-page selector (centred) -->
                            <div class="flex items-center gap-2">
                                <label
                                    for="per-page-desktop"
                                    class="text-sm text-indigo-700"
                                >
                                    Show
                                </label>
                                <input
                                    id="per-page-desktop"
                                    v-model="perPageInput"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-16 rounded-md border-indigo-100 bg-white py-1 pl-2 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                    @blur="changePerPage"
                                    @keydown.enter="changePerPage"
                                />
                                <span class="text-sm text-indigo-700"
                                    >per page</span
                                >
                            </div>

                            <!-- Navigation -->
                            <div>
                                <nav
                                    v-if="pagination.last_page > 1"
                                    class="isolate inline-flex -space-x-px rounded-md shadow-xs"
                                >
                                    <LinkButton
                                        v-if="pagination.prev_page_url"
                                        id="pagination-prev"
                                        :href="pagination.prev_page_url"
                                        variant="rounded-left"
                                        @click="handlePaginationClick('prev')"
                                    >
                                        Previous
                                    </LinkButton>
                                    <span
                                        class="relative inline-flex items-center border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700"
                                    >
                                        Page
                                        {{ pagination.current_page }}
                                        of
                                        {{ pagination.last_page }}
                                    </span>
                                    <LinkButton
                                        v-if="pagination.next_page_url"
                                        id="pagination-next"
                                        :href="pagination.next_page_url"
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
    </AppLayout>
</template>
