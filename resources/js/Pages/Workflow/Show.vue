<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonSuccess from '@/Components/Base/Button/ButtonSuccess.vue';
import FormSelect from '@/Components/Base/Form/FormSelect.vue';
import ExpandableModal from '@/Components/Features/Workflow/ExpandableModal.vue';
import NotificationToast from '@/Components/Features/Workflow/NotificationToast.vue';
import OutputPanel from '@/Components/Features/Workflow/OutputPanel.vue';
import PageHeader from '@/Components/Features/Workflow/PageHeader.vue';
import VariantSelector from '@/Components/Features/Workflow/VariantSelector.vue';
import { useWorkflowOperations } from '@/Composables/features/useWorkflowOperations';
import { useWorkflowViewModes } from '@/Composables/features/useWorkflowViewModes';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

interface Variant {
    key: string;
    name: string;
    description?: string;
}

interface PreparePromptNode {
    name: string;
    passNumber: number;
    passInput: object | null;
    javascriptOld: string | null;
    javascriptNew: string | null;
    promptOld: object | null;
    promptNew: object | null;
}

interface Props {
    workflowNumber: number;
    currentVariant?: string;
    currentPass?: number;
    availableVariants?: Variant[];
    input?: object | null;
    preparePromptNodes?: PreparePromptNode[];
    outputOld?: object | null;
    outputNew?: object | null;
    // Legacy props for backwards compatibility
    javascriptOld?: string | null;
    javascriptNew?: string | null;
    promptOld?: object | null;
    promptNew?: object | null;
}

const props = withDefaults(defineProps<Props>(), {
    input: null,
    currentVariant: 'default',
    currentPass: 0,
    availableVariants: () => [],
    preparePromptNodes: () => [],
    javascriptOld: null,
    javascriptNew: null,
    promptOld: null,
    promptNew: null,
    outputOld: null,
    outputNew: null,
});

const { t } = useI18n({ useScope: 'global' });
const { confirm, success, error: showError } = useAlert();
const { countryRoute } = useCountryRoute();

defineOptions({
    layout: AdminLayout,
});

const workflowRoute = (
    name: string,
    parameters: Record<string, unknown> = {},
) => {
    return countryRoute(name, {
        workflowNumber: props.workflowNumber,
        ...parameters,
    });
};

// Utility functions for markdown rendering and clipboard operations
const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};

const copyToClipboard = async (text: string | null | undefined) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        // Silently fail - clipboard might not be available
    }
};

const getMessagesAsText = (messages: unknown) => {
    if (!Array.isArray(messages)) {
        return JSON.stringify(messages, null, 2);
    }
    return messages
        .map((msg) => {
            if (typeof msg === 'object' && msg !== null) {
                if ((msg as Record<string, unknown>).content)
                    return String((msg as Record<string, unknown>).content);
                return JSON.stringify(msg, null, 2);
            }
            return String(msg);
        })
        .join('\n\n');
};

// Use composables for operations and view modes
const {
    isPreparingOld,
    isPreparingNew,
    isExecutingOld,
    isExecutingNew,
    isUploadingOld,
    isUploadingNew,
    isUploadingToLive,
    error,
    saveMessage,
    reloadJavaScriptFromWorkflowAsOld,
    reloadJavaScriptFromWorkflowAsNew,
    executeWorkflowOld: executeWorkflowOldOp,
    executeWorkflowNew: executeWorkflowNewOp,
    uploadWorkflowOld: uploadWorkflowOldOp,
    uploadWorkflowNew: uploadWorkflowNewOp,
    uploadWorkflowToLive: uploadWorkflowToLiveOp,
    saveInputData: saveInputDataOp,
    saveOldJavaScriptToFile: saveOldJavaScriptToFileOp,
    saveNewJavaScriptToFile: saveNewJavaScriptToFileOp,
    preparePromptOld: preparePromptOldOp,
    preparePromptNew: preparePromptNewOp,
    savePassInputData: savePassInputDataOp,
} = useWorkflowOperations(workflowRoute);

const {
    expandedView,
    rawModeMessagesOld,
    rawModeMessagesNew,
    rawModeSystemOld,
    rawModeSystemNew,
    rawModeWorkflowMessagesOld,
    rawModeWorkflowMessagesNew,
    rawModeWorkflowSystemOld,
    rawModeWorkflowSystemNew,
    handleRawModeUpdate,
} = useWorkflowViewModes();

const input = ref(props.input);
const inputJson = ref(JSON.stringify(props.input, null, 2));
const passInputJson = ref<Record<string, string>>({});
const javascriptOld = ref(props.javascriptOld || '');
const javascriptNew = ref(props.javascriptNew || '');
const promptOld = ref(props.promptOld);
const promptNew = ref(props.promptNew);
const outputOld = ref(props.outputOld);
const outputNew = ref(props.outputNew);
const preparePromptNodes = ref(props.preparePromptNodes || []);

// Track which pass is selected when multiple passes exist (0-based index)
const selectedPass = ref(props.currentPass);

// Get the current node being displayed based on selected pass
const currentNode = computed(() => {
    return preparePromptNodes.value[selectedPass.value] || null;
});

// Get pass options for FormSelect
const passOptions = computed(() => {
    return preparePromptNodes.value.map((_, index) => ({
        value: String(index),
        label: t('workflow.passLabel', { number: index + 1 }),
    }));
});

// Watch for pass changes and update URL
watch(selectedPass, (newPass) => {
    const url = new URL(window.location.href);
    if (newPass === 0) {
        url.searchParams.delete('pass');
    } else {
        url.searchParams.set('pass', String(newPass));
    }
    window.location.href = url.toString();
});

// Wrapper functions for composable operations
const executeWorkflowOld = async (nodeName: string = 'Prepare Prompt') => {
    const output = await executeWorkflowOldOp(
        inputJson.value,
        props.currentVariant,
        nodeName,
    );
    if (output) {
        outputOld.value = output;
    }
};

const executeWorkflowNew = async (nodeName: string = 'Prepare Prompt') => {
    const output = await executeWorkflowNewOp(
        inputJson.value,
        props.currentVariant,
        nodeName,
    );
    if (output) {
        outputNew.value = output;
    }
};

const uploadWorkflowOld = async (nodeName: string = 'Prepare Prompt') => {
    await saveOldJavaScriptToFileOp(
        currentNode.value?.javascriptOld ?? javascriptOld.value,
        props.currentVariant,
        currentNode.value?.name || 'Prepare Prompt',
    );
    await uploadWorkflowOldOp(props.currentVariant, nodeName);
};

const uploadWorkflowNew = async (nodeName: string = 'Prepare Prompt') => {
    await saveNewJavaScriptToFileOp(
        currentNode.value?.javascriptNew ?? javascriptNew.value,
        props.currentVariant,
        currentNode.value?.name || 'Prepare Prompt',
    );
    await uploadWorkflowNewOp(props.currentVariant, nodeName);
};

const uploadWorkflowToLive = async () => {
    const confirmed = await confirm(
        t('workflow.confirmUploadMessage'),
        t('workflow.confirmUploadTitle'),
        {
            confirmText: t('workflow.uploadToLiveButton'),
            cancelText: t('common.buttons.cancel'),
            confirmButtonStyle: 'danger',
        },
    );

    if (!confirmed) {
        return;
    }

    const success_result = await uploadWorkflowToLiveOp();
    if (success_result) {
        await success(
            t('workflow.uploadSuccessMessage'),
            t('workflow.uploadSuccessTitle'),
        );
    } else if (error.value) {
        await showError(
            error.value || t('workflow.uploadFailedMessage'),
            t('workflow.uploadFailedTitle'),
        );
    }
};

const saveInputData = async () => {
    await saveInputDataOp(inputJson.value);
};

const saveOldJavaScriptToFile = async () => {
    const code = currentNode.value?.javascriptOld ?? javascriptOld.value;
    const nodeName = currentNode.value?.name || 'Prepare Prompt';
    await saveOldJavaScriptToFileOp(code, props.currentVariant, nodeName);
};

const saveNewJavaScriptToFile = async () => {
    const code = currentNode.value?.javascriptNew ?? javascriptNew.value;
    const nodeName = currentNode.value?.name || 'Prepare Prompt';
    await saveNewJavaScriptToFileOp(code, props.currentVariant, nodeName);
};

const preparePromptOld = async (nodeName: string = 'Prepare Prompt') => {
    const promptData = await preparePromptOldOp(
        inputJson.value,
        props.currentVariant,
        preparePromptNodes.value,
        nodeName,
    );
    if (nodeName === 'Prepare Prompt' && promptData) {
        promptOld.value = promptData;
    }
};

const preparePromptNew = async (nodeName: string = 'Prepare Prompt') => {
    const promptData = await preparePromptNewOp(
        inputJson.value,
        props.currentVariant,
        preparePromptNodes.value,
        nodeName,
    );
    if (nodeName === 'Prepare Prompt' && promptData) {
        promptNew.value = promptData;
    }
};

const savePassInputData = async (nodeName: string, passNumber: number) => {
    await savePassInputDataOp(
        nodeName,
        passNumber,
        passInputJson.value[nodeName],
    );
};

// Auto-execute both versions when page loads if they have code
onMounted(async () => {
    props.preparePromptNodes.forEach((node) => {
        if (node.passNumber > 1) {
            passInputJson.value[node.name] = JSON.stringify(
                node.passInput || {},
                null,
                2,
            );
        }
    });

    if (currentNode.value) {
        if (currentNode.value.javascriptOld && input.value) {
            await preparePromptOld(currentNode.value.name);
        }

        if (currentNode.value.javascriptNew && input.value) {
            await preparePromptNew(currentNode.value.name);
        }
    }
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between">
            <PageHeader
                :title="t('workflow.show.pageTitle', { workflowNumber })"
                :subtitle="t('workflow.show.subtitle')"
            />

            <div class="flex items-center gap-4">
                <VariantSelector
                    v-if="availableVariants.length > 1"
                    :workflow-number="workflowNumber"
                    :current-variant="currentVariant"
                    :available-variants="availableVariants"
                />

                <!-- Pass selector (only show if multiple passes exist) -->
                <FormSelect
                    v-if="preparePromptNodes.length > 1"
                    id="pass-selector"
                    :model-value="String(selectedPass)"
                    label="Pass:"
                    label-sr-only
                    :options="passOptions"
                    :show-placeholder="false"
                    @update:model-value="selectedPass = parseInt($event)"
                />

                <ButtonPrimary @click="expandedView = 'input'">
                    {{ t('workflow.show.buttons.inputData') }}
                </ButtonPrimary>

                <!-- Pass Input button for Pass 2+ -->
                <ButtonPrimary
                    v-if="currentNode.passNumber > 1"
                    @click="expandedView = `pass-input-${selectedPass}`"
                >
                    {{
                        t('workflow.show.buttons.passInput', {
                            passNumber: currentNode.passNumber,
                        })
                    }}
                </ButtonPrimary>

                <ButtonDanger
                    :disabled="isUploadingToLive"
                    @click="uploadWorkflowToLive"
                >
                    {{
                        isUploadingToLive
                            ? t('workflow.show.states.uploading')
                            : t('workflow.show.buttons.uploadLive')
                    }}
                </ButtonDanger>
            </div>
        </div>

        <!-- Controls -->
        <div class="space-y-8">
            <!-- Show only the currently selected pass -->
            <div v-if="currentNode" class="space-y-4">
                <!-- Node heading -->
                <div class="items-centre flex justify-between">
                    <h3 class="text-lg font-semibold text-indigo-800">
                        {{ t('workflow.show.sections.preparePrompt')
                        }}{{
                            preparePromptNodes.length > 1
                                ? ` ${selectedPass + 1}`
                                : ''
                        }}
                    </h3>
                </div>

                <!-- Buttons for this node (OLD and NEW) -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-8">
                    <!-- OLD version buttons -->
                    <ButtonSecondary
                        @click="expandedView = `javascript-old-${selectedPass}`"
                    >
                        {{ t('workflow.show.buttons.viewJavaScript') }}
                    </ButtonSecondary>

                    <ButtonPrimary
                        :disabled="!currentNode.javascriptOld || !input"
                        @click="preparePromptOld(currentNode.name)"
                    >
                        {{
                            isPreparingOld
                                ? t('workflow.show.states.preparing')
                                : t('workflow.show.sections.preparePrompt')
                        }}
                    </ButtonPrimary>

                    <ButtonDanger
                        :disabled="!currentNode.javascriptOld || !input"
                        @click="uploadWorkflowOld(currentNode.name)"
                    >
                        {{
                            isUploadingOld
                                ? t('workflow.show.states.uploading')
                                : 'Upload to n8n'
                        }}
                    </ButtonDanger>

                    <ButtonSuccess
                        :disabled="!currentNode.javascriptOld || !input"
                        @click="executeWorkflowOld(currentNode.name)"
                    >
                        {{
                            isExecutingOld ? 'Executing...' : 'Execute workflow'
                        }}
                    </ButtonSuccess>

                    <!-- NEW version buttons -->
                    <ButtonSecondary
                        @click="expandedView = `javascript-new-${selectedPass}`"
                    >
                        {{ t('workflow.show.buttons.viewJavaScript') }}
                    </ButtonSecondary>

                    <ButtonPrimary
                        :disabled="!currentNode.javascriptNew || !input"
                        @click="preparePromptNew(currentNode.name)"
                    >
                        {{
                            isPreparingNew
                                ? t('workflow.show.states.preparing')
                                : t('workflow.show.sections.preparePrompt')
                        }}
                    </ButtonPrimary>

                    <ButtonDanger
                        :disabled="!currentNode.javascriptNew || !input"
                        @click="uploadWorkflowNew(currentNode.name)"
                    >
                        {{
                            isUploadingNew
                                ? t('workflow.show.states.uploading')
                                : 'Upload to n8n'
                        }}
                    </ButtonDanger>

                    <ButtonSuccess
                        :disabled="!currentNode.javascriptNew || !input"
                        @click="executeWorkflowNew(currentNode.name)"
                    >
                        {{
                            isExecutingNew ? 'Executing...' : 'Execute workflow'
                        }}
                    </ButtonSuccess>
                </div>

                <!-- Output panels for this node -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <OutputPanel
                        :title="`Prompt${preparePromptNodes.length > 1 ? ` ${selectedPass + 1}` : ''} (OLD)`"
                        :output="currentNode.promptOld"
                        @expand-system="
                            expandedView = `system-old-${selectedPass}`
                        "
                        @expand-messages="
                            expandedView = `messages-old-${selectedPass}`
                        "
                        @update-raw-mode="handleRawModeUpdate"
                    />
                    <OutputPanel
                        :title="`Prompt${preparePromptNodes.length > 1 ? ` ${selectedPass + 1}` : ''} (NEW)`"
                        :output="currentNode.promptNew"
                        @expand-system="
                            expandedView = `system-new-${selectedPass}`
                        "
                        @expand-messages="
                            expandedView = `messages-new-${selectedPass}`
                        "
                        @update-raw-mode="handleRawModeUpdate"
                    />
                </div>
            </div>

            <!-- Workflow Output Sections (shown at the end after all nodes) -->
            <div class="border-t-2 border-indigo-300 pt-8">
                <h3 class="mb-4 text-lg font-semibold text-indigo-800">
                    {{ t('workflow.show.sections.workflowOutput') }}
                </h3>
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <OutputPanel
                        title="Output (OLD)"
                        :output="outputOld"
                        show-raw-json
                    />
                    <OutputPanel
                        title="Output (NEW)"
                        :output="outputNew"
                        show-raw-json
                    />
                </div>
            </div>
        </div>

        <!-- Modal: Expanded System Prompt (old) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`system-old-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `system-old-${nodeIndex}`"
                :title="`${node.name} - System Prompt (old) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <div class="flex-1 overflow-auto p-6">
                        <div
                            v-if="rawModeSystemOld"
                            class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                        >
                            {{ node.promptOld?.system }}
                        </div>
                        <div
                            v-else
                            class="prose prose-sm dark:prose-invert max-w-none"
                            v-html="renderMarkdown(node.promptOld?.system)"
                        />
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                    >
                        <span class="text-xs text-indigo-600">
                            {{
                                node.promptOld?.system
                                    ? `${(node.promptOld.system as string).length} characters`
                                    : 'N/A'
                            }}
                        </span>
                        <button
                            class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                            title="Copy to clipboard"
                            @click="copyToClipboard(node.promptOld?.system)"
                        >
                            📋 Copy
                        </button>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded Messages (old) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`messages-old-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `messages-old-${nodeIndex}`"
                :title="`${node.name} - Messages (old) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <div class="flex-1 overflow-auto p-6">
                        <div
                            v-if="Array.isArray(node.promptOld?.messages)"
                            class="space-y-4"
                        >
                            <div
                                v-for="(message, index) in node.promptOld
                                    ?.messages"
                                :key="index"
                                class="rounded border border-indigo-200 bg-indigo-50 p-4"
                            >
                                <div
                                    v-if="
                                        message.content && !rawModeMessagesOld
                                    "
                                    class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                    v-html="renderMarkdown(message.content)"
                                />
                                <div
                                    v-else-if="
                                        message.content && rawModeMessagesOld
                                    "
                                    class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                                >
                                    {{ message.content }}
                                </div>
                                <div v-else class="text-sm text-indigo-700">
                                    {{ JSON.stringify(message, null, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                    >
                        <span class="text-xs text-indigo-600">
                            {{
                                node.promptOld?.messages &&
                                Array.isArray(node.promptOld.messages)
                                    ? `${node.promptOld.messages.length} messages`
                                    : 'N/A'
                            }}
                        </span>
                        <button
                            class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                            title="Copy to clipboard"
                            @click="
                                copyToClipboard(
                                    getMessagesAsText(node.promptOld?.messages),
                                )
                            "
                        >
                            📋 Copy
                        </button>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded Input Data -->
        <ExpandableModal
            :show="expandedView === 'input'"
            title="Input Data (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <textarea
                    v-model="inputJson"
                    class="flex-1 resize-none overflow-auto border-0 bg-white p-6 font-mono text-sm leading-6 focus:outline-none"
                    placeholder="Enter input data as JSON..."
                    style="
                        line-height: 1.5;
                        white-space: pre;
                        overflow-wrap: normal;
                    "
                ></textarea>
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            inputJson ? `${inputJson.length} characters` : 'N/A'
                        }}
                    </span>
                    <ButtonPrimary
                        @click="
                            saveInputData();
                            expandedView = null;
                        "
                    >
                        Save and Close
                    </ButtonPrimary>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded JavaScript Code (old) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`javascript-old-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `javascript-old-${nodeIndex}`"
                :title="`${node.name} - JavaScript Code (old) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <textarea
                        v-model="node.javascriptOld"
                        class="flex-1 resize-none overflow-auto border-0 bg-white p-6 font-mono text-sm leading-6 focus:outline-none"
                        placeholder="Edit JavaScript code here..."
                        style="
                            line-height: 1.5;
                            white-space: pre;
                            overflow-wrap: normal;
                        "
                    ></textarea>
                    <div
                        class="flex items-center justify-between gap-2 border-t bg-indigo-50 py-3"
                    >
                        <span class="ml-4 text-xs text-indigo-600">
                            {{
                                node.javascriptOld
                                    ? `${node.javascriptOld.length} characters`
                                    : 'N/A'
                            }}
                        </span>

                        <div>
                            <ButtonSecondary
                                class="mr-2"
                                @click="reloadJavaScriptFromWorkflowAsOld"
                            >
                                Reload JS from JSON (old)
                            </ButtonSecondary>

                            <ButtonPrimary
                                @click="
                                    saveOldJavaScriptToFile();
                                    expandedView = null;
                                "
                            >
                                Save and Close
                            </ButtonPrimary>
                        </div>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded JavaScript Code (new) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`javascript-new-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `javascript-new-${nodeIndex}`"
                :title="`${node.name} - JavaScript Code (new) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <textarea
                        v-model="node.javascriptNew"
                        class="flex-1 resize-none overflow-auto border-0 bg-white p-6 font-mono text-sm leading-6 focus:outline-none"
                        placeholder="New JavaScript code will appear here..."
                        style="
                            line-height: 1.5;
                            white-space: pre;
                            overflow-wrap: normal;
                        "
                    ></textarea>
                    <div
                        class="flex items-center justify-between gap-2 border-t bg-indigo-50 py-3"
                    >
                        <span class="ml-4 text-xs text-indigo-600">
                            {{
                                node.javascriptNew
                                    ? `${node.javascriptNew.length} characters`
                                    : 'N/A'
                            }}
                        </span>

                        <div>
                            <ButtonSecondary
                                class="mr-2"
                                @click="reloadJavaScriptFromWorkflowAsNew"
                            >
                                Reload JS from JSON (new)
                            </ButtonSecondary>

                            <ButtonPrimary
                                @click="
                                    saveNewJavaScriptToFile();
                                    expandedView = null;
                                "
                            >
                                Save and Close
                            </ButtonPrimary>
                        </div>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded System Prompt (new) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`system-new-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `system-new-${nodeIndex}`"
                :title="`${node.name} - System Prompt (new) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <div class="flex-1 overflow-auto p-6">
                        <div
                            v-if="rawModeSystemNew"
                            class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                        >
                            {{ node.promptNew?.system }}
                        </div>
                        <div
                            v-else
                            class="prose prose-sm dark:prose-invert max-w-none"
                            v-html="renderMarkdown(node.promptNew?.system)"
                        />
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                    >
                        <span class="text-xs text-indigo-600">
                            {{
                                node.promptNew?.system
                                    ? `${(node.promptNew.system as string).length} characters`
                                    : 'N/A'
                            }}
                        </span>
                        <button
                            class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                            title="Copy to clipboard"
                            @click="copyToClipboard(node.promptNew?.system)"
                        >
                            📋 Copy
                        </button>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded Messages (new) for multiple nodes -->
        <template
            v-for="(node, nodeIndex) in preparePromptNodes"
            :key="`messages-new-modal-${nodeIndex}`"
        >
            <ExpandableModal
                :show="expandedView === `messages-new-${nodeIndex}`"
                :title="`${node.name} - Messages (new) - Expanded`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <div class="flex-1 overflow-auto p-6">
                        <div
                            v-if="Array.isArray(node.promptNew?.messages)"
                            class="space-y-4"
                        >
                            <div
                                v-for="(message, index) in node.promptNew
                                    ?.messages"
                                :key="index"
                                class="rounded border border-indigo-200 bg-indigo-50 p-4"
                            >
                                <div
                                    v-if="
                                        message.content && !rawModeMessagesNew
                                    "
                                    class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                    v-html="renderMarkdown(message.content)"
                                />
                                <div
                                    v-else-if="
                                        message.content && rawModeMessagesNew
                                    "
                                    class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                                >
                                    {{ message.content }}
                                </div>
                                <div v-else class="text-sm text-indigo-700">
                                    {{ JSON.stringify(message, null, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                    >
                        <span class="text-xs text-indigo-600">
                            {{
                                node.promptNew?.messages &&
                                Array.isArray(node.promptNew.messages)
                                    ? `${node.promptNew.messages.length} messages`
                                    : 'N/A'
                            }}
                        </span>
                        <button
                            class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                            title="Copy to clipboard"
                            @click="
                                copyToClipboard(
                                    getMessagesAsText(node.promptNew?.messages),
                                )
                            "
                        >
                            📋 Copy
                        </button>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Modal: Expanded Workflow System Prompt (old) -->
        <ExpandableModal
            :show="expandedView === 'workflow-system-old'"
            title="Workflow System Prompt (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeWorkflowSystemOld"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ outputOld?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(outputOld?.system)"
                    />
                </div>
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            outputOld?.system
                                ? `${(outputOld.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(outputOld?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Workflow Messages (old) -->
        <ExpandableModal
            :show="expandedView === 'workflow-messages-old'"
            title="Workflow Messages (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(outputOld?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in outputOld?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="
                                    message.content &&
                                    !rawModeWorkflowMessagesOld
                                "
                                class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                v-html="renderMarkdown(message.content)"
                            />
                            <div
                                v-else-if="
                                    message.content &&
                                    rawModeWorkflowMessagesOld
                                "
                                class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                            >
                                {{ message.content }}
                            </div>
                            <div v-else class="text-sm text-indigo-700">
                                {{ JSON.stringify(message, null, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            outputOld?.messages &&
                            Array.isArray(outputOld.messages)
                                ? `${outputOld.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(outputOld?.messages),
                            )
                        "
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Workflow System Prompt (new) -->
        <ExpandableModal
            :show="expandedView === 'workflow-system-new'"
            title="Workflow System Prompt (new) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeWorkflowSystemNew"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ outputNew?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(outputNew?.system)"
                    />
                </div>
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            outputNew?.system
                                ? `${(outputNew.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(outputNew?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Workflow Messages (new) -->
        <ExpandableModal
            :show="expandedView === 'workflow-messages-new'"
            title="Workflow Messages (new) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(outputNew?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in outputNew?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="
                                    message.content &&
                                    !rawModeWorkflowMessagesNew
                                "
                                class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                v-html="renderMarkdown(message.content)"
                            />
                            <div
                                v-else-if="
                                    message.content &&
                                    rawModeWorkflowMessagesNew
                                "
                                class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                            >
                                {{ message.content }}
                            </div>
                            <div v-else class="text-sm text-indigo-700">
                                {{ JSON.stringify(message, null, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            outputNew?.messages &&
                            Array.isArray(outputNew.messages)
                                ? `${outputNew.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(outputNew?.messages),
                            )
                        "
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Pass Input Modals (for Pass 2+) -->
        <template
            v-for="node in preparePromptNodes.filter(
                (n: PreparePromptNode) => n.passNumber > 1,
            )"
            :key="`pass-input-${node.name}`"
        >
            <ExpandableModal
                :show="
                    expandedView ===
                    `pass-input-${preparePromptNodes.indexOf(node)}`
                "
                :title="`Pass ${node.passNumber} Input - ${node.name}`"
                @close="expandedView = null"
            >
                <div class="flex h-full flex-col">
                    <textarea
                        v-model="passInputJson[node.name]"
                        class="flex-1 resize-none overflow-auto border-0 bg-white p-6 font-mono text-sm leading-6 focus:outline-none"
                        placeholder="Enter input data as JSON (object or array format)..."
                        style="
                            line-height: 1.5;
                            white-space: pre;
                            overflow-wrap: normal;
                        "
                    ></textarea>
                    <div
                        class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                    >
                        <span class="text-xs text-indigo-600">
                            {{
                                passInputJson[node.name]
                                    ? `${passInputJson[node.name].length} characters`
                                    : 'N/A'
                            }}
                        </span>
                        <ButtonPrimary
                            @click="
                                savePassInputData(node.name, node.passNumber);
                                expandedView = null;
                            "
                        >
                            Save and Close
                        </ButtonPrimary>
                    </div>
                </div>
            </ExpandableModal>
        </template>

        <!-- Notifications -->
        <NotificationToast
            :show="!!error"
            :message="error || ''"
            type="error"
        />
        <NotificationToast
            :show="!!saveMessage"
            :message="saveMessage || ''"
            type="success"
        />
    </div>
</template>
