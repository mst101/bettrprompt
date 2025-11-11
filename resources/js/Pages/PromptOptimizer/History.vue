<script setup lang="ts">
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SvgChevronDown from '@/Icons/SvgChevronDown.vue';
import SvgChevronUp from '@/Icons/SvgChevronUp.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PERSONALITY_TYPE_NAMES } from '@/constants/workflow';
import type { Paginated, PromptRunResource } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const perPage = parseInt(target.value);

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

const perPageOptions = [
    { value: '10', label: '10' },
    { value: '15', label: '15' },
    { value: '25', label: '25' },
    { value: '50', label: '50' },
];
</script>

<template>
    <Head title="Prompt History" />

    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="items-centre flex justify-between">
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Prompt History
                </h2>
                <Link
                    :href="route('prompt-optimizer.index')"
                    class="items-centre inline-flex rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                >
                    Create New
                </Link>
            </div>
        </div>
    </header>

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
                    <!-- Per-page selector -->
                    <div
                        class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 sm:px-6"
                    >
                        <div class="flex items-center gap-2">
                            <label for="per-page" class="text-sm text-gray-700">
                                Show
                            </label>
                            <select
                                id="per-page"
                                :value="filters.per_page"
                                @change="changePerPage"
                                class="rounded-md border-gray-300 py-1 pr-8 pl-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="option in perPageOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <span class="text-sm text-gray-700">per page</span>
                        </div>
                    </div>

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

                    <!-- Pagination -->
                    <div
                        v-if="promptRuns.meta.lastPage > 1"
                        class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6"
                    >
                        <div class="items-centre flex justify-between">
                            <div class="flex flex-1 justify-between sm:hidden">
                                <Link
                                    v-if="promptRuns.meta.prevPageUrl"
                                    :href="promptRuns.meta.prevPageUrl"
                                    class="items-centre relative inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Previous
                                </Link>
                                <Link
                                    v-if="promptRuns.meta.nextPageUrl"
                                    :href="promptRuns.meta.nextPageUrl"
                                    class="items-centre relative ml-3 inline-flex rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Next
                                </Link>
                            </div>
                            <div
                                class="sm:items-centre hidden sm:flex sm:flex-1 sm:justify-between"
                            >
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
                                <div>
                                    <nav
                                        class="isolate inline-flex -space-x-px rounded-md shadow-xs"
                                        aria-label="Pagination"
                                    >
                                        <Link
                                            v-if="promptRuns.meta.prevPageUrl"
                                            :href="promptRuns.meta.prevPageUrl"
                                            class="items-centre relative inline-flex rounded-l-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                        >
                                            Previous
                                        </Link>
                                        <span
                                            class="items-centre relative inline-flex border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700"
                                        >
                                            Page
                                            {{ promptRuns.meta.currentPage }}
                                            of
                                            {{ promptRuns.meta.lastPage }}
                                        </span>
                                        <Link
                                            v-if="promptRuns.meta.nextPageUrl"
                                            :href="promptRuns.meta.nextPageUrl"
                                            class="items-centre relative inline-flex rounded-r-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-500 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                        >
                                            Next
                                        </Link>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>
