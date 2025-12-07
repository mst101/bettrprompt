<script setup lang="ts">
import Card from '@/Components/Card.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import StageIndicator from '@/Components/PromptBuilder/StageIndicator.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Simulated progress tracking
const startTime = ref(Date.now());
const elapsedTime = ref(0);
const interval = ref<number | null>(null);

// Average completion time: 18 seconds
const ESTIMATED_TOTAL_TIME = 18000; // ms

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
    if (p < 35) return 1;
    if (p < 60) return 2;
    if (p < 85) return 3;
    return 4;
});

const stages = [
    {
        id: 0,
        label: 'Classifying task',
        activity: 'Classifying your task',
        description: 'Identifying the task category and complexity',
    },
    {
        id: 1,
        label: 'Analysing requirements',
        activity: 'Analysing cognitive requirements',
        description: 'Determining which thinking styles suit your task',
    },
    {
        id: 2,
        label: 'Selecting framework',
        activity: 'Selecting optimal prompt framework',
        description: 'Choosing from 62 frameworks based on your needs',
    },
    {
        id: 3,
        label: 'Personalising approach',
        activity: 'Customising for your personality',
        description: 'Tailoring recommendations to your unique traits',
    },
    {
        id: 4,
        label: 'Generating questions',
        activity: 'Generating clarifying questions',
        description: 'Creating targeted questions to refine your prompt',
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
            <h3 class="text-lg font-semibold text-indigo-900">
                Analysing Your Task
            </h3>
            <p class="mt-2 text-sm text-indigo-600">
                Please wait while we build the perfect prompt for you
            </p>
        </div>

        <!-- Progress Bar -->
        <div class="space-y-2">
            <div class="flex justify-between text-sm text-indigo-600">
                <span>{{ currentStage }}</span>
                <span>{{ Math.round(progress) }}% complete</span>
            </div>
            <div class="h-3 w-full overflow-hidden rounded-full bg-indigo-100">
                <div
                    class="h-full rounded-full bg-indigo-600 transition-all duration-500"
                    :style="{ width: `${progress}%` }"
                />
            </div>
        </div>

        <!-- Current Activity -->
        <div class="flex items-start gap-4 rounded-lg bg-indigo-50 p-4">
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

        <!-- Analysis Stages (Educational) -->
        <div
            class="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
        >
            <p class="text-xs font-semibold text-indigo-500 uppercase">
                Analysis Pipeline
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
