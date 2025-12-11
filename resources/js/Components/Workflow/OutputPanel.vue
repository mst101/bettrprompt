<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked } from 'marked';

interface Message {
    role?: string;
    content?: string;
    [key: string]: unknown;
}

interface Output {
    system?: string;
    messages?: Message[] | unknown;
    [key: string]: unknown;
}

interface Props {
    output: Output | null;
}

defineProps<Props>();

const emit = defineEmits<{
    expandSystem: [];
    expandMessages: [];
}>();

const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="bg-indigo-300 px-6 py-4 font-semibold text-indigo-800">
            Output
        </div>
        <div class="flex-1 overflow-auto bg-indigo-100 p-6">
            <div v-if="output">
                <!-- System Prompt -->
                <div v-if="output.system" class="mb-6">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-indigo-900">
                            System Prompt
                        </h3>
                        <button
                            class="rounded bg-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-300"
                            title="Expand to full screen"
                            @click="emit('expandSystem')"
                        >
                            ⛶ Expand
                        </button>
                    </div>
                    <div
                        class="prose dark:prose-invert prose-sm max-h-60 overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 text-indigo-700"
                        v-html="renderMarkdown(output.system)"
                    />
                </div>

                <!-- Messages -->
                <div v-if="output.messages">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-indigo-900">Messages</h3>
                        <button
                            class="rounded bg-indigo-200 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-300"
                            title="Expand to full screen"
                            @click="emit('expandMessages')"
                        >
                            ⛶ Expand
                        </button>
                    </div>
                    <div
                        v-if="Array.isArray(output.messages)"
                        class="space-y-2"
                    >
                        <div
                            v-for="(message, index) in output.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-3"
                        >
                            <div v-if="typeof message === 'object'">
                                <p
                                    class="mb-1 font-mono text-xs text-indigo-600"
                                >
                                    Role:
                                    {{ message.role || 'N/A' }}
                                </p>
                                <div
                                    v-if="message.content"
                                    class="prose dark:prose-invert prose-sm text-indigo-700"
                                    v-html="renderMarkdown(message.content)"
                                />
                                <div v-else class="text-xs text-indigo-700">
                                    {{ JSON.stringify(message, null, 2) }}
                                </div>
                            </div>
                            <p v-else class="text-xs text-indigo-700">
                                {{ message }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-else
                        class="rounded border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-700"
                    >
                        {{ JSON.stringify(output.messages, null, 2) }}
                    </div>
                </div>

                <!-- Full Output (if there's more) -->
                <details class="mt-6 cursor-pointer">
                    <summary class="font-semibold text-indigo-900">
                        Full Output
                    </summary>
                    <div
                        class="mt-2 max-h-40 overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-700"
                    >
                        {{ JSON.stringify(output, null, 2) }}
                    </div>
                </details>
            </div>
            <p v-else class="text-indigo-500 italic">
                No output yet. Execute the JavaScript to see results.
            </p>
        </div>
    </div>
</template>
