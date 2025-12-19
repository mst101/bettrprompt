<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    variant?: 'fixed' | 'inline';
}

withDefaults(defineProps<Props>(), {
    variant: 'fixed',
});

defineEmits<{
    (e: 'register'): void;
    (e: 'login'): void;
}>();
</script>

<template>
    <!-- Fixed bottom banner variant -->
    <div
        v-if="variant === 'fixed'"
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

    <!-- Inline card variant -->
    <div
        v-else
        class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4"
    >
        <div class="flex items-start gap-4">
            <DynamicIcon
                name="information-circle"
                class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
            />
            <h3 class="text-lg font-semibold text-indigo-900">
                You've reached your visitor limit
            </h3>
        </div>
        <div class="ml-3">
            <p class="mt-2 text-indigo-700">
                You've already created an optimised prompt as a visitor. To see
                your existing prompts, create more prompts, and iterate on
                existing ones, you'll need to create a free account.
            </p>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:justify-end">
                <ButtonPrimary
                    class="w-full sm:w-fit"
                    @click="$emit('register')"
                >
                    Create Free Account
                </ButtonPrimary>
                <ButtonPrimary class="w-full sm:w-fit" @click="$emit('login')">
                    Log in
                </ButtonPrimary>
            </div>
        </div>
    </div>
</template>
