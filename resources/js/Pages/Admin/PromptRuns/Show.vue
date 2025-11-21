<script setup lang="ts">
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface PromptRun {
    id: number;
    task_description: string;
    personality_type: string | null;
    selected_framework: string | null;
    framework_reasoning: string | null;
    framework_questions: string[] | null;
    clarifying_answers: string[] | null;
    optimized_prompt: string | null;
    status: string;
    workflow_stage: string | null;
    created_at: string;
    completed_at: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    prompt_run: PromptRun;
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
    <Head :title="`Admin - Prompt Run #${props.prompt_run.id}`" />

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
                    Prompt Run #{{ props.prompt_run.id }}
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Status and Meta -->
                <Card>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Status
                            </label>
                            <span
                                :class="[
                                    'mt-1 inline-flex rounded-full px-3 py-1 text-sm font-semibold',
                                    getStatusColor(props.prompt_run.status),
                                ]"
                            >
                                {{ props.prompt_run.status }}
                            </span>
                        </div>
                        <div v-if="props.prompt_run.user">
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                User
                            </label>
                            <div class="mt-1">
                                <div class="font-medium text-gray-900">
                                    {{ props.prompt_run.user.name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ props.prompt_run.user.email }}
                                </div>
                            </div>
                        </div>
                        <div v-if="props.prompt_run.personality_type">
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Personality Type
                            </label>
                            <div class="mt-1">
                                <span
                                    class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800"
                                >
                                    {{ props.prompt_run.personality_type }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Created
                            </label>
                            <div class="mt-1 text-gray-900">
                                {{
                                    new Date(
                                        props.prompt_run.created_at,
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
                        {{ props.prompt_run.task_description }}
                    </p>
                </Card>

                <!-- Framework Selection -->
                <Card v-if="props.prompt_run.selected_framework">
                    <h3 class="mb-3 font-semibold text-gray-900">
                        Selected Framework
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <span
                                class="inline-flex rounded-lg bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800"
                            >
                                {{ props.prompt_run.selected_framework }}
                            </span>
                        </div>
                        <div v-if="props.prompt_run.framework_reasoning">
                            <label
                                class="block text-sm font-medium text-gray-700"
                            >
                                Reasoning
                            </label>
                            <p class="mt-1 text-gray-700">
                                {{ props.prompt_run.framework_reasoning }}
                            </p>
                        </div>
                    </div>
                </Card>

                <!-- Clarifying Questions & Answers -->
                <Card
                    v-if="
                        props.prompt_run.framework_questions &&
                        props.prompt_run.framework_questions.length > 0
                    "
                >
                    <h3 class="mb-4 font-semibold text-gray-900">
                        Clarifying Questions & Answers
                    </h3>
                    <div class="space-y-4">
                        <div
                            v-for="(question, index) in props.prompt_run
                                .framework_questions"
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
                                    props.prompt_run.clarifying_answers &&
                                    props.prompt_run.clarifying_answers[index]
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
                                                props.prompt_run
                                                    .clarifying_answers[index]
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
                <Card v-if="props.prompt_run.optimized_prompt">
                    <h3 class="mb-3 font-semibold text-gray-900">
                        Optimised Prompt
                    </h3>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <pre
                            class="font-mono text-sm whitespace-pre-wrap text-gray-900"
                            >{{ props.prompt_run.optimized_prompt }}</pre
                        >
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
