<script setup lang="ts">
interface Props {
    spacing?: boolean;
}

withDefaults(defineProps<Props>(), {
    spacing: false,
});
</script>

<template>
    <div class="sm:py-8">
        <div
            class="mx-auto max-w-7xl sm:px-6 lg:px-8"
            :class="[spacing ? 'space-y-6' : '']"
        >
            <slot />
        </div>
    </div>
</template>
