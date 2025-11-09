<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';

interface Props {
    size?: 'small' | 'medium' | 'large';
}

withDefaults(defineProps<Props>(), {
    size: 'medium',
});

const sizeClasses = {
    small: 'h-4 w-4',
    medium: 'h-5 w-5',
    large: 'h-8 w-8',
};
</script>

<template>
    <DynamicIcon
        name="spinner"
        class="text-indigo-600"
        :class="sizeClasses[size]"
    />
</template>
