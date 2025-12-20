<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useWorkflowStageColor } from '@/Composables/features/useWorkflowStageColor';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PromptRun {
    id: number;
    taskDescription: string;
    personalityType: string | null;
    selectedFramework: string | null;
    frameworkReasoning: string | null;
    frameworkQuestions: string[] | null;
    clarifyingAnswers: string[] | null;
    optimizedPrompt: string | null;
    workflowStage: string;
    createdAt: string;
    completedAt: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    promptRun: PromptRun;
}

const props = defineProps<Props>();

const { getStatusColor } = useWorkflowStageColor();
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

        <ContainerPage spacing>
            <!-- Status and Meta -->
            <Card>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            Status
                        </label>
                        <span
                            :class="[
                                'mt-1 inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                getStatusColor(props.promptRun.workflowStage),
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

            <!-- Task Description -->
            <Card>
                <h2 class="mb-2 font-semibold text-indigo-900">
                    Task Description
                </h2>
                <p class="text-indigo-700">
                    {{ props.promptRun.taskDescription }}
                </p>
            </Card>

            <!-- Framework Selection -->
            <Card v-if="props.promptRun.selectedFramework">
                <h2 class="mb-3 font-semibold text-indigo-900">
                    Selected Framework
                </h2>
                <div class="space-y-3">
                    <div>
                        <span
                            class="inline-flex rounded-lg bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                        >
                            {{ props.promptRun.selectedFramework }}
                        </span>
                    </div>
                    <div v-if="props.promptRun.frameworkReasoning">
                        <label
                            class="block text-sm font-medium text-indigo-700"
                        >
                            Reasoning
                        </label>
                        <p class="mt-1 text-indigo-700">
                            {{ props.promptRun.frameworkReasoning }}
                        </p>
                    </div>
                </div>
            </Card>

            <!-- Clarifying Questions & Answers -->
            <Card
                v-if="
                    props.promptRun.frameworkQuestions &&
                    props.promptRun.frameworkQuestions.length > 0
                "
            >
                <h2 class="mb-4 font-semibold text-indigo-900">
                    Clarifying Questions & Answers
                </h2>
                <div class="space-y-4">
                    <div
                        v-for="(question, index) in props.prompt_run
                            .frameworkQuestions"
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
                                props.promptRun.clarifyingAnswers &&
                                props.promptRun.clarifyingAnswers[index]
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
                                        {{
                                            props.promptRun.clarifyingAnswers[
                                                index
                                            ]
                                        }}
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
            </Card>

            <!-- Optimised Prompt -->
            <Card v-if="props.promptRun.optimizedPrompt">
                <h2 class="mb-3 font-semibold text-indigo-900">
                    Optimised Prompt
                </h2>
                <div class="rounded-lg bg-indigo-50 p-4">
                    <pre
                        class="font-mono text-sm whitespace-pre-wrap text-indigo-900"
                        >{{ props.promptRun.optimizedPrompt }}</pre
                    >
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
