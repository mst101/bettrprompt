<!-- eslint-disable vue/no-v-html -->
<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

defineProps<{
    icon: string;
    title: string;
    description: string;
    iconBgColour?: string;
    iconColour?: string;
    darkBgColour?: string;
}>();
</script>

<template>
    <div
        data-testid="feature-card"
        class="rounded-lg bg-white p-6 text-indigo-900 shadow-lg dark:text-indigo-900"
        :class="darkBgColour || 'dark:bg-indigo-50'"
    >
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
                <h3 class="text-base font-semibold text-indigo-900 sm:text-lg">
                    {{ title }}
                </h3>
            </div>
        </div>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <p class="mt-4 text-sm sm:text-base" v-html="description"></p>
    </div>
</template>
