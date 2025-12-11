<script setup lang="ts">
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: WorkflowLayout,
});
</script>

<template>
    <div>
        <h1 class="mb-8 text-3xl font-bold text-slate-900 dark:text-white">
            Workflow Debug System
        </h1>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Workflow 0 Card -->
            <Link
                href="/workflow/0"
                class="group overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2
                            class="text-xl font-semibold text-slate-900 transition group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                        >
                            Workflow 0
                        </h2>
                        <p
                            class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            Pre-Analysis Workflow
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-500"
                        >
                            Gathers clarifying questions based on task
                            classification
                        </p>
                    </div>
                    <div class="text-2xl">→</div>
                </div>
            </Link>

            <!-- Workflow 1 Card -->
            <Link
                href="/workflow/1"
                class="group overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2
                            class="text-xl font-semibold text-slate-900 transition group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                        >
                            Workflow 1
                        </h2>
                        <p
                            class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            Question Generation Workflow
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-500"
                        >
                            Generates task classification and clarifying
                            questions
                        </p>
                    </div>
                    <div class="text-2xl">→</div>
                </div>
            </Link>

            <!-- Workflow 2 Card -->
            <Link
                href="/workflow/2"
                class="group overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2
                            class="text-xl font-semibold text-slate-900 transition group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400"
                        >
                            Workflow 2
                        </h2>
                        <p
                            class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                        >
                            Prompt Optimisation Workflow
                        </p>
                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-500"
                        >
                            Generates optimised prompts using selected
                            frameworks
                        </p>
                    </div>
                    <div class="text-2xl">→</div>
                </div>
            </Link>

            <!-- Reference Documents Card -->
            <Link
                href="/workflow/docs"
                class="group overflow-hidden rounded-lg border border-amber-200 bg-amber-50 p-6 shadow-sm transition hover:shadow-lg dark:border-amber-700 dark:bg-amber-900/20"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2
                            class="text-xl font-semibold text-amber-900 transition group-hover:text-amber-700 dark:text-amber-200 dark:group-hover:text-amber-300"
                        >
                            Reference Documents
                        </h2>
                        <p
                            class="mt-2 text-sm text-amber-800 dark:text-amber-300"
                        >
                            Document Management
                        </p>
                        <p
                            class="mt-1 text-xs text-amber-700 dark:text-amber-400"
                        >
                            View, edit, and manage reference documents
                        </p>
                    </div>
                    <div class="text-2xl">📄</div>
                </div>
            </Link>
        </div>

        <!-- Quick Info Section -->
        <div
            class="mt-8 rounded-lg border border-slate-200 bg-slate-50 p-6 dark:border-slate-700 dark:bg-slate-800/50"
        >
            <h3
                class="mb-4 text-lg font-semibold text-slate-900 dark:text-white"
            >
                How to Use
            </h3>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Workflow pages:</strong> Debug individual
                        workflows, inspect input/output, and test JavaScript
                        code</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Reference Documents:</strong> View and edit the
                        markdown documents used in workflows, then embed them
                        into the workflow JSON files</span
                    >
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 text-blue-500">•</span>
                    <span
                        ><strong>Framework Templates:</strong> 64 different
                        prompting frameworks available for use in workflow
                        optimisation</span
                    >
                </li>
            </ul>
        </div>
    </div>
</template>
