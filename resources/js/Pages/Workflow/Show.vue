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

const props = withDefaults(defineProps<Props>(), {
    input: null,
    javascript: null,
    javascriptNew: null,
    output: null,
    outputNew: null,
});

defineOptions({
    layout: WorkflowLayout,
});

interface Props {
    workflowNumber: number;
    input?: object | null;
    javascript?: string | null;
    javascriptNew?: string | null;
    output?: object | null;
    outputNew?: object | null;
}

const isExecuting = ref(false);
const isExecutingNew = ref(false);
const error = ref<string | null>(null);

const input = ref(props.input);
const inputJson = ref(JSON.stringify(props.input, null, 2));
const javascript = ref(props.javascript || '');
const javascriptNew = ref(props.javascriptNew || '');
const output = ref(props.output);
const outputNew = ref(props.outputNew);
const workflowOutput = ref(null);
const workflowOutputNew = ref(null);

// Modal state for maximized views
const expandedView = ref<
    | 'system'
    | 'messages'
    | 'system-new'
    | 'messages-new'
    | 'workflow-system'
    | 'workflow-messages'
    | 'workflow-system-new'
    | 'workflow-messages-new'
    | 'input'
    | 'javascript'
    | 'javascript-new'
    | null
>(null);
const saveMessage = ref<string | null>(null);

// Track raw vs formatted mode for expanded views
const rawModeSystem = ref(false);
const rawModeMessages = ref(false);
const rawModeSystemNew = ref(false);
const rawModeMessagesNew = ref(false);
const rawModeWorkflowSystem = ref(false);
const rawModeWorkflowMessages = ref(false);
const rawModeWorkflowSystemNew = ref(false);
const rawModeWorkflowMessagesNew = ref(false);

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

const reloadJavaScriptFromWorkflow = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/reload-javascript`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value =
                result.error || 'Failed to reload JavaScript from workflow';
        } else {
            javascript.value = result.code || '';
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to reload JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const reloadJavaScriptNewFromWorkflow = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/reload-javascript`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value =
                result.error || 'Failed to reload new JavaScript from workflow';
        } else {
            javascriptNew.value = result.code || '';
            // Save the new version to file
            await saveJavaScriptNewToFile();
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to reload new JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const loadJavaScriptNew = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/load-javascript-new`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value =
                result.error || 'Failed to load new JavaScript version';
        } else {
            javascriptNew.value = result.code || '';
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to load new JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const executeWorkflow = async () => {
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
            `/debug/workflow/${props.workflowNumber}/execute-workflow`,
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

        workflowOutput.value = result.output;
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

        workflowOutputNew.value = result.output;
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
        | 'system'
        | 'messages'
        | 'system-new'
        | 'messages-new'
        | 'workflow-system'
        | 'workflow-messages'
        | 'workflow-system-new'
        | 'workflow-messages-new',
    isRaw: boolean,
) => {
    if (section === 'system') {
        rawModeSystem.value = isRaw;
    } else if (section === 'messages') {
        rawModeMessages.value = isRaw;
    } else if (section === 'system-new') {
        rawModeSystemNew.value = isRaw;
    } else if (section === 'messages-new') {
        rawModeMessagesNew.value = isRaw;
    } else if (section === 'workflow-system') {
        rawModeWorkflowSystem.value = isRaw;
    } else if (section === 'workflow-messages') {
        rawModeWorkflowMessages.value = isRaw;
    } else if (section === 'workflow-system-new') {
        rawModeWorkflowSystemNew.value = isRaw;
    } else if (section === 'workflow-messages-new') {
        rawModeWorkflowMessagesNew.value = isRaw;
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

const saveJavaScriptToFile = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript`,
            'POST',
            { code: javascript.value },
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save JavaScript code';
        } else {
            saveMessage.value = 'JavaScript code saved successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to save JavaScript code: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const saveJavaScriptNewToFile = async () => {
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

const preparePrompt = async () => {
    if (!javascript.value || !inputJson.value) {
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
        await saveJavaScriptToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/execute`,
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

        output.value = result.output;
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
        await saveJavaScriptNewToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/execute-new`,
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

        outputNew.value = result.output;
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isExecutingNew.value = false;
    }
};

// Auto-execute both versions when page loads if they have code
onMounted(async () => {
    // Execute old version if available
    if (javascript.value && input.value) {
        await preparePrompt();
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
                <ButtonSecondary @click="expandedView = 'javascript'">
                    View JavaScript
                </ButtonSecondary>

                <ButtonPrimary
                    :disabled="!javascript || !input"
                    @click="preparePrompt"
                >
                    {{ isExecuting ? 'Executing...' : 'Prepare Prompt' }}
                </ButtonPrimary>

                <ButtonSuccess @click="executeWorkflow">
                    Upload to n8n & Execute workflow
                </ButtonSuccess>

                <ButtonSecondary
                    @click="
                        loadJavaScriptNew();
                        expandedView = 'javascript-new';
                    "
                >
                    View JavaScript
                </ButtonSecondary>

                <ButtonPrimary
                    :disabled="!javascriptNew || !input"
                    @click="preparePromptNew"
                >
                    {{ isExecutingNew ? 'Executing...' : 'Prepare Prompt' }}
                </ButtonPrimary>

                <ButtonSuccess @click="executeWorkflowNew">
                    Upload to n8n & Execute workflow
                </ButtonSuccess>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <OutputPanel
                    title="Workflow Prompt (OLD)"
                    :output="output"
                    @expand-system="expandedView = 'system'"
                    @expand-messages="expandedView = 'messages'"
                    @update-raw-mode="handleRawModeUpdate"
                />
                <OutputPanel
                    title="Workflow Prompt (NEW)"
                    :output="outputNew"
                    @expand-system="expandedView = 'system-new'"
                    @expand-messages="expandedView = 'messages-new'"
                    @update-raw-mode="handleRawModeUpdate"
                />
            </div>

            <!-- Workflow Output Sections -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <OutputPanel
                    title="Output (old)"
                    :output="workflowOutput"
                    @expand-system="expandedView = 'workflow-system'"
                    @expand-messages="expandedView = 'workflow-messages'"
                    @update-raw-mode="handleRawModeUpdate"
                />
                <OutputPanel
                    title="Output (new)"
                    :output="workflowOutputNew"
                    @expand-system="expandedView = 'workflow-system-new'"
                    @expand-messages="expandedView = 'workflow-messages-new'"
                    @update-raw-mode="handleRawModeUpdate"
                />
            </div>
        </div>

        <!-- Modal: Expanded System Prompt -->
        <ExpandableModal
            :show="expandedView === 'system'"
            title="System Prompt (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeSystem"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ output?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(output?.system)"
                    />
                </div>
                <div
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            output?.system
                                ? `${(output.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(output?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Messages -->
        <ExpandableModal
            :show="expandedView === 'messages'"
            title="Messages (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(output?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in output?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="message.content && !rawModeMessages"
                                class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                v-html="renderMarkdown(message.content)"
                            />
                            <div
                                v-else-if="message.content && rawModeMessages"
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
                            output?.messages && Array.isArray(output.messages)
                                ? `${output.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(getMessagesAsText(output?.messages))
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
            :show="expandedView === 'javascript'"
            title="JavaScript Code (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <textarea
                    v-model="javascript"
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
                            javascript
                                ? `${javascript.length} characters`
                                : 'N/A'
                        }}
                    </span>

                    <div>
                        <ButtonSecondary
                            class="mr-2"
                            @click="reloadJavaScriptFromWorkflow"
                        >
                            Reload JS from JSON
                        </ButtonSecondary>

                        <ButtonPrimary
                            @click="
                                saveJavaScriptToFile();
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
                            @click="reloadJavaScriptNewFromWorkflow"
                        >
                            Reload JS from JSON
                        </ButtonSecondary>

                        <ButtonPrimary
                            @click="
                                saveJavaScriptNewToFile();
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

        <!-- Modal: Expanded Messages (new) -->
        <ExpandableModal
            :show="expandedView === 'messages-new'"
            title="Messages (new) - Expanded"
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

        <!-- Modal: Expanded Workflow System Prompt (old) -->
        <ExpandableModal
            :show="expandedView === 'workflow-system'"
            title="Workflow System Prompt (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="rawModeWorkflowSystem"
                        class="font-mono text-sm break-words whitespace-pre-wrap text-indigo-700"
                    >
                        {{ workflowOutput?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(workflowOutput?.system)"
                    />
                </div>
                <div
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            workflowOutput?.system
                                ? `${(workflowOutput.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(workflowOutput?.system)"
                    >
                        📋 Copy
                    </button>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Workflow Messages (old) -->
        <ExpandableModal
            :show="expandedView === 'workflow-messages'"
            title="Workflow Messages (old) - Expanded"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(workflowOutput?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in workflowOutput?.messages"
                            :key="index"
                            class="rounded border border-indigo-200 bg-indigo-50 p-4"
                        >
                            <div
                                v-if="
                                    message.content && !rawModeWorkflowMessages
                                "
                                class="prose prose-sm dark:prose-invert max-w-none text-indigo-700"
                                v-html="renderMarkdown(message.content)"
                            />
                            <div
                                v-else-if="
                                    message.content && rawModeWorkflowMessages
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
                            workflowOutput?.messages &&
                            Array.isArray(workflowOutput.messages)
                                ? `${workflowOutput.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(workflowOutput?.messages),
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
                        {{ workflowOutputNew?.system }}
                    </div>
                    <div
                        v-else
                        class="prose prose-sm dark:prose-invert max-w-none"
                        v-html="renderMarkdown(workflowOutputNew?.system)"
                    />
                </div>
                <div
                    class="flex flex-shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            workflowOutputNew?.system
                                ? `${(workflowOutputNew.system as string).length} characters`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="copyToClipboard(workflowOutputNew?.system)"
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
                        v-if="Array.isArray(workflowOutputNew?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(
                                message, index
                            ) in workflowOutputNew?.messages"
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
                            workflowOutputNew?.messages &&
                            Array.isArray(workflowOutputNew.messages)
                                ? `${workflowOutputNew.messages.length} messages`
                                : 'N/A'
                        }}
                    </span>
                    <button
                        class="rounded bg-indigo-600 px-3 py-2 text-xs text-white hover:bg-indigo-700"
                        title="Copy to clipboard"
                        @click="
                            copyToClipboard(
                                getMessagesAsText(workflowOutputNew?.messages),
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
