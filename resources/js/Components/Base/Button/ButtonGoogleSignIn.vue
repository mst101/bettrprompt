<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';

defineProps<{
    text?: string;
}>();
</script>

<template>
    <a
        data-testid="google-sign-in-button"
        :href="route('auth.google')"
        class="flex w-full items-center justify-center gap-3 rounded-md border border-indigo-300 bg-white px-4 py-2 text-sm font-medium text-indigo-700 shadow-xs transition hover:bg-indigo-50 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
    >
        <DynamicIcon name="google" class="h-5 w-5" />
        {{ text || 'Continue with Google' }}
    </a>
</template>
