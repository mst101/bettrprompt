<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type LinkButtonVariant =
    | 'default'
    | 'primary'
    | 'rounded-left'
    | 'rounded-right';

interface Props {
    id?: string;
    href: string;
    variant?: LinkButtonVariant;
}

const props = withDefaults(defineProps<Props>(), {
    id: undefined,
    variant: 'default',
});

const buttonClasses = computed(() => {
    const baseStyles = {
        default:
            'text-xs tracking-wider uppercase relative inline-flex items-center border border-gray-300 bg-white px-4 py-2 font-medium text-gray-500 focus:text-gray-700 hover:bg-gray-50 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-gray-100 focus:outline-hidden',
        primary:
            'text-xs tracking-wider uppercase inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-100 focus:outline-hidden',
    };

    const roundingVariants = {
        default: 'rounded-md',
        primary: '',
        'rounded-left': 'rounded-l-md',
        'rounded-right': 'rounded-r-md',
    };

    if (props.variant === 'primary') {
        return baseStyles.primary;
    }

    return [baseStyles.default, roundingVariants[props.variant]].join(' ');
});
</script>

<script lang="ts">
import { computed } from 'vue';
</script>

<template>
    <Link :id="id" :href="href" :class="buttonClasses">
        <slot />
    </Link>
</template>
