<script setup lang="ts">
import Button from '@/Components/Base/Button/Button.vue';
import LogoChatGPT from '@/Components/Logos/AIProviders/LogoChatGPT.vue';
import LogoClaude from '@/Components/Logos/AIProviders/LogoClaude.vue';
import LogoCopilot from '@/Components/Logos/AIProviders/LogoCopilot.vue';
import LogoGemini from '@/Components/Logos/AIProviders/LogoGemini.vue';
import LogoGrok from '@/Components/Logos/AIProviders/LogoGrok.vue';
import LogoMetaAI from '@/Components/Logos/AIProviders/LogoMetaAI.vue';
import LogoMistral from '@/Components/Logos/AIProviders/LogoMistral.vue';
import LogoPerplexity from '@/Components/Logos/AIProviders/LogoPerplexity.vue';
import { ref, type Component } from 'vue';

interface Props {
    prompt: string;
    headingNumber: number;
}

const props = defineProps<Props>();

interface AIProvider {
    name: string;
    url: string;
    logo: Component;
    supportsUrlPrompt: boolean;
    getUrl?: (prompt: string) => string;
}

const providers: AIProvider[] = [
    {
        name: 'ChatGPT',
        url: 'https://chat.openai.com/',
        logo: LogoChatGPT,
        supportsUrlPrompt: false,
    },
    {
        name: 'Claude',
        url: 'https://claude.ai/new',
        logo: LogoClaude,
        supportsUrlPrompt: false,
    },
    {
        name: 'Gemini',
        url: 'https://gemini.google.com/app',
        logo: LogoGemini,
        supportsUrlPrompt: false,
    },
    {
        name: 'Copilot',
        url: 'https://copilot.microsoft.com/',
        logo: LogoCopilot,
        supportsUrlPrompt: false,
    },
    {
        name: 'Perplexity',
        url: 'https://www.perplexity.ai/',
        logo: LogoPerplexity,
        supportsUrlPrompt: false,
    },
    {
        name: 'Meta AI',
        url: 'https://www.meta.ai/',
        logo: LogoMetaAI,
        supportsUrlPrompt: false,
    },
    {
        name: 'Mistral',
        url: 'https://chat.mistral.ai/',
        logo: LogoMistral,
        supportsUrlPrompt: false,
    },
    {
        name: 'Grok',
        url: 'https://x.com/i/grok',
        logo: LogoGrok,
        supportsUrlPrompt: false,
    },
];

const copiedProvider = ref<string | null>(null);

const handleProviderClick = async (provider: AIProvider) => {
    // Copy prompt to clipboard
    try {
        await navigator.clipboard.writeText(props.prompt);
        copiedProvider.value = provider.name;
        setTimeout(() => {
            copiedProvider.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }

    // Open provider in new tab
    const url =
        provider.supportsUrlPrompt && provider.getUrl
            ? provider.getUrl(props.prompt)
            : provider.url;

    window.open(url, '_blank', 'noopener,noreferrer');
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 v-if="headingNumber === 3" class="text-indigo-800">
                {{ $t('promptBuilder.components.aiProviderLinks.description') }}
            </h3>
            <h4 v-if="headingNumber === 4" class="text-indigo-800">
                {{ $t('promptBuilder.components.aiProviderLinks.description') }}
            </h4>
            <p
                v-if="copiedProvider"
                class="animate-fade-in text-xs text-green-600"
            >
                {{
                    $t('promptBuilder.components.aiProviderLinks.copied', {
                        provider: copiedProvider,
                    })
                }}
            </p>
        </div>
        <div
            class="grid w-full grid-cols-2 gap-2 sm:grid-cols-4 sm:grid-rows-2 sm:justify-items-stretch sm:gap-3"
        >
            <Button
                v-for="provider in providers"
                :key="provider.name"
                variant="secondary"
                size="sm"
                type="button"
                :title="
                    $t('promptBuilder.components.aiProviderLinks.openTitle', {
                        provider: provider.name,
                    })
                "
                class="group relative flex w-full items-center justify-center px-3 py-2 sm:w-full"
                :class="
                    prompt.length > 0
                        ? 'bg-indigo-50! hover:bg-indigo-100! dark:bg-indigo-100! dark:hover:bg-indigo-200!'
                        : 'bg-white hover:bg-indigo-100! dark:hover:bg-indigo-200!'
                "
                @click="handleProviderClick(provider)"
            >
                <component
                    :is="provider.logo"
                    class="mr-2 -ml-1 h-7 w-7 shrink-0"
                />
                <span class="text-xs text-indigo-800">
                    {{ provider.name }}
                </span>
                <span
                    class="absolute -top-1 -right-1 hidden h-5 w-5 items-center justify-center rounded-full bg-green-500 text-xs text-white group-hover:flex"
                    :class="{
                        flex: copiedProvider === provider.name,
                    }"
                >
                    ✓
                </span>
            </Button>
        </div>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.animate-fade-in {
    animation: fade-in 0.3s ease-in;
}
</style>
