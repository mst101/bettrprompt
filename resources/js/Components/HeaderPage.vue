<script setup lang="ts">
interface Props {
    title: string;
}

defineProps<Props>();
</script>

<template>
    <header class="">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex h-10 items-center justify-between">
                <h2 class="ml-2 text-lg font-thin text-indigo-700">
                    {{ title }}
                </h2>
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
