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
        class="relative inline-flex items-center leading-none"
        :class="sizeClasses"
    >
        <div
            data-testid="svg-logo"
            class="inline-flex items-center leading-none"
        >
            <span class="mr-1 font-light italic">#bettr</span>
            <span class="font-semibold">prompt</span>
        </div>
        <!-- BETA Badge -->
        <div
            class="absolute -right-2 -bottom-2.5 rounded bg-yellow-400 px-1.5 py-0.5 text-[12px] font-bold text-yellow-900 shadow-sm"
        >
            BETA
        </div>
    </div>
</template>
