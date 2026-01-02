<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import Tabs, { type Tab } from '@/Components/Base/Tabs.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useWorkflowStageColor } from '@/Composables/features/useWorkflowStageColor';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/Types/resources/PromptRunResource';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Props {
    promptRun: PromptRunResource;
}

const props = defineProps<Props>();

const { getWorkflowStageColor } = useWorkflowStageColor();

// Type-safe helper for selectedFramework
interface FrameworkData {
    name: string;
    code: string;
    rationale: string;
    components?: string[];
}

const selectedFramework = computed(() => {
    return props.promptRun.selectedFramework as FrameworkData | null;
});

const frameworkQuestions = computed(() => {
    return props.promptRun.frameworkQuestions as string[] | null;
});

const clarifyingAnswers = computed(() => {
    return props.promptRun.clarifyingAnswers as string[] | null;
});

// Define tabs dynamically based on available data
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: 'Task',
        icon: 'squares-2x2',
    });

    // Framework tab (show if framework has been selected)
    if (selectedFramework.value) {
        allTabs.push({
            id: 'framework',
            label: 'Framework',
            icon: 'cube',
        });
    }

    // Questions tab (show if framework questions exist)
    if (frameworkQuestions.value && frameworkQuestions.value.length > 0) {
        allTabs.push({
            id: 'questions',
            label: 'Questions',
            icon: 'question-mark-circle',
        });
    }

    // Optimised Prompt tab (show if prompt exists)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: 'Optimised Prompt',
            icon: 'sparkles',
        });
    }

    return allTabs;
});

const activeTab = ref<string>('task');
</script>

<template>
    <Head :title="`Admin - Prompt Run #${props.promptRun.id}`" />

    <AppLayout>
        <HeaderPage :title="`Prompt Run #${props.promptRun.id}`">
            <template #actions>
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-900"
                >
                    ← Back to Tasks
                </Link>
            </template>
        </HeaderPage>

        <ContainerPage v-if="props.promptRun" spacing>
            <!-- Workflow Stage and Meta -->
            <Card>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            Workflow Stage
                        </label>
                        <span
                            :class="[
                                'mt-1 inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                getWorkflowStageColor(
                                    props.promptRun.workflowStage,
                                ),
                            ]"
                        >
                            {{ props.promptRun.workflowStage }}
                        </span>
                    </div>
                    <div v-if="props.promptRun.user">
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            User
                        </label>
                        <div class="mt-1">
                            <div class="font-medium text-indigo-900">
                                {{ props.promptRun.user.name }}
                            </div>
                            <div class="text-sm text-indigo-500">
                                {{ props.promptRun.user.email }}
                            </div>
                        </div>
                    </div>
                    <div v-if="props.promptRun.personalityType">
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            Personality Type
                        </label>
                        <div class="mt-1">
                            <span
                                class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800"
                            >
                                {{ props.promptRun.personalityType }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            Created
                        </label>
                        <div class="mt-1 text-indigo-900">
                            {{
                                new Date(
                                    props.promptRun.createdAt,
                                ).toLocaleString()
                            }}
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Tabbed Content -->
            <div class="max-w-4xl shadow-xs sm:rounded-lg">
                <Tabs v-model="activeTab" :tabs="tabs" />

                <!-- Task Tab -->
                <div
                    v-if="activeTab === 'task'"
                    class="space-y-4 rounded-b-lg border border-t-0 border-indigo-100 bg-white p-6"
                >
                    <div>
                        <h2 class="mb-2 font-semibold text-indigo-900">
                            Task Description
                        </h2>
                        <p class="text-indigo-700">
                            {{ props.promptRun.taskDescription }}
                        </p>
                    </div>
                </div>

                <!-- Framework Tab -->
                <div
                    v-if="activeTab === 'framework' && selectedFramework"
                    class="space-y-4 rounded-b-lg border border-t-0 border-indigo-100 bg-white p-6"
                >
                    <div>
                        <h2 class="mb-3 font-semibold text-indigo-900">
                            Selected Framework
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <span
                                    class="inline-flex rounded-lg bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                                >
                                    {{ selectedFramework.name }}
                                </span>
                            </div>
                            <div v-if="selectedFramework.rationale">
                                <label
                                    class="block text-sm font-medium text-indigo-700"
                                >
                                    Reasoning
                                </label>
                                <p class="mt-1 text-indigo-700">
                                    {{ selectedFramework.rationale }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Questions Tab -->
                <div
                    v-if="
                        activeTab === 'questions' &&
                        frameworkQuestions &&
                        frameworkQuestions.length > 0
                    "
                    class="space-y-4 rounded-b-lg border border-t-0 border-indigo-100 bg-white p-6"
                >
                    <div class="space-y-4">
                        <div
                            v-for="(question, index) in frameworkQuestions"
                            :key="index"
                            class="rounded-lg border border-indigo-100 p-4"
                        >
                            <div class="mb-2 flex items-start">
                                <DynamicIcon
                                    name="help-circle"
                                    class="mt-1 mr-2 h-5 w-5 shrink-0 text-blue-600"
                                />
                                <div class="flex-1">
                                    <label
                                        class="block text-sm font-medium text-indigo-700"
                                    >
                                        Question {{ index + 1 }}
                                    </label>
                                    <p class="mt-1 text-indigo-900">
                                        {{ question }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    clarifyingAnswers &&
                                    clarifyingAnswers[index]
                                "
                                class="mt-3 ml-7 rounded-lg bg-indigo-50 p-3"
                            >
                                <div class="flex items-start">
                                    <DynamicIcon
                                        name="check-circle"
                                        class="mt-1 mr-2 h-5 w-5 shrink-0 text-green-600"
                                    />
                                    <div class="flex-1">
                                        <label
                                            class="block text-sm font-medium text-indigo-700"
                                        >
                                            Answer
                                        </label>
                                        <p class="mt-1 text-indigo-900">
                                            {{ clarifyingAnswers[index] }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="mt-3 ml-7 rounded-lg bg-yellow-50 p-3"
                            >
                                <p class="text-sm text-yellow-800">
                                    Not answered yet
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optimised Prompt Tab -->
                <div
                    v-if="
                        activeTab === 'prompt' &&
                        props.promptRun.optimizedPrompt
                    "
                    class="space-y-4 rounded-b-lg border border-t-0 border-indigo-100 bg-white p-6"
                >
                    <div>
                        <h2 class="mb-3 font-semibold text-indigo-900">
                            Optimised Prompt
                        </h2>
                        <div class="rounded-lg bg-indigo-50 p-4">
                            <pre
                                class="font-mono text-sm whitespace-pre-wrap text-indigo-900"
                                >{{ props.promptRun.optimizedPrompt }}</pre
                            >
                        </div>
                    </div>
                </div>
            </div>
        </ContainerPage>
    </AppLayout>
</template>
