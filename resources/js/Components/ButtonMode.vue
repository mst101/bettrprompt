<script setup lang="ts">
import type { ButtonType } from '@/types';

defineProps<{
    type: ButtonType;
    title?: string;
}>();
</script>

<template>
    <button
        :type="type"
        class="cursor-pointer rounded-md fill-current text-gray-600 hover:text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none active:text-black"
        :title="title"
    >
        <slot />
    </button>
</template>
