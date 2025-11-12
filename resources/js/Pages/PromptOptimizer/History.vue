<script setup lang="ts">
import Card from '@/Components/Card.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useLocalStorage } from '@/Composables/useLocalStorage';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
import SvgChevronDown from '@/Icons/SvgChevronDown.vue';
import SvgChevronUp from '@/Icons/SvgChevronUp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Paginated, PromptRunResource } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

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

const props = defineProps<Props>();

const truncate = (text: string | null | undefined, length: number = 100) => {
    if (!text) return '';
    if (text.length <= length) return text;
    return text.substring(0, length) + '...';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getPersonalityTypeName = (type: string | null) => {
    if (!type) return '';
    // Extract base type without -A/-T suffix
    const baseType = type.split('-')[0] as keyof typeof PERSONALITY_TYPE_NAMES;
    return PERSONALITY_TYPE_NAMES[baseType] || '';
};

const getFullPersonalityType = (type: string | null) => {
    if (!type) return '';
    const name = getPersonalityTypeName(type);
    return name ? `${name} (${type})` : type;
};

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

const isSortedBy = (column: string) => {
    return props.filters.sort_by === column;
};

const sortDirection = computed(() => {
    return props.filters.sort_direction;
});

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
});
</script>

<template>
    <Head title="Prompt History" />

    <HeaderPage title="Prompt History">
        <template #actions>
            <Link
                :href="route('prompt-optimizer.index')"
                class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
            >
                Create New
            </Link>
        </template>
    </HeaderPage>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
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
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase sm:table-cell"
                                    >
                                        <button
                                            @click="sortBy('personality_type')"
                                            class="group inline-flex items-center gap-1 hover:text-gray-700"
                                        >
                                            Personality Type
                                            <span class="flex flex-col">
                                                <SvgChevronUp
                                                    class="h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'personality_type',
                                                            ) &&
                                                            sortDirection ===
                                                                'asc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'personality_type',
                                                            ) ||
                                                            sortDirection !==
                                                                'asc',
                                                    }"
                                                />
                                                <SvgChevronDown
                                                    class="-mt-1 h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'personality_type',
                                                            ) &&
                                                            sortDirection ===
                                                                'desc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'personality_type',
                                                            ) ||
                                                            sortDirection !==
                                                                'desc',
                                                    }"
                                                />
                                            </span>
                                        </button>
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        <button
                                            @click="sortBy('task_description')"
                                            class="group inline-flex items-center gap-1 hover:text-gray-700"
                                        >
                                            Task Description
                                            <span class="flex flex-col">
                                                <SvgChevronUp
                                                    class="h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'task_description',
                                                            ) &&
                                                            sortDirection ===
                                                                'asc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'task_description',
                                                            ) ||
                                                            sortDirection !==
                                                                'asc',
                                                    }"
                                                />
                                                <SvgChevronDown
                                                    class="-mt-1 h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'task_description',
                                                            ) &&
                                                            sortDirection ===
                                                                'desc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'task_description',
                                                            ) ||
                                                            sortDirection !==
                                                                'desc',
                                                    }"
                                                />
                                            </span>
                                        </button>
                                    </th>
                                    <th
                                        scope="col"
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase lg:table-cell"
                                    >
                                        <button
                                            @click="
                                                sortBy('selected_framework')
                                            "
                                            class="group inline-flex items-center gap-1 hover:text-gray-700"
                                        >
                                            Framework
                                            <span class="flex flex-col">
                                                <SvgChevronUp
                                                    class="h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'selected_framework',
                                                            ) &&
                                                            sortDirection ===
                                                                'asc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'selected_framework',
                                                            ) ||
                                                            sortDirection !==
                                                                'asc',
                                                    }"
                                                />
                                                <SvgChevronDown
                                                    class="-mt-1 h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'selected_framework',
                                                            ) &&
                                                            sortDirection ===
                                                                'desc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'selected_framework',
                                                            ) ||
                                                            sortDirection !==
                                                                'desc',
                                                    }"
                                                />
                                            </span>
                                        </button>
                                    </th>
                                    <th
                                        scope="col"
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase sm:table-cell"
                                    >
                                        <button
                                            @click="sortBy('status')"
                                            class="group inline-flex items-center gap-1 hover:text-gray-700"
                                        >
                                            Status
                                            <span class="flex flex-col">
                                                <SvgChevronUp
                                                    class="h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'status',
                                                            ) &&
                                                            sortDirection ===
                                                                'asc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'status',
                                                            ) ||
                                                            sortDirection !==
                                                                'asc',
                                                    }"
                                                />
                                                <SvgChevronDown
                                                    class="-mt-1 h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'status',
                                                            ) &&
                                                            sortDirection ===
                                                                'desc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'status',
                                                            ) ||
                                                            sortDirection !==
                                                                'desc',
                                                    }"
                                                />
                                            </span>
                                        </button>
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        <button
                                            @click="sortBy('created_at')"
                                            class="group inline-flex items-center gap-1 hover:text-gray-700"
                                        >
                                            Created
                                            <span class="flex flex-col">
                                                <SvgChevronUp
                                                    class="h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'created_at',
                                                            ) &&
                                                            sortDirection ===
                                                                'asc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'created_at',
                                                            ) ||
                                                            sortDirection !==
                                                                'asc',
                                                    }"
                                                />
                                                <SvgChevronDown
                                                    class="-mt-1 h-3 w-3 transition-colors"
                                                    :class="{
                                                        'text-indigo-600':
                                                            isSortedBy(
                                                                'created_at',
                                                            ) &&
                                                            sortDirection ===
                                                                'desc',
                                                        'text-gray-400':
                                                            !isSortedBy(
                                                                'created_at',
                                                            ) ||
                                                            sortDirection !==
                                                                'desc',
                                                    }"
                                                />
                                            </span>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="promptRun in promptRuns.data"
                                    :key="promptRun.id"
                                    class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-100"
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
                                            truncate(
                                                promptRun.taskDescription,
                                                80,
                                            )
                                        }}
                                    </td>
                                    <td
                                        class="hidden px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 lg:table-cell"
                                    >
                                        {{
                                            promptRun.selectedFramework ||
                                            '\u2014'
                                        }}
                                    </td>
                                    <td
                                        class="hidden px-6 py-4 text-sm whitespace-nowrap sm:table-cell"
                                    >
                                        <StatusBadge
                                            :status="promptRun.status"
                                        />
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                    >
                                        <div>
                                            {{
                                                formatDate(promptRun.createdAt)
                                            }}
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
                            <Link
                                v-if="promptRuns.meta.prevPageUrl"
                                :href="promptRuns.meta.prevPageUrl"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Previous
                            </Link>
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
                            <Link
                                v-if="promptRuns.meta.nextPageUrl"
                                :href="promptRuns.meta.nextPageUrl"
                                class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Next
                            </Link>
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
                                        promptRuns.meta.from &&
                                        promptRuns.meta.to
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
                                <label
                                    for="per-page"
                                    class="text-sm text-gray-700"
                                >
                                    Show
                                </label>
                                <input
                                    id="per-page"
                                    v-model="perPageInput"
                                    @blur="changePerPage"
                                    @keydown.enter="changePerPage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-16 rounded-md border-gray-300 bg-white px-2 py-1 pl-4 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-gray-700"
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
                                    v-if="
                                        promptRuns.meta.from &&
                                        promptRuns.meta.to
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
                                    @blur="changePerPage"
                                    @keydown.enter="changePerPage"
                                    type="number"
                                    min="1"
                                    max="100"
                                    class="w-16 rounded-md border-gray-300 bg-white py-1 pl-2 text-center text-sm text-black focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <span class="text-sm text-gray-700"
                                    >per page</span
                                >
                            </div>

                            <!-- Navigation -->
                            <div>
                                <nav
                                    v-if="promptRuns.meta.lastPage > 1"
                                    class="isolate inline-flex -space-x-px rounded-md shadow-xs"
                                    aria-label="Pagination"
                                >
                                    <Link
                                        v-if="promptRuns.meta.prevPageUrl"
                                        :href="promptRuns.meta.prevPageUrl"
                                        class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    >
                                        Previous
                                    </Link>
                                    <span
                                        class="relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700"
                                    >
                                        Page
                                        {{ promptRuns.meta.currentPage }}
                                        of
                                        {{ promptRuns.meta.lastPage }}
                                    </span>
                                    <Link
                                        v-if="promptRuns.meta.nextPageUrl"
                                        :href="promptRuns.meta.nextPageUrl"
                                        class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    >
                                        Next
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
