<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: 'sm' | 'md' | 'lg' | 'xl';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
});

const sizeClasses = computed(() => {
    const sizes = {
        sm: 'text-base',
        md: 'text-xl',
        lg: 'text-3xl',
        xl: 'text-4xl',
    };
    return sizes[props.size];
});
</script>

<template>
    <div
        data-testid="svg-logo"
        class="inline-flex items-center leading-none"
        :class="sizeClasses"
    >
        <span class="mr-1 font-light italic">#bettr</span>
        <span class="font-semibold">prompt</span>
    </div>
</template>
