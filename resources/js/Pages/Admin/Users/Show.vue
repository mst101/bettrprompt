<script setup lang="ts">
import SessionListItem from '@/Components/Admin/SessionListItem.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
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
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type {
    PromptRunResource,
    SessionStatsResource,
    UserDetailResource,
} from '@/Types';
import { truncateText } from '@/Utils/formatting/formatters';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Pagination {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
    nextPageUrl: string | null;
    prevPageUrl: string | null;
    links: Array<Record<string, unknown>>;
}

interface Filters {
    sortBy: string;
    sortDirection: string;
    perPage: number;
}

interface Props {
    user: UserDetailResource;
    promptRuns: PromptRunResource[];
    pagination: Pagination;
    filters: Filters;
    promptRunsCount: number;
    sessionStats: SessionStatsResource | null;
}

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();
const { t } = useI18n({ useScope: 'global' });

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sortBy;
    const currentDirection = props.filters.sortDirection;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        countryRoute('admin.users.show', { user: props.user.id }),
        {
            sort_by: column,
            sort_direction: newDirection,
            per_page: props.filters.perPage,
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
const perPageInput = ref(props.filters.perPage.toString());

// Watch props changes and update input
watch(
    () => props.filters.perPage,
    (newValue) => {
        perPageInput.value = newValue.toString();
    },
);

const changePerPage = () => {
    const perPage = parseInt(perPageInput.value);

    // Validate: must be a number between 1 and 100
    if (isNaN(perPage) || perPage < 1 || perPage > 100) {
        // Reset to current value if invalid
        perPageInput.value = props.filters.perPage.toString();
        return;
    }

    // Only navigate if the value has actually changed
    if (perPage === props.filters.perPage) {
        return;
    }

    // Save to localStorage
    perPageStorage.value = perPage;

    router.get(
        countryRoute('admin.users.show', { user: props.user.id }),
        {
            sort_by: props.filters.sortBy,
            sort_direction: props.filters.sortDirection,
            per_page: perPage,
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

// Focus management for pagination buttons
const handlePaginationClick = (direction: 'prev' | 'next') => {
    sessionStorage.setItem('pagination_focus', direction);
};

// On mount, if the per_page from server doesn't match localStorage, update it
onMounted(() => {
    const storedPerPage = perPageStorage.value;
    if (storedPerPage !== props.filters.perPage) {
        router.get(
            countryRoute('admin.users.show', { user: props.user.id }),
            {
                sort_by: props.filters.sortBy,
                sort_direction: props.filters.sortDirection,
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
            countryRoute('admin.prompt-runs.show', { promptRun: runId }),
            '_blank',
        );
        return;
    }

    // Normal left click - use Inertia navigation
    router.visit(countryRoute('admin.prompt-runs.show', { promptRun: runId }));
};

const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};

const handleMiddleClick = (event: MouseEvent, runId: number): void => {
    if (event.button === 1) {
        globalThis.window.open(
            countryRoute('admin.prompt-runs.show', { promptRun: runId }),
            '_blank',
        );
    }
};

const userMetadataItems = computed<MetadataItem[]>(() => {
    const items: MetadataItem[] = [];

    items.push({
        label: t('admin.users.metadata.name'),
        value: props.user.name,
    });

    items.push({
        label: t('admin.users.metadata.email'),
        value: props.user.email,
    });

    if (props.user.personalityType) {
        items.push({
            label: t('admin.users.metadata.personality'),
            value: props.user.personalityType,
            badge: true,
            badgeColor: 'purple',
        });
    }

    if (props.user.isAdmin) {
        items.push({
            label: t('admin.users.metadata.role'),
            value: t('admin.users.metadata.adminRole'),
            badge: true,
            badgeColor: 'indigo',
        });
    }

    items.push({
        label: t('admin.users.metadata.joined'),
        value: new Date(props.user.createdAt).toLocaleDateString(),
    });

    return items;
});
</script>

<template>
    <Head :title="$t('admin.users.headTitleUser', { name: props.user.name })" />
    <HeaderPage :title="$t('admin.users.detailsTitle')">
        <template #actions>
            <Link
                :href="countryRoute('admin.users.index')"
                class="text-sm text-indigo-600 hover:text-indigo-900"
            >
                {{ $t('admin.users.backToUsers') }}
            </Link>
        </template>
    </HeaderPage>

    <ContainerPage spacing>
        <!-- Compact User Metadata Card -->
        <CompactMetadataCard :items="userMetadataItems" />

        <!-- Prompts Heading -->
        <div class="mt-6 flex items-baseline gap-2">
            <h2 class="text-lg font-semibold text-indigo-900">
                {{ $t('admin.users.promptsTitle') }}
            </h2>
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
                <p>{{ $t('admin.users.emptyPrompts') }}</p>
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
                                        :current-sort="props.filters.sortBy"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{
                                            $t(
                                                'admin.users.table.taskDescription',
                                            )
                                        }}
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase lg:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="selected_framework"
                                        :current-sort="props.filters.sortBy"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{ $t('admin.users.table.framework') }}
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase sm:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="workflow_stage"
                                        :current-sort="props.filters.sortBy"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{ $t('admin.users.table.status') }}
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    <TableHeaderSortable
                                        column="created_at"
                                        :current-sort="props.filters.sortBy"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{ $t('admin.users.table.created') }}
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
                                        countryRoute('admin.prompt-runs.show', {
                                            promptRun: run.id,
                                        }),
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
                                    {{
                                        run.selectedFramework?.name ||
                                        $t('admin.common.notAvailable')
                                    }}
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
                                            :workflow-stage="run.workflowStage"
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
                            v-if="props.pagination.prevPageUrl"
                            id="pagination-prev-mobile"
                            :href="props.pagination.prevPageUrl"
                            @click="handlePaginationClick('prev')"
                        >
                            {{ $t('admin.pagination.previous') }}
                        </LinkButton>
                    </div>

                    <p
                        v-if="props.pagination.lastPage > 1"
                        class="text-center text-sm text-indigo-700"
                    >
                        {{
                            $t('admin.pagination.pageOf', {
                                current: props.pagination.currentPage,
                                total: props.pagination.lastPage,
                            })
                        }}
                    </p>

                    <div class="flex justify-end">
                        <LinkButton
                            v-if="props.pagination.nextPageUrl"
                            id="pagination-next-mobile"
                            :href="props.pagination.nextPageUrl"
                            @click="handlePaginationClick('next')"
                        >
                            {{ $t('admin.pagination.next') }}
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
                            <i18n-t
                                v-if="
                                    props.pagination.from && props.pagination.to
                                "
                                keypath="admin.pagination.resultsSummary"
                                scope="global"
                                tag="p"
                                class="text-center"
                            >
                                <span class="font-medium">{{
                                    props.pagination.from
                                }}</span>
                                <span class="font-medium">{{
                                    props.pagination.to
                                }}</span>
                                <span class="font-medium">{{
                                    props.pagination.total
                                }}</span>
                            </i18n-t>
                        </div>

                        <!-- Per-page selector -->
                        <div
                            class="mt-3 flex items-center justify-center gap-2"
                        >
                            <label
                                for="per-page"
                                class="text-sm text-indigo-700"
                            >
                                {{ $t('admin.pagination.show') }}
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
                            <span class="text-sm text-indigo-700">
                                {{ $t('admin.pagination.perPage') }}
                            </span>
                        </div>
                    </div>

                    <!-- Desktop Layout -->
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-between"
                    >
                        <!-- Results info -->
                        <div>
                            <i18n-t
                                v-if="
                                    props.pagination.from && props.pagination.to
                                "
                                keypath="admin.pagination.resultsSummary"
                                scope="global"
                                tag="p"
                                class="text-sm text-indigo-700"
                            >
                                <span class="font-medium">{{
                                    props.pagination.from
                                }}</span>
                                <span class="font-medium">{{
                                    props.pagination.to
                                }}</span>
                                <span class="font-medium">{{
                                    props.pagination.total
                                }}</span>
                            </i18n-t>
                        </div>

                        <!-- Per-page selector (centred) -->
                        <div class="flex items-center gap-2">
                            <label
                                for="per-page-desktop"
                                class="text-sm text-indigo-700"
                            >
                                {{ $t('admin.pagination.show') }}
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
                            <span class="text-sm text-indigo-700">
                                {{ $t('admin.pagination.perPage') }}
                            </span>
                        </div>

                        <!-- Navigation -->
                        <div>
                            <nav
                                v-if="props.pagination.lastPage > 1"
                                class="isolate inline-flex -space-x-px rounded-md shadow-xs"
                            >
                                <LinkButton
                                    v-if="props.pagination.prevPageUrl"
                                    id="pagination-prev"
                                    :href="props.pagination.prevPageUrl"
                                    variant="rounded-left"
                                    @click="handlePaginationClick('prev')"
                                >
                                    {{ $t('admin.pagination.previous') }}
                                </LinkButton>
                                <span
                                    class="relative inline-flex items-center border border-indigo-100 bg-white px-4 py-2 text-sm font-medium text-indigo-700"
                                >
                                    {{
                                        $t('admin.pagination.pageOf', {
                                            current:
                                                props.pagination.currentPage,
                                            total: props.pagination.lastPage,
                                        })
                                    }}
                                </span>
                                <LinkButton
                                    v-if="props.pagination.nextPageUrl"
                                    id="pagination-next"
                                    :href="props.pagination.nextPageUrl"
                                    variant="rounded-right"
                                    @click="handlePaginationClick('next')"
                                >
                                    {{ $t('admin.pagination.next') }}
                                </LinkButton>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Session History Section -->
        <div v-if="props.sessionStats" class="mt-8">
            <h2 class="mb-4 text-lg font-semibold text-indigo-900">
                Session History
            </h2>

            <!-- Session Stats -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Total Sessions"
                    :value="props.sessionStats.totalSessions"
                    icon="chart-line"
                    icon-colour="blue"
                />
                <StatCard
                    title="Page Views"
                    :value="props.sessionStats.totalPageViews"
                    icon="eye"
                    icon-colour="green"
                />
                <StatCard
                    title="Avg Duration"
                    :value="formatDuration(props.sessionStats.avgDuration)"
                    icon="clock"
                    icon-colour="purple"
                />
                <StatCard
                    title="Last Active"
                    :value="
                        props.sessionStats.lastActive
                            ? new Date(
                                  props.sessionStats.lastActive,
                              ).toLocaleDateString('en-GB', {
                                  month: 'short',
                                  day: 'numeric',
                              })
                            : 'Never'
                    "
                    icon="calendar-days"
                    icon-colour="indigo"
                />
            </div>

            <!-- Sessions List -->
            <div
                v-if="
                    props.user.visitor?.sessions &&
                    props.user.visitor.sessions.length > 0
                "
            >
                <SessionListItem
                    v-for="session in props.user.visitor.sessions"
                    :key="session.id"
                    :session="session"
                />
                <div class="mt-4 text-center">
                    <Link
                        :href="
                            countryRoute('admin.visitors.show', {
                                visitor: props.user.visitor.id,
                            })
                        "
                        class="text-sm text-indigo-600 hover:text-indigo-900 hover:underline"
                    >
                        View all sessions →
                    </Link>
                </div>
            </div>
            <Card v-else>
                <p class="text-center text-indigo-500">
                    No sessions recorded for this user
                </p>
            </Card>
        </div>
    </ContainerPage>
</template>
