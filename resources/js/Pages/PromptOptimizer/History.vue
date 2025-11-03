<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PromptRun {
    id: number;
    personality_type: string;
    task_description: string;
    status: string;
    created_at: string;
    completed_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPromptRuns {
    data: PromptRun[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    promptRuns: PaginatedPromptRuns;
}

defineProps<Props>();

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const truncate = (text: string, length: number = 100) => {
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-centre justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Prompt History
                </h2>
                <a
                    :href="route('prompt-optimizer.index')"
                    class="inline-flex items-centre rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700"
                >
                    Create New
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div v-if="promptRuns.data.length === 0" class="p-6 text-centre text-gray-500">
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
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Personality Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Task Description
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Created
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr v-for="promptRun in promptRuns.data" :key="promptRun.id" class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                            {{ promptRun.personality_type }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            {{ truncate(promptRun.task_description, 80) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            <span
                                                :class="getStatusBadgeClass(promptRun.status)"
                                                class="inline-flex rounded-full px-2 py-1 text-xs font-semibold uppercase"
                                            >
                                                {{ promptRun.status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(promptRun.created_at) }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Link
                                                :href="route('prompt-optimizer.show', promptRun.id)"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="promptRuns.last_page > 1" class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                            <div class="flex items-centre justify-between">
                                <div class="flex flex-1 justify-between sm:hidden">
                                    <Link
                                        v-if="promptRuns.current_page > 1"
                                        :href="promptRuns.links[0].url || '#'"
                                        class="relative inline-flex items-centre rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="promptRuns.current_page < promptRuns.last_page"
                                        :href="promptRuns.links[promptRuns.links.length - 1].url || '#'"
                                        class="relative ml-3 inline-flex items-centre rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex sm:flex-1 sm:items-centre sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing
                                            <span class="font-medium">{{ (promptRuns.current_page - 1) * promptRuns.per_page + 1 }}</span>
                                            to
                                            <span class="font-medium">{{ Math.min(promptRuns.current_page * promptRuns.per_page, promptRuns.total) }}</span>
                                            of
                                            <span class="font-medium">{{ promptRuns.total }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                            <Link
                                                v-for="(link, index) in promptRuns.links"
                                                :key="index"
                                                :href="link.url || '#'"
                                                :class="[
                                                    link.active
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                                    'relative inline-flex items-centre border px-4 py-2 text-sm font-medium',
                                                    index === 0 ? 'rounded-l-md' : '',
                                                    index === promptRuns.links.length - 1 ? 'rounded-r-md' : '',
                                                ]"
                                                :disabled="!link.url"
                                                v-html="link.label"
                                            />
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
