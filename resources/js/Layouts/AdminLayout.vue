<script setup lang="ts">
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import AlertDialog from '@/Components/Base/AlertDialog.vue';
import ButtonDarkMode from '@/Components/Base/Button/ButtonDarkMode.vue';
import { useThemeStore } from '@/Stores/themeStore';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Admin',
});

// Initialize theme store to ensure consistent dark mode handling
useThemeStore();
</script>

<template>
    <div
        class="flex h-screen bg-linear-to-br from-indigo-100 via-white to-purple-100 dark:from-indigo-50 dark:to-purple-50"
    >
        <!-- Sidebar -->
        <AdminSidebar />

        <!-- Main content area -->
        <div class="flex flex-1 flex-col overflow-hidden md:ml-64">
            <!-- Top bar (simplified) -->
            <nav
                class="flex h-16 items-center justify-end bg-white px-6 shadow-sm"
            >
                <ButtonDarkMode class="size-10 shrink-0 p-2" />
            </nav>

            <!-- Page content (scrollable) -->
            <main class="flex-1 overflow-y-auto px-4 py-8 sm:px-6">
                <slot />
            </main>
        </div>

        <!-- Alert Dialog -->
        <AlertDialog />
    </div>
</template>
