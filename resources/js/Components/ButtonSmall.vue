<script setup lang="ts">
interface Props {
    id?: string;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    title?: string;
}

withDefaults(defineProps<Props>(), {
    id: undefined,
    disabled: false,
    type: 'button',
    title: undefined,
});
</script>

<template>
    <button
        :id="id"
        :type="type"
        :disabled="disabled"
        :title="title"
        class="rounded bg-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 transition-colors hover:bg-indigo-300 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
    >
        <slot />
    </button>
</template>
