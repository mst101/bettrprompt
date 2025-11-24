<script setup lang="ts">
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
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
    status: string;
    workflowStage: string | null;
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
    <Head :title="`Admin - Prompt Run #${props.promptRun.id}`" />

    <AppLayout>
        <HeaderPage :title="`Prompt Run #${props.promptRun.id}`">
            <template #actions>
                <Link
                    :href="route('admin.tasks.index')"
                    class="text-sm text-gray-600 hover:text-gray-900"
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
                        <label class="block text-sm font-medium text-gray-700">
                            Status
                        </label>
                        <span
                            :class="[
                                'mt-1 inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                getStatusColor(props.promptRun.status),
                            ]"
                        >
                            {{ props.promptRun.status }}
                        </span>
                    </div>
                    <div v-if="props.promptRun.user">
                        <label class="block text-sm font-medium text-gray-700">
                            User
                        </label>
                        <div class="mt-1">
                            <div class="font-medium text-gray-900">
                                {{ props.promptRun.user.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ props.promptRun.user.email }}
                            </div>
                        </div>
                    </div>
                    <div v-if="props.promptRun.personalityType">
                        <label class="block text-sm font-medium text-gray-700">
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
                        <label class="block text-sm font-medium text-gray-700">
                            Created
                        </label>
                        <div class="mt-1 text-gray-900">
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
                <h3 class="mb-2 font-semibold text-gray-900">
                    Task Description
                </h3>
                <p class="text-gray-700">
                    {{ props.promptRun.taskDescription }}
                </p>
            </Card>

            <!-- Framework Selection -->
            <Card v-if="props.promptRun.selectedFramework">
                <h3 class="mb-3 font-semibold text-gray-900">
                    Selected Framework
                </h3>
                <div class="space-y-3">
                    <div>
                        <span
                            class="inline-flex rounded-lg bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                        >
                            {{ props.promptRun.selectedFramework }}
                        </span>
                    </div>
                    <div v-if="props.promptRun.frameworkReasoning">
                        <label class="block text-sm font-medium text-gray-700">
                            Reasoning
                        </label>
                        <p class="mt-1 text-gray-700">
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
                <h3 class="mb-4 font-semibold text-gray-900">
                    Clarifying Questions & Answers
                </h3>
                <div class="space-y-4">
                    <div
                        v-for="(question, index) in props.prompt_run
                            .frameworkQuestions"
                        :key="index"
                        class="rounded-lg border border-gray-200 p-4"
                    >
                        <div class="mb-2 flex items-start">
                            <DynamicIcon
                                name="help-circle"
                                class="mt-1 mr-2 h-5 w-5 flex-shrink-0 text-blue-600"
                            />
                            <div class="flex-1">
                                <label
                                    class="block text-sm font-medium text-gray-700"
                                >
                                    Question {{ index + 1 }}
                                </label>
                                <p class="mt-1 text-gray-900">
                                    {{ question }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                props.promptRun.clarifyingAnswers &&
                                props.promptRun.clarifyingAnswers[index]
                            "
                            class="mt-3 ml-7 rounded-lg bg-gray-50 p-3"
                        >
                            <div class="flex items-start">
                                <DynamicIcon
                                    name="check-circle"
                                    class="mt-1 mr-2 h-5 w-5 flex-shrink-0 text-green-600"
                                />
                                <div class="flex-1">
                                    <label
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Answer
                                    </label>
                                    <p class="mt-1 text-gray-900">
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
                <h3 class="mb-3 font-semibold text-gray-900">
                    Optimised Prompt
                </h3>
                <div class="rounded-lg bg-gray-50 p-4">
                    <pre
                        class="font-mono text-sm whitespace-pre-wrap text-gray-900"
                        >{{ props.promptRun.optimizedPrompt }}</pre
                    >
                </div>
            </Card>
        </ContainerPage>
    </AppLayout>
</template>
