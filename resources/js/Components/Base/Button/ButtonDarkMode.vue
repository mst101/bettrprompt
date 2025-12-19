<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useThemeStore } from '@/stores/themeStore';
import { computed } from 'vue';

const themeStore = useThemeStore();
const isDark = computed(() => themeStore.theme === 'dark');
const otherMode = computed(() => (isDark.value ? 'light' : 'dark'));
const title = computed(() => `Switch to ${otherMode.value} mode`);
</script>

<template>
    <button
        type="button"
        class="mr-2 size-10 shrink-0 cursor-pointer rounded-md fill-current p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus:text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden active:text-black sm:mr-4"
        :title="title"
        @click="themeStore.toggleTheme()"
    >
        <DynamicIcon :name="isDark ? 'moon' : 'sun'" />
    </button>
</template>
