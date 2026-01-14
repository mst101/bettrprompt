<script setup lang="ts">
import AlertDialog from '@/Components/Base/AlertDialog.vue';
import ButtonDanger from '@/Components/Base/Button/ButtonDanger.vue';
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import DocumentSidebar from '@/Components/Features/Workflow/DocumentSidebar.vue';
import ExpandableModal from '@/Components/Features/Workflow/ExpandableModal.vue';
import InfoSection from '@/Components/Features/Workflow/InfoSection.vue';
import PageHeader from '@/Components/Features/Workflow/PageHeader.vue';
import { useAlert } from '@/Composables/ui/useAlert';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePage } from '@inertiajs/vue3';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps<Props>();

defineOptions({
    layout: AdminLayout,
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
const isEmbeddingAll = ref(false);
const message = ref<{ type: 'success' | 'error'; text: string } | null>(null);
const expandedView = ref<'editor' | 'preview' | null>(null);

const { t } = useI18n({ useScope: 'global' });
const { confirm, success, error } = useAlert();
const { countryRoute } = useCountryRoute();

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
            countryRoute('workflows.docs.show', {
                type: doc.type,
                filename: doc.filename,
            }),
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
            countryRoute('workflows.docs.update', {
                type: selectedDocument.value.type,
                filename: selectedDocument.value.filename,
            }),
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
                text: t('workflow.docs.saveSuccessMessage', {
                    filename: selectedDocument.value.filename,
                }),
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
            text: t('workflow.docs.errors.save', {
                error: err instanceof Error ? err.message : 'Unknown error',
            }),
        };
    } finally {
        isSaving.value = false;
    }
}

/**
 * Embed all documents into workflows with confirmation
 */
async function handleEmbedAll() {
    const confirmed = await confirm(
        t('workflow.docs.confirmEmbedMessage'),
        t('workflow.docs.confirmEmbedTitle'),
        {
            confirmText: t('common.buttons.embedAll'),
            cancelText: t('common.buttons.cancel'),
            confirmButtonStyle: 'danger',
        },
    );

    if (!confirmed) return;

    isEmbeddingAll.value = true;
    try {
        const csrfToken = getCsrfToken();

        const response = await fetch(countryRoute('workflows.docs.embed-all'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

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
            await success(data.message);
            message.value = {
                type: 'success',
                text: `✓ ${data.message}`,
            };
            setTimeout(() => {
                message.value = null;
            }, 5000);
        } else {
            throw new Error(data.error || t('workflow.docs.errors.embed'));
        }
    } catch (err) {
        const errorMsg = t('workflow.docs.errors.embedFailed', {
            error: err instanceof Error ? err.message : 'Unknown error',
        });
        await error(errorMsg);
        message.value = {
            type: 'error',
            text: errorMsg,
        };
    } finally {
        isEmbeddingAll.value = false;
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

const infoItems = computed(() => {
    return [
        {
            strong: t('workflow.docs.infoItems.coreDocuments.title'),
            text: t('workflow.docs.infoItems.coreDocuments.description'),
        },
        {
            strong: t('workflow.docs.infoItems.frameworks.title'),
            text: t('workflow.docs.infoItems.frameworks.description'),
        },
        {
            strong: t('workflow.docs.infoItems.saveEmbed.title'),
            text: t('workflow.docs.infoItems.saveEmbed.description'),
        },
        {
            strong: t('workflow.docs.infoItems.embedAll.title'),
            text: t('workflow.docs.infoItems.embedAll.description'),
        },
        {
            strong: t('workflow.docs.infoItems.livePreview.title'),
            text: t('workflow.docs.infoItems.livePreview.description'),
        },
    ];
});
</script>

<template>
    <div>
        <PageHeader
            :title="t('workflow.docs.page.title')"
            subtitle="View and edit markdown documents used in n8n workflows"
        />

        <!-- Status Message -->
        <div
            v-if="message"
            class="mb-6 rounded-lg border p-4 transition"
            :class="{
                'border-green-200 bg-green-50 text-green-800':
                    message.type === 'success',
                'border-red-200 bg-red-50 text-red-800':
                    message.type === 'error',
            }"
        >
            {{ message.text }}
        </div>

        <!-- Three-Column Layout -->
        <div class="grid gap-6 lg:grid-cols-4">
            <!-- Sidebar: Document List -->
            <DocumentSidebar
                :documents="documents"
                :selected-document="selectedDocument"
                @select="selectedDocument = $event"
            />

            <!-- Editor and Preview -->
            <div class="lg:col-span-3">
                <!-- Document Header -->
                <div
                    v-if="selectedDocument"
                    class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 p-6 dark:bg-indigo-100"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-indigo-900">
                                {{ selectedDocument.displayName }}
                            </h2>
                            <p class="mt-1 text-sm text-indigo-600">
                                <span v-if="selectedDocument.usedIn.length > 0">
                                    Used in:
                                    <strong>{{
                                        selectedDocument.usedIn.join(', ')
                                    }}</strong>
                                </span>
                                <span v-else class="text-indigo-500">
                                    Not used in any workflows
                                </span>
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <ButtonPrimary
                                :disabled="isSaving"
                                :loading="isSaving"
                                @click="saveDocument"
                            >
                                {{
                                    isSaving
                                        ? t('workflow.docs.buttons.saving')
                                        : t('workflow.docs.buttons.saveEmbed')
                                }}
                            </ButtonPrimary>
                            <ButtonDanger
                                :disabled="isEmbeddingAll"
                                :loading="isEmbeddingAll"
                                @click="handleEmbedAll"
                            >
                                {{
                                    isEmbeddingAll
                                        ? t('workflow.docs.buttons.embedding')
                                        : t('common.buttons.embedAll')
                                }}
                            </ButtonDanger>
                        </div>
                    </div>
                </div>

                <!-- Editor and Preview Panes -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <!-- Editor Pane -->
                    <div
                        class="overflow-hidden rounded-lg border border-indigo-200"
                    >
                        <div
                            class="flex items-center justify-between border-b border-indigo-200 bg-indigo-300 px-4 py-2 text-indigo-800"
                        >
                            <h3 class="text-sm font-semibold">
                                {{ t('workflow.docs.sections.markdownEditor') }}
                            </h3>
                            <button
                                class="rounded px-2 py-1 text-xs hover:bg-indigo-400"
                                :title="t('workflow.codeEditor.expandTitle')"
                                @click="expandedView = 'editor'"
                            >
                                {{ t('workflow.docs.buttons.expand') }}
                            </button>
                        </div>
                        <textarea
                            v-model="content"
                            class="h-96 w-full resize-none border-0 bg-indigo-50 p-4 font-mono text-sm text-black dark:bg-indigo-100"
                            :placeholder="
                                t('workflow.docs.placeholders.editMarkdown')
                            "
                        />
                    </div>

                    <!-- Preview Pane -->
                    <div
                        class="overflow-hidden rounded-lg border border-indigo-200"
                    >
                        <div
                            class="flex items-center justify-between border-b border-indigo-200 bg-indigo-300 px-4 py-2 text-indigo-800"
                        >
                            <h3 class="text-sm font-semibold">
                                {{ t('workflow.docs.sections.preview') }}
                            </h3>
                            <button
                                class="rounded px-2 py-1 text-xs hover:bg-indigo-400"
                                :title="t('workflow.codeEditor.expandTitle')"
                                @click="expandedView = 'preview'"
                            >
                                {{ t('workflow.docs.buttons.expand') }}
                            </button>
                        </div>
                        <div
                            class="prose prose-sm dark:prose-invert h-96 w-full overflow-y-auto bg-indigo-50 p-4 dark:bg-indigo-100"
                            v-html="renderedContent"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Expanded Markdown Editor -->
        <ExpandableModal
            :show="expandedView === 'editor'"
            title="Markdown Editor (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex h-full flex-col">
                <textarea
                    v-model="content"
                    class="flex-1 resize-none overflow-auto border-0 bg-white p-6 font-mono text-sm leading-6 focus:outline-none"
                    :placeholder="t('workflow.docs.placeholders.editMarkdown')"
                    style="
                        line-height: 1.5;
                        white-space: pre;
                        overflow-wrap: normal;
                    "
                />
                <div
                    class="flex shrink-0 items-center justify-between border-t bg-indigo-50 px-6 py-3"
                >
                    <span class="text-xs text-indigo-600">
                        {{
                            content
                                ? `${content.length} ${t('workflow.show.metadata.characters')}`
                                : t('workflow.show.metadata.na')
                        }}
                    </span>
                    <ButtonPrimary
                        :disabled="isSaving"
                        :loading="isSaving"
                        @click="
                            saveDocument();
                            expandedView = null;
                        "
                    >
                        {{
                            isSaving
                                ? t('workflow.docs.buttons.saving')
                                : t('workflow.docs.buttons.saveEmbed')
                        }}
                    </ButtonPrimary>
                </div>
            </div>
        </ExpandableModal>

        <!-- Modal: Expanded Preview -->
        <ExpandableModal
            :show="expandedView === 'preview'"
            title="Preview (Expanded)"
            @close="expandedView = null"
        >
            <div class="flex-1 overflow-auto p-6">
                <div
                    class="prose prose-sm dark:prose-invert max-w-none"
                    v-html="renderedContent"
                />
            </div>
        </ExpandableModal>

        <!-- Info Section -->
        <InfoSection
            class="mt-8"
            title="How to Use Reference Documents"
            :items="infoItems"
        />

        <!-- Alert Dialog -->
        <AlertDialog />
    </div>
</template>
