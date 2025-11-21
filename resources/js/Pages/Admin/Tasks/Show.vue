<script setup lang="ts">
import Card from '@/Components/Card.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PromptRun {
    id: number;
    personality_type: string | null;
    selected_framework: string | null;
    status: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    task_description: string;
    prompt_runs: {
        data: PromptRun[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}

const props = defineProps<Props>();

const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
        pending: 'bg-gray-100 text-gray-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <Head
        :title="`Admin - Task: ${props.task_description.substring(0, 50)}...`"
    />

    <AppLayout>
        <template #header>
            <div class="space-y-2">
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Tasks
                </Link>
                <h2 class="text-xl leading-tight font-semibold text-gray-800">
                    Prompt Runs for Task
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Task Description -->
                <Card class="mb-6">
                    <h3 class="mb-2 font-semibold text-gray-900">
                        Task Description:
                    </h3>
                    <p class="text-gray-700">{{ props.task_description }}</p>
                </Card>

                <!-- Prompt Runs List -->
                <Card>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        ID
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        User
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Personality
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Framework
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Created
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr
                                    v-for="run in props.prompt_runs.data"
                                    :key="run.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 text-sm font-medium text-gray-900"
                                    >
                                        #{{ run.id }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div v-if="run.user">
                                            <div class="font-medium">
                                                {{ run.user.name }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{ run.user.email }}
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-400">
                                            Guest
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <span
                                            v-if="run.personality_type"
                                            class="inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800"
                                        >
                                            {{ run.personality_type }}
                                        </span>
                                        <span v-else class="text-gray-400">
                                            N/A
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ run.selected_framework || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="[
                                                'inline-flex rounded-full px-2 text-xs leading-5 font-semibold',
                                                getStatusColor(run.status),
                                            ]"
                                        >
                                            {{ run.status }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 text-sm whitespace-nowrap text-gray-500"
                                    >
                                        {{
                                            new Date(
                                                run.created_at,
                                            ).toLocaleString()
                                        }}
                                    </td>
                                    <td
                                        class="px-6 py-4 text-right text-sm font-medium"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'admin.prompt-runs.show',
                                                    run.id,
                                                )
                                            "
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >
                                            View Details →
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="props.prompt_runs.data.length === 0">
                                    <td
                                        colspan="7"
                                        class="px-6 py-4 text-center text-sm text-gray-500"
                                    >
                                        No prompt runs found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="props.prompt_runs.last_page > 1"
                        class="mt-4 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
                    >
                        <div>
                            <p class="text-sm text-gray-700">
                                Page
                                <span class="font-medium">{{
                                    props.prompt_runs.current_page
                                }}</span>
                                of
                                <span class="font-medium">{{
                                    props.prompt_runs.last_page
                                }}</span>
                            </p>
                        </div>
                        <div>
                            <nav
                                class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                            >
                                <Link
                                    v-for="link in props.prompt_runs.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        link.active
                                            ? 'z-10 bg-indigo-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50',
                                        'relative inline-flex items-center border border-gray-300 px-4 py-2 text-sm font-medium',
                                    ]"
                                    :text="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
