<script setup lang="ts">
import ContainerPage from '@/Components/ContainerPage.vue';
import HeaderPage from '@/Components/HeaderPage.vue';
import QuestionAnsweringForm from '@/Components/PromptOptimizer/QuestionAnsweringForm.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
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

interface AnalysisData {
    task_classification: {
        primary_category: string;
        secondary_category: string | null;
        complexity: string;
        classification_reasoning: string;
    };
    selected_framework: {
        name: string;
        code: string;
        components: string[];
        rationale: string;
    };
    alternative_frameworks: Array<{
        name: string;
        code: string;
        when_to_use_instead: string;
    }>;
    personality_tier: 'full' | 'partial' | 'none';
    personality_adjustments_preview: string[];
    clarifying_questions: ClarifyingQuestion[];
    question_rationale: string;
}

interface Props {
    analysis: {
        success: boolean;
        data: AnalysisData;
        original_input: {
            task_description: string;
            personality_type: string | null;
            trait_percentages: Record<string, number> | null;
        };
        error: {
            message: string;
            details: string;
        } | null;
    };
}

const taskDescription = computed(
    () => props.analysis.original_input.task_description,
);
const analysisData = computed(() => props.analysis.data);

const questions = computed(
    () => props.analysis.data?.clarifying_questions || [],
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

const noop = () => {};

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
        const payload = {
            task_classification: analysisData.value.task_classification,
            selected_framework: analysisData.value.selected_framework,
            alternative_frameworks: analysisData.value.alternative_frameworks,
            personality_tier: analysisData.value.personality_tier,
            personality_adjustments_preview:
                analysisData.value.personality_adjustments_preview,
            original_task_description:
                props.analysis.original_input.task_description,
            personality_type: props.analysis.original_input.personality_type,
            trait_percentages: props.analysis.original_input.trait_percentages,
            question_answers: answers.value,
        };

        const response = await axios.post(
            route('prompt-builder.generate'),
            payload,
        );

        generationResult.value = response.data;
    } catch (error: unknown) {
        const axiosError = error as {
            response?: { data?: { error?: string } };
            message?: string;
        };
        submitError.value =
            axiosError?.response?.data?.error ||
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
</script>

<template>
    <Head title="Prompt Analysis" />

    <ContainerPage>
        <HeaderPage
            title="Prompt Analysis Results"
            :description="`Analysis for: ${taskDescription}`"
        />

        <!-- Task Classification -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Task Classification
            </h2>
            <div class="space-y-2">
                <div>
                    <span class="text-grey-700 font-medium">Category:</span>
                    <span class="text-grey-900 ml-2">
                        {{ analysisData.task_classification.primary_category }}
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Complexity:</span>
                    <span class="text-grey-900 ml-2 capitalize">
                        {{ analysisData.task_classification.complexity }}
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Reasoning:</span>
                    <p class="text-grey-900 mt-1">
                        {{
                            analysisData.task_classification
                                .classification_reasoning
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Selected Framework -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Selected Framework
            </h2>
            <div class="space-y-2">
                <div>
                    <span class="text-grey-700 font-medium">Framework:</span>
                    <span class="text-grey-900 ml-2">
                        {{ analysisData.selected_framework.name }}
                        ({{ analysisData.selected_framework.code }})
                    </span>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Components:</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <span
                            v-for="component in analysisData.selected_framework
                                .components"
                            :key="component"
                            class="rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800"
                        >
                            {{ component }}
                        </span>
                    </div>
                </div>
                <div>
                    <span class="text-grey-700 font-medium">Rationale:</span>
                    <p class="text-grey-900 mt-1">
                        {{ analysisData.selected_framework.rationale }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Personality Adjustments -->
        <div
            v-if="analysisData.personality_tier !== 'none'"
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Personality Adjustments ({{ analysisData.personality_tier }})
            </h2>
            <ul class="text-grey-900 list-inside list-disc space-y-1">
                <li
                    v-for="(
                        adjustment, index
                    ) in analysisData.personality_adjustments_preview"
                    :key="index"
                >
                    {{ adjustment }}
                </li>
            </ul>
        </div>

        <!-- Clarifying Questions -->
        <div
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Clarifying Questions
            </h2>
            <p class="text-grey-600 mb-4 text-sm">
                {{ analysisData.question_rationale }}
            </p>
            <QuestionAnsweringForm
                v-if="currentQuestion"
                v-model:answer="currentAnswer"
                :question="currentQuestion.question"
                :current-question-number="currentIndex + 1"
                :total-questions="questions.length"
                :is-submitting="isSubmitting"
                :can-go-back="currentIndex > 0"
                :show-all="false"
                @submit="submitAnswer"
                @skip="skipQuestion"
                @go-back="goBack"
                @clear="clearCurrentAnswer"
                @toggle-show-all="noop"
            />
        </div>

        <!-- Alternative Frameworks -->
        <div
            v-if="analysisData.alternative_frameworks.length > 0"
            class="border-grey-200 mb-6 rounded-lg border bg-white p-6 shadow-sm"
        >
            <h2 class="text-grey-900 mb-3 text-lg font-semibold">
                Alternative Frameworks
            </h2>
            <div class="space-y-3">
                <div
                    v-for="framework in analysisData.alternative_frameworks"
                    :key="framework.code"
                    class="border-grey-200 bg-grey-50 rounded border p-3"
                >
                    <div class="text-grey-900 font-medium">
                        {{ framework.name }} ({{ framework.code }})
                    </div>
                    <div class="text-grey-600 mt-1 text-sm">
                        When to use: {{ framework.when_to_use_instead }}
                    </div>
                </div>
            </div>
        </div>

        <div v-if="generationResult" class="space-y-6">
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

        <div v-if="submitError" class="text-red-600">
            {{ submitError }}
        </div>
    </ContainerPage>
</template>
