import { ref, watch, type Ref } from 'vue';

/**
 * Reactive localStorage composable
 *
 * @param key - localStorage key
 * @param defaultValue - default value if key doesn't exist
 * @returns Reactive ref that syncs with localStorage
 *
 * @example
 * const theme = useLocalStorage('theme', 'light');
 * theme.value = 'dark'; // Automatically saved to localStorage
 */
export function useLocalStorage<T>(key: string, defaultValue: T): Ref<T> {
    // Try to get initial value from localStorage
    const getInitialValue = (): T => {
        try {
            const stored = localStorage.getItem(key);
            if (stored !== null) {
                return JSON.parse(stored);
            }
        } catch (error) {
            console.warn(
                `Error reading localStorage key "${key}":`,
                error,
            );
        }
        return defaultValue;
    };

    const value = ref<T>(getInitialValue()) as Ref<T>;

    // Watch for changes and persist to localStorage
    watch(
        value,
        (newValue) => {
            try {
                localStorage.setItem(key, JSON.stringify(newValue));
            } catch (error) {
                console.error(
                    `Error writing localStorage key "${key}":`,
                    error,
                );
            }
        },
        { deep: true },
    );

    return value;
}
