<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useThemeStore } from '@/Stores/themeStore';
import { computed } from 'vue';

const themeStore = useThemeStore();
const isDark = computed(() => themeStore.theme === 'dark');
const otherMode = computed(() => (isDark.value ? 'light' : 'dark'));
const title = computed(() => `Switch to ${otherMode.value} mode`);
</script>

<template>
    <button
        type="button"
        class="mr-2 size-10 shrink-0 cursor-pointer rounded-md fill-current p-2 text-indigo-700 hover:bg-indigo-50 hover:text-indigo-800 focus:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden active:text-indigo-900 sm:mr-4"
        :title="title"
        @click="themeStore.toggleTheme()"
    >
        <DynamicIcon :name="isDark ? 'moon' : 'sun'" />
    </button>
</template>
