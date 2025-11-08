<script setup lang="ts">
defineProps<{
    number: number;
    title: string;
    description: string;
    bgColour?: string;
}>();
</script>

<template>
    <div class="text-center">
        <div
            :class="[
                bgColour || 'bg-indigo-600',
                'mx-auto flex h-16 w-16 items-center justify-center rounded-full text-xl font-bold text-white',
            ]"
        >
            {{ number }}
        </div>
        <h3 class="mt-6 text-lg font-semibold text-gray-900">
            {{ title }}
        </h3>
        <p class="mt-2 text-sm text-gray-600" v-html="description"></p>
    </div>
</template>
