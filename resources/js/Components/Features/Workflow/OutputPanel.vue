<script setup lang="ts">
import ButtonSmall from '@/Components/Base/Button/ButtonSmall.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
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
    showRawJson?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Output',
    showRawJson: false,
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

// Track which sections are expanded/collapsed
const systemExpanded = ref(false);
const messagesExpanded = ref(true);

// Track wrap lines mode for raw JSON
const wrapRawJson = ref(false);

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

const getJsonCharacterCount = (data: unknown): string => {
    if (!data) return '0';
    const jsonString = JSON.stringify(data, null, 2);
    const count = jsonString.length;
    return new Intl.NumberFormat().format(count);
};
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="bg-indigo-300 px-6 py-4 font-semibold text-indigo-800">
            {{ props.title }} - {{ getJsonCharacterCount(output) }} characters
        </div>
        <div class="flex-1 overflow-auto bg-indigo-100 p-6">
            <div v-if="output">
                <!-- Raw JSON display (for n8n workflow outputs) -->
                <div v-if="props.showRawJson">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="text-xs font-semibold text-indigo-900">
                            Raw JSON
                        </div>
                        <div class="flex gap-2">
                            <ButtonSmall
                                title="Copy to clipboard"
                                @click="
                                    copyToClipboard(
                                        JSON.stringify(output, null, 2),
                                    )
                                "
                            >
                                📋 Copy
                            </ButtonSmall>
                            <ButtonSmall
                                :title="`${wrapRawJson ? 'Disable' : 'Enable'} line wrapping`"
                                @click="wrapRawJson = !wrapRawJson"
                            >
                                {{ wrapRawJson ? '↔ Wrap' : '↔ No Wrap' }}
                            </ButtonSmall>
                        </div>
                    </div>
                    <div
                        class="max-h-screen overflow-auto rounded border border-indigo-200 bg-indigo-50 p-3 text-xs text-indigo-700"
                    >
                        <pre
                            class="break-words"
                            :class="{
                                'whitespace-pre-wrap': wrapRawJson,
                                'whitespace-pre': !wrapRawJson,
                            }"
                            >{{ JSON.stringify(output, null, 2) }}</pre
                        >
                    </div>
                </div>

                <!-- Structured display (for prompt outputs with system/messages) -->
                <template v-else>
                    <!-- System Prompt -->
                    <div v-if="output.system" class="mb-6">
                        <div class="mb-2 flex items-center justify-between">
                            <button
                                class="flex cursor-pointer items-center gap-2 font-semibold text-indigo-900 hover:text-indigo-700"
                                @click="systemExpanded = !systemExpanded"
                            >
                                <DynamicIcon
                                    name="chevron-right"
                                    class="h-5 w-5 transition-transform duration-200"
                                    :class="{
                                        'rotate-90': systemExpanded,
                                    }"
                                />
                                System
                            </button>
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
                                    {{
                                        showRawSystem ? '◆ Formatted' : '◇ Raw'
                                    }}
                                </ButtonSmall>
                                <ButtonSmall
                                    title="Expand to full screen"
                                    @click="emit('expandSystem')"
                                >
                                    ⛶ Expand
                                </ButtonSmall>
                            </div>
                        </div>
                        <div v-if="systemExpanded">
                            <div
                                v-if="showRawSystem"
                                class="overflow-auto rounded-md border border-indigo-200 bg-indigo-50 p-3 font-mono text-sm wrap-break-word whitespace-pre-wrap text-indigo-700"
                            >
                                {{ output.system }}
                            </div>
                            <!-- eslint-disable-next-line vue/no-v-html -->
                            <div
                                v-else
                                class="prose dark:prose-invert prose-sm max-w-none overflow-auto rounded-md border border-indigo-200 bg-indigo-50 p-3 text-indigo-700"
                                v-html="renderMarkdown(output.system)"
                            />
                        </div>
                    </div>

                    <!-- Messages -->
                    <div v-if="output.messages">
                        <div class="mb-2 flex items-center justify-between">
                            <button
                                class="flex cursor-pointer items-center gap-2 font-semibold text-indigo-900 hover:text-indigo-700"
                                @click="messagesExpanded = !messagesExpanded"
                            >
                                <DynamicIcon
                                    name="chevron-right"
                                    class="h-5 w-5 transition-transform duration-200"
                                    :class="{
                                        'rotate-90': messagesExpanded,
                                    }"
                                />
                                Messages
                            </button>
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
                                    {{
                                        showRawMessages
                                            ? '◆ Formatted'
                                            : '◇ Raw'
                                    }}
                                </ButtonSmall>
                                <ButtonSmall
                                    title="Expand to full screen"
                                    @click="emit('expandMessages')"
                                >
                                    ⛶ Expand
                                </ButtonSmall>
                            </div>
                        </div>
                        <div v-if="messagesExpanded">
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
                                            v-if="
                                                message.content &&
                                                !showRawMessages
                                            "
                                            class="prose dark:prose-invert prose-sm max-w-none text-indigo-700"
                                            v-html="
                                                renderMarkdown(message.content)
                                            "
                                        />
                                        <div
                                            v-else-if="
                                                message.content &&
                                                showRawMessages
                                            "
                                            class="font-mono text-sm wrap-break-word whitespace-pre-wrap text-indigo-700"
                                        >
                                            {{ message.content }}
                                        </div>
                                        <div
                                            v-else
                                            class="text-xs text-indigo-700"
                                        >
                                            {{
                                                JSON.stringify(message, null, 2)
                                            }}
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
                    </div>
                </template>
            </div>
            <p v-else class="text-indigo-500 italic">
                No output yet. Execute the JavaScript to see results.
            </p>
        </div>
    </div>
</template>
