<!-- eslint-disable vue/no-v-html -->
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
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p class="mt-2 text-sm text-gray-600" v-html="description"></p>
    </div>
</template>
