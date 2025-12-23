<script setup lang="ts">
interface Props {
    spacing?: boolean;
}

withDefaults(defineProps<Props>(), {
    spacing: false,
});
</script>

<template>
    <div
        class="mx-auto max-w-7xl sm:px-6 sm:pb-6 lg:px-8 lg:pb-8"
        :class="[spacing ? 'space-y-6' : '']"
    >
        <slot />
    </div>
</template>
