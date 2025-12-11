<script setup lang="ts">
import ButtonDanger from '@/Components/ButtonDanger.vue';
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import CodeEditor from '@/Components/Workflow/CodeEditor.vue';
import ExpandableModal from '@/Components/Workflow/ExpandableModal.vue';
import NotificationToast from '@/Components/Workflow/NotificationToast.vue';
import OutputPanel from '@/Components/Workflow/OutputPanel.vue';
import PageHeader from '@/Components/Workflow/PageHeader.vue';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { ref } from 'vue';

const props = withDefaults(defineProps<Props>(), {
    input: null,
    javascript: null,
    output: null,
});

defineOptions({
    layout: WorkflowLayout,
});

interface Props {
    workflowNumber: number;
    input?: object | null;
    javascript?: string | null;
    output?: object | null;
}

const isExecuting = ref(false);
const error = ref<string | null>(null);

const input = ref(props.input);
const inputJson = ref(JSON.stringify(props.input, null, 2));
const javascript = ref(props.javascript || '');
const output = ref(props.output);

// Modal state for maximized views
const expandedView = ref<'system' | 'messages' | 'input' | 'javascript' | null>(
    null,
);
const saveMessage = ref<string | null>(null);

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

const saveJavaScriptToN8nWorkflow = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/save-to-n8n`,
            'POST',
            { code: javascript.value },
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save to n8n workflow';
        } else {
            error.value = null;
            saveMessage.value =
                'JavaScript saved to workflow file successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
        }
    } catch (err) {
        error.value = `Failed to save to n8n workflow: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const uploadWorkflowToN8n = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/upload-to-n8n`,
            'POST',
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to upload workflow to n8n';
        } else {
            error.value = null;
            saveMessage.value = 'Workflow uploaded to n8n server successfully!';
            setTimeout(() => {
                saveMessage.value = null;
            }, 3000);
        }
    } catch (err) {
        error.value = `Failed to upload workflow to n8n: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
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

const executeJavaScript = async () => {
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
</script>

<template>
    <div>
        <!-- Header -->
        <PageHeader
            :title="`Workflow ${workflowNumber}`"
            subtitle="Inspect workflow input, JavaScript code, and output"
        />

        <!-- Controls -->
        <div class="mb-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <ButtonPrimary @click="reloadJavaScriptFromWorkflow">
                    Reload JS from JSON
                </ButtonPrimary>
                <ButtonPrimary
                    :disabled="!javascript || !input"
                    @click="executeJavaScript"
                >
                    {{ isExecuting ? 'Executing...' : 'Execute' }}
                </ButtonPrimary>
                <ButtonPrimary @click="saveJavaScriptToN8nWorkflow">
                    Save to File
                </ButtonPrimary>
                <ButtonDanger @click="uploadWorkflowToN8n">
                    Upload to n8n
                </ButtonDanger>
            </div>
        </div>

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

        <!-- Three-Column Layout -->
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
            <!-- Input Column -->
            <CodeEditor
                v-model="inputJson"
                title="Input Data"
                placeholder="Enter input data as JSON..."
                @expand="expandedView = 'input'"
                @save="saveInputData"
            />

            <!-- JavaScript Column -->
            <CodeEditor
                v-model="javascript"
                title="JavaScript Code"
                placeholder="Edit JavaScript code here..."
                @expand="expandedView = 'javascript'"
                @save="saveJavaScriptToFile"
            />

            <!-- Output Column -->
            <OutputPanel
                :output="output"
                @expand-system="expandedView = 'system'"
                @expand-messages="expandedView = 'messages'"
            />
        </div>

        <!-- Modal: Expanded System Prompt -->
        <ExpandableModal
            :show="expandedView === 'system'"
            title="System Prompt (Expanded)"
            @close="expandedView = null"
        >
            <div
                class="prose prose-sm dark:prose-invert flex-1 overflow-auto p-6"
                v-html="renderMarkdown(output?.system)"
            />
        </ExpandableModal>

        <!-- Modal: Expanded Messages -->
        <ExpandableModal
            :show="expandedView === 'messages'"
            title="Messages (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex-1 overflow-auto p-6">
                <div v-if="Array.isArray(output?.messages)" class="space-y-4">
                    <div
                        v-for="(message, index) in output?.messages"
                        :key="index"
                        class="rounded border border-indigo-200 bg-indigo-50 p-4"
                    >
                        <p class="mb-2 font-mono text-sm text-indigo-600">
                            Role: {{ message.role || 'N/A' }}
                        </p>
                        <div
                            v-if="message.content"
                            class="prose prose-sm dark:prose-invert text-indigo-700"
                            v-html="renderMarkdown(message.content)"
                        />
                        <div v-else class="text-sm text-indigo-700">
                            {{ JSON.stringify(message, null, 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Input Data -->
        <ExpandableModal
            :show="expandedView === 'input'"
            title="Input Data (Expanded)"
            @close="expandedView = null"
        >
            <textarea
                v-model="inputJson"
                class="flex-1 resize-none border-0 bg-white p-6 font-mono text-sm focus:outline-none"
                placeholder="Enter input data as JSON..."
            ></textarea>
            <div
                class="flex items-center justify-between border-t bg-indigo-50 px-6 py-3"
            >
                <span class="text-xs text-indigo-600">
                    {{ inputJson ? `${inputJson.length} characters` : 'N/A' }}
                </span>
                <button
                    class="rounded bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700"
                    @click="
                        saveInputData();
                        expandedView = null;
                    "
                >
                    Save and Close
                </button>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded JavaScript Code -->
        <ExpandableModal
            :show="expandedView === 'javascript'"
            title="JavaScript Code (Expanded)"
            @close="expandedView = null"
        >
            <textarea
                v-model="javascript"
                class="flex-1 resize-none border-0 bg-white p-6 font-mono text-sm focus:outline-none"
                placeholder="Edit JavaScript code here..."
            ></textarea>
            <div
                class="flex items-center justify-between border-t bg-indigo-50 px-6 py-3"
            >
                <span class="text-xs text-indigo-600">
                    {{ javascript ? `${javascript.length} characters` : 'N/A' }}
                </span>
                <button
                    class="rounded bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700"
                    @click="
                        saveJavaScriptToFile();
                        expandedView = null;
                    "
                >
                    Save and Close
                </button>
            </div>
        </ExpandableModal>
    </div>
</template>
