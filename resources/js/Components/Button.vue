<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { useButtonClasses } from '@/Composables/useButtonClasses';

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

const buttonClasses = useButtonClasses(props);
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
