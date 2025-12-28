<script setup lang="ts">
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import ButtonSuccess from '@/Components/Base/Button/ButtonSuccess.vue';
import ExpandableModal from '@/Components/Features/Workflow/ExpandableModal.vue';
import NotificationToast from '@/Components/Features/Workflow/NotificationToast.vue';
import OutputPanel from '@/Components/Features/Workflow/OutputPanel.vue';
import PageHeader from '@/Components/Features/Workflow/PageHeader.vue';
import VariantSelector from '@/Components/Features/Workflow/VariantSelector.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, onMounted, ref } from 'vue';

interface Variant {
    key: string;
    name: string;
    description?: string;
}

interface PreparePromptNode {
    name: string;
    javascriptOld: string | null;
    javascriptNew: string | null;
    promptOld: object | null;
    promptNew: object | null;
}

interface Props {
    workflowNumber: number;
    currentVariant?: string;
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
    availableVariants: () => [],
    preparePromptNodes: () => [],
    javascriptOld: null,
    javascriptNew: null,
    promptOld: null,
    promptNew: null,
    outputOld: null,
    outputNew: null,
});

const { confirm, success, error: showError } = useAlert();

defineOptions({
    layout: WorkflowLayout,
});

const isPreparingOld = ref(false);
const isPreparingNew = ref(false);
const isExecutingOld = ref(false);
const isExecutingNew = ref(false);
const isUploadingOld = ref(false);
const isUploadingNew = ref(false);
const isUploadingToLive = ref(false);
const error = ref<string | null>(null);

const input = ref(props.input);
const inputJson = ref(JSON.stringify(props.input, null, 2));
const javascriptOld = ref(props.javascriptOld || '');
const javascriptNew = ref(props.javascriptNew || '');
const promptOld = ref(props.promptOld);
const promptNew = ref(props.promptNew);
const outputOld = ref(props.outputOld);
const outputNew = ref(props.outputNew);
const preparePromptNodes = ref(props.preparePromptNodes || []);

// Track which pass is selected when multiple passes exist (0-based index)
const selectedPass = ref(0);

// Get the current node being displayed based on selected pass
const currentNode = computed(() => {
    return preparePromptNodes.value[selectedPass.value] || null;
});

// Modal state for maximized views
// Uses dynamic values like 'javascript-old-0', 'javascript-old-1', etc. for multiple nodes
const expandedView = ref<string | null>(null);
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

const executeWorkflowOld = async (nodeName: string = 'Prepare Prompt') => {
    if (!inputJson.value) {
        error.value = 'Input data is required';
        return;
    }

    isExecutingOld.value = true;
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
            { input: inputData, variant: props.currentVariant, nodeName },
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
        isExecutingOld.value = false;
    }
};

const executeWorkflowNew = async (nodeName: string = 'Prepare Prompt') => {
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
            { input: inputData, variant: props.currentVariant, nodeName },
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

const uploadWorkflowOld = async (nodeName: string = 'Prepare Prompt') => {
    isUploadingOld.value = true;
    error.value = null;

    try {
        // First, save the old JavaScript to ensure it's up to date
        await saveOldJavaScriptToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/upload-to-n8n-old`,
            'POST',
            { variant: props.currentVariant, nodeName },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Failed to upload workflow to n8n';
            return;
        }

        error.value = null;
    } catch (err) {
        error.value = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isUploadingOld.value = false;
    }
};

const uploadWorkflowNew = async (nodeName: string = 'Prepare Prompt') => {
    isUploadingNew.value = true;
    error.value = null;

    try {
        // First, save the new JavaScript to ensure it's up to date
        await saveNewJavaScriptToFile();

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/upload-to-n8n-new`,
            'POST',
            { variant: props.currentVariant, nodeName },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value = result.error || 'Failed to upload workflow to n8n';
            return;
        }

        error.value = null;
    } catch (err) {
        error.value = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isUploadingNew.value = false;
    }
};

const uploadWorkflowToLive = async () => {
    // Show confirmation dialog
    const confirmed = await confirm(
        'This will upload the current workflow_0.json file to the LIVE production n8n server at https://n8n.bettrprompt.ai/. This action cannot be undone. Are you sure you want to proceed?',
        'Upload to Live Server?',
        {
            confirmText: 'Upload to Live',
            cancelText: 'Cancel',
            confirmButtonStyle: 'danger',
        },
    );

    if (!confirmed) {
        return;
    }

    isUploadingToLive.value = true;
    error.value = null;

    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/upload-to-live`,
            'POST',
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error.value =
                result.error || 'Failed to upload workflow to live server';
            await showError(
                result.error || 'Failed to upload workflow to live server',
                'Upload Failed',
            );
            return;
        }

        await success(
            'Workflow successfully uploaded to live production server!',
            'Upload Successful',
        );
        error.value = null;
    } catch (err) {
        const errorMessage = `Upload error: ${err instanceof Error ? err.message : 'Unknown error'}`;
        error.value = errorMessage;
        await showError(errorMessage, 'Upload Failed');
    } finally {
        isUploadingToLive.value = false;
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
        // Use the current node's JavaScript if in multi-node mode, otherwise use the global ref
        const code = currentNode.value?.javascriptOld ?? javascriptOld.value;
        const nodeName = currentNode.value?.name || 'Prepare Prompt';

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript-old`,
            'POST',
            { code, variant: props.currentVariant, nodeName },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

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
        // Use the current node's JavaScript if in multi-node mode, otherwise use the global ref
        const code = currentNode.value?.javascriptNew ?? javascriptNew.value;
        const nodeName = currentNode.value?.name || 'Prepare Prompt';

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript-new`,
            'POST',
            { code, variant: props.currentVariant, nodeName },
        );

        if (!response.ok) {
            error.value = `Server error: ${response.status} ${response.statusText}`;
            return;
        }

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

const preparePromptOld = async (nodeName: string = 'Prepare Prompt') => {
    const node = preparePromptNodes.value.find((n) => n.name === nodeName);
    if (!node?.javascriptOld || !inputJson.value) {
        error.value = 'Both input and JavaScript code are required';
        return;
    }

    isPreparingOld.value = true;
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

        // If input is an array (n8n format), extract the first element
        if (Array.isArray(inputData) && inputData.length > 0) {
            inputData = inputData[0];
        }

        // For Pass 2+, require the previous pass to have been run
        const currentPassIndex = preparePromptNodes.value.findIndex(
            (n) => n.name === nodeName,
        );
        if (currentPassIndex > 0) {
            const previousNode = preparePromptNodes.value[currentPassIndex - 1];
            if (!previousNode?.promptOld) {
                error.value = `Pass ${currentPassIndex + 1} requires Pass ${currentPassIndex} to be run first. Please run "Prepare Prompt ${currentPassIndex === 1 ? '' : currentPassIndex}" and generate its output first.`;
                return;
            }

            // Merge the classification and selected_questions from the previous pass output
            // This simulates the "Filter Questions by Category" node output in the actual workflow
            inputData.classification = previousNode.promptOld.classification;
            inputData.selected_questions =
                previousNode.promptOld.selected_questions;
        }

        // Save the old JavaScript to file first (for backwards compatibility, also save primary node)
        if (nodeName === 'Prepare Prompt') {
            await saveOldJavaScriptToFile();
        }

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/prepare-prompt-old`,
            'POST',
            { input: inputData, variant: props.currentVariant, nodeName },
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

        // Update the node's prompt data
        // Handle both array format (from workflow) and object format
        let promptData = result.prompt;
        if (
            Array.isArray(promptData) &&
            promptData.length > 0 &&
            promptData[0] &&
            promptData[0].json
        ) {
            promptData = promptData[0].json;
        }
        node.promptOld = promptData;
        // Also update the main promptOld if this is the primary node
        if (nodeName === 'Prepare Prompt') {
            promptOld.value = promptData;
        }
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isPreparingOld.value = false;
    }
};

const preparePromptNew = async (nodeName: string = 'Prepare Prompt') => {
    const node = preparePromptNodes.value.find((n) => n.name === nodeName);
    if (!node?.javascriptNew || !inputJson.value) {
        error.value = 'Both input and JavaScript code are required';
        return;
    }

    isPreparingNew.value = true;
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

        // If input is an array (n8n format), extract the first element
        if (Array.isArray(inputData) && inputData.length > 0) {
            inputData = inputData[0];
        }

        // For Pass 2+, require the previous pass to have been run
        const currentPassIndex = preparePromptNodes.value.findIndex(
            (n) => n.name === nodeName,
        );
        if (currentPassIndex > 0) {
            const previousNode = preparePromptNodes.value[currentPassIndex - 1];
            if (!previousNode?.promptNew) {
                error.value = `Pass ${currentPassIndex + 1} requires Pass ${currentPassIndex} to be run first. Please run "Prepare Prompt ${currentPassIndex === 1 ? '' : currentPassIndex}" and generate its output first.`;
                return;
            }

            // Merge the classification and selected_questions from the previous pass output
            // This simulates the "Filter Questions by Category" node output in the actual workflow
            inputData.classification = previousNode.promptNew.classification;
            inputData.selected_questions =
                previousNode.promptNew.selected_questions;
        }

        // Save the new JavaScript to file first (for backwards compatibility, also save primary node)
        if (nodeName === 'Prepare Prompt') {
            await saveNewJavaScriptToFile();
        }

        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/prepare-prompt-new`,
            'POST',
            { input: inputData, variant: props.currentVariant, nodeName },
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

        // Update the node's prompt data
        // Handle both array format (from workflow) and object format
        let promptData = result.prompt;
        if (
            Array.isArray(promptData) &&
            promptData.length > 0 &&
            promptData[0] &&
            promptData[0].json
        ) {
            promptData = promptData[0].json;
        }
        node.promptNew = promptData;
        // Also update the main promptNew if this is the primary node
        if (nodeName === 'Prepare Prompt') {
            promptNew.value = promptData;
        }
    } catch (err) {
        error.value = `Execution error: ${err instanceof Error ? err.message : 'Unknown error'}`;
    } finally {
        isPreparingNew.value = false;
    }
};

// Auto-execute both versions when page loads if they have code
onMounted(async () => {
    // Execute the current node (old and new versions)
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
        <div class="mb-6 flex items-center justify-between">
            <PageHeader
                :title="`Workflow ${workflowNumber}`"
                subtitle="Inspect workflow input, JavaScript code, and output"
            />

            <div class="flex items-center gap-4">
                <VariantSelector
                    v-if="availableVariants.length > 1"
                    :workflow-number="workflowNumber"
                    :current-variant="currentVariant"
                    :available-variants="availableVariants"
                />

                <!-- Pass selector (only show if multiple passes exist) -->
                <div
                    v-if="preparePromptNodes.length > 1"
                    class="items-centre flex gap-3"
                >
                    <label
                        for="pass-selector"
                        class="text-sm font-medium text-slate-700"
                    >
                        Pass:
                    </label>
                    <select
                        id="pass-selector"
                        v-model.number="selectedPass"
                        class="rounded-md border border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option
                            v-for="(node, index) in preparePromptNodes"
                            :key="index"
                            :value="index"
                        >
                            Pass {{ index + 1 }}
                        </option>
                    </select>
                </div>

                <ButtonPrimary @click="expandedView = 'input'">
                    Input Data
                </ButtonPrimary>

                <ButtonDanger
                    :disabled="isUploadingToLive"
                    @click="uploadWorkflowToLive"
                >
                    {{
                        isUploadingToLive
                            ? 'Uploading...'
                            : 'Upload to n8n (live)'
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
                    <h3 class="text-lg font-semibold text-slate-800">
                        Prepare Prompt{{
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
                        View JavaScript
                    </ButtonSecondary>

                    <ButtonPrimary
                        :disabled="!currentNode.javascriptOld || !input"
                        @click="preparePromptOld(currentNode.name)"
                    >
                        {{ isPreparingOld ? 'Preparing...' : 'Prepare Prompt' }}
                    </ButtonPrimary>

                    <ButtonDanger
                        :disabled="!currentNode.javascriptOld || !input"
                        @click="uploadWorkflowOld(currentNode.name)"
                    >
                        {{ isUploadingOld ? 'Uploading...' : 'Upload to n8n' }}
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
                        View JavaScript
                    </ButtonSecondary>

                    <ButtonPrimary
                        :disabled="!currentNode.javascriptNew || !input"
                        @click="preparePromptNew(currentNode.name)"
                    >
                        {{ isPreparingNew ? 'Preparing...' : 'Prepare Prompt' }}
                    </ButtonPrimary>

                    <ButtonDanger
                        :disabled="!currentNode.javascriptNew || !input"
                        @click="uploadWorkflowNew(currentNode.name)"
                    >
                        {{ isUploadingNew ? 'Uploading...' : 'Upload to n8n' }}
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
            <div class="border-t-2 border-slate-300 pt-8">
                <h3 class="mb-4 text-lg font-semibold text-slate-800">
                    Workflow Output
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
