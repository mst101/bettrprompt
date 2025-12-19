<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface PromptRun {
    id: number;
    personalityType: string | null;
    selectedFramework: string | null;
    status: string;
    createdAt: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    taskDescription: string;
    promptRuns: {
        data: PromptRun[];
        links: Array<Record<string, unknown>>;
        currentPage: number;
        lastPage: number;
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

const handleRowClick = (event: MouseEvent, runId: number): void => {
    // Allow default behavior for right-click and middle-click
    if (event.button === 2 || event.button === 1) {
        return;
    }

    // Allow Ctrl/Cmd + click to open in new tab
    if (event.ctrlKey || event.metaKey) {
        globalThis.window.open(
            route('admin.prompt-runs.show', runId),
            '_blank',
        );
        return;
    }

    // Normal left click - use Inertia navigation
    router.visit(route('admin.prompt-runs.show', runId));
};

const handleMiddleClick = (event: MouseEvent, runId: number): void => {
    if (event.button === 1) {
        globalThis.window.open(
            route('admin.prompt-runs.show', runId),
            '_blank',
        );
    }
};
</script>

<template>
    <Head
        :title="`Admin - Task: ${props.taskDescription.substring(0, 50)}...`"
    />

    <AppLayout>
        <HeaderPage title="Prompt Runs for Task">
            <template #actions>
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Tasks
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage>
            <!-- Task Description -->
            <Card class="mb-6">
                <h3 class="mb-2 font-semibold text-gray-900">
                    Task Description:
                </h3>
                <p class="text-gray-700">{{ props.taskDescription }}</p>
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr
                                v-for="run in props.promptRuns.data"
                                :key="run.id"
                                class="group cursor-pointer transition hover:bg-gray-50"
                                @click="handleRowClick($event, run.id)"
                                @auxclick.prevent="
                                    handleMiddleClick($event, run.id)
                                "
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium text-gray-900"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'admin.prompt-runs.show',
                                                run.id,
                                            )
                                        "
                                        class="block"
                                        @click.prevent
                                    >
                                        #{{ run.id }}
                                    </Link>
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
                                        v-if="run.personalityType"
                                        class="inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800"
                                    >
                                        {{ run.personalityType }}
                                    </span>
                                    <span v-else class="text-gray-400">
                                        N/A
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ run.selectedFramework || 'N/A' }}
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
                                        new Date(run.createdAt).toLocaleString()
                                    }}
                                </td>
                            </tr>
                            <tr v-if="props.promptRuns.data.length === 0">
                                <td
                                    colspan="6"
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
                    v-if="props.promptRuns.lastPage > 1"
                    class="mt-4 flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6"
                >
                    <div>
                        <p class="text-sm text-gray-700">
                            Page
                            <span class="font-medium">{{
                                props.promptRuns.currentPage
                            }}</span>
                            of
                            <span class="font-medium">{{
                                props.promptRuns.lastPage
                            }}</span>
                        </p>
                    </div>
                    <div>
                        <nav
                            class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                        >
                            <Link
                                v-for="link in props.promptRuns.links"
                                v-show="link.url"
                                :key="link.label"
                                :href="link.url || '#'"
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
        </ContainerPage>
    </AppLayout>
</template>
