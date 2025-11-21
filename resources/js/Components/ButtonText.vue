<script setup lang="ts">
import { computed } from 'vue';

type ButtonVariant = 'primary' | 'secondary' | 'info' | 'danger' | 'warning';

interface Props {
    id: string;
    variant?: ButtonVariant;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    underline?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    disabled: false,
    type: 'button',
    underline: false,
});

const buttonClasses = computed(() => {
    const base =
        'underline underline-offset-3 cursor-pointer text-sm font-normal rounded-md p-1 transition-colors duration-150 focus:outline-hidden focus:ring-2 focus:outline-hidden disabled:cursor-not-allowed disabled:opacity-50';

    const variants = {
        primary: 'text-indigo-600 hover:text-indigo-800 focus:ring-indigo-500',
        secondary: 'text-gray-600 hover:text-gray-800 focus:ring-gray-500',
        info: 'text-blue-600 hover:text-blue-800 focus:ring-blue-500',
        danger: 'text-red-600 hover:text-red-800 focus:ring-red-500',
        warning: 'text-amber-600 hover:text-amber-800 focus:ring-amber-500',
    };

    const underlineClass = props.underline ? 'hover:underline' : '';

    return [base, variants[props.variant], underlineClass]
        .filter(Boolean)
        .join(' ');
});
</script>

<template>
    <button :id="id" :type="type" :disabled="disabled" :class="buttonClasses">
        <slot />
    </button>
</template>
