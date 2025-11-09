import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

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
                'resources/js/types/**',
                'resources/js/Icons/**', // Exclude SVG icon components
                '**/*.d.ts',
                '**/*.config.ts',
                '**/node_modules/**',
            ],
            thresholds: {
                statements: 70,
                branches: 65,
                functions: 65,
                lines: 70,
            },
            all: true,
        },
    },
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            'ziggy-js': fileURLToPath(new URL('./vendor/tightenco/ziggy', import.meta.url)),
        },
    },
});
