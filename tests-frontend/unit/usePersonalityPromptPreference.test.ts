import { usePersonalityPromptPreference } from '@/Composables/features/usePersonalityPromptPreference';
import { beforeEach, describe, expect, it } from 'vitest';

// Mock localStorage
const mockLocalStorage = {
    data: {} as Record<string, string>,
    getItem(key: string) {
        return this.data[key] || null;
    },
    setItem(key: string, value: string) {
        this.data[key] = value;
    },
    removeItem(key: string) {
        delete this.data[key];
    },
    clear() {
        this.data = {};
    },
};

Object.defineProperty(window, 'localStorage', {
    value: mockLocalStorage,
});

describe('usePersonalityPromptPreference', () => {
    beforeEach(() => {
        mockLocalStorage.clear();
    });

    describe('Initial state', () => {
        it('should initialize isDismissed as false when no localStorage value', () => {
            const { isDismissed } = usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(false);
        });

        it('should initialize isDismissed as true when localStorage has true value', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { isDismissed } = usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(true);
        });

        it('should initialize isDismissed as false when localStorage has false value', () => {
            localStorage.setItem('personality-prompt-dismissed', 'false');

            const { isDismissed } = usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(false);
        });
    });

    describe('showPrompt computed', () => {
        it('should be true when isDismissed is false', () => {
            const { showPrompt } = usePersonalityPromptPreference();

            expect(showPrompt.value).toBe(true);
        });

        it('should be false when isDismissed is true', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { showPrompt } = usePersonalityPromptPreference();

            expect(showPrompt.value).toBe(false);
        });

        it('should be inverse of isDismissed', () => {
            const { isDismissed, showPrompt, dismissPrompt } =
                usePersonalityPromptPreference();

            expect(showPrompt.value).toBe(!isDismissed.value);

            dismissPrompt();

            expect(showPrompt.value).toBe(!isDismissed.value);
        });
    });

    describe('dismissPrompt method', () => {
        it('should set isDismissed to true', () => {
            const { isDismissed, dismissPrompt } =
                usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(false);

            dismissPrompt();

            expect(isDismissed.value).toBe(true);
        });

        it('should persist dismissal to localStorage', () => {
            const { dismissPrompt } = usePersonalityPromptPreference();

            dismissPrompt();

            const stored = localStorage.getItem('personality-prompt-dismissed');

            expect(stored).toBe('true');
        });

        it('should update showPrompt after dismissing', () => {
            const { showPrompt, dismissPrompt } =
                usePersonalityPromptPreference();

            expect(showPrompt.value).toBe(true);

            dismissPrompt();

            expect(showPrompt.value).toBe(false);
        });

        it('should be idempotent', () => {
            const { dismissPrompt } = usePersonalityPromptPreference();

            dismissPrompt();
            dismissPrompt();
            dismissPrompt();

            const stored = localStorage.getItem('personality-prompt-dismissed');
            expect(stored).toBe('true');
        });
    });

    describe('resetPreference method', () => {
        it('should set isDismissed to false', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { isDismissed, resetPreference } =
                usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(true);

            resetPreference();

            expect(isDismissed.value).toBe(false);
        });

        it('should remove value from localStorage', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { resetPreference } = usePersonalityPromptPreference();

            resetPreference();

            const stored = localStorage.getItem('personality-prompt-dismissed');

            expect(stored).toBeNull();
        });

        it('should update showPrompt after resetting', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { showPrompt, resetPreference } =
                usePersonalityPromptPreference();

            expect(showPrompt.value).toBe(false);

            resetPreference();

            expect(showPrompt.value).toBe(true);
        });

        it('should revert to default state', () => {
            localStorage.setItem('personality-prompt-dismissed', 'true');

            const { isDismissed, showPrompt, resetPreference } =
                usePersonalityPromptPreference();

            resetPreference();

            expect(isDismissed.value).toBe(false);
            expect(showPrompt.value).toBe(true);
        });

        it('should be safe to call when preference not set', () => {
            const { resetPreference, isDismissed } =
                usePersonalityPromptPreference();

            expect(() => resetPreference()).not.toThrow();
            expect(isDismissed.value).toBe(false);
        });
    });

    describe('Persist across instances', () => {
        it('should persist state when creating new instance', () => {
            const instance1 = usePersonalityPromptPreference();
            instance1.dismissPrompt();

            const instance2 = usePersonalityPromptPreference();

            expect(instance2.isDismissed.value).toBe(true);
        });

        it('should revert state when resetting in one instance', () => {
            const instance1 = usePersonalityPromptPreference();
            instance1.dismissPrompt();

            const instance2 = usePersonalityPromptPreference();
            instance2.resetPreference();

            const instance3 = usePersonalityPromptPreference();
            expect(instance3.isDismissed.value).toBe(false);
        });
    });

    describe('Workflow', () => {
        it('should allow dismissing and resetting repeatedly', () => {
            const { isDismissed, dismissPrompt, resetPreference } =
                usePersonalityPromptPreference();

            // Initial state
            expect(isDismissed.value).toBe(false);

            // First dismiss
            dismissPrompt();
            expect(isDismissed.value).toBe(true);

            // First reset
            resetPreference();
            expect(isDismissed.value).toBe(false);

            // Second dismiss
            dismissPrompt();
            expect(isDismissed.value).toBe(true);

            // Second reset
            resetPreference();
            expect(isDismissed.value).toBe(false);
        });
    });

    describe('Edge cases', () => {
        it('should handle corrupted localStorage value', () => {
            localStorage.setItem('personality-prompt-dismissed', 'invalid');

            const { isDismissed } = usePersonalityPromptPreference();

            // Should treat non-'true' as false
            expect(isDismissed.value).toBe(false);
        });

        it('should handle uppercase True', () => {
            localStorage.setItem('personality-prompt-dismissed', 'True');

            const { isDismissed } = usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(false);
        });

        it('should handle empty string', () => {
            localStorage.setItem('personality-prompt-dismissed', '');

            const { isDismissed } = usePersonalityPromptPreference();

            expect(isDismissed.value).toBe(false);
        });
    });

    describe('Return values', () => {
        it('should return all expected properties', () => {
            const result = usePersonalityPromptPreference();

            expect(result).toHaveProperty('isDismissed');
            expect(result).toHaveProperty('showPrompt');
            expect(result).toHaveProperty('dismissPrompt');
            expect(result).toHaveProperty('resetPreference');
        });

        it('should return computed refs with value property', () => {
            const { isDismissed, showPrompt } =
                usePersonalityPromptPreference();

            expect(isDismissed.value).toBeDefined();
            expect(showPrompt.value).toBeDefined();
        });

        it('should return methods as functions', () => {
            const { dismissPrompt, resetPreference } =
                usePersonalityPromptPreference();

            expect(typeof dismissPrompt).toBe('function');
            expect(typeof resetPreference).toBe('function');
        });
    });
});
