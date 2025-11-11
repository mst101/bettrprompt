<script setup lang="ts">
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
        'inline-flex items-center justify-center font-medium transition-colors duration-150 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50';

    const variants = {
        primary:
            'border border-transparent bg-indigo-600 text-white shadow-xs hover:bg-indigo-700 focus:ring-indigo-500',
        secondary:
            'border border-gray-300 bg-white text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-indigo-500',
        danger: 'border border-transparent bg-red-600 text-white shadow-xs hover:bg-red-700 focus:ring-red-500',
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
        <svg
            v-if="loading"
            class="mr-2 -ml-1 h-4 w-4 animate-spin text-current"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
        </svg>
        <slot />
    </button>
</template>
