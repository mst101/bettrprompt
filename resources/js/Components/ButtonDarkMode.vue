<script setup lang="ts">
import ButtonMode from '@/Components/ButtonMode.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import { useThemeStore } from '@/stores/themeStore';
import { computed } from 'vue';

const themeStore = useThemeStore();
const isDark = computed(() => themeStore.theme === 'dark');
const otherMode = computed(() => (isDark.value ? 'light' : 'dark'));
const title = computed(() => `Switch to ${otherMode.value} mode`);
</script>

<template>
    <ButtonMode
        class="cursor-pointer rounded-md fill-current text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none active:text-black"
        type="button"
        :title="title"
        @click="themeStore.toggleTheme()"
    >
        <DynamicIcon :name="isDark ? 'moon' : 'sun'" />
    </ButtonMode>
</template>
