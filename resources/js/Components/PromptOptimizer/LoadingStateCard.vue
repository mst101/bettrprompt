<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';

interface Props {
    state: 'selecting-framework' | 'generating-prompt';
    selectedFramework?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
        <div class="p-6">
            <div class="flex items-center">
                <DynamicIcon
                    name="spinner"
                    class="mr-3 h-5 w-5 text-indigo-600"
                />
                <div v-if="state === 'selecting-framework'">
                    <span class="text-gray-700"
                        >Selecting optimal framework...</span
                    >
                </div>
                <div v-else-if="state === 'generating-prompt'">
                    <p class="font-medium text-gray-900">
                        Generating your optimised prompt...
                    </p>
                    <p class="mt-1 text-sm text-gray-600">
                        This may take a few moments. We're crafting a
                        personalised prompt using the
                        {{ selectedFramework }}
                        framework.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
