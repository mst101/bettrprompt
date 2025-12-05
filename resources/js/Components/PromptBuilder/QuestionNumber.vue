<script setup lang="ts">
interface Props {
    number: number;
}

defineProps<Props>();
</script>

<template>
    <span
        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-semibold text-white"
    >
        {{ number }}
    </span>
</template>
