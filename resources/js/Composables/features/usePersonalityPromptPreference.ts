import { computed, ref } from 'vue';

const STORAGE_KEY = 'personality-prompt-dismissed';

/**
 * Manages the user's preference for dismissing the personality prompt with "Maybe later"
 * Persists the preference to localStorage so it won't show again on future visits
 */
export const usePersonalityPromptPreference = () => {
    // Initialize isDismissed from localStorage
    const getInitialValue = () => {
        if (typeof window === 'undefined') return false;
        const stored = localStorage.getItem(STORAGE_KEY);
        return stored === 'true';
    };

    const isDismissed = ref<boolean>(getInitialValue());
    const showPrompt = computed(() => !isDismissed.value);

    const dismissPrompt = () => {
        isDismissed.value = true;
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, 'true');
        }
    };

    const resetPreference = () => {
        isDismissed.value = false;
        if (typeof window !== 'undefined') {
            localStorage.removeItem(STORAGE_KEY);
        }
    };

    return {
        isDismissed: computed(() => isDismissed.value),
        showPrompt,
        dismissPrompt,
        resetPreference,
    };
};
