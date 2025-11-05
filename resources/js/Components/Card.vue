<script setup lang="ts">
interface Props {
    padding?: 'none' | 'small' | 'medium' | 'large';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    padding: 'medium',
});

const paddingClasses = {
    none: '',
    small: 'p-4',
    medium: 'p-6',
    large: 'p-8',
};
</script>

<template>
    <div
        class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
        :class="[paddingClasses[padding], class]"
    >
        <slot />
    </div>
</template>
