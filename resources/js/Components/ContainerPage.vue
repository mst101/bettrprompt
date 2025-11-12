<script setup lang="ts">
interface Props {
    width?: 'narrow' | 'default';
    spacing?: boolean;
}

withDefaults(defineProps<Props>(), {
    width: 'default',
    spacing: false,
});
</script>

<template>
    <div class="py-12">
        <div
            class="mx-auto sm:px-6 lg:px-8"
            :class="[
                width === 'narrow' ? 'max-w-3xl' : 'max-w-7xl',
                spacing ? 'space-y-6' : '',
            ]"
        >
            <slot />
        </div>
    </div>
</template>
