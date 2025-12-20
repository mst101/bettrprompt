import { useLocalStorage } from '@/Composables/data/useLocalStorage';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

describe('useLocalStorage', () => {
    // Mock localStorage
    let localStorageMock: Record<string, string> = {};

    beforeEach(() => {
        localStorageMock = {};

        // Mock localStorage methods
        globalThis.localStorage = {
            getItem: vi.fn((key: string) => localStorageMock[key] || null),
            setItem: vi.fn((key: string, value: string) => {
                localStorageMock[key] = value;
            }),
            removeItem: vi.fn((key: string) => {
                delete localStorageMock[key];
            }),
            clear: vi.fn(() => {
                localStorageMock = {};
            }),
            key: vi.fn(),
            length: 0,
        };
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    it('should initialise with default value when localStorage is empty', () => {
        const value = useLocalStorage('test-key', 'default-value');

        expect(value.value).toBe('default-value');
        expect(localStorage.getItem).toHaveBeenCalledWith('test-key');
    });

    it('should initialise with stored value when localStorage has data', () => {
        localStorageMock['test-key'] = JSON.stringify('stored-value');

        const value = useLocalStorage('test-key', 'default-value');

        expect(value.value).toBe('stored-value');
        expect(localStorage.getItem).toHaveBeenCalledWith('test-key');
    });

    it('should persist changes to localStorage', async () => {
        const value = useLocalStorage('test-key', 'initial');

        // Change the value
        value.value = 'updated';

        // Wait for watchers to run
        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-key',
            JSON.stringify('updated'),
        );
        expect(localStorageMock['test-key']).toBe(JSON.stringify('updated'));
    });

    it('should work with objects', async () => {
        const value = useLocalStorage('test-object', { count: 0 });

        expect(value.value).toEqual({ count: 0 });

        // Update the object
        value.value = { count: 5 };

        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-object',
            JSON.stringify({ count: 5 }),
        );
    });

    it('should handle deep object changes', async () => {
        const value = useLocalStorage('test-deep', { nested: { count: 0 } });

        // Update nested property
        value.value.nested.count = 10;

        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-deep',
            JSON.stringify({ nested: { count: 10 } }),
        );
    });

    it('should handle arrays', async () => {
        const value = useLocalStorage('test-array', [1, 2, 3]);

        expect(value.value).toEqual([1, 2, 3]);

        value.value = [4, 5, 6];

        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-array',
            JSON.stringify([4, 5, 6]),
        );
    });

    it('should handle booleans', async () => {
        const value = useLocalStorage('test-bool', false);

        expect(value.value).toBe(false);

        value.value = true;

        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-bool',
            JSON.stringify(true),
        );
    });

    it('should handle numbers', async () => {
        const value = useLocalStorage('test-number', 0);

        expect(value.value).toBe(0);

        value.value = 42;

        await nextTick();

        expect(localStorage.setItem).toHaveBeenCalledWith(
            'test-number',
            JSON.stringify(42),
        );
    });

    it('should gracefully handle invalid JSON in localStorage', () => {
        localStorageMock['test-key'] = 'invalid-json{';

        // Spy on console.warn to verify it was called
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        const value = useLocalStorage('test-key', 'default');

        expect(value.value).toBe('default');
        expect(warnSpy).toHaveBeenCalled();

        warnSpy.mockRestore();
    });

    it('should handle localStorage.setItem errors', async () => {
        const errorSpy = vi
            .spyOn(console, 'error')
            .mockImplementation(() => {});

        // Mock setItem to throw an error (e.g., quota exceeded)
        vi.mocked(localStorage.setItem).mockImplementation(() => {
            throw new Error('QuotaExceededError');
        });

        const value = useLocalStorage('test-key', 'initial');
        value.value = 'updated';

        await nextTick();

        expect(errorSpy).toHaveBeenCalled();

        errorSpy.mockRestore();
    });
});
