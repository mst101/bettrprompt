<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

interface PromptRun {
    id: number;
    personality_type: string;
    trait_percentages: Record<string, number> | null;
    task_description: string;
    selected_framework: string | null;
    framework_reasoning: string | null;
    framework_questions: string[] | null;
    clarifying_answers: (string | null)[] | null;
    optimized_prompt: string | null;
    status: string;
    workflow_stage: string;
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface Progress {
    answered: number;
    total: number;
}

interface Props {
    promptRun: PromptRun;
    currentQuestion: string | null;
    progress: Progress;
}

const props = defineProps<Props>();

const copied = ref(false);
const isSubmitting = ref(false);

// Collapsible Q&A state
const expandedQuestions = ref<Set<number>>(new Set());

const toggleQuestion = (index: number) => {
    if (expandedQuestions.value.has(index)) {
        expandedQuestions.value.delete(index);
    } else {
        expandedQuestions.value.add(index);
    }
};

const allExpanded = () => {
    const totalQuestions = props.promptRun.framework_questions?.length ?? 0;
    return totalQuestions > 0 && expandedQuestions.value.size === totalQuestions;
};

const toggleAll = () => {
    if (allExpanded()) {
        expandedQuestions.value.clear();
    } else {
        const totalQuestions = props.promptRun.framework_questions?.length ?? 0;
        for (let i = 0; i < totalQuestions; i++) {
            expandedQuestions.value.add(i);
        }
    }
};

// Form for submitting answers
const answerForm = useForm({
    answer: '',
});

const submitAnswer = () => {
    if (!answerForm.answer.trim()) return;

    isSubmitting.value = true;
    answerForm.post(route('prompt-optimizer.answer', props.promptRun.id), {
        preserveScroll: true,
        onSuccess: () => {
            answerForm.reset();
            isSubmitting.value = false;
        },
        onError: () => {
            isSubmitting.value = false;
        },
    });
};

const skipQuestion = () => {
    isSubmitting.value = true;
    router.post(
        route('prompt-optimizer.skip', props.promptRun.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const copyToClipboard = async () => {
    if (!props.promptRun.optimized_prompt) return;

    try {
        await navigator.clipboard.writeText(props.promptRun.optimized_prompt);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const getStatusBadgeClass = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'processing':
            return 'bg-yellow-100 text-yellow-800';
        case 'failed':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-grey-100 text-grey-800';
    }
};

const getWorkflowStageLabel = (stage: string) => {
    switch (stage) {
        case 'submitted':
            return 'Submitted';
        case 'framework_selected':
            return 'Framework Selected';
        case 'answering_questions':
            return 'Answering Questions';
        case 'generating_prompt':
            return 'Generating Prompt';
        case 'completed':
            return 'Completed';
        case 'failed':
            return 'Failed';
        default:
            return stage;
    }
};

// Laravel Echo for real-time updates
onMounted(() => {
    const channel = window.Echo.channel(`prompt-run.${props.promptRun.id}`);

    // Listen for framework selection
    channel.listen('FrameworkSelected', (event: any) => {
        console.log('Framework selected:', event);
        // Reload the page to show questions
        router.reload({ preserveScroll: true });
    });

    // Listen for prompt optimization completion
    channel.listen('PromptOptimizationCompleted', (event: any) => {
        console.log('Optimization completed:', event);
        // Reload the page to show final prompt
        router.reload({ preserveScroll: true });
    });
});

onUnmounted(() => {
    window.Echo.leave(`prompt-run.${props.promptRun.id}`);
});
</script>

<template>
    <Head title="Optimised Prompt" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-grey-800">
                    Prompt Optimiser
                </h2>
                <a
                    :href="route('prompt-optimizer.index')"
                    class="text-sm text-indigo-600 hover:text-indigo-800"
                >
                    Create New
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Status Badges -->
                <div class="mb-4 flex items-center gap-2">
                    <span
                        :class="getStatusBadgeClass(promptRun.status)"
                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase"
                    >
                        {{ promptRun.status }}
                    </span>
                    <span
                        class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800"
                    >
                        {{ getWorkflowStageLabel(promptRun.workflow_stage) }}
                    </span>
                </div>

                <!-- Input Information -->
                <div
                    class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-grey-900">
                            Your Task
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-grey-700"
                                    >Personality Type:</span
                                >
                                <span class="ml-2 text-sm text-grey-900">{{
                                    promptRun.personality_type
                                }}</span>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-grey-700"
                                    >Task Description:</span
                                >
                                <p
                                    class="ml-2 mt-1 whitespace-pre-wrap text-sm text-grey-900"
                                >
                                    {{ promptRun.task_description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Framework Selection Info -->
                <div
                    v-if="
                        promptRun.selected_framework &&
                        promptRun.framework_reasoning
                    "
                    class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-grey-900">
                            Selected Framework
                        </h3>

                        <div class="space-y-3">
                            <div>
                                <span
                                    class="inline-flex rounded-md bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-800"
                                >
                                    {{ promptRun.selected_framework }}
                                </span>
                            </div>

                            <div>
                                <span class="text-sm font-medium text-grey-700"
                                    >Why this framework:</span
                                >
                                <p class="mt-1 text-sm text-grey-700">
                                    {{ promptRun.framework_reasoning }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Question Answering Interface -->
                <div
                    v-if="
                        (promptRun.workflow_stage === 'framework_selected' ||
                            promptRun.workflow_stage ===
                                'answering_questions') &&
                        currentQuestion
                    "
                    class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <!-- Progress Indicator -->
                        <div class="mb-6">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-sm font-medium text-grey-700">
                                    Question {{ progress.answered + 1 }} of
                                    {{ progress.total }}
                                </span>
                                <span class="text-sm text-grey-500">
                                    {{
                                        Math.round(
                                            (progress.answered /
                                                progress.total) *
                                                100,
                                        )
                                    }}% complete
                                </span>
                            </div>
                            <div
                                class="h-2 w-full overflow-hidden rounded-full bg-grey-200"
                            >
                                <div
                                    class="h-full bg-indigo-600 transition-all duration-300"
                                    :style="{
                                        width: `${(progress.answered / progress.total) * 100}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <h3 class="mb-4 text-lg font-semibold text-grey-900">
                            Clarifying Question
                        </h3>

                        <p class="mb-6 text-grey-800">
                            {{ currentQuestion }}
                        </p>

                        <form @submit.prevent="submitAnswer" class="space-y-4">
                            <div>
                                <label
                                    for="answer"
                                    class="block text-sm font-medium text-grey-700"
                                >
                                    Your Answer
                                </label>
                                <textarea
                                    id="answer"
                                    v-model="answerForm.answer"
                                    rows="4"
                                    class="mt-1 block w-full rounded-md border-grey-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    placeholder="Type your answer here..."
                                    :disabled="isSubmitting"
                                ></textarea>
                                <p
                                    v-if="answerForm.errors.answer"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ answerForm.errors.answer }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="submit"
                                    :disabled="
                                        isSubmitting || !answerForm.answer.trim()
                                    "
                                    class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="isSubmitting"
                                        class="mr-2 h-4 w-4 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    {{
                                        isSubmitting
                                            ? 'Submitting...'
                                            : 'Submit Answer'
                                    }}
                                </button>

                                <button
                                    type="button"
                                    @click="skipQuestion"
                                    :disabled="isSubmitting"
                                    class="inline-flex items-center rounded-md border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Skip Question
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Generating Prompt Loading State -->
                <div
                    v-else-if="promptRun.workflow_stage === 'generating_prompt'"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="flex items-center">
                            <svg
                                class="mr-3 h-5 w-5 animate-spin text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <div>
                                <p class="font-medium text-grey-900">
                                    Generating your optimised prompt...
                                </p>
                                <p class="mt-1 text-sm text-grey-600">
                                    This may take a few moments. We're crafting
                                    a personalised prompt using the
                                    {{ promptRun.selected_framework }} framework.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Clarifying Questions & Answers -->
                <div
                    v-if="
                        promptRun.framework_questions &&
                        promptRun.framework_questions.length > 0 &&
                        promptRun.clarifying_answers &&
                        promptRun.clarifying_answers.length > 0 &&
                        promptRun.workflow_stage === 'completed'
                    "
                    class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-grey-900">
                                Clarifying Questions
                            </h3>
                            <button
                                @click="toggleAll"
                                class="inline-flex items-center rounded-md border border-grey-300 bg-white px-3 py-2 text-sm font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {{ allExpanded() ? 'Hide All' : 'Show All' }}
                            </button>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="(question, index) in promptRun.framework_questions"
                                :key="index"
                                class="border-b border-grey-200 pb-3 last:border-b-0"
                            >
                                <button
                                    @click="toggleQuestion(index)"
                                    class="flex w-full items-start justify-between text-left"
                                >
                                    <div class="flex-1">
                                        <div class="flex items-start">
                                            <span
                                                class="mr-2 mt-0.5 inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-medium text-indigo-800"
                                            >
                                                {{ index + 1 }}
                                            </span>
                                            <p class="text-sm font-medium text-grey-900">
                                                {{ question }}
                                            </p>
                                        </div>
                                    </div>
                                    <svg
                                        :class="[
                                            'ml-4 h-5 w-5 flex-shrink-0 text-grey-400 transition-transform',
                                            expandedQuestions.has(index)
                                                ? 'rotate-180'
                                                : '',
                                        ]"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>

                                <div
                                    v-show="expandedQuestions.has(index)"
                                    class="ml-8 mt-2"
                                >
                                    <div
                                        v-if="
                                            promptRun.clarifying_answers[index] !== null &&
                                            promptRun.clarifying_answers[index] !== undefined
                                        "
                                        class="rounded-md bg-grey-50 p-3"
                                    >
                                        <p class="text-sm text-grey-700">
                                            {{ promptRun.clarifying_answers[index] }}
                                        </p>
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-md bg-grey-50 p-3"
                                    >
                                        <p class="text-sm italic text-grey-500">
                                            [Skipped]
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optimised Prompt Result -->
                <div
                    v-if="
                        promptRun.workflow_stage === 'completed' &&
                        promptRun.optimized_prompt
                    "
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-grey-900">
                                Your Optimised Prompt
                            </h3>
                            <button
                                @click="copyToClipboard"
                                class="inline-flex items-center rounded-md border border-grey-300 bg-white px-3 py-2 text-sm font-medium text-grey-700 shadow-sm hover:bg-grey-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                <svg
                                    v-if="!copied"
                                    class="mr-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="mr-2 h-4 w-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                            </button>
                        </div>

                        <div class="rounded-md bg-grey-50 p-4">
                            <pre
                                class="whitespace-pre-wrap font-mono text-sm text-grey-900"
                                >{{ promptRun.optimized_prompt }}</pre
                            >
                        </div>

                        <div class="mt-4">
                            <p class="text-sm text-grey-600">
                                This prompt was generated using the
                                <span class="font-medium">{{
                                    promptRun.selected_framework
                                }}</span>
                                framework, tailored to your
                                <span class="font-medium">{{
                                    promptRun.personality_type
                                }}</span>
                                personality type.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div
                    v-else-if="promptRun.status === 'failed'"
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-semibold text-red-600">
                            Error
                        </h3>
                        <p class="text-sm text-grey-900">
                            {{
                                promptRun.error_message ||
                                'An error occurred whilst processing your request.'
                            }}
                        </p>
                        <div class="mt-4">
                            <a
                                :href="route('prompt-optimizer.index')"
                                class="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                Try again
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Processing State (initial submission) -->
                <div
                    v-else-if="
                        promptRun.status === 'processing' &&
                        promptRun.workflow_stage === 'submitted'
                    "
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6">
                        <div class="flex items-center">
                            <svg
                                class="mr-3 h-5 w-5 animate-spin text-indigo-600"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span class="text-grey-700"
                                >Selecting optimal framework...</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Back to History -->
                <div class="mt-6 text-center">
                    <a
                        :href="route('prompt-optimizer.history')"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                    >
                        View All History
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
