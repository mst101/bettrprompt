<script setup lang="ts">
interface Props {
    title: string;
}

defineProps<Props>();
</script>

<template>
    <header class="">
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <!-- Mobile/Tablet: Stack vertically -->
            <div class="lg:flex lg:h-10 lg:items-center lg:justify-between">
                <div class="mb-3 lg:mb-0">
                    <h1 class="text-base font-thin text-indigo-700 sm:text-lg">
                        {{ title }}
                    </h1>
                    <slot name="subtitle" />
                </div>
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
