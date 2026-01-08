<script setup lang="ts">
import ButtonSmall from '@/Components/Base/Button/ButtonSmall.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

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
    title: '',
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
const { t } = useI18n();
const titleText = computed(() =>
    props.title ? props.title : t('workflow.outputPanel.title'),
);

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
    // If data has system/messages (prompt output), count the generated prompt text
    if (typeof data === 'object' && data !== null) {
        const obj = data as any;
        let totalLength = 0;
        if (typeof obj.system === 'string') {
            totalLength += obj.system.length;
        }
        if (Array.isArray(obj.messages)) {
            totalLength += obj.messages.reduce((sum: number, msg: any) => {
                if (typeof msg.content === 'string') {
                    return sum + msg.content.length;
                }
                return sum;
            }, 0);
        }
        if (totalLength > 0) {
            return new Intl.NumberFormat().format(totalLength);
        }
    }
    // Otherwise count the entire JSON serialisation
    const jsonString = JSON.stringify(data, null, 2);
    const count = jsonString.length;
    return new Intl.NumberFormat().format(count);
};
</script>

<template>
    <div class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md">
        <div class="bg-indigo-300 px-6 py-4 font-semibold text-indigo-800">
            {{
                $t('workflow.outputPanel.header', {
                    title: titleText,
                    count: getJsonCharacterCount(output),
                })
            }}
        </div>
        <div class="flex-1 overflow-auto bg-indigo-100 p-6">
            <div v-if="output">
                <!-- Raw JSON display (for n8n workflow outputs) -->
                <div v-if="props.showRawJson">
                    <div class="mb-2 flex items-center justify-between">
                        <div class="text-xs font-semibold text-indigo-900">
                            {{ $t('workflow.outputPanel.rawJson') }}
                        </div>
                        <div class="flex gap-2">
                            <ButtonSmall
                                :title="$t('workflow.outputPanel.copyTitle')"
                                @click="
                                    copyToClipboard(
                                        JSON.stringify(output, null, 2),
                                    )
                                "
                            >
                                {{ $t('workflow.outputPanel.copy') }}
                            </ButtonSmall>
                            <ButtonSmall
                                :title="
                                    wrapRawJson
                                        ? $t('workflow.outputPanel.wrapDisable')
                                        : $t('workflow.outputPanel.wrapEnable')
                                "
                                @click="wrapRawJson = !wrapRawJson"
                            >
                                {{
                                    wrapRawJson
                                        ? $t('workflow.outputPanel.wrapOn')
                                        : $t('workflow.outputPanel.wrapOff')
                                }}
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
                                {{ $t('workflow.outputPanel.system') }}
                            </button>
                            <div class="flex gap-2">
                                <ButtonSmall
                                    :title="
                                        $t('workflow.outputPanel.copyTitle')
                                    "
                                    @click="copyToClipboard(output.system)"
                                >
                                    {{ $t('workflow.outputPanel.copy') }}
                                </ButtonSmall>
                                <ButtonSmall
                                    :title="
                                        showRawSystem
                                            ? $t(
                                                  'workflow.outputPanel.switchFormatted',
                                              )
                                            : $t(
                                                  'workflow.outputPanel.switchRaw',
                                              )
                                    "
                                    @click="toggleRawMode('system')"
                                >
                                    {{
                                        showRawSystem
                                            ? $t(
                                                  'workflow.outputPanel.formattedLabel',
                                              )
                                            : $t(
                                                  'workflow.outputPanel.rawLabel',
                                              )
                                    }}
                                </ButtonSmall>
                                <ButtonSmall
                                    :title="
                                        $t('workflow.outputPanel.expandTitle')
                                    "
                                    @click="emit('expandSystem')"
                                >
                                    {{ $t('workflow.outputPanel.expand') }}
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
                                {{ $t('workflow.outputPanel.messages') }}
                            </button>
                            <div class="flex gap-2">
                                <ButtonSmall
                                    :title="
                                        $t('workflow.outputPanel.copyTitle')
                                    "
                                    @click="
                                        copyToClipboard(
                                            getMessagesAsText(output.messages),
                                        )
                                    "
                                >
                                    {{ $t('workflow.outputPanel.copy') }}
                                </ButtonSmall>
                                <ButtonSmall
                                    :title="
                                        showRawMessages
                                            ? $t(
                                                  'workflow.outputPanel.switchFormatted',
                                              )
                                            : $t(
                                                  'workflow.outputPanel.switchRaw',
                                              )
                                    "
                                    @click="toggleRawMode('messages')"
                                >
                                    {{
                                        showRawMessages
                                            ? $t(
                                                  'workflow.outputPanel.formattedLabel',
                                              )
                                            : $t(
                                                  'workflow.outputPanel.rawLabel',
                                              )
                                    }}
                                </ButtonSmall>
                                <ButtonSmall
                                    :title="
                                        $t('workflow.outputPanel.expandTitle')
                                    "
                                    @click="emit('expandMessages')"
                                >
                                    {{ $t('workflow.outputPanel.expand') }}
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
                {{ $t('workflow.outputPanel.empty') }}
            </p>
        </div>
    </div>
</template>
