<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';

defineEmits<{
    (e: 'register'): void;
}>();
</script>

<template>
    <div class="mb-6 rounded-lg border-2 border-indigo-200 bg-indigo-50 p-6">
        <div class="flex items-start gap-3">
            <DynamicIcon
                name="information-circle"
                class="mt-0.5 h-6 w-6 shrink-0 text-indigo-600"
            />
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-indigo-900">
                    You've reached your visitor limit
                </h3>
                <p class="mt-2 text-gray-700">
                    You've already created an optimised prompt as a visitor. To
                    create more prompts, save your work, and iterate on existing
                    ones, you'll need to create a free account.
                </p>
                <div class="mt-4 flex gap-3">
                    <ButtonPrimary @click="$emit('register')">
                        Create Free Account
                    </ButtonPrimary>
                </div>
            </div>
        </div>
    </div>
</template>
