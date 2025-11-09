import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    test: {
        globals: true,
        environment: 'happy-dom',
        setupFiles: ['./tests-frontend/setup/vitest.setup.ts'],
        include: ['tests-frontend/**/*.{test,spec}.{js,ts}'],
        exclude: [
            '**/node_modules/**',
            '**/dist/**',
            '**/vendor/**',
            '**/storage/**',
            '**/public/**',
            '**/.{git,cache}/**',
        ],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json', 'html', 'lcov'],
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: [
                'resources/js/app.ts',
                'resources/js/ssr.ts',
                'resources/js/bootstrap.ts',
                'resources/js/types/**',
                'resources/js/Icons/**', // Exclude SVG icon components
                'resources/js/Pages/**', // Pages are tested via E2E
                'resources/js/Layouts/**', // Layouts are tested via E2E
                'resources/js/constants/**', // Simple constants, low value to test
                '**/*.d.ts',
                '**/*.config.ts',
                '**/node_modules/**',
            ],
            thresholds: {
                statements: 14,
                branches: 12,
                functions: 13,
                lines: 14,
            },
            all: true,
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'ziggy-js': fileURLToPath(
                new URL('./vendor/tightenco/ziggy', import.meta.url),
            ),
        },
    },
});
