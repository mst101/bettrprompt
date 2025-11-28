<script setup lang="ts">
import Card from '@/Components/Card.vue';
import ContainerPage from '@/Components/ContainerPage.vue';
import FormTextarea from '@/Components/FormTextarea.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import LinkButton from '@/Components/LinkButton.vue';
import AlternativeFrameworks from '@/Components/PromptBuilder/Cards/AlternativeFrameworks.vue';
import ApiUsage from '@/Components/PromptBuilder/Cards/ApiUsage.vue';
import CognitiveRequirements from '@/Components/PromptBuilder/Cards/CognitiveRequirements.vue';
import PersonalityAdjustments from '@/Components/PromptBuilder/Cards/PersonalityAdjustments.vue';
import PersonalityAdjustmentsSummary from '@/Components/PromptBuilder/Cards/PersonalityAdjustmentsSummary.vue';
import Recommendations from '@/Components/PromptBuilder/Cards/Recommendations.vue';
import SelectedFramework from '@/Components/PromptBuilder/Cards/SelectedFramework.vue';
import TaskClassification from '@/Components/PromptBuilder/Cards/TaskClassification.vue';
import TaskTraitAlignment from '@/Components/PromptBuilder/Cards/TaskTraitAlignment.vue';
import QuestionAnsweringForm from '@/Components/PromptBuilder/QuestionAnsweringForm.vue';
import OptimizedPrompt from '@/Components/PromptOptimizer/Cards/OptimizedPrompt.vue';
import TaskInformation from '@/Components/PromptOptimizer/Cards/TaskInformation.vue';
import Tabs, { type Tab } from '@/Components/Tabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PromptRunResource } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';

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

const questions = computed<ClarifyingQuestion[]>(
    () => (props.promptRun.frameworkQuestions as ClarifyingQuestion[]) || [],
);

const answers = ref<(string | null)[]>(
    Array.from({ length: questions.value.length }, () => null),
);
const currentIndex = ref(0);
const isSubmitting = ref(false);
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
        await axios.post(route('prompt-builder.generate', props.promptRun.id), {
            question_answers: answers.value,
        });

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

// Define tabs
const tabs = computed<Tab[]>(() => {
    const allTabs: Tab[] = [];

    // Your Task tab (always shown)
    allTabs.push({
        id: 'task',
        label: 'Your Task',
        icon: 'squares-2x2',
    });

    // Framework tab
    allTabs.push({
        id: 'framework',
        label: 'Framework',
        icon: 'cube',
    });

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
    });

    // Optimised Prompt tab (only for completed runs with prompt)
    if (props.promptRun.optimizedPrompt) {
        allTabs.push({
            id: 'prompt',
            label: 'Optimised Prompt',
            icon: 'sparkles',
        });
    }

    // Recommendations tab (only shown when model recommendations exist)
    if (
        props.promptRun.modelRecommendations ||
        props.promptRun.iterationSuggestions
    ) {
        allTabs.push({
            id: 'recommendations',
            label: 'Recommendations',
            icon: 'light-bulb',
        });
    }

    // API Usage tab (always shown for transparency)
    allTabs.push({
        id: 'api-usage',
        label: 'API Usage',
        icon: 'chart-bar',
    });

    return allTabs;
});

const activeTab = ref<string>('task'); // Start on Your Task tab

// Switch to prompt tab when optimized prompt is returned
watch(
    () => props.promptRun.optimizedPrompt,
    (newPrompt) => {
        if (newPrompt) {
            activeTab.value = 'prompt';
        }
    },
);
</script>

<template>
    <Head title="Prompt Analysis" />

    <HeaderPage title="Prompt Builder">
        <template #actions>
            <LinkButton :href="route('prompt-builder.index')" variant="primary">
                Create New
            </LinkButton>
        </template>
    </HeaderPage>

    <ContainerPage>
        <div class="mb-6 max-w-4xl shadow-xs sm:rounded-lg">
            <Tabs v-model="activeTab" :tabs="tabs" />

            <!-- Optimised Prompt Tab -->
            <OptimizedPrompt
                v-if="activeTab === 'prompt' && promptRun.optimizedPrompt"
                :optimized-prompt="promptRun.optimizedPrompt"
                :prompt-run-id="promptRun.id"
            />

            <!-- Your Task Tab -->
            <div v-if="activeTab === 'task'" class="space-y-4">
                <TaskInformation :prompt-run="promptRun" />
                <TaskClassification
                    v-if="promptRun.taskClassification"
                    :classification="promptRun.taskClassification as any"
                />
                <CognitiveRequirements
                    v-if="promptRun.cognitiveRequirements"
                    :requirements="promptRun.cognitiveRequirements as any"
                />
            </div>

            <!-- Framework Tab -->
            <div v-if="activeTab === 'framework'" class="space-y-4">
                <SelectedFramework
                    v-if="promptRun.selectedFramework"
                    :framework="promptRun.selectedFramework as any"
                />
                <AlternativeFrameworks
                    v-if="promptRun.alternativeFrameworks"
                    :frameworks="promptRun.alternativeFrameworks as any"
                />
            </div>

            <!-- Personality Tab -->
            <div v-if="activeTab === 'personality'" class="space-y-4">
                <TaskTraitAlignment
                    v-if="promptRun.taskTraitAlignment"
                    :alignment="promptRun.taskTraitAlignment as any"
                />
                <PersonalityAdjustments
                    v-if="promptRun.personalityTier"
                    :tier="promptRun.personalityTier as any"
                    :adjustments="promptRun.personalityAdjustmentsPreview || []"
                />
                <PersonalityAdjustmentsSummary
                    v-if="promptRun.personalityAdjustmentsSummary"
                    :adjustments="
                        promptRun.personalityAdjustmentsSummary as any
                    "
                />
            </div>

            <!-- Questions Tab -->
            <Card v-if="activeTab === 'questions'">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    Clarifying Questions
                </h2>
                <p
                    v-if="promptRun.questionRationale"
                    class="mb-4 text-sm text-gray-600"
                >
                    {{ promptRun.questionRationale }}
                </p>

                <!-- Display submitted answers if they exist -->
                <div
                    v-if="
                        promptRun.clarifyingAnswers &&
                        promptRun.clarifyingAnswers.length > 0
                    "
                    class="mb-6 space-y-4"
                >
                    <h3 class="text-base font-semibold text-gray-900">
                        Your Answers
                    </h3>
                    <div
                        v-for="(question, index) in questions"
                        :key="`answer-${question.id}`"
                        class="rounded-lg border border-gray-200 bg-gray-50 p-4"
                    >
                        <div class="flex items-start gap-2">
                            <span
                                class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-medium text-white"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="flex-1 space-y-2">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ question.question }}
                                </p>
                                <p class="text-sm text-gray-700">
                                    {{
                                        promptRun.clarifyingAnswers[index] ||
                                        '(Skipped)'
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

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
                        class="rounded border border-gray-200 p-4"
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
                                    <p class="mt-1 text-sm text-gray-600">
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
            </Card>

            <!-- Recommendations Tab -->
            <Recommendations
                v-if="activeTab === 'recommendations'"
                :model-recommendations="promptRun.modelRecommendations as any"
                :iteration-suggestions="promptRun.iterationSuggestions as any"
            />

            <!-- API Usage Tab -->
            <ApiUsage
                v-if="activeTab === 'api-usage'"
                :analysis-usage="promptRun.analysisApiUsage as any"
                :generation-usage="promptRun.generationApiUsage as any"
            />
        </div>

        <!-- Error Display -->
        <div v-if="submitError" class="rounded-lg bg-red-50 p-4 text-red-600">
            {{ submitError }}
        </div>
    </ContainerPage>
</template>
