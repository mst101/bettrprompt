<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useButtonClasses } from '@/Composables/ui/useButtonClasses';
import { computed, ref, useAttrs } from 'vue';

type ButtonVariant = 'primary' | 'secondary' | 'danger' | 'success';
type ButtonSize = 'sm' | 'md' | 'lg';

interface Props {
    variant?: ButtonVariant;
    size?: ButtonSize;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    loading?: boolean;
    icon?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    disabled: false,
    type: 'button',
    loading: false,
    icon: '',
});

const buttonClasses = useButtonClasses(props);
const attrs = useAttrs();

const mergedClasses = computed(() => {
    const classes = [];
    if (attrs.class) {
        classes.push(attrs.class);
    }
    classes.push(buttonClasses.value);
    return classes;
});

const buttonRef = ref<HTMLButtonElement | null>(null);

const focus = () => {
    buttonRef.value?.focus();
};

defineExpose({ focus });
</script>

<template>
    <button
        ref="buttonRef"
        :type="type"
        :disabled="disabled || loading"
        :class="mergedClasses"
    >
        <span class="flex items-center">
            <!-- Reserve space for icon during loading to maintain width -->
            <span
                v-if="loading || icon"
                class="mr-2 -ml-1 h-4 w-4 flex-shrink-0"
            >
                <DynamicIcon
                    v-if="loading"
                    name="arrow-path-spin"
                    class="h-4 w-4 animate-spin"
                />
                <DynamicIcon v-else-if="icon" :name="icon" class="h-4 w-4" />
            </span>
            <slot />
        </span>
    </button>
</template>
