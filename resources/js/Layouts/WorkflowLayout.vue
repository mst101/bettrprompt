<script setup lang="ts">
import ButtonDarkMode from '@/Components/ButtonDarkMode.vue';
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Workflow',
});

// Initialize dark mode from localStorage
onMounted(() => {
    const isDark =
        localStorage.getItem('theme') === 'dark' ||
        (!('theme' in localStorage) &&
            window.matchMedia('(prefers-color-scheme: dark)').matches);

    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <div class="min-h-screen bg-indigo-50">
        <!-- Navigation Bar -->
        <nav class="border-b border-indigo-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo/Home -->
                    <div class="flex items-center space-x-4">
                        <Link
                            href="/workflow"
                            class="hover:text-indigo-7000 text-xl font-bold text-indigo-900 transition"
                        >
                            Workflow
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <div class="hidden space-x-1 md:flex">
                        <Link
                            href="/workflow/0"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    $page.url.includes('/workflow/0'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !$page.url.includes('/workflow/0'),
                            }"
                        >
                            Workflow 0
                        </Link>
                        <Link
                            href="/workflow/1"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    $page.url.includes('/workflow/1'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !$page.url.includes('/workflow/1'),
                            }"
                        >
                            Workflow 1
                        </Link>
                        <Link
                            href="/workflow/2"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    $page.url.includes('/workflow/2'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !$page.url.includes('/workflow/2'),
                            }"
                        >
                            Workflow 2
                        </Link>
                        <Link
                            href="/workflow/docs"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    $page.url.includes('/workflow/docs'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !$page.url.includes('/workflow/docs'),
                            }"
                        >
                            Docs
                        </Link>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center space-x-2">
                        <!-- Dark Mode Toggle -->
                        <ButtonDarkMode class="size-10 shrink-0 p-2" />

                        <!-- Mobile Menu Button (simplified for now) -->
                        <div class="md:hidden">
                            <span class="text-sm text-indigo-600">{{
                                title
                            }}</span>
                        </div>
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
