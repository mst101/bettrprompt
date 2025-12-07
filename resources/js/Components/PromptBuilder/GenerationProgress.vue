<script setup lang="ts">
import Card from '@/Components/Card.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import StageIndicator from '@/Components/PromptBuilder/StageIndicator.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

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
            'Retrieving the structure and examples for your selected framework',
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
        <div class="text-center">
            <h3 class="text-lg font-semibold text-green-900">
                Generating Your Optimised Prompt
            </h3>
            <p class="mt-2 text-sm text-green-600">
                Please wait while we craft the perfect prompt for you
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="space-y-2">
            <div class="flex justify-between text-sm text-indigo-600">
                <span>{{ currentStage }}</span>
                <span>{{ Math.round(progress) }}% complete</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-green-100">
                <div
                    class="h-full rounded-full bg-green-600 transition-all duration-500"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </div>

        <!-- Current Activity -->
        <div class="flex items-start gap-4 rounded-lg bg-green-50 p-4">
            <div class="mt-0.5">
                <LoadingSpinner class="h-5 w-5 text-green-600" />
            </div>
            <div class="flex-1">
                <p class="font-medium text-green-900">
                    {{ currentActivity }}
                </p>
                <p class="mt-1 text-sm text-green-600">
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
            class="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
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
    </Card>
</template>
