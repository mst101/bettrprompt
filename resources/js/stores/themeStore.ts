import { localStorageKey } from '@/constants/namespace';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useThemeStore = defineStore('theme', () => {
    // Initialise from localStorage or default to system preference
    const getInitialTheme = (): 'light' | 'dark' => {
        if (localStorage.getItem(localStorageKey('theme')) === 'dark')
            return 'dark';
        if (localStorage.getItem(localStorageKey('theme')) === 'light')
            return 'light';

        // If no theme in localStorage, check system preference
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    };

    const theme = ref<'light' | 'dark'>(getInitialTheme());

    // Apply theme to document
    const applyTheme = (newTheme: 'light' | 'dark'): void => {
        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Store in localStorage for persistence
        localStorage.setItem(localStorageKey('theme'), newTheme);
    };

    // Initialise theme on store creation
    applyTheme(theme.value);

    // Toggle theme
    const toggleTheme = (): void => {
        theme.value = theme.value === 'dark' ? 'light' : 'dark';
        applyTheme(theme.value);
    };

    // Set specific theme
    const setTheme = (newTheme: 'light' | 'dark'): void => {
        theme.value = newTheme;
        applyTheme(newTheme);
    };

    return {
        theme,
        toggleTheme,
        setTheme,
    };
});
