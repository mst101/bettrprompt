<script>
import DynamicIcon from '@/Components/DynamicIcon.vue';

export default {
    name: 'ButtonClose',
    components: { DynamicIcon },
    emits: ['close'],
    methods: {
        emitClose(event) {
            this.$emit('close', event);
        },
    },
};
</script>

<template>
    <button
        type="button"
        @click="emitClose"
        aria-label="Close"
        class="absolute right-0 top-0 -translate-y-1/2 translate-x-1/2 text-gray-400 transition hover:text-gray-500"
    >
        <DynamicIcon
            name="x-circle"
            class="size-10 fill-white stroke-gray-300"
        />
    </button>
</template>
