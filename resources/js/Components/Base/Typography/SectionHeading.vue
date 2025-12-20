<script setup lang="ts">
interface Props {
    title: string;
    level?: 2 | 3;
    variant?: 'large' | 'default' | 'small';
}

withDefaults(defineProps<Props>(), {
    level: 2,
    variant: 'default',
});

const variantClasses = {
    large: 'text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl',
    default: 'text-base sm:text-lg font-semibold text-indigo-900',
    small: 'text-xs sm:text-sm font-medium text-indigo-900',
};

const headingTag = (level: 2 | 3) => (level === 2 ? 'h2' : 'h3');
</script>

<template>
    <component :is="headingTag(level)" :class="variantClasses[variant]">
        {{ title }}
    </component>
</template>
