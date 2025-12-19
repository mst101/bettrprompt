<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

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
        aria-label="Close"
        class="absolute top-0 right-0 translate-x-1/2 -translate-y-1/2 rounded-full text-gray-400 transition hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-gray-100 focus:outline-hidden"
        @click="emitClose"
    >
        <DynamicIcon
            name="x-circle"
            class="size-10 fill-white stroke-gray-300"
        />
    </button>
</template>
