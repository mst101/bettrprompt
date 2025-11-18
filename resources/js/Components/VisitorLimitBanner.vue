<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';

defineEmits<{
    (e: 'register'): void;
}>();
</script>

<template>
    <div
        class="fixed right-0 bottom-0 left-0 z-50 bg-gradient-to-r from-indigo-600 to-purple-600 shadow-lg"
    >
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            <div
                class="flex flex-col items-center justify-between gap-4 sm:flex-row"
            >
                <div class="flex items-start gap-3 text-white">
                    <DynamicIcon
                        name="information-circle"
                        class="mt-0.5 h-6 w-6 shrink-0"
                    />
                    <div>
                        <p class="font-semibold">
                            You've created your first optimised prompt!
                        </p>
                        <p class="mt-1 text-sm text-indigo-100">
                            Create a free account to save your prompts, create
                            new ones, and iterate on existing ones.
                        </p>
                    </div>
                </div>
                <ButtonPrimary class="shrink-0" @click="$emit('register')">
                    Create Free Account
                    <DynamicIcon name="arrow-right" class="ml-2 h-4 w-4" />
                </ButtonPrimary>
            </div>
        </div>
    </div>
</template>
