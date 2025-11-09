<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';

defineProps<{
    icon: string;
    title: string;
    description: string;
    iconBgColour?: string;
    iconColour?: string;
}>();
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-xs ring-1 ring-gray-100">
        <div class="flex items-center gap-4">
            <div
                :class="[
                    iconBgColour || 'bg-indigo-100',
                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-lg',
                ]"
            >
                <DynamicIcon
                    :name="icon"
                    :class="[iconColour || 'text-indigo-600', 'h-6 w-6']"
                />
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ title }}
                </h3>
            </div>
        </div>
        <p class="mt-4 text-sm text-gray-600" v-html="description"></p>
    </div>
</template>
