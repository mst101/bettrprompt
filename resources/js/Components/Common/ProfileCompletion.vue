<script setup lang="ts">
withDefaults(
    defineProps<{
        percentage?: number;
    }>(),
    {
        percentage: 0,
    },
);

const getCompletionColor = (percentage: number) => {
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-blue-500';
    if (percentage >= 40) return 'bg-yellow-500';
    return 'bg-orange-500';
};

const getCompletionLabel = (percentage: number) => {
    if (percentage === 100) return 'Complete';
    if (percentage >= 80) return 'Nearly Complete';
    if (percentage >= 60) return 'Good Progress';
    if (percentage >= 40) return 'Getting Started';
    return 'Just Beginning';
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-gray-900 sm:text-sm">
                Profile Completion
            </p>
            <span class="text-xs font-medium text-gray-600 sm:text-sm">
                {{ percentage }}%
            </span>
        </div>

        <div class="overflow-hidden rounded-full bg-gray-200">
            <div
                class="h-2 transition-all duration-300"
                :class="getCompletionColor(percentage)"
                :style="{ width: `${percentage}%` }"
            />
        </div>

        <p class="text-xs text-gray-500 sm:text-sm">
            {{ getCompletionLabel(percentage) }}
        </p>
    </div>
</template>
