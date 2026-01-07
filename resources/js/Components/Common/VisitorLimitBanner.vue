<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

interface Props {
    variant?: 'fixed' | 'inline';
    visitorHasAccount?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'fixed',
    visitorHasAccount: false,
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
        class="fixed right-0 bottom-0 left-0 z-50 bg-indigo-800"
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
                            <template v-if="visitorHasAccount">
                                Welcome back!
                            </template>
                            <template v-else>
                                You've created your first optimised prompt!
                            </template>
                        </p>
                        <p class="mt-1 text-indigo-100">
                            <template v-if="visitorHasAccount">
                                Log in to save your prompt, create new ones, and
                                iterate on existing ones.
                            </template>
                            <template v-else>
                                Create a free account to save your prompts,
                                create new ones, and iterate on existing ones.
                            </template>
                        </p>
                    </div>
                </div>
                <ButtonPrimary
                    class="shrink-0"
                    icon="arrow-right"
                    @click="$emit(visitorHasAccount ? 'login' : 'register')"
                >
                    <template v-if="visitorHasAccount">Log in</template>
                    <template v-else>Create Free Account</template>
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
            <h3 class="font-semibold text-indigo-900">
                <template v-if="visitorHasAccount"> Welcome back! </template>
                <template v-else> You've reached your visitor limit </template>
            </h3>
        </div>
        <div>
            <p class="mt-2 text-indigo-700">
                <template v-if="visitorHasAccount">
                    Log in to access your prompts and create new ones.
                </template>
                <template v-else>
                    You've already created an optimised prompt as a visitor. To
                    see your existing prompts, create more prompts, and iterate
                    on existing ones, you'll need to create a free account.
                </template>
            </p>
            <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:justify-end">
                <template v-if="visitorHasAccount">
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('login')"
                    >
                        Log in
                    </ButtonPrimary>
                </template>
                <template v-else>
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('register')"
                    >
                        Create Free Account
                    </ButtonPrimary>
                    <ButtonPrimary
                        class="w-full sm:w-fit"
                        icon="arrow-right"
                        @click="$emit('login')"
                    >
                        Log in
                    </ButtonPrimary>
                </template>
            </div>
        </div>
    </div>
</template>
