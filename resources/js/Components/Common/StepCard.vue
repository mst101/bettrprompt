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
                'mx-auto flex size-10 items-center justify-center rounded-full text-xl font-bold text-white sm:size-16',
            ]"
        >
            {{ number }}
        </div>
        <h3 class="mt-4 font-semibold text-indigo-900 sm:mt-6">
            {{ title }}
        </h3>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p class="mt-2 text-indigo-700" v-html="description"></p>
    </div>
</template>
