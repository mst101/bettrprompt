<script setup lang="ts">
import ButtonSmall from '@/Components/ButtonSmall.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { ref } from 'vue';

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
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Output',
});

const emit = defineEmits<{
    expandSystem: [];
    expandMessages: [];
    updateRawMode: [
        section: 'system' | 'messages' | 'system-new' | 'messages-new',
        isRaw: boolean,
    ];
}>();

// Track which sections should display raw markdown
const showRawSystem = ref(false);
const showRawMessages = ref(false);

const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};

const toggleRawMode = (section: 'system' | 'messages') => {
    if (section === 'system') {
        showRawSystem.value = !showRawSystem.value;
        emit('updateRawMode', 'system', showRawSystem.value);
    } else {
        showRawMessages.value = !showRawMessages.value;
        emit('updateRawMode', 'messages', showRawMessages.value);
    }
};

const copyToClipboard = async (text: string | null | undefined) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
    } catch (err) {
        console.error('Failed to copy to clipboard:', err);
    }
};

const getMessagesAsText = (messages: Message[] | unknown) => {
    if (!Array.isArray(messages)) {
        return JSON.stringify(messages, null, 2);
    }
    return messages
        .map((msg) => {
            if (typeof msg === 'object' && msg !== null) {
                if (msg.content) return msg.content;
                return JSON.stringify(msg, null, 2);
            }
            return String(msg);
        })
        .join('\n\n');
};
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="bg-indigo-300 px-6 py-4 font-semibold text-indigo-800">
            {{ props.title }}
        </div>
        <div class="flex-1 overflow-auto bg-indigo-100 p-6">
            <div v-if="output">
                <!-- System Prompt -->
                <div v-if="output.system" class="mb-6">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-indigo-900">System</h3>
                        <div class="flex gap-2">
                            <ButtonSmall
                                title="Copy to clipboard"
                                @click="copyToClipboard(output.system)"
                            >
                                📋 Copy
                            </ButtonSmall>
                            <ButtonSmall
                                :title="`Switch to ${showRawSystem ? 'formatted' : 'raw'} markdown`"
                                @click="toggleRawMode('system')"
                            >
                                {{ showRawSystem ? '◆ Formatted' : '◇ Raw' }}
                            </ButtonSmall>
                            <ButtonSmall
                                title="Expand to full screen"
                                @click="emit('expandSystem')"
                            >
                                ⛶ Expand
                            </ButtonSmall>
                        </div>
                    </div>
                    <div
                        v-if="showRawSystem"
                        class="max-h-60 overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ output.system }}
                    </div>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div
                        v-else
                        class="prose dark:prose-invert prose-sm max-h-60 overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 text-indigo-700"
                        v-html="renderMarkdown(output.system)"
                    />
                </div>

                <!-- Messages -->
                <div v-if="output.messages">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="font-semibold text-indigo-900">Messages</h3>
                        <div class="flex gap-2">
                            <ButtonSmall
                                title="Copy to clipboard"
                                @click="
                                    copyToClipboard(
                                        getMessagesAsText(output.messages),
                                    )
                                "
                            >
                                📋 Copy
                            </ButtonSmall>
                            <ButtonSmall
                                :title="`Switch to ${showRawMessages ? 'formatted' : 'raw'} markdown`"
                                @click="toggleRawMode('messages')"
                            >
                                {{ showRawMessages ? '◆ Formatted' : '◇ Raw' }}
                            </ButtonSmall>
                            <ButtonSmall
                                title="Expand to full screen"
                                @click="emit('expandMessages')"
                            >
                                ⛶ Expand
                            </ButtonSmall>
                        </div>
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
                                <!-- eslint-disable-next-line vue/no-v-html -->
                                <div
                                    v-if="message.content && !showRawMessages"
                                    class="prose dark:prose-invert prose-sm text-indigo-700"
                                    v-html="renderMarkdown(message.content)"
                                />
                                <div
                                    v-else-if="
                                        message.content && showRawMessages
                                    "
                                    class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                                >
                                    {{ message.content }}
                                </div>
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
                        class="mt-2 max-h-96 overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-700"
                    >
                        <pre>{{ JSON.stringify(output, null, 2) }}</pre>
                    </div>
                </details>
            </div>
            <p v-else class="text-indigo-500 italic">
                No output yet. Execute the JavaScript to see results.
            </p>
        </div>
    </div>
</template>
