<script setup lang="ts">
withDefaults(
    defineProps<{
        percentage?: number;
    }>(),
    {
        percentage: 0,
    },
);

const getCompletionLabel = (percentage: number) => {
    if (percentage === 100) return 'Complete';
    if (percentage >= 80) return 'Nearly Complete';
    if (percentage >= 60) return 'Good Progress';
    if (percentage >= 40) return 'Getting Started';
    return 'Just Beginning';
};
</script>

<template>
    <div>
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-indigo-900 sm:text-sm">
                Profile Completion
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

        <p class="text-xs text-indigo-500 sm:text-sm">
            {{ getCompletionLabel(percentage) }}
        </p>
    </div>
</template>
