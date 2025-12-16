<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import ButtonSuccess from '@/Components/ButtonSuccess.vue';
import ExpandableModal from '@/Components/Workflow/ExpandableModal.vue';
import NotificationToast from '@/Components/Workflow/NotificationToast.vue';
import OutputPanel from '@/Components/Workflow/OutputPanel.vue';
import PageHeader from '@/Components/Workflow/PageHeader.vue';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { onMounted, ref } from 'vue';

interface Props {
    workflowNumber: number;
    input?: object | null;
    javascriptOld?: string | null;
    javascriptNew?: string | null;
    promptOld?: object | null;
    promptNew?: object | null;
    outputOld?: object | null;
    outputNew?: object | null;
}

const props = withDefaults(defineProps<Props>(), {
    input: null,
    javascriptOld: null,
    javascriptNew: null,
    promptOld: null,
    promptNew: null,
    outputOld: null,
    outputNew: null,
});

defineOptions({
    layout: WorkflowLayout,
});

const isExecuting = ref(false);
const isExecutingNew = ref(false);
const error = ref<string | null>(null);

const input = ref(props.input);
const inputJson = ref(JSON.stringify(props.input, null, 2));
const javascriptOld = ref(props.javascriptOld || '');
const javascriptNew = ref(props.javascriptNew || '');
const promptOld = ref(props.promptOld);
const promptNew = ref(props.promptNew);
const outputOld = ref(props.outputOld);
const outputNew = ref(props.outputNew);

// Modal state for maximized views
const expandedView = ref<
    | 'input'
    | 'javascript-old'
    | 'javascript-new'
    | 'messages-old'
    | 'messages-new'
    | 'system-old'
    | 'system-new'
    | 'workflow-messages-old'
    | 'workflow-messages-new'
    | 'workflow-system-old'
    | 'workflow-system-new'
    | null
>(null);
const saveMessage = ref<string | null>(null);

// Track raw vs formatted mode for expanded views
const rawModeMessagesOld = ref(false);
const rawModeMessagesNew = ref(false);
const rawModeSystemOld = ref(false);
const rawModeSystemNew = ref(false);
const rawModeWorkflowMessagesOld = ref(false);
const rawModeWorkflowMessagesNew = ref(false);
const rawModeWorkflowSystemOld = ref(false);
const rawModeWorkflowSystemNew = ref(false);

const getCsrfToken = () => {
    const token = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (!token) {
        // Fallback: try to extract from cookies
        const name = 'XSRF-TOKEN';
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop()?.split(';').shift();
    }
    return token;
};

const makeRequest = async (url: string, method: string, body?: unknown) => {
    const csrfToken = getCsrfToken();
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
    };
    if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const config: RequestInit = {
        method,
        headers,
    };
    if (body) {
        config.body = JSON.stringify(body);
    }

    return fetch(url, config);
};

const reloadJavaScriptFromWorkflowAsOld = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/reload-javascript-old`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value =
                result.error || 'Failed to reload JavaScript from workflow';
        } else {
            javascriptOld.value = result.code || '';
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to reload JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const reloadJavaScriptFromWorkflowAsNew = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/reload-javascript-new`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value =
                result.error || 'Failed to reload JavaScript from workflow';
        } else {
            javascriptNew.value = result.code || '';
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to reload JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const executeWorkflowOld = async () => {
    if (!inputJson.value) {
        error.value = 'Input data is required';
        return;
    }

    isExecuting.value = true;
    error.value = null;

    try {
        // Parse the input JSON
        let inputData;
        try {
            inputData = JSON.parse(inputJson.value);
        } catch {
            error.value = 'Invalid JSON in input data';
            return;
        }

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/execute-workflow-old`,
            'POST',
            { input: inputData },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Workflow execution failed';
            return;
        }

        outputOld.value = result.output;
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isExecuting.value = false;
    }
};

const executeWorkflowNew = async () => {
    if (!inputJson.value) {
        error.value = 'Input data is required';
        return;
    }

    isExecutingNew.value = true;
    error.value = null;

    try {
        // Parse the input JSON
        let inputData;
        try {
            inputData = JSON.parse(inputJson.value);
        } catch {
            error.value = 'Invalid JSON in input data';
            return;
        }

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/execute-workflow-new`,
            'POST',
            { input: inputData },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Workflow execution failed';
            return;
        }

        outputNew.value = result.output;
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isExecutingNew.value = false;
    }
};

const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};

const handleRawModeUpdate = (
    section:
        | 'messages-old'
        | 'messages-new'
        | 'system-old'
        | 'system-new'
        | 'workflow-messages-old'
        | 'workflow-messages-new'
        | 'workflow-system-old'
        | 'workflow-system-new',
    isRaw: boolean,
) => {
    if (section === 'system-old') {
        rawModeSystemOld.value = isRaw;
    } else if (section === 'system-new') {
        rawModeSystemNew.value = isRaw;
    } else if (section === 'messages-old') {
        rawModeMessagesOld.value = isRaw;
    } else if (section === 'messages-new') {
        rawModeMessagesNew.value = isRaw;
    } else if (section === 'workflow-messages-old') {
        rawModeWorkflowMessagesOld.value = isRaw;
    } else if (section === 'workflow-messages-new') {
        rawModeWorkflowMessagesNew.value = isRaw;
    } else if (section === 'workflow-system-old') {
        rawModeWorkflowSystemOld.value = isRaw;
    } else if (section === 'workflow-system-new') {
        rawModeWorkflowSystemNew.value = isRaw;
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

const saveInputData = async () => {
    try {
        // Parse the input JSON to validate and wrap in array format
        let inputData;
        try {
            inputData = JSON.parse(inputJson.value);
        } catch {
            error.value = 'Invalid JSON in input data';
            return;
        }

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/input`,
            'POST',
            [inputData],
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save input data';
        } else {
            saveMessage.value = 'Input data saved successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to save input data: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const saveOldJavaScriptToFile = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript-old`,
            'POST',
            { code: javascriptOld.value },
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save JavaScript code';
        } else {
            saveMessage.value = 'Old JavaScript code saved successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to save JavaScript code: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const saveNewJavaScriptToFile = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript-new`,
            'POST',
            { code: javascriptNew.value },
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save new JavaScript code';
        } else {
            saveMessage.value = 'New JavaScript code saved successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to save new JavaScript code: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const preparePromptOld = async () => {
    if (!javascriptOld.value || !inputJson.value) {
        error.value = 'Both input and JavaScript code are required';
        return;
    }

    isExecuting.value = true;
    error.value = null;

    try {
        // Parse the input JSON
        let inputData;
        try {
            inputData = JSON.parse(inputJson.value);
        } catch {
            error.value = 'Invalid JSON in input data';
            return;
        }

        // Save the old JavaScript to file first
        await saveOldJavaScriptToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/prepare-prompt-old`,
            'POST',
            { input: inputData },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Execution failed';
            return;
        }

        promptOld.value = result.prompt;
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isExecuting.value = false;
    }
};

const preparePromptNew = async () => {
    if (!javascriptNew.value || !inputJson.value) {
        error.value = 'Both input and JavaScript code are required';
        return;
    }

    isExecutingNew.value = true;
    error.value = null;

    try {
        // Parse the input JSON
        let inputData;
        try {
            inputData = JSON.parse(inputJson.value);
        } catch {
            error.value = 'Invalid JSON in input data';
            return;
        }

        // Save the new JavaScript to file first
        await saveNewJavaScriptToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/prepare-prompt-new`,
            'POST',
            { input: inputData },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Execution failed';
            return;
        }

        promptNew.value = result.prompt;
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isExecutingNew.value = false;
    }
};

// Auto-execute both versions when page loads if they have code
onMounted(async () => {
    // Execute old version if available
    if (javascriptOld.value && input.value) {
        await preparePromptOld();
    }

    // Execute new version if available
    if (javascriptNew.value && input.value) {
        await preparePromptNew();
    }
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between">
            <PageHeader
                :title="`Workflow ${workflowNumber}`"
                subtitle="Inspect workflow input, JavaScript code, and output"
            />

            <ButtonPrimary @click="expandedView = 'input'">
                Input Data
            </ButtonPrimary>
        </div>

        <!-- Controls -->
        <div class="space-y-8">
            <!-- Input Data and JavaScript Code Buttons -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
                <ButtonSecondary @click="expandedView = 'javascript-old'">
                    View JavaScript (old)
                </ButtonSecondary>

                <ButtonPrimary
                    :disabled="!javascriptOld || !input"
                    @click="preparePromptOld"
                >
                    {{ isExecuting ? 'Executing...' : 'Prepare Prompt' }}
                </ButtonPrimary>

                <ButtonSuccess @click="executeWorkflowOld">
                    Upload to n8n & Execute workflow (old)
                </ButtonSuccess>

                <ButtonSecondary @click="expandedView = 'javascript-new'">
                    View JavaScript (new)
                </ButtonSecondary>

                <ButtonPrimary
                    :disabled="!javascriptNew || !input"
                    @click="preparePromptNew"
                >
                    {{ isExecutingNew ? 'Executing...' : 'Prepare Prompt' }}
                </ButtonPrimary>

                <ButtonSuccess @click="executeWorkflowNew">
                    Upload to n8n & Execute workflow (new)
                </ButtonSuccess>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <OutputPanel
                    title="Prompt (OLD)"
                    :output="promptOld"
                    @expand-system="expandedView = 'system-old'"
                    @expand-messages="expandedView = 'messages-old'"
                    @update-raw-mode="handleRawModeUpdate"
                />
                <OutputPanel
                    title="Prompt (NEW)"
                    :output="promptNew"
                    @expand-system="expandedView = 'system-new'"
                    @expand-messages="expandedView = 'messages-new'"
                    @update-raw-mode="handleRawModeUpdate"
                />
            </div>

            <!-- Workflow Output Sections -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <OutputPanel
                    title="Output (old)"
                    :output="outputOld"
                    @expand-system="expandedView = 'workflow-system-old'"
                    @expand-messages="expandedView = 'workflow-messages-old'"
                    @update-raw-mode="handleRawModeUpdate"
                />
                <OutputPanel
                    title="Output (new)"
                    :output="outputNew"
                    @expand-system="expandedView = 'workflow-system-new'"
                    @expand-messages="expandedView = 'workflow-messages-new'"
                    @update-raw-mode="handleRawModeUpdate"
                />
            </div>
        </div>

        <!-- Modal: Expanded System Prompt -->
        <ExpandableModal
            :show="expandedView === 'system-old'"
            title="System Prompt (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeSystemOld"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ promptOld?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(promptOld?.system)"
                    />
                </div>
                <div
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            promptOld?.system
                                ? `${(promptOld.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(promptOld?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Messages -->
        <ExpandableModal
            :show="expandedView === 'messages-old'"
            title="Messages (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(promptOld?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in promptOld?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="message.content && !rawModeMessagesOld"
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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            promptOld?.messages &&
                            Array.isArray(promptOld.messages)
                                ? `${promptOld.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(promptOld?.messages),
                            )
                        "
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
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

        <!-- Modal: Expanded JavaScript Code (old) -->
        <ExpandableModal
            :show="expandedView === 'javascript-old'"
            title="JavaScript Code (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <textarea
                    v-model="javascriptOld"
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
                            javascriptOld
                                ? `${javascriptOld.length} characters`
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

        <!-- Modal: Expanded JavaScript Code (new) -->
        <ExpandableModal
            :show="expandedView === 'javascript-new'"
            title="JavaScript Code (new) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <textarea
                    v-model="javascriptNew"
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
                            javascriptNew
                                ? `${javascriptNew.length} characters`
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

        <!-- Modal: Expanded System Prompt (new) -->
        <ExpandableModal
            :show="expandedView === 'system-new'"
            title="System Prompt (new) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeSystemNew"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ promptNew?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(promptNew?.system)"
                    />
                </div>
                <div
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            promptNew?.system
                                ? `${(promptNew.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(promptNew?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Messages (new) -->
        <ExpandableModal
            :show="expandedView === 'messages-new'"
            title="Messages (new) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(promptNew?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in promptNew?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="message.content && !rawModeMessagesNew"
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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            promptNew?.messages &&
                            Array.isArray(promptNew.messages)
                                ? `${promptNew.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(promptNew?.messages),
                            )
                        "
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
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
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
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
