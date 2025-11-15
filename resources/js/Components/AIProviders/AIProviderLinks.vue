<script setup lang="ts">
import LogoChatGPT from '@/Components/AIProviders/LogoChatGPT.vue';
import LogoClaude from '@/Components/AIProviders/LogoClaude.vue';
import LogoCopilot from '@/Components/AIProviders/LogoCopilot.vue';
import LogoGemini from '@/Components/AIProviders/LogoGemini.vue';
import LogoGrok from '@/Components/AIProviders/LogoGrok.vue';
import LogoMetaAI from '@/Components/AIProviders/LogoMetaAI.vue';
import LogoPerplexity from '@/Components/AIProviders/LogoPerplexity.vue';
import { ref, type Component } from 'vue';

interface Props {
    prompt: string;
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
            <h4 class="text-sm font-medium text-gray-700">
                Try your prompt with:
            </h4>
            <p
                v-if="copiedProvider"
                class="animate-fade-in text-xs text-green-600"
            >
                Prompt copied! Opening {{ copiedProvider }}...
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button
                v-for="provider in providers"
                :key="provider.name"
                type="button"
                :title="`Open ${provider.name} (prompt copied to clipboard)`"
                class="group relative flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 transition-all hover:border-gray-300 hover:shadow-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                @click="handleProviderClick(provider)"
            >
                <component :is="provider.logo" class="h-6 w-6" />
                <span class="text-sm font-medium text-gray-700">
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
            </button>
        </div>
        <p class="text-xs text-gray-500">
            Click any provider to copy your optimised prompt and open their chat
            interface in a new tab.
        </p>
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
