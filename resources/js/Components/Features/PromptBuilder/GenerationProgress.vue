<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import LoadingSpinner from '@/Components/Base/LoadingSpinner.vue';
import StageIndicator from '@/Components/Common/StageIndicator.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    errorMessage?: string | null;
    onRetry?: () => void;
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = withDefaults(defineProps<Props>(), {
    errorMessage: null,
    onRetry: undefined,
});

// Simulated progress tracking
const startTime = ref(Date.now());
const elapsedTime = ref(0);
const interval = ref<number | null>(null);

// Average completion time: 40 seconds
const ESTIMATED_TOTAL_TIME = 40000; // ms

// Progress simulation (incremental)
const progress = computed(() => {
    const elapsed = elapsedTime.value;
    // Progress curve: fast at start, slows near end
    const rawProgress = Math.min((elapsed / ESTIMATED_TOTAL_TIME) * 100, 95);
    return rawProgress;
});

// Time remaining
const estimatedTimeRemaining = computed(() => {
    const remaining = Math.max(
        0,
        Math.ceil((ESTIMATED_TOTAL_TIME - elapsedTime.value) / 1000),
    );
    return remaining === 0 ? 'Almost done...' : `${remaining}s`;
});

// Current stage based on progress
const currentStageIndex = computed(() => {
    const p = progress.value;
    if (p < 15) return 0;
    if (p < 30) return 1;
    if (p < 50) return 2;
    if (p < 80) return 3;
    return 4;
});

const stages = [
    {
        id: 0,
        label: 'Loading framework details',
        activity: 'Loading framework details',
        description:
            'Retrieving the structure and debug for your selected framework',
    },
    {
        id: 1,
        label: 'Applying personality adjustments',
        activity: 'Applying personality adjustments',
        description: 'Customising the prompt based on your personality type',
    },
    {
        id: 2,
        label: 'Preparing context',
        activity: 'Preparing context',
        description: 'Combining framework, personality, and your answers',
    },
    {
        id: 3,
        label: 'Generating optimised prompt',
        activity: 'Generating optimised prompt',
        description: 'AI is crafting your personalised prompt',
    },
    {
        id: 4,
        label: 'Finalising output',
        activity: 'Finalising output',
        description: 'Formatting and validating the final prompt',
    },
];

const currentStage = computed(() => stages[currentStageIndex.value].label);
const currentActivity = computed(
    () => stages[currentStageIndex.value].activity,
);
const currentDescription = computed(
    () => stages[currentStageIndex.value].description,
);

function getStageStatus(stageId: number): 'pending' | 'active' | 'complete' {
    if (stageId < currentStageIndex.value) return 'complete';
    if (stageId === currentStageIndex.value) return 'active';
    return 'pending';
}

// Update elapsed time every 100ms
onMounted(() => {
    interval.value = window.setInterval(() => {
        elapsedTime.value = Date.now() - startTime.value;
    }, 100);
});

onUnmounted(() => {
    if (interval.value) {
        clearInterval(interval.value);
    }
});
</script>

<template>
    <Card class="space-y-6">
        <!-- Error State -->
        <div v-if="errorMessage" class="space-y-4">
            <div class="rounded-lg border border-red-300 bg-red-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        <svg
                            class="h-5 w-5 text-red-600"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-900">
                            Prompt Generation Failed
                        </h3>
                        <p class="mt-1 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                        <p class="mt-3 text-xs text-red-600">
                            This usually happens when the AI takes too long to
                            generate the response. Please try again, or if the
                            problem persists, try with a simpler task
                            description.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Retry Button -->
            <div class="flex gap-3">
                <ButtonPrimary v-if="onRetry" class="flex-1" @click="onRetry">
                    Retry Generation
                </ButtonPrimary>
                <ButtonSecondary
                    class="flex-1"
                    @click="() => window.history.back()"
                >
                    Go Back
                </ButtonSecondary>
            </div>
        </div>

        <!-- Progress State (shown when no error) -->
        <template v-else>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-indigo-900">
                    Generating Your Optimised Prompt
                </h3>
                <p class="mt-2 text-sm text-indigo-600">
                    Please wait while we craft the perfect prompt for you
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-indigo-600">
                    <span>{{ currentStage }}</span>
                    <span>{{ Math.round(progress) }}% complete</span>
                </div>
                <div
                    class="h-3 w-full overflow-hidden rounded-full bg-indigo-100"
                >
                    <div
                        class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
            </div>

            <!-- Current Activity -->
            <div
                class="flex items-start gap-4 rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
            >
                <div class="mt-0.5">
                    <LoadingSpinner class="h-5 w-5 text-indigo-600" />
                </div>
                <div class="flex-1">
                    <p class="font-medium text-indigo-900">
                        {{ currentActivity }}
                    </p>
                    <p class="mt-1 text-sm text-indigo-600">
                        {{ currentDescription }}
                    </p>
                </div>
            </div>

            <!-- Time Estimate -->
            <div class="text-center text-sm text-indigo-500">
                Estimated time remaining: {{ estimatedTimeRemaining }}
            </div>

            <!-- Generation Stages (Educational) -->
            <div
                class="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:bg-indigo-100"
            >
                <p class="text-xs font-semibold text-indigo-500 uppercase">
                    Generation Pipeline
                </p>
                <div class="space-y-2">
                    <StageIndicator
                        v-for="stage in stages"
                        :key="stage.id"
                        :label="stage.label"
                        :status="getStageStatus(stage.id)"
                    />
                </div>
            </div>
        </template>
    </Card>
</template>
