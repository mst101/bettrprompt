<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Workflow Debug',
});
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-800"
    >
        <!-- Navigation Bar -->
        <nav
            class="border-b border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
        >
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo/Home -->
                    <div class="flex items-center space-x-4">
                        <Link
                            href="/workflow"
                            class="text-xl font-bold text-slate-900 transition hover:text-slate-700 dark:text-white dark:hover:text-slate-200"
                        >
                            Workflow Debug
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-1 md:flex">
                        <Link
                            href="/workflow"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-white':
                                    $page.url === '/workflow',
                                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700':
                                    $page.url !== '/workflow',
                            }"
                        >
                            Index
                        </Link>
                        <Link
                            href="/workflow/0"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-white':
                                    $page.url.includes('/workflow/0'),
                                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700':
                                    !$page.url.includes('/workflow/0'),
                            }"
                        >
                            Workflow 0
                        </Link>
                        <Link
                            href="/workflow/1"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-white':
                                    $page.url.includes('/workflow/1'),
                                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700':
                                    !$page.url.includes('/workflow/1'),
                            }"
                        >
                            Workflow 1
                        </Link>
                        <Link
                            href="/workflow/2"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-white':
                                    $page.url.includes('/workflow/2'),
                                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700':
                                    !$page.url.includes('/workflow/2'),
                            }"
                        >
                            Workflow 2
                        </Link>
                        <Link
                            href="/workflow/docs"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-slate-100 text-slate-900 dark:bg-slate-700 dark:text-white':
                                    $page.url.includes('/workflow/docs'),
                                'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700':
                                    !$page.url.includes('/workflow/docs'),
                            }"
                        >
                            Docs
                        </Link>
                    </div>

                    <!-- Mobile Menu Button (simplified for now) -->
                    <div class="md:hidden">
                        <span
                            class="text-sm text-slate-600 dark:text-slate-300"
                            >{{ title }}</span
                        >
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
