<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { computed } from 'vue';

type ButtonVariant = 'primary' | 'secondary' | 'danger';
type ButtonSize = 'sm' | 'md' | 'lg';

interface Props {
    variant?: ButtonVariant;
    size?: ButtonSize;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    disabled: false,
    type: 'button',
    loading: false,
});

const buttonClasses = computed(() => {
    const base =
        'border inline-flex items-center justify-center font-medium transition-colors duration-150 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50';

    const variants = {
        primary:
            'border-transparent bg-indigo-600 text-white shadow-xs hover:bg-indigo-700 focus:ring-indigo-500',
        secondary:
            'border-gray-300 bg-white text-gray-700 shadow-xs hover:bg-gray-50 dark:hover:bg-gray-200 focus:ring-indigo-500',
        danger: 'border-transparent bg-red-600 text-white shadow-xs hover:bg-red-700 focus:ring-red-500',
    };

    const sizes = {
        sm: 'rounded-md px-3 py-1.5 text-sm',
        md: 'rounded-md px-4 py-2 text-sm',
        lg: 'rounded-md px-6 py-3 text-base',
    };

    return [base, variants[props.variant], sizes[props.size]].join(' ');
});
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="buttonClasses">
        <DynamicIcon
            v-if="loading"
            name="arrow-path-spin"
            class="mr-2 -ml-1 h-4 w-4 animate-spin"
        />
        <slot />
    </button>
</template>
