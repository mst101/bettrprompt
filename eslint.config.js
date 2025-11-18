import prettierConfig from '@vue/eslint-config-prettier';
import {
    defineConfigWithVueTs,
    vueTsConfigs,
} from '@vue/eslint-config-typescript';
import pluginVue from 'eslint-plugin-vue';

export default defineConfigWithVueTs(
    {
        name: 'app/files-to-lint',
        files: ['**/*.{ts,mts,tsx,vue}'],
    },

    {
        name: 'app/files-to-ignore',
        ignores: [
            '**/dist/**',
            '**/dist-ssr/**',
            '**/coverage/**',
            '**/public/**',
        ],
    },

    ...pluginVue.configs['flat/recommended'],
    vueTsConfigs.recommended,
    prettierConfig,
    {
        name: 'app/custom-rules',
        rules: {
            'vue/multi-word-component-names': 'off',
            'no-undef': 'off',
            '@typescript-eslint/no-explicit-any': 'warn',
            'vue/block-lang': 'warn',

            // Ordering rules (auto-fixable)
            'vue/attributes-order': 'error',
            'vue/block-order': [
                'error',
                { order: ['script', 'template', 'style'] },
            ],
            'vue/define-macros-order': 'error',
            'vue/padding-line-between-blocks': 'warn',

            // Style consistency
            'vue/component-api-style': ['error', ['script-setup']],
            'vue/first-attribute-linebreak': 'warn',
        },
    },
);
