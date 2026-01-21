<script setup lang="ts">
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LinkButton from '@/Components/Base/LinkButton.vue';
import TableHeaderSortable from '@/Components/Base/TableHeaderSortable.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { useLocalStorage } from '@/Composables/data/useLocalStorage';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Paginated, PromptRunResource } from '@/Types';
import { getFullPersonalityType } from '@/Utils/data/personalityTypes';
import { formatDate, truncateText } from '@/Utils/formatting/formatters';
import { Head, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface Props {
    promptRuns: Paginated<PromptRunResource>;
    filters: {
        sortBy: string;
        sortDirection: string;
        perPage: number;
    };
}

const sortBy = (column: string) => {
    const currentSortBy = props.filters.sortBy;
    const currentDirection = props.filters.sortDirection;

    let newDirection = 'asc';
    if (currentSortBy === column && currentDirection === 'asc') {
        newDirection = 'desc';
    }

    router.get(
        countryRoute('prompt-builder.history'),
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

// Use localStorage for per_page preference (default 10)
const perPageStorage = useLocalStorage('history_per_page', 10);

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
        countryRoute('prompt-builder.history'),
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
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

// Focus management for pagination buttons
const handlePaginationClick = (direction: 'prev' | 'next') => {
    sessionStorage.setItem('pagination_focus', direction);
};

// On mount, if the per_page from server doesn't match localStorage, update it
onMounted(() => {
    const storedPerPage = perPageStorage.value;
    if (storedPerPage !== props.filters.perPage) {
        router.get(
            countryRoute('prompt-builder.history'),
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

const { confirm } = useAlert();

const handleDelete = async (promptRunId: number, event: Event) => {
    event.stopPropagation(); // Prevent row click from firing

    const confirmed = await confirm(
        t('promptBuilder.confirmations.deletePromptRun.message'),
        t('promptBuilder.confirmations.deletePromptRun.title'),
        {
            confirmButtonStyle: 'danger',
            confirmText: t('common.buttons.delete'),
        },
    );

    if (!confirmed) {
        return;
    }

    router.delete(
        countryRoute('prompt-builder.destroy', {
            promptRun: promptRunId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                // Success feedback is handled by flash message
            },
        },
    );
};
</script>

<template>
    <Head :title="$t('promptBuilder.history.title')" />

    <HeaderPage :title="$t('promptBuilder.history.title')">
        <template #actions>
            <LinkButton
                :href="countryRoute('prompt-builder.index')"
                variant="primary"
                icon="plus"
                icon-position="left"
            >
                {{ $t('promptBuilder.actions.createNew') }}
            </LinkButton>
        </template>
    </HeaderPage>

    <ContainerPage>
        <Card padding="none">
            <div
                v-if="promptRuns.data.length === 0"
                class="p-6 text-center text-indigo-500"
            >
                <p>{{ $t('promptBuilder.history.empty.title') }}</p>
                <a
                    :href="countryRoute('prompt-builder.index')"
                    class="mt-2 text-indigo-700 hover:text-indigo-800"
                >
                    {{ $t('promptBuilder.history.empty.cta') }}
                </a>
            </div>

            <div v-else>
                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-white dark:bg-indigo-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-indigo-700 uppercase sm:table-cell"
                                >
                                    <TableHeaderSortable
                                        column="personality_type"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{
                                            $t(
                                                'promptBuilder.history.table.personalityType',
                                            )
                                        }}
                                    </TableHeaderSortable>
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider whitespace-nowrap text-indigo-700 uppercase"
                                >
                                    <TableHeaderSortable
                                        column="task_description"
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{
                                            $t(
                                                'promptBuilder.history.table.taskDescription',
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
                                        :current-sort="filters.sort_by"
                                        :sort-direction="sortDirection"
                                        @sort="sortBy"
                                    >
                                        {{
                                            $t(
                                                'promptBuilder.history.table.framework',
                                            )
                                        }}
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
                                        {{
                                            $t(
                                                'promptBuilder.history.table.status',
                                            )
                                        }}
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
                                        {{
                                            $t(
                                                'promptBuilder.history.table.created',
                                            )
                                        }}
                                    </TableHeaderSortable>
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-indigo-200 bg-white text-indigo-900 dark:bg-indigo-50"
                        >
                            <tr
                                v-for="promptRun in promptRuns.data"
                                :key="promptRun.id"
                                class="group duration-150rounded-md cursor-pointer transition-colors hover:bg-indigo-50 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 dark:hover:bg-indigo-100"
                                tabindex="0"
                                @click="
                                    router.visit(
                                        countryRoute('prompt-builder.show', {
                                            promptRun: promptRun.id,
                                        }),
                                    )
                                "
                                @keydown.enter="
                                    router.visit(
                                        countryRoute('prompt-builder.show', {
                                            promptRun: promptRun.id,
                                        }),
                                    )
                                "
                            >
                                <td
                                    class="hidden px-6 py-4 text-sm whitespace-nowrap transition-colors duration-150 group-hover:text-indigo-950 sm:table-cell"
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
                                <td
                                    data-testid="table-cell-task"
                                    class="px-6 py-4 text-sm transition-colors duration-150 group-hover:text-indigo-950"
                                >
                                    {{
                                        truncateText(
                                            promptRun.taskDescription,
                                            80,
                                        )
                                    }}
                                </td>
                                <td
                                    data-testid="table-cell-framework"
                                    class="hidden px-6 py-4 text-sm font-medium whitespace-nowrap text-indigo-900 lg:table-cell"
                                >
                                    {{
                                        promptRun.selectedFramework?.name ||
                                        '\u2014'
                                    }}
                                </td>
                                <td
                                    class="hidden px-6 py-4 text-sm whitespace-nowrap sm:table-cell"
                                >
                                    <StatusBadge
                                        :workflow-stage="
                                            promptRun.workflowStage
                                        "
                                    />
                                </td>
                                <td
                                    data-testid="table-cell-date"
                                    class="px-6 py-4 text-xs whitespace-nowrap text-indigo-700 transition-colors duration-150 group-hover:text-indigo-800"
                                >
                                    <div>
                                        {{ formatDate(promptRun.createdAt) }}
                                    </div>
                                    <div class="mt-1 sm:hidden">
                                        <StatusBadge
                                            :workflow-stage="
                                                promptRun.workflowStage
                                            "
                                        />
                                    </div>
                                    <div class="mt-2">
                                        <ButtonSecondary
                                            type="button"
                                            class="group-hover:border-indigo-200 dark:group-hover:bg-indigo-300 dark:hover:bg-indigo-400!"
                                            size="sm"
                                            @click="
                                                handleDelete(
                                                    promptRun.id,
                                                    $event,
                                                )
                                            "
                                        >
                                            <DynamicIcon
                                                name="trash"
                                                class="mr-2 -ml-1 h-4 w-4"
                                            />
                                            {{ $t('common.buttons.delete') }}
                                        </ButtonSecondary>
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
                            {{
                                $t('promptBuilder.history.pagination.previous')
                            }}
                        </LinkButton>
                    </div>

                    <p
                        v-if="promptRuns.meta.lastPage > 1"
                        class="text-center text-sm text-indigo-700"
                    >
                        {{
                            $t('promptBuilder.history.pagination.pageOf', {
                                current: promptRuns.meta.currentPage,
                                total: promptRuns.meta.lastPage,
                            })
                        }}
                    </p>

                    <div class="flex justify-end">
                        <LinkButton
                            v-if="promptRuns.meta.nextPageUrl"
                            id="pagination-next-mobile"
                            :href="promptRuns.meta.nextPageUrl"
                            @click="handlePaginationClick('next')"
                        >
                            {{ $t('promptBuilder.history.pagination.next') }}
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
                                    promptRuns.meta.from && promptRuns.meta.to
                                "
                                keypath="promptBuilder.history.pagination.resultsSummary"
                                scope="global"
                                tag="p"
                                class="text-center"
                            >
                                <span class="font-medium">{{
                                    promptRuns.meta.from
                                }}</span>
                                <span class="font-medium">{{
                                    promptRuns.meta.to
                                }}</span>
                                <span class="font-medium">{{
                                    promptRuns.meta.total
                                }}</span>
                            </i18n-t>
                        </div>

                        <!-- Per-page selector -->
                        <div
                            class="mt-3 flex items-center justify-center-safe gap-2"
                        >
                            <label
                                for="per-page"
                                data-testid="per-page-label"
                                class="text-sm text-indigo-700"
                            >
                                {{
                                    $t('promptBuilder.history.pagination.show')
                                }}
                            </label>
                            <input
                                id="per-page"
                                v-model="perPageInput"
                                data-testid="per-page-input-mobile"
                                type="number"
                                min="1"
                                max="100"
                                class="w-16 rounded-md border-indigo-100 bg-white px-2 py-1 pl-4 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                @blur="changePerPage"
                                @keydown.enter="changePerPage"
                            />
                            <span class="text-sm text-indigo-700">
                                {{
                                    $t(
                                        'promptBuilder.history.pagination.perPage',
                                    )
                                }}
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
                                    promptRuns.meta.from && promptRuns.meta.to
                                "
                                keypath="promptBuilder.history.pagination.resultsSummary"
                                scope="global"
                                tag="p"
                                class="text-sm text-indigo-700"
                            >
                                <span class="font-medium">{{
                                    promptRuns.meta.from
                                }}</span>
                                <span class="font-medium">{{
                                    promptRuns.meta.to
                                }}</span>
                                <span class="font-medium">{{
                                    promptRuns.meta.total
                                }}</span>
                            </i18n-t>
                        </div>

                        <!-- Per-page selector (centred) -->
                        <div class="flex items-center gap-2">
                            <label
                                for="per-page-desktop"
                                class="text-sm text-indigo-700"
                            >
                                {{
                                    $t('promptBuilder.history.pagination.show')
                                }}
                            </label>
                            <input
                                id="per-page-desktop"
                                v-model="perPageInput"
                                data-testid="per-page-input"
                                type="number"
                                min="1"
                                max="100"
                                class="w-16 rounded-md border-indigo-100 bg-white py-1 pl-2 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                @blur="changePerPage"
                                @keydown.enter="changePerPage"
                            />
                            <span class="text-sm text-indigo-700">
                                {{
                                    $t(
                                        'promptBuilder.history.pagination.perPage',
                                    )
                                }}
                            </span>
                        </div>

                        <!-- Navigation -->
                        <div>
                            <Pagination :meta="promptRuns.meta" />
                        </div>
                    </div>
                </div>
            </div>
        </Card>
    </ContainerPage>
</template>
