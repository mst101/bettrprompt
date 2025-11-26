<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import AlternativeFrameworks from '@/Components/PromptBuilder/Cards/AlternativeFrameworks.vue';
import PersonalityAdjustments from '@/Components/PromptBuilder/Cards/PersonalityAdjustments.vue';
import SelectedFramework from '@/Components/PromptBuilder/Cards/SelectedFramework.vue';
import TaskClassification from '@/Components/PromptBuilder/Cards/TaskClassification.vue';
import QuestionAnsweringForm from '@/Components/PromptBuilder/QuestionAnsweringForm.vue';
import TaskInformation from '@/Components/PromptOptimizer/Cards/TaskInformation.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: AppLayout,
});

interface ClarifyingQuestion {
    id: string;
    question: string;
    purpose: string;
    required: boolean;
}

interface Props {
    promptRun: PromptRunResource;
}

const taskDescription = computed(() => props.promptRun.taskDescription);

const questions = computed<ClarifyingQuestion[]>(
    () => (props.promptRun.frameworkQuestions as ClarifyingQuestion[]) || [],
);

const answers = ref<(string | null)[]>(
    Array.from({ length: questions.value.length }, () => null),
);
const currentIndex = ref(0);
const isSubmitting = ref(false);
const generationResult = ref<unknown | null>(null);
const submitError = ref<string | null>(null);

const currentQuestion = computed(() => questions.value[currentIndex.value]);
const currentAnswer = computed({
    get: () => answers.value[currentIndex.value] || '',
    set: (value: string) => {
        answers.value[currentIndex.value] = value || null;
    },
});

const clearCurrentAnswer = () => {
    answers.value[currentIndex.value] = null;
};

const atLastQuestion = computed(
    () => currentIndex.value === questions.value.length - 1,
);

const progress = computed(() => ({
    answered: answers.value.filter((a) => a !== null).length,
    total: questions.value.length,
}));

const showAllQuestions = ref(false);

const goNext = () => {
    if (!atLastQuestion.value) {
        currentIndex.value += 1;
    }
};

const goBack = () => {
    if (currentIndex.value > 0) {
        currentIndex.value -= 1;
    }
};

const skipQuestion = () => {
    answers.value[currentIndex.value] = null;
    goNext();
};

const submitAnswer = () => {
    answers.value[currentIndex.value] = currentAnswer.value.trim()
        ? currentAnswer.value
        : null;

    if (atLastQuestion.value) {
        submitAllAnswers();
    } else {
        goNext();
    }
};

const submitAllAnswers = async () => {
    isSubmitting.value = true;
    submitError.value = null;

    try {
        const response = await axios.post(
            route('prompt-builder.generate', props.promptRun.id),
            {
                question_answers: answers.value,
            },
        );

        generationResult.value = response.data;

        // Reload the prompt run to show updated data
        router.reload({ only: ['promptRun'] });
    } catch (error: unknown) {
        const axiosError = error as {
            response?: { data?: { error?: { message?: string } } };
            message?: string;
        };
        submitError.value =
            axiosError?.response?.data?.error?.message ||
            axiosError?.message ||
            'Failed to generate prompt';
    } finally {
        isSubmitting.value = false;
    }
};

const parsedPrompt = computed(() => {
    const result = generationResult.value as {
        data?: {
            optimised_prompt?: string;
            metadata?: {
                framework_used?: {
                    name?: string;
                    code?: string;
                    components?: string[];
                    explanation?: string;
                };
                personality_adjustments?: {
                    trait?: string;
                    adjustment?: string;
                }[];
                model_recommendations?: {
                    rank?: number;
                    model?: string;
                    model_id?: string;
                    rationale?: string;
                }[];
                iteration_suggestions?: string[];
            };
        };
    } | null;

    return {
        prompt: result?.data?.optimised_prompt || null,
        framework: result?.data?.metadata?.framework_used || null,
        adjustments: result?.data?.metadata?.personality_adjustments || [],
        recommendations:
            result?.data?.metadata?.model_recommendations || ([] as []),
        suggestions: result?.data?.metadata?.iteration_suggestions || [],
    };
});

// Define tabs
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [
        // Your Task tab (always shown first)
        {
            id: 'task',
            label: 'Your Task',
            icon: 'squares-2x2',
        },
        {
            id: 'classification',
            label: 'Classification',
            icon: 'tag',
        },
        {
            id: 'framework',
            label: 'Framework',
            icon: 'cube',
        },
    ];

    // Add personality tab if tier is not 'none'
    if (
        props.promptRun.personalityTier &&
        props.promptRun.personalityTier !== 'none'
    ) {
        allTabs.push({
            id: 'personality',
            label: 'Personality',
            icon: 'user',
        });
    }

    // Add questions tab
    allTabs.push({
        id: 'questions',
        label: 'Questions',
        icon: 'question-mark-circle',
        badge:
            progress.value.total > 0
                ? `${progress.value.answered}/${progress.value.total}`
                : undefined,
    });

    // Add alternatives tab if there are any
    if (
        props.promptRun.alternativeFrameworks &&
        props.promptRun.alternativeFrameworks.length > 0
    ) {
        allTabs.push({
            id: 'alternatives',
            label: 'Alternatives',
            icon: 'arrows-right-left',
        });
    }

    return allTabs;
});

const activeTab = ref<string>('task'); // Start on Your Task tab
</script>

<template>
    <Head title="Prompt Analysis" />

    <HeaderPage title="Prompt Builder">
        <template #description> Analysis for: {{ taskDescription }} </template>
    </HeaderPage>

    <ContainerPage>
        <div class="mb-6 max-w-4xl">
            <!-- Tabs Navigation -->
            <Tabs v-model="activeTab" :tabs="tabs" />

            <!-- Tab Content Container -->
            <div
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <!-- Your Task Tab -->
                <TaskInformation
                    v-if="activeTab === 'task'"
                    :prompt-run="promptRun"
                />

                <!-- Classification Tab -->
                <TaskClassification
                    v-if="
                        activeTab === 'classification' &&
                        promptRun.taskClassification
                    "
                    :classification="promptRun.taskClassification"
                />

                <!-- Framework Tab -->
                <SelectedFramework
                    v-if="
                        activeTab === 'framework' &&
                        promptRun.selectedFrameworkDetails
                    "
                    :framework="promptRun.selectedFrameworkDetails"
                />

                <!-- Personality Tab -->
                <PersonalityAdjustments
                    v-if="
                        activeTab === 'personality' && promptRun.personalityTier
                    "
                    :tier="promptRun.personalityTier"
                    :adjustments="promptRun.personalityAdjustmentsPreview || []"
                />

                <!-- Questions Tab -->
                <div v-if="activeTab === 'questions'">
                    <h2 class="text-grey-900 mb-4 text-lg font-semibold">
                        Clarifying Questions
                    </h2>
                    <p
                        v-if="promptRun.questionRationale"
                        class="text-grey-600 mb-4 text-sm"
                    >
                        {{ promptRun.questionRationale }}
                    </p>

                    <!-- One-at-a-time Question Form -->
                    <QuestionAnsweringForm
                        v-if="currentQuestion && !showAllQuestions"
                        v-model:answer="currentAnswer"
                        :question="currentQuestion.question"
                        :current-question-number="currentIndex + 1"
                        :total-questions="questions.length"
                        :is-submitting="isSubmitting"
                        :can-go-back="currentIndex > 0"
                        :show-all="showAllQuestions"
                        @submit="submitAnswer"
                        @skip="skipQuestion"
                        @go-back="goBack"
                        @clear="clearCurrentAnswer"
                        @toggle-show-all="
                            () => (showAllQuestions = !showAllQuestions)
                        "
                    />

                    <!-- Bulk Answer Mode -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="(question, index) in questions"
                            :key="question.id"
                            class="border-grey-200 rounded border p-4"
                        >
                            <div class="flex items-start gap-2">
                                <span
                                    class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                                >
                                    {{ index + 1 }}
                                </span>
                                <div class="flex-1 space-y-2">
                                    <div>
                                        <p
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ question.question }}
                                            <span
                                                v-if="question.required"
                                                class="ml-1 text-red-500"
                                                title="Required"
                                            >
                                                *
                                            </span>
                                        </p>
                                        <p class="text-grey-600 mt-1 text-sm">
                                            {{ question.purpose }}
                                        </p>
                                    </div>
                                    <FormTextarea
                                        :id="`bulk-answer-${index}`"
                                        :model-value="answers[index] || ''"
                                        :label="`Answer ${index + 1}`"
                                        :disabled="isSubmitting"
                                        :rows="3"
                                        :placeholder="`Answer for question ${index + 1}`"
                                        @update:model-value="
                                            (value: string) =>
                                                (answers[index] = value || null)
                                        "
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <button
                                class="text-sm text-indigo-600 underline"
                                type="button"
                                @click="() => (showAllQuestions = false)"
                            >
                                Back to one-at-a-time
                            </button>
                            <button
                                type="button"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                :disabled="isSubmitting"
                                @click="submitAllAnswers"
                            >
                                <span v-if="isSubmitting">Submitting...</span>
                                <span v-else>Submit All Answers</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alternatives Tab -->
                <AlternativeFrameworks
                    v-if="
                        activeTab === 'alternatives' &&
                        promptRun.alternativeFrameworks
                    "
                    :frameworks="promptRun.alternativeFrameworks"
                />
            </div>
        </div>

        <!-- Generation Result -->
        <div v-if="generationResult" class="mb-6 space-y-6">
            <div
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                    Generated Prompt
                </h2>
                <p
                    v-if="parsedPrompt.prompt"
                    class="text-grey-900 whitespace-pre-wrap"
                >
                    {{ parsedPrompt.prompt }}
                </p>
                <p v-else class="text-grey-600 text-sm">
                    No prompt text returned.
                </p>
            </div>

            <div
                v-if="parsedPrompt.framework"
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h3 class="text-grey-900 mb-2 text-lg font-semibold">
                    Framework Used
                </h3>
                <p class="text-grey-900 font-medium">
                    {{ parsedPrompt.framework.name }}
                    ({{ parsedPrompt.framework.code }})
                </p>
                <p class="text-grey-700 mt-2">
                    {{ parsedPrompt.framework.explanation }}
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span
                        v-for="component in parsedPrompt.framework.components"
                        :key="component"
                        class="rounded-full bg-indigo-50 px-3 py-1 text-sm text-indigo-800"
                    >
                        {{ component }}
                    </span>
                </div>
            </div>

            <div
                v-if="parsedPrompt.adjustments.length"
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h3 class="text-grey-900 mb-2 text-lg font-semibold">
                    Personality Adjustments
                </h3>
                <ul class="text-grey-900 list-disc space-y-2 pl-5">
                    <li
                        v-for="(adj, index) in parsedPrompt.adjustments"
                        :key="index"
                    >
                        <span class="font-medium">{{ adj.trait }}:</span>
                        {{ adj.adjustment }}
                    </li>
                </ul>
            </div>

            <div
                v-if="parsedPrompt.recommendations.length"
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h3 class="text-grey-900 mb-2 text-lg font-semibold">
                    Model Recommendations
                </h3>
                <div class="space-y-2">
                    <div
                        v-for="rec in parsedPrompt.recommendations"
                        :key="rec.model_id || rec.model"
                        class="border-grey-100 rounded border p-3"
                    >
                        <p class="text-grey-900 font-medium">
                            #{{ rec.rank }} — {{ rec.model }}
                            <span class="text-grey-600 text-sm">
                                ({{ rec.model_id }})
                            </span>
                        </p>
                        <p class="text-grey-700 mt-1 text-sm">
                            {{ rec.rationale }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="parsedPrompt.suggestions.length"
                class="border-grey-200 rounded-lg border bg-white p-6 shadow-sm"
            >
                <h3 class="text-grey-900 mb-2 text-lg font-semibold">
                    Iteration Suggestions
                </h3>
                <ul class="text-grey-900 list-disc space-y-1 pl-5">
                    <li
                        v-for="(suggestion, index) in parsedPrompt.suggestions"
                        :key="index"
                    >
                        {{ suggestion }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Error Display -->
        <div v-if="submitError" class="rounded-lg bg-red-50 p-4 text-red-600">
            {{ submitError }}
        </div>
    </ContainerPage>
</template>
