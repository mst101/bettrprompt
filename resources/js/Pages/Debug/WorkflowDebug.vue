<script setup lang="ts">
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { ref } from 'vue';

interface Props {
    workflowNumber: number;
    input?: object | null;
    javascript?: string | null;
    output?: object | null;
}

const props = withDefaults(defineProps<Props>(), {
    input: null,
    javascript: null,
    output: null,
});

const isExecuting = ref(false);
const error = ref<string | null>(null);

const input = ref(props.input);
const javascript = ref(props.javascript || '');
const output = ref(props.output);

// Modal state for maximized views
const expandedView = ref<'system' | 'messages' | null>(null);

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

const saveJavaScriptToServer = async () => {
    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/javascript`,
            'POST',
            { code: javascript.value },
        );

        const result = await response.json();
        if (!result.success) {
            error.value = result.error || 'Failed to save JavaScript';
        } else {
            error.value = null;
        }
    } catch (err) {
        error.value = `Failed to save JavaScript: ${err instanceof Error ? err.message : 'Unknown error'}`;
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
            alert('JavaScript code successfully saved to n8n workflow!');
        }
    } catch (err) {
        error.value = `Failed to save to n8n workflow: ${err instanceof Error ? err.message : 'Unknown error'}`;
    }
};

const renderMarkdown = (text: string | null | undefined): string => {
    if (!text) return '';
    const html = marked(text) as string;
    return DOMPurify.sanitize(html);
};

const executeJavaScript = async () => {
    if (!javascript.value || !input.value) {
        error.value = 'Both input and JavaScript code are required';
        return;
    }

    isExecuting.value = true;
    error.value = null;

    try {
        const response = await makeRequest(
            `/debug/workflow/${props.workflowNumber}/execute`,
            'POST',
        );

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
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 p-8">
        <div class="mx-auto max-w-7xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="mb-2 text-4xl font-bold text-slate-900">
                    Workflow {{ workflowNumber }} Debug
                </h1>
                <p class="text-slate-600">
                    Inspect workflow input, JavaScript code, and output
                </p>
            </div>

            <!-- Controls -->
            <div class="mb-8 rounded-lg bg-white p-6 shadow-md">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <button
                        :disabled="!javascript || !input"
                        class="rounded-lg bg-purple-600 px-4 py-2 text-white transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="executeJavaScript"
                    >
                        {{ isExecuting ? 'Executing...' : 'Execute' }}
                    </button>
                    <button
                        class="rounded-lg bg-green-600 px-4 py-2 text-white transition hover:bg-green-700"
                        @click="saveJavaScriptToServer"
                    >
                        Save JavaScript
                    </button>
                    <button
                        class="rounded-lg bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700"
                        @click="saveJavaScriptToN8nWorkflow"
                    >
                        Save to n8n Workflow
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div
                v-if="error"
                class="mb-8 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
            >
                {{ error }}
            </div>

            <!-- Three-Column Layout -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                <!-- Input Column -->
                <div
                    class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md"
                >
                    <div class="bg-blue-600 px-6 py-4 font-semibold text-white">
                        Input Data
                    </div>
                    <div class="flex-1 overflow-auto p-6">
                        <pre
                            v-if="input"
                            class="text-xs break-words whitespace-pre-wrap text-slate-700"
                            >{{ JSON.stringify(input, null, 2) }}</pre
                        >
                        <p v-else class="text-slate-500 italic">
                            No input loaded
                        </p>
                    </div>
                    <div
                        class="border-t bg-slate-50 px-6 py-2 text-xs text-slate-600"
                    >
                        {{
                            input
                                ? `${JSON.stringify(input).length} bytes`
                                : 'N/A'
                        }}
                    </div>
                </div>

                <!-- JavaScript Column -->
                <div
                    class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md"
                >
                    <div
                        class="bg-green-600 px-6 py-4 font-semibold text-white"
                    >
                        JavaScript Code
                    </div>
                    <textarea
                        v-model="javascript"
                        class="flex-1 resize-none border-0 p-6 font-mono text-xs focus:outline-none"
                        placeholder="Edit JavaScript code here..."
                    ></textarea>
                    <div
                        class="border-t bg-slate-50 px-6 py-2 text-xs text-slate-600"
                    >
                        {{
                            javascript
                                ? `${javascript.length} characters`
                                : 'N/A'
                        }}
                    </div>
                </div>

                <!-- Output Column -->
                <div
                    class="flex flex-col overflow-hidden rounded-lg bg-white shadow-md"
                >
                    <div
                        class="bg-purple-600 px-6 py-4 font-semibold text-white"
                    >
                        Output
                    </div>
                    <div class="flex-1 overflow-auto p-6">
                        <div v-if="output">
                            <!-- System Prompt -->
                            <div v-if="output.system" class="mb-6">
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <h3 class="font-semibold text-slate-900">
                                        System Prompt
                                    </h3>
                                    <button
                                        class="rounded bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-200"
                                        title="Expand to full screen"
                                        @click="expandedView = 'system'"
                                    >
                                        ⛶ Expand
                                    </button>
                                </div>
                                <div
                                    class="prose prose-sm max-h-60 overflow-auto rounded border border-slate-200 bg-slate-50 p-3 text-slate-700"
                                    v-html="renderMarkdown(output.system)"
                                />
                            </div>

                            <!-- Messages -->
                            <div v-if="output.messages">
                                <div
                                    class="mb-2 flex items-center justify-between"
                                >
                                    <h3 class="font-semibold text-slate-900">
                                        Messages
                                    </h3>
                                    <button
                                        class="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 hover:bg-green-200"
                                        title="Expand to full screen"
                                        @click="expandedView = 'messages'"
                                    >
                                        ⛶ Expand
                                    </button>
                                </div>
                                <div
                                    v-if="Array.isArray(output.messages)"
                                    class="space-y-2"
                                >
                                    <div
                                        v-for="(
                                            message, index
                                        ) in output.messages"
                                        :key="index"
                                        class="rounded border border-slate-200 bg-slate-50 p-3"
                                    >
                                        <div v-if="typeof message === 'object'">
                                            <p
                                                class="mb-1 font-mono text-xs text-slate-600"
                                            >
                                                Role:
                                                {{ message.role || 'N/A' }}
                                            </p>
                                            <div
                                                v-if="message.content"
                                                class="prose prose-sm text-slate-700"
                                                v-html="
                                                    renderMarkdown(
                                                        message.content,
                                                    )
                                                "
                                            />
                                            <div
                                                v-else
                                                class="text-xs text-slate-700"
                                            >
                                                {{
                                                    JSON.stringify(
                                                        message,
                                                        null,
                                                        2,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                        <p
                                            v-else
                                            class="text-xs text-slate-700"
                                        >
                                            {{ message }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700"
                                >
                                    {{
                                        JSON.stringify(output.messages, null, 2)
                                    }}
                                </div>
                            </div>

                            <!-- Full Output (if there's more) -->
                            <details class="mt-6 cursor-pointer">
                                <summary class="font-semibold text-slate-900">
                                    Full Output
                                </summary>
                                <div
                                    class="mt-2 max-h-40 overflow-auto rounded border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700"
                                >
                                    {{ JSON.stringify(output, null, 2) }}
                                </div>
                            </details>
                        </div>
                        <p v-else class="text-slate-500 italic">
                            No output yet. Execute the JavaScript to see
                            results.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Expanded System Prompt -->
        <div
            v-if="expandedView === 'system'"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
            @click="expandedView = null"
        >
            <div
                class="flex max-h-screen w-full max-w-screen flex-col rounded-lg bg-white shadow-lg"
                @click.stop
            >
                <div
                    class="flex items-center justify-between border-b bg-blue-600 px-6 py-4 text-white"
                >
                    <h2 class="text-lg font-semibold">
                        System Prompt (Expanded)
                    </h2>
                    <button
                        class="rounded hover:bg-blue-700"
                        title="Close"
                        @click="expandedView = null"
                    >
                        ✕
                    </button>
                </div>
                <div
                    class="prose prose-sm flex-1 overflow-auto p-6"
                    v-html="renderMarkdown(output?.system)"
                />
            </div>
        </div>

        <!-- Modal: Expanded Messages -->
        <div
            v-if="expandedView === 'messages'"
            class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-black p-4"
            @click="expandedView = null"
        >
            <div
                class="flex max-h-screen w-full max-w-4xl flex-col rounded-lg bg-white shadow-lg"
                @click.stop
            >
                <div
                    class="flex items-center justify-between border-b bg-green-600 px-6 py-4 text-white"
                >
                    <h2 class="text-lg font-semibold">Messages (Expanded)</h2>
                    <button
                        class="rounded hover:bg-green-700"
                        title="Close"
                        @click="expandedView = null"
                    >
                        ✕
                    </button>
                </div>
                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="Array.isArray(output?.messages)"
                        class="space-y-4"
                    >
                        <div
                            v-for="(message, index) in output?.messages"
                            :key="index"
                            class="rounded border border-slate-200 bg-slate-50 p-4"
                        >
                            <p class="mb-2 font-mono text-sm text-slate-600">
                                Role: {{ message.role || 'N/A' }}
                            </p>
                            <div
                                v-if="message.content"
                                class="prose prose-sm text-slate-700"
                                v-html="renderMarkdown(message.content)"
                            />
                            <div v-else class="text-sm text-slate-700">
                                {{ JSON.stringify(message, null, 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
