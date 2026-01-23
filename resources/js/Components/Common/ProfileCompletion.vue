<script setup lang="ts">
import { useI18n } from 'vue-i18n';

withDefaults(
    defineProps<{
        percentage?: number;
    }>(),
    {
        percentage: 0,
    },
);

const { t } = useI18n({ useScope: 'global' });

const getCompletionLabel = (percentage: number) => {
    if (percentage === 100)
        return t('components.common.profileCompletion.labels.complete');
    if (percentage >= 80)
        return t('components.common.profileCompletion.labels.nearlyComplete');
    if (percentage >= 60)
        return t('components.common.profileCompletion.labels.goodProgress');
    if (percentage >= 40)
        return t('components.common.profileCompletion.labels.gettingStarted');
    return t('components.common.profileCompletion.labels.justBeginning');
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-indigo-900 sm:text-sm">
                {{ $t('components.common.profileCompletion.title') }}
            </p>
            <span class="text-xs font-medium text-indigo-600 sm:text-sm">
                {{ percentage }}%
            </span>
        </div>

        <div class="overflow-hidden rounded-full bg-indigo-100">
            <div
                class="h-2 bg-indigo-500 transition-all duration-300"
                :style="{ width: `${percentage}%` }"
            />
        </div>

        <p class="text-xs text-indigo-600 sm:text-sm">
            {{ getCompletionLabel(percentage) }}
        </p>
    </div>
</template>
