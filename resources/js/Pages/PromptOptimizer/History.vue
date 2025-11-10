<script setup lang="ts">
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Paginated, PromptRunResource } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

interface Props {
    promptRuns: Paginated<PromptRunResource>;
}

defineProps<Props>();

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
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase sm:table-cell"
                                    >
                                        Personality Type
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Task Description
                                    </th>
                                    <th
                                        scope="col"
                                        class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase sm:table-cell"
                                    >
                                        Status
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Created
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
                                        {{ promptRun.personalityType }}
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
