<script setup lang="ts">
interface Props {
    message: string;
    subMessage?: string;
    variant?: 'blue' | 'green' | 'indigo';
}

const props = withDefaults(defineProps<Props>(), {
    subMessage: undefined,
    variant: 'blue',
});

const variantClasses = {
    blue: {
        container: 'border-blue-200 bg-blue-50',
        spinner: 'border-blue-200 border-t-blue-600',
        message: 'text-blue-900',
        subMessage: 'text-blue-700',
    },
    green: {
        container: 'border-green-200 bg-green-50',
        spinner: 'border-green-200 border-t-green-600',
        message: 'text-green-900',
        subMessage: 'text-green-700',
    },
    indigo: {
        container: 'border-indigo-200 bg-indigo-50',
        spinner: 'border-indigo-200 border-t-indigo-600',
        message: 'text-indigo-900',
        subMessage: 'text-indigo-700',
    },
};

const classes = variantClasses[props.variant];
</script>

<template>
    <div class="rounded-lg border p-6 text-center" :class="classes.container">
        <div
            class="mx-auto mb-3 h-8 w-8 animate-spin rounded-full border-4"
            :class="classes.spinner"
        ></div>
        <p class="font-medium" :class="classes.message">
            {{ message }}
        </p>
        <p v-if="subMessage" class="mt-1 text-sm" :class="classes.subMessage">
            {{ subMessage }}
        </p>
    </div>
</template>
