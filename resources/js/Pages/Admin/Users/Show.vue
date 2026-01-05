<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useWorkflowStageColor } from '@/Composables/features/useWorkflowStageColor';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';

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
    next_page_url: string | null;
    prev_page_url: string | null;
    links: Array<Record<string, unknown>>;
}

interface Props {
    user: User;
    promptRuns: PromptRunResource[];
    pagination: Pagination;
    promptRunsCount: number;
}

const props = defineProps<Props>();

const { getWorkflowStageColor } = useWorkflowStageColor();

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
    <Head :title="`Admin - User: ${props.user.name}`" />
    <AppLayout>
        <HeaderPage title="User Details">
            <template #actions>
                <Link
                    :href="route('admin.users.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Back to Users
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage spacing>
            <!-- Compact User Metadata Card -->
            <Card>
                <div class="flex flex-wrap items-center gap-4 sm:gap-6">
                    <!-- Name -->
                    <div
                        class="border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
                    >
                        <span class="text-sm text-indigo-900">
                            {{ props.user.name }}
                        </span>
                    </div>

                    <!-- Email -->
                    <div
                        class="border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
                    >
                        <span class="text-sm text-indigo-500">
                            {{ props.user.email }}
                        </span>
                    </div>

                    <!-- Personality Type -->
                    <div
                        v-if="props.user.personalityType"
                        class="border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
                    >
                        <span
                            class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-xs font-semibold text-purple-800"
                        >
                            {{ props.user.personalityType }}
                        </span>
                    </div>

                    <!-- Admin Badge -->
                    <div
                        v-if="props.user.isAdmin"
                        class="border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
                    >
                        <span
                            class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700"
                        >
                            Admin
                        </span>
                    </div>

                    <!-- Joined Date -->
                    <div
                        class="border-r border-indigo-200 pr-4 last:border-r-0"
                    >
                        <span class="text-sm text-indigo-500">
                            {{
                                new Date(
                                    props.user.createdAt,
                                ).toLocaleDateString()
                            }}
                        </span>
                    </div>
                </div>
            </Card>

            <!-- Prompts Heading -->
            <div class="mt-6 flex items-baseline gap-2">
                <h2 class="text-lg font-semibold text-indigo-900">Prompts</h2>
                <span class="text-sm text-indigo-500">
                    ({{ props.promptRunsCount }})
                </span>
            </div>

            <!-- Prompt Runs Table -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase sm:table-cell"
                                >
                                    Task Description
                                </th>
                                <th
                                    class="hidden px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase lg:table-cell"
                                >
                                    Framework
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    Created
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-indigo-200 bg-white dark:bg-indigo-50"
                        >
                            <tr
                                v-for="run in props.promptRuns"
                                :key="run.id"
                                class="group cursor-pointer transition hover:bg-indigo-50 dark:hover:bg-indigo-100"
                                @click="handleRowClick($event, run.id)"
                                @auxclick.prevent="
                                    handleMiddleClick($event, run.id)
                                "
                            >
                                <td
                                    class="hidden px-6 py-4 text-sm text-indigo-900 sm:table-cell"
                                >
                                    {{
                                        run.taskDescription?.substring(0, 40) ||
                                        'N/A'
                                    }}
                                </td>
                                <td
                                    class="hidden px-6 py-4 text-sm text-indigo-900 lg:table-cell"
                                >
                                    {{ run.selectedFramework?.name || 'N/A' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 text-xs leading-5 font-semibold',
                                            getWorkflowStageColor(
                                                run.workflowStage,
                                            ),
                                        ]"
                                    >
                                        {{ run.workflowStage }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-500"
                                >
                                    {{
                                        new Date(run.createdAt).toLocaleString()
                                    }}
                                </td>
                            </tr>
                            <tr v-if="props.promptRuns.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    No prompts yet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="props.pagination.last_page > 1"
                    class="mt-4 flex items-center justify-between border-t border-indigo-100 px-4 py-3 sm:px-6"
                >
                    <div>
                        <p class="text-sm text-indigo-700">
                            Page
                            <span class="font-medium">{{
                                props.pagination.current_page
                            }}</span>
                            of
                            <span class="font-medium">{{
                                props.pagination.last_page
                            }}</span>
                        </p>
                    </div>
                    <div>
                        <nav
                            class="isolate inline-flex -space-x-px rounded-md shadow-sm"
                        >
                            <Link
                                v-for="link in props.pagination.links"
                                v-show="link.url"
                                :key="link.label"
                                :href="(link.url as string) || '#'"
                                :class="[
                                    link.active
                                        ? 'z-10 bg-indigo-600 text-white'
                                        : 'bg-white text-indigo-700 hover:bg-indigo-50',
                                    'relative inline-flex items-center border border-indigo-100 px-4 py-2 text-sm font-medium',
                                ]"
                                :text="link.label as string"
                            />
                        </nav>
                    </div>
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
