<script setup lang="ts">
import Card from '@/Components/Card.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { ref } from 'vue';

interface Props {
    optimizedPrompt: string;
}

defineProps<Props>();

const copied = ref(false);

const copyToClipboard = async (text: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};
</script>

<template>
    <Card data-testid="optimized-prompt-display">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-green-100 p-2 text-green-600">
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Your Optimised Prompt
                    </h3>
                </div>

                <PrimaryButton
                    data-testid="copy-prompt-button"
                    @click="copyToClipboard(optimizedPrompt)"
                >
                    <svg
                        v-if="!copied"
                        class="mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                        />
                    </svg>
                    <svg
                        v-else
                        class="mr-2 h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
                </PrimaryButton>
            </div>

            <div
                data-testid="optimized-prompt-text"
                class="rounded-lg bg-gray-50 p-6 font-mono text-sm leading-relaxed text-gray-800"
            >
                <pre class="whitespace-pre-wrap break-words">{{
                    optimizedPrompt
                }}</pre>
            </div>

            <div class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm text-blue-900">
                    <strong>💡 Tip:</strong> Copy this prompt and use it with
                    your preferred AI assistant (ChatGPT, Claude, etc.) for
                    better, more personalised responses!
                </p>
            </div>
        </div>
    </Card>
</template>
