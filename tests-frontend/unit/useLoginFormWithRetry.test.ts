import { useLoginFormWithRetry } from '@/Composables/useLoginFormWithRetry';
import { getCsrfToken } from '@/Utils/cookies';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

// Mock useNotification
const mockWarning = vi.fn();

vi.mock('@/Composables/ui/useNotification', () => ({
    useNotification: () => ({
        warning: mockWarning,
        error: vi.fn(),
    }),
}));

// Mock cookies utility
vi.mock('@/Utils/cookies', () => ({
    getCsrfToken: vi.fn(),
    getCookie: vi.fn(),
}));

// Mock Inertia with proper router mocking
vi.mock('@inertiajs/vue3', () => {
    const mockRouterPost = vi.fn();

    const mockUseForm = (data: any) => {
        const form = { ...data, processing: false, errors: {} };

        return {
            ...form,
            post: vi.fn((route: string, options?: any) => {
                mockRouterPost(route, form, options);
            }),
            reset: vi.fn(function (field?: string) {
                if (field) {
                    (this as any)[field] = '';
                } else {
                    Object.assign(this, data);
                }
            }),
            clearErrors: vi.fn(),
        };
    };

    return {
        useForm: mockUseForm,
        router: {
            post: mockRouterPost,
        },
    };
});

// We'll access mockRouterPost through the module's router object in tests
let mockRouterPost: any;

describe('useLoginFormWithRetry', () => {
    const mockOnSuccess = vi.fn();

    beforeEach(async () => {
        // Get reference to the mocked router post
        const inertia = await import('@inertiajs/vue3');
        mockRouterPost = (inertia.router as any).post;

        vi.clearAllMocks();
        mockWarning.mockClear();
        if (mockRouterPost) mockRouterPost.mockClear();
        mockOnSuccess.mockClear();
    });

    /**
     * Helper to mount a test component that uses the composable
     */
    function createTestComponent() {
        return defineComponent({
            setup() {
                const { form, submit } = useLoginFormWithRetry(mockOnSuccess);
                return { form, submit };
            },
            template: `<div></div>`,
        });
    }

    describe('form initialization', () => {
        it('should initialize form with empty values', () => {
            const wrapper = mount(createTestComponent());
            const { form } = wrapper.vm as any;

            expect(form.email).toBe('');
            expect(form.password).toBe('');
            expect(form.remember).toBe(false);
        });

        it('should have submit method', () => {
            const wrapper = mount(createTestComponent());
            const { submit } = wrapper.vm as any;

            expect(typeof submit).toBe('function');
        });
    });

    describe('form submission', () => {
        it('should submit form to login endpoint', () => {
            const wrapper = mount(createTestComponent());
            const { submit } = wrapper.vm as any;

            submit();

            expect(mockRouterPost).toHaveBeenCalledWith(
                expect.stringContaining('login'),
                expect.any(Object),
                expect.any(Object),
            );
        });

        it('should call onSuccess callback on successful login', () => {
            mockRouterPost.mockImplementation((route, data, options) => {
                // Simulate onSuccess callback
                options?.onSuccess?.();
            });

            const wrapper = mount(createTestComponent());
            const { submit } = wrapper.vm as any;

            submit();

            expect(mockOnSuccess).toHaveBeenCalled();
        });
    });

    describe('CSRF token refresh on 419 error', () => {
        it('should listen for csrf-token-expired event on mount', async () => {
            const addEventListenerSpy = vi.spyOn(window, 'addEventListener');

            mount(createTestComponent());

            expect(addEventListenerSpy).toHaveBeenCalledWith(
                'csrf-token-expired',
                expect.any(Function),
            );

            addEventListenerSpy.mockRestore();
        });

        it('should fetch home page to refresh CSRF token', async () => {
            vi.mocked(getCsrfToken).mockReturnValue('new-token-123');

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            mount(createTestComponent());

            // Simulate CSRF token expired event
            window.dispatchEvent(new Event('csrf-token-expired'));

            // Wait for fetch to be called
            await vi.waitFor(() => {
                expect(mockFetch).toHaveBeenCalledWith(
                    '/',
                    expect.objectContaining({
                        method: 'GET',
                        credentials: 'include',
                    }),
                );
            });
        });

        it('should mark event as handled to prevent global reload', async () => {
            vi.mocked(getCsrfToken).mockReturnValue('new-token-123');

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            mount(createTestComponent());

            window.dispatchEvent(new Event('csrf-token-expired'));

            await vi.waitFor(() => {
                expect(window.csrfTokenRefreshHandled).toBe(true);
            });
        });

        it('should show warning notification when refreshing token', async () => {
            vi.mocked(getCsrfToken).mockReturnValue('new-token-123');

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            mount(createTestComponent());

            window.dispatchEvent(new Event('csrf-token-expired'));

            await vi.waitFor(() => {
                expect(mockWarning).toHaveBeenCalled();
                const lastCall =
                    mockWarning.mock.calls[mockWarning.mock.calls.length - 1];
                expect(lastCall[0]).toContain('Session expired');
                expect(lastCall[0]).toContain('Attempt');
            });
        });
    });

    describe('retry logic', () => {
        it('should retry login after token refresh', async () => {
            vi.mocked(getCsrfToken).mockReturnValue('new-token-123');

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            const wrapper = mount(createTestComponent());
            const { form } = wrapper.vm as any;

            form.email = 'test@example.com';
            form.password = 'password123';

            window.dispatchEvent(new Event('csrf-token-expired'));

            await vi.waitFor(() => {
                expect(mockRouterPost).toHaveBeenCalledWith(
                    expect.stringContaining('login'),
                    expect.any(Object),
                    expect.any(Object),
                );
            });
        });

        it('should handle fetch failures gracefully', async () => {
            const mockFetch = vi
                .fn()
                .mockRejectedValue(new Error('Network error'));
            global.fetch = mockFetch;

            mount(createTestComponent());

            window.dispatchEvent(new Event('csrf-token-expired'));

            // Should not mark as handled since fetch failed
            await vi.waitFor(
                () => {
                    expect(window.csrfTokenRefreshHandled).not.toBe(true);
                },
                { timeout: 300 },
            );
        });

        it('should handle non-200 responses', async () => {
            const mockFetch = vi.fn().mockResolvedValue({
                ok: false,
                status: 500,
            });
            global.fetch = mockFetch;

            mount(createTestComponent());

            window.dispatchEvent(new Event('csrf-token-expired'));

            // Should not mark as handled since response was not ok
            await vi.waitFor(
                () => {
                    expect(window.csrfTokenRefreshHandled).not.toBe(true);
                },
                { timeout: 300 },
            );
        });

        it('should handle when new CSRF token cannot be retrieved', async () => {
            vi.mocked(getCsrfToken).mockReturnValue(null);

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            mount(createTestComponent());

            window.dispatchEvent(new Event('csrf-token-expired'));

            // Should not mark as handled since token is null
            await vi.waitFor(
                () => {
                    expect(window.csrfTokenRefreshHandled).not.toBe(true);
                },
                { timeout: 300 },
            );
        });
    });

    describe('cleanup', () => {
        it('should remove event listener on component unmount', async () => {
            const removeEventListenerSpy = vi.spyOn(
                window,
                'removeEventListener',
            );

            const wrapper = mount(createTestComponent());
            wrapper.unmount();

            expect(removeEventListenerSpy).toHaveBeenCalledWith(
                'csrf-token-expired',
                expect.any(Function),
            );

            removeEventListenerSpy.mockRestore();
        });

        it('should not trigger login retry after unmount', async () => {
            vi.mocked(getCsrfToken).mockReturnValue('new-token-123');

            const mockFetch = vi.fn().mockResolvedValue({
                ok: true,
            });
            global.fetch = mockFetch;

            const wrapper = mount(createTestComponent());
            wrapper.unmount();

            mockRouterPost.mockClear();

            window.dispatchEvent(new Event('csrf-token-expired'));

            // Should not trigger login after unmount
            expect(mockRouterPost).not.toHaveBeenCalled();
        });
    });
});
