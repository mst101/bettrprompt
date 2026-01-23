import { useLocalStorage } from '@/Composables/data/useLocalStorage';
import { computed } from 'vue';

const STORAGE_KEY = 'personality-prompt-dismissed';

/**
 * Manages the user's preference for dismissing the personality prompt with "Maybe later"
 * Persists the preference to localStorage so it won't show again on future visits
 */
export const usePersonalityPromptPreference = () => {
    const isDismissed = useLocalStorage(STORAGE_KEY, false);
    const showPrompt = computed(() => !isDismissed.value);

    const dismissPrompt = () => {
        isDismissed.value = true;
    };

    const resetPreference = () => {
        isDismissed.value = false;
    };

    return {
        isDismissed: computed(() => isDismissed.value),
        showPrompt,
        dismissPrompt,
        resetPreference,
    };
};
