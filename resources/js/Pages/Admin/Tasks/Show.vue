<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useWorkflowStageColor } from '@/Composables/features/useWorkflowStageColor';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PromptRunResource } from '@/Types';
import { Head, Link, router } from '@inertiajs/vue3';

interface Props {
    taskDescription: string;
    promptRuns: {
        data: PromptRunResource[];
        links: Array<Record<string, unknown>>;
        currentPage: number;
        lastPage: number;
    };
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
    <Head
        :title="
            $t('admin.tasks.headTitleTask', {
                task: props.taskDescription.substring(0, 50),
            })
        "
    />

    <AppLayout>
        <HeaderPage :title="$t('admin.tasks.promptRunsTitle')">
            <template #actions>
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    {{ $t('admin.tasks.backToTasks') }}
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage>
            <!-- Task Description -->
            <Card class="mb-6">
                <h2 class="mb-2 font-semibold text-indigo-900">
                    {{ $t('admin.tasks.taskDescriptionLabel') }}
                </h2>
                <p class="text-indigo-700">{{ props.taskDescription }}</p>
            </Card>

            <!-- Prompt Runs List -->
            <Card>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{ $t('admin.tasks.columns.id') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{ $t('admin.tasks.columns.user') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{ $t('admin.tasks.columns.personality') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{ $t('admin.tasks.columns.framework') }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{
                                        $t('admin.tasks.columns.workflowStage')
                                    }}
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-500 uppercase"
                                >
                                    {{ $t('admin.tasks.columns.created') }}
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
                                    class="px-6 py-4 text-sm font-medium text-indigo-900"
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
                                <td class="px-6 py-4 text-sm text-indigo-900">
                                    <div v-if="run.user">
                                        <div class="font-medium">
                                            {{ run.user.name }}
                                        </div>
                                        <div class="text-indigo-500">
                                            {{ run.user.email }}
                                        </div>
                                    </div>
                                    <span v-else class="text-indigo-400">
                                        {{ $t('admin.tasks.guest') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-indigo-900">
                                    <span
                                        v-if="run.personalityType"
                                        class="inline-flex rounded-full bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-800"
                                    >
                                        {{ run.personalityType }}
                                    </span>
                                    <span v-else class="text-indigo-400">
                                        {{ $t('admin.common.notAvailable') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-indigo-900">
                                    {{
                                        run.selectedFramework?.name ||
                                        $t('admin.common.notAvailable')
                                    }}
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
                                    colspan="6"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    {{ $t('admin.tasks.emptyRuns') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="props.promptRuns.lastPage > 1"
                    class="mt-4 flex items-center justify-between border-t border-indigo-100 px-4 py-3 sm:px-6"
                >
                    <div>
                        <p class="text-sm text-indigo-700">
                            {{
                                $t('admin.pagination.pageOf', {
                                    current: props.promptRuns.currentPage,
                                    total: props.promptRuns.lastPage,
                                })
                            }}
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
                                        : 'bg-white text-indigo-700 hover:bg-indigo-50',
                                    'relative inline-flex items-center border border-indigo-100 px-4 py-2 text-sm font-medium',
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
