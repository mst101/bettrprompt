<script setup lang="ts">
interface Props {
    title: string;
    subtitle?: string;
    variant?: 'hero' | 'large' | 'medium';
}

withDefaults(defineProps<Props>(), {
    variant: 'large',
});

const variantClasses = {
    hero: 'text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl',
    large: 'text-3xl font-bold text-indigo-900 md:text-4xl',
    medium: 'text-2xl font-bold text-indigo-900 md:text-3xl',
};
</script>

<template>
    <div>
        <h1 :class="variantClasses[variant]">{{ title }}</h1>
        <p v-if="subtitle" class="mt-4 text-lg text-indigo-600">
            {{ subtitle }}
        </p>
    </div>
</template>
