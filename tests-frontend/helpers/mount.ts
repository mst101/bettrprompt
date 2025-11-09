import { mount, VueWrapper } from '@vue/test-utils';
import { vi } from 'vitest';
import type { Component } from 'vue';

interface InertiaPageProps {
    auth?: {
        user?: {
            id: number;
            name: string;
            email: string;
        };
    };
    errors?: Record<string, string>;
    flash?: {
        success?: string;
        error?: string;
    };
    [key: string]: any;
}

interface MountInertiaOptions {
    props?: Record<string, any>;
    pageProps?: InertiaPageProps;
    global?: Record<string, any>;
}

/**
 * Mount a Vue component with Inertia.js context
 *
 * @param component - The Vue component to mount
 * @param options - Mount options including props and pageProps
 * @returns VueWrapper instance
 */
export function mountWithInertia(
    component: Component,
    options: MountInertiaOptions = {},
): VueWrapper {
    const { props = {}, pageProps = {}, global = {} } = options;

    // Mock usePage to return custom page props
    const mockUsePage = vi.fn(() => ({
        props: {
            value: {
                auth: pageProps.auth || {
                    user: {
                        id: 1,
                        name: 'Test User',
                        email: 'test@example.com',
                    },
                },
                errors: pageProps.errors || {},
                flash: pageProps.flash || {},
                ...pageProps,
            },
        },
        url: '/',
        component: 'TestComponent',
        version: '1',
        scrollRegions: [],
        rememberedState: {},
        resolvedErrors: {},
    }));

    return mount(component, {
        props,
        global: {
            ...global,
            mocks: {
                ...(global.mocks || {}),
                route:
                    global.route ||
                    vi.fn((name: string, params?: any) => {
                        if (params && typeof params === 'object') {
                            const paramString = Object.values(params).join('/');
                            return `/${name}/${paramString}`;
                        }
                        return `/${name}`;
                    }),
            },
            stubs: {
                ...(global.stubs || {}),
            },
        },
    });
}

/**
 * Create a mock User object
 */
export function createMockUser(
    overrides: Partial<{
        id: number;
        name: string;
        email: string;
        google_id: string | null;
        avatar: string | null;
    }> = {},
) {
    return {
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        google_id: null,
        avatar: null,
        ...overrides,
    };
}

/**
 * Create a mock PromptRun object
 */
export function createMockPromptRun(
    overrides: Partial<{
        id: number;
        user_id: number;
        slug: string;
        original_prompt: string;
        status: string;
        workflow_stage: string;
        selected_framework: string | null;
        framework_reasoning: string | null;
        framework_questions: Array<{
            question: string;
            reasoning: string;
        }> | null;
        clarifying_answers: Record<string, string> | null;
        optimised_prompt: string | null;
        created_at: string;
        updated_at: string;
    }> = {},
) {
    return {
        id: 1,
        user_id: 1,
        slug: 'test-slug',
        original_prompt: 'Test prompt',
        status: 'submitted',
        workflow_stage: 'submitted',
        selected_framework: null,
        framework_reasoning: null,
        framework_questions: null,
        clarifying_answers: null,
        optimised_prompt: null,
        created_at: '2025-01-01T00:00:00.000000Z',
        updated_at: '2025-01-01T00:00:00.000000Z',
        ...overrides,
    };
}

/**
 * Create a mock MediaRecorder instance
 */
export function createMockMediaRecorder() {
    return {
        start: vi.fn(),
        stop: vi.fn(),
        pause: vi.fn(),
        resume: vi.fn(),
        ondataavailable: null,
        onstop: null,
        onerror: null,
        state: 'inactive',
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    };
}

/**
 * Create a mock SpeechRecognition instance
 */
export function createMockSpeechRecognition() {
    return {
        start: vi.fn(),
        stop: vi.fn(),
        abort: vi.fn(),
        continuous: false,
        interimResults: false,
        lang: 'en-GB',
        onresult: null,
        onerror: null,
        onend: null,
        onstart: null,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        dispatchEvent: vi.fn(),
    };
}

/**
 * Create a mock Laravel Echo channel
 */
export function createMockEchoChannel() {
    return {
        listen: vi.fn((event: string, callback: Function) => ({
            listen: vi.fn(),
            stopListening: vi.fn(),
        })),
        stopListening: vi.fn(),
        subscribed: vi.fn((callback: Function) => ({
            listen: vi.fn(),
            stopListening: vi.fn(),
        })),
    };
}

/**
 * Wait for next tick and all promises to resolve
 */
export async function flushPromises() {
    return new Promise((resolve) => {
        setTimeout(resolve, 0);
    });
}
