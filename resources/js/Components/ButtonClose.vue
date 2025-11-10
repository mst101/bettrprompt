<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';

const emit = defineEmits<{
    (e: 'close', event: Event): void;
}>();

const emitClose = (event: Event) => {
    emit('close', event);
};
</script>

<template>
    <button
        type="button"
        @click="emitClose"
        aria-label="Close"
        class="absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 text-gray-400 transition hover:text-gray-500"
    >
        <DynamicIcon
            name="x-circle"
            class="size-10 fill-white stroke-gray-300"
        />
    </button>
</template>
