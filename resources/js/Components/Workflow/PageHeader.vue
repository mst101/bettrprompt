<script setup lang="ts">
interface Props {
    title: string;
    subtitle?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold text-indigo-900 md:text-4xl">
            {{ title }}
        </h1>
        <p v-if="subtitle" class="text-indigo-600">
            {{ subtitle }}
        </p>
    </div>
</template>
