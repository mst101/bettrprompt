<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LoadingSpinner from '@/Components/Base/LoadingSpinner.vue';
import StageIndicator from '@/Components/Common/StageIndicator.vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

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
const { t } = useI18n({ useScope: 'global' });

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
    return remaining === 0
        ? t('promptBuilder.progress.almostDone')
        : t('promptBuilder.progress.secondsRemaining', { seconds: remaining });
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

const stages = computed(() => [
    {
        id: 0,
        label: t('promptBuilder.progress.generation.stages.framework.label'),
        activity: t(
            'promptBuilder.progress.generation.stages.framework.activity',
        ),
        description: t(
            'promptBuilder.progress.generation.stages.framework.description',
        ),
    },
    {
        id: 1,
        label: t('promptBuilder.progress.generation.stages.personality.label'),
        activity: t(
            'promptBuilder.progress.generation.stages.personality.activity',
        ),
        description: t(
            'promptBuilder.progress.generation.stages.personality.description',
        ),
    },
    {
        id: 2,
        label: t('promptBuilder.progress.generation.stages.context.label'),
        activity: t(
            'promptBuilder.progress.generation.stages.context.activity',
        ),
        description: t(
            'promptBuilder.progress.generation.stages.context.description',
        ),
    },
    {
        id: 3,
        label: t('promptBuilder.progress.generation.stages.prompt.label'),
        activity: t('promptBuilder.progress.generation.stages.prompt.activity'),
        description: t(
            'promptBuilder.progress.generation.stages.prompt.description',
        ),
    },
    {
        id: 4,
        label: t('promptBuilder.progress.generation.stages.final.label'),
        activity: t('promptBuilder.progress.generation.stages.final.activity'),
        description: t(
            'promptBuilder.progress.generation.stages.final.description',
        ),
    },
]);

const currentStage = computed(
    () => stages.value[currentStageIndex.value].label,
);
const currentActivity = computed(
    () => stages.value[currentStageIndex.value].activity,
);
const currentDescription = computed(
    () => stages.value[currentStageIndex.value].description,
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
                        <DynamicIcon
                            name="x-circle"
                            class="h-5 w-5 text-red-600"
                        />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-900">
                            {{
                                $t(
                                    'promptBuilder.progress.generation.errorTitle',
                                )
                            }}
                        </h3>
                        <p class="mt-1 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                        <p class="mt-3 text-xs text-red-600">
                            {{
                                $t(
                                    'promptBuilder.progress.generation.errorDescription',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Retry Button -->
            <div class="flex gap-3">
                <ButtonPrimary v-if="onRetry" class="flex-1" @click="onRetry">
                    {{ $t('promptBuilder.progress.generation.retryButton') }}
                </ButtonPrimary>
                <ButtonSecondary
                    class="flex-1"
                    @click="() => window.history.back()"
                >
                    {{ $t('promptBuilder.progress.generation.backButton') }}
                </ButtonSecondary>
            </div>
        </div>

        <!-- Progress State (shown when no error) -->
        <template v-else>
            <div class="text-center">
                <h3 class="text-lg font-semibold text-indigo-900">
                    {{ $t('promptBuilder.progress.generation.title') }}
                </h3>
                <p class="mt-2 text-sm text-indigo-600">
                    {{ $t('promptBuilder.progress.generation.subtitle') }}
                </p>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm text-indigo-600">
                    <span>{{ currentStage }}</span>
                    <span>{{
                        $t('promptBuilder.progress.percentComplete', {
                            percent: Math.round(progress),
                        })
                    }}</span>
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
                {{
                    $t('promptBuilder.progress.timeRemaining', {
                        time: estimatedTimeRemaining,
                    })
                }}
            </div>

            <!-- Generation Stages (Educational) -->
            <div
                class="space-y-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:bg-indigo-100"
            >
                <p class="text-xs font-semibold text-indigo-500 uppercase">
                    {{ $t('promptBuilder.progress.generation.pipeline') }}
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
