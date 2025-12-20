<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    isOpen: boolean;
}

defineProps<Props>();
</script>

<template>
    <button
        type="button"
        class="size-10 shrink-0 cursor-pointer rounded-md fill-current p-2 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden active:text-indigo-900 sm:mr-4"
        aria-label="Toggle navigation menu"
    >
        <DynamicIcon v-if="!isOpen" name="bars-3" class="h-6 w-6" />
        <DynamicIcon v-else name="x-mark" class="h-6 w-6" />
    </button>
</template>
