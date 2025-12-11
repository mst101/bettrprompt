<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import { usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';

const props = defineProps<Props>();

defineOptions({
    layout: WorkflowLayout,
});

interface Document {
    type: 'core' | 'framework';
    filename: string;
    displayName: string;
    size: number;
    lastModified: number;
    usedIn: string[];
}

interface Props {
    documents: {
        core: Document[];
        framework: Document[];
    };
}

const selectedDocument = ref<Document | null>(null);
const content = ref('');
const isSaving = ref(false);
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null);

// Rendered markdown preview
const renderedContent = computed(() => {
    if (!content.value) return '';
    const html = marked(content.value, { breaks: true });
    return DOMPurify.sanitize(html as string);
});

// Watch for changes to selectedDocument and load its content
watch(
    selectedDocument,
    async (doc) => {
        if (!doc) {
            content.value = '';
            return;
        }

        await loadDocumentContent(doc);
    },
    { immediate: false },
);

/**
 * Load document content from the API
 */
async function loadDocumentContent(doc: Document) {
    try {
        const response = await fetch(
            `/workflow/docs/api/${doc.type}/${encodeURIComponent(doc.filename)}`,
        );

        if (!response.ok) {
            throw new Error(`Failed to load document: ${response.statusText}`);
        }

        const data = await response.json();
        if (data.success) {
            content.value = data.content;
            message.value = null;
        } else {
            throw new Error(data.error || 'Failed to load document');
        }
    } catch (err) {
        message.value = {
            type: 'error',
            text: `Error loading document: ${err instanceof Error ? err.message : 'Unknown error'}`,
        };
    }
}

/**
 * Get CSRF token from multiple sources
 */
function getCsrfToken(): string {
    const page = usePage();

    // Try Inertia props first
    if (page.props.csrf_token) {
        return page.props.csrf_token as string;
    }

    // Fallback: try meta tag
    const metaToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');
    if (metaToken) {
        return metaToken;
    }

    // Final fallback: try cookie
    const name = 'XSRF-TOKEN';
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() || '';
    }

    return '';
}

/**
 * Save document changes
 */
async function saveDocument() {
    if (!selectedDocument.value) return;

    isSaving.value = true;
    try {
        const csrfToken = getCsrfToken();

        const response = await fetch(
            `/workflow/docs/api/${selectedDocument.value.type}/${encodeURIComponent(selectedDocument.value.filename)}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    content: content.value,
                }),
            },
        );

        if (!response.ok) {
            const responseText = await response.text();
            console.error('Response status:', response.status);
            console.error('Response body:', responseText);
            throw new Error(
                `Server error: ${response.status} ${response.statusText}`,
            );
        }

        const data = await response.json();

        if (data.success) {
            message.value = {
                type: 'success',
                text: `✓ ${selectedDocument.value.filename} saved successfully and embedded into workflows`,
            };
            setTimeout(() => {
                message.value = null;
            }, 5000);
        } else {
            throw new Error(data.error || 'Failed to save document');
        }
    } catch (err) {
        message.value = {
            type: 'error',
            text: `Error saving document: ${err instanceof Error ? err.message : 'Unknown error'}`,
        };
    } finally {
        isSaving.value = false;
    }
}

/**
 * Select first document on mount
 */
function selectFirstDocument() {
    if (props.documents.core.length > 0) {
        selectedDocument.value = props.documents.core[0];
    } else if (props.documents.framework.length > 0) {
        selectedDocument.value = props.documents.framework[0];
    }
}

watch(
    () => props.documents,
    () => {
        selectFirstDocument();
    },
    { immediate: true },
);
</script>

<template>
    <div>
        <h1 class="mb-8 text-3xl font-bold text-slate-900 dark:text-white">
            Reference Documents
        </h1>

        <!-- Status Message -->
        <div
            v-if="message"
            class="mb-6 rounded-lg border p-4 transition"
            :class="{
                'border-green-200 bg-green-50 text-green-800 dark:border-green-700 dark:bg-green-900/20 dark:text-green-200':
                    message.type === 'success',
                'border-red-200 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-900/20 dark:text-red-200':
                    message.type === 'error',
            }"
        >
            {{ message.text }}
        </div>

        <!-- Three-Column Layout -->
        <div class="grid gap-6 lg:grid-cols-4">
            <!-- Sidebar: Document List -->
            <div class="lg:col-span-1">
                <div class="sticky top-4 space-y-4">
                    <!-- Core Documents Section -->
                    <div>
                        <h2
                            class="mb-3 text-sm font-semibold text-slate-500 uppercase dark:text-slate-400"
                        >
                            Core Documents
                        </h2>
                        <div class="space-y-2">
                            <button
                                v-for="doc in documents.core"
                                :key="`${doc.type}-${doc.filename}`"
                                class="w-full rounded-lg border px-4 py-2 text-left text-sm transition"
                                :class="{
                                    'border-blue-300 bg-blue-50 font-medium text-blue-900 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200':
                                        selectedDocument?.filename ===
                                        doc.filename,
                                    'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700':
                                        selectedDocument?.filename !==
                                        doc.filename,
                                }"
                                @click="selectedDocument = doc"
                            >
                                <div class="truncate font-medium">
                                    {{ doc.displayName }}
                                </div>
                                <div
                                    class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    Used in:
                                    {{ doc.usedIn.join(', ') || 'None' }}
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Framework Templates Section -->
                    <div>
                        <h2
                            class="mb-3 text-sm font-semibold text-slate-500 uppercase dark:text-slate-400"
                        >
                            Framework Templates ({{
                                documents.framework.length
                            }})
                        </h2>
                        <div class="max-h-96 space-y-2 overflow-y-auto pr-2">
                            <button
                                v-for="doc in documents.framework"
                                :key="`${doc.type}-${doc.filename}`"
                                class="w-full rounded-lg border px-3 py-2 text-left text-xs transition"
                                :class="{
                                    'border-blue-300 bg-blue-50 font-medium text-blue-900 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200':
                                        selectedDocument?.filename ===
                                        doc.filename,
                                    'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700':
                                        selectedDocument?.filename !==
                                        doc.filename,
                                }"
                                @click="selectedDocument = doc"
                            >
                                <div class="truncate font-medium">
                                    {{ doc.displayName }}
                                </div>
                                <div class="text-slate-500 dark:text-slate-500">
                                    workflow_2
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editor and Preview -->
            <div class="lg:col-span-3">
                <!-- Document Header -->
                <div
                    v-if="selectedDocument"
                    class="mb-6 rounded-lg border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2
                                class="text-2xl font-bold text-slate-900 dark:text-white"
                            >
                                {{ selectedDocument.displayName }}
                            </h2>
                            <p
                                class="mt-1 text-sm text-slate-600 dark:text-slate-400"
                            >
                                <span v-if="selectedDocument.usedIn.length > 0">
                                    Used in:
                                    <strong>{{
                                        selectedDocument.usedIn.join(', ')
                                    }}</strong>
                                </span>
                                <span
                                    v-else
                                    class="text-slate-500 dark:text-slate-500"
                                >
                                    Not used in any workflows
                                </span>
                            </p>
                        </div>
                        <ButtonPrimary
                            :disabled="isSaving"
                            :loading="isSaving"
                            @click="saveDocument"
                        >
                            {{ isSaving ? 'Saving...' : 'Save & Embed' }}
                        </ButtonPrimary>
                    </div>
                </div>

                <!-- Editor and Preview Panes -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Editor Pane -->
                    <div
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
                    >
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <h3
                                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >
                                Markdown Editor
                            </h3>
                        </div>
                        <textarea
                            v-model="content"
                            class="h-96 w-full resize-none border-0 bg-white p-4 font-mono text-sm dark:bg-slate-900 dark:text-slate-100"
                            placeholder="Edit markdown here..."
                        />
                    </div>

                    <!-- Preview Pane -->
                    <div
                        class="overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700"
                    >
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-4 py-2 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <h3
                                class="text-sm font-semibold text-slate-700 dark:text-slate-300"
                            >
                                Preview
                            </h3>
                        </div>
                        <div
                            class="prose prose-sm dark:prose-invert h-96 w-full overflow-y-auto bg-white p-4 dark:bg-slate-900"
                            v-html="renderedContent"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div
            class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-800/50"
        >
            <h3
                class="mb-3 text-lg font-semibold text-slate-900 dark:text-white"
            >
                About Reference Documents
            </h3>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Core Documents (3):</strong> Framework
                        Taxonomy, Personality Calibration, and Question Bank
                        used by workflows</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Framework Templates (64):</strong> Individual
                        prompting frameworks that can be selected during prompt
                        optimisation</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Save & Embed:</strong> Clicking "Save & Embed"
                        updates both the markdown file and embeds the content
                        into the relevant n8n workflow JSON files</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Live Preview:</strong> The right pane shows a
                        rendered markdown preview as you type</span
                    >
                </li>
            </ul>
        </div>
    </div>
</template>
