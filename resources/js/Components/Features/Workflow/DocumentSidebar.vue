<script setup lang="ts">
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
    selectedDocument: Document | null;
}

defineProps<Props>();

const emit = defineEmits<{
    select: [doc: Document];
}>();
</script>

<template>
    <div class="lg:col-span-1">
        <div class="sticky top-4 space-y-4">
            <!-- Core Documents Section -->
            <div>
                <h2
                    class="mb-3 text-sm font-semibold text-indigo-500 uppercase"
                >
                    Core Documents
                </h2>
                <div class="space-y-2">
                    <button
                        v-for="doc in documents.core"
                        :key="`${doc.type}-${doc.filename}`"
                        class="w-full rounded-lg border px-4 py-2 text-left text-sm transition"
                        :class="{
                            'border-indigo-100 bg-indigo-200 font-medium text-indigo-900 dark:bg-indigo-100':
                                selectedDocument?.filename === doc.filename,
                            'border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-50 dark:bg-indigo-50':
                                selectedDocument?.filename !== doc.filename,
                        }"
                        @click="emit('select', doc)"
                    >
                        <span
                            class="block truncate font-medium text-indigo-800"
                        >
                            {{ doc.displayName }}
                        </span>
                        <span class="mt-1 block text-xs text-indigo-600">
                            Used in:
                            {{ doc.usedIn.join(', ') || 'None' }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Framework Templates Section -->
            <div>
                <h2
                    class="mb-3 text-sm font-semibold text-indigo-500 uppercase"
                >
                    Framework Templates ({{ documents.framework.length }})
                </h2>
                <div class="max-h-96 space-y-2 overflow-y-auto pr-2">
                    <button
                        v-for="doc in documents.framework"
                        :key="`${doc.type}-${doc.filename}`"
                        class="w-full rounded-lg border px-3 py-2 text-left text-xs transition"
                        :class="{
                            'border-indigo-100 bg-indigo-200 font-medium text-indigo-900 dark:bg-indigo-100':
                                selectedDocument?.filename === doc.filename,
                            'border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-50 dark:bg-indigo-50':
                                selectedDocument?.filename !== doc.filename,
                        }"
                        @click="emit('select', doc)"
                    >
                        <span
                            class="block truncate font-medium text-indigo-800"
                        >
                            {{ doc.displayName }}
                        </span>
                        <span class="mt-1 block text-indigo-600"
                            >workflow_2</span
                        >
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
