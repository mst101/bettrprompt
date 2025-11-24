import { config } from '@vue/test-utils';
import { vi } from 'vitest';

// Mock Inertia.js
vi.mock('@inertiajs/vue3', () => ({
    usePage: vi.fn(() => ({
        props: {
            value: {
                auth: {
                    user: {
                        id: 1,
                        name: 'Test User',
                        email: 'test@example.com',
                    },
                },
                errors: {},
                flash: {},
            },
        },
        url: '/',
        component: 'TestComponent',
        version: '1',
        scrollRegions: [],
        rememberedState: {},
        resolvedErrors: {},
    })),
    useForm: vi.fn((data: any) => ({
        ...data,
        processing: false,
        errors: {},
        hasErrors: false,
        progress: null,
        wasSuccessful: false,
        recentlySuccessful: false,
        setData: vi.fn(),
        transform: vi.fn(),
        defaults: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
        setError: vi.fn(),
        submit: vi.fn(),
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        cancel: vi.fn(),
    })),
    router: {
        visit: vi.fn(),
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        reload: vi.fn(),
        replace: vi.fn(),
        remember: vi.fn(),
        restore: vi.fn(),
        on: vi.fn(),
    },
    Link: {
        name: 'Link',
        template: '<a><slot /></a>',
    },
    Head: {
        name: 'Head',
        template: '<div><slot /></div>',
    },
}));

// Mock Ziggy (Laravel routes)
vi.mock('ziggy-js', () => ({
    default: vi.fn((name: string, params?: any) => {
        // Simple mock route generator
        if (params && typeof params === 'object') {
            const paramString = Object.values(params).join('/');
            return `/${name}/${paramString}`;
        }
        return `/${name}`;
    }),
}));

// Mock Laravel Echo for WebSocket testing
vi.mock('laravel-echo', () => ({
    default: class Echo {
        private channels: Map<string, any> = new Map();

        channel(name: string) {
            if (!this.channels.has(name)) {
                this.channels.set(name, {
                    listen: vi.fn(() => this.channels.get(name)),
                    stopListening: vi.fn(),
                });
            }
            return this.channels.get(name);
        }

        private(name: string) {
            return this.channel(`private-${name}`);
        }

        leave(name: string) {
            this.channels.delete(name);
        }

        leaveChannel(name: string) {
            this.leave(name);
        }
    },
}));

// Mock window.route() global helper (Ziggy)
global.route = vi.fn((name: string, params?: any) => {
    if (params && typeof params === 'object') {
        const paramString = Object.values(params).join('/');
        return `/${name}/${paramString}`;
    }
    return `/${name}`;
});

// Configure Vue Test Utils global properties
config.global.mocks = {
    route: global.route,
};

// Mock browser APIs that happy-dom doesn't support
global.MediaRecorder = vi.fn(() => ({
    start: vi.fn(),
    stop: vi.fn(),
    pause: vi.fn(),
    resume: vi.fn(),
    ondataavailable: null,
    onstop: null,
    state: 'inactive',
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
})) as any;

// Mock SpeechRecognition API
global.SpeechRecognition = vi.fn(() => ({
    start: vi.fn(),
    stop: vi.fn(),
    abort: vi.fn(),
    continuous: false,
    interimResults: false,
    lang: 'en-GB',
    onresult: null,
    onerror: null,
    onend: null,
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
})) as any;

global.webkitSpeechRecognition = global.SpeechRecognition;
