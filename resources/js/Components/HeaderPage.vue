<script setup lang="ts">
interface Props {
    title: string;
}

defineProps<Props>();
</script>

<template>
    <header class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
            <div class="flex h-8 items-center justify-between">
                <h2
                    class="ml-2 text-xl leading-tight font-semibold text-gray-800"
                >
                    {{ title }}
                </h2>
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
