<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    loading?: boolean;
}>();
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-6">
        <p class="text-sm font-medium text-gray-600">{{ label }}</p>
        <div
            v-if="loading"
            class="mt-2 h-10 animate-pulse rounded bg-gray-200"
        />
        <p v-else class="mt-2 text-3xl font-bold text-gray-900">
            {{ value }}
        </p>
    </div>
</template>
