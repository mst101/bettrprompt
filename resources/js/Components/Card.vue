<script setup lang="ts">
interface Props {
    padding?: 'none' | 'small' | 'medium' | 'large';
}

withDefaults(defineProps<Props>(), {
    padding: 'medium',
});

const paddingClasses = {
    none: '',
    small: 'p-4',
    medium: 'p-6',
    large: 'p-8',
};
</script>

<template>
    <div
        class="max-w-4xl overflow-hidden bg-white text-gray-600 shadow-lg ring-1 ring-gray-100 sm:rounded-lg dark:bg-indigo-50 dark:text-indigo-900"
        :class="paddingClasses[padding]"
    >
        <slot />
    </div>
</template>
