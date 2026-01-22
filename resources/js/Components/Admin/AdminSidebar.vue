<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import SvgLogo from '@/Icons/SvgLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminSidebarItem from './AdminSidebarItem.vue';
import AdminSidebarSection from './AdminSidebarSection.vue';

const { countryRoute } = useCountryRoute();
const page = usePage();
const isOpen = ref(false); // For mobile

// Note: route() is a global function provided by Inertia.js/Ziggy
// It's declared in resources/js/Types/global.d.ts
declare const route: {
    (name: string, params?: Record<string, any>): string;
    current(name?: string, params?: Record<string, any>): boolean | string;
};

// Dashboard active state using URL comparison (accounts for country prefix)
const dashboardUrl = computed(() => countryRoute('admin.dashboard'));
const isDashboardActive = computed(() => {
    const currentUrl = page.url.split('?')[0].replace(/\/$/, '');

    // Extract path from href (countryRoute returns full URL like https://app.localhost/gb/admin)
    let hrefUrl = dashboardUrl.value;
    try {
        const url = new URL(dashboardUrl.value, window.location.origin);
        hrefUrl = url.pathname;
    } catch {
        // If it's already a path, use it as-is
    }
    hrefUrl = hrefUrl.replace(/\/$/, '');

    return currentUrl === hrefUrl;
});

const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
};

const closeSidebar = () => {
    isOpen.value = false;
};

// Helper to detect if a specific workflow is active
const isWorkflowActive = (workflowNumber: number) => {
    return route().current('workflows.show', { workflowNumber });
};
</script>

<template>
    <!-- Mobile backdrop overlay -->
    <Teleport to="body">
        <Transition>
            <div
                v-show="isOpen"
                class="bg-opacity-50 fixed inset-0 z-40 bg-black md:hidden"
                @click="closeSidebar"
            />
        </Transition>
    </Teleport>

    <!-- Mobile hamburger button -->
    <button
        class="fixed top-4 left-4 z-30 rounded-lg bg-white p-2 text-indigo-600 shadow-md hover:bg-indigo-50 md:hidden"
        @click="toggleSidebar"
    >
        <DynamicIcon name="bars-3" class="h-6 w-6" />
    </button>

    <!-- Sidebar -->
    <aside
        :class="[
            'fixed top-0 left-0 z-50 h-screen w-64 bg-white shadow-lg transition-transform duration-300 md:translate-x-0',
            isOpen ? 'translate-x-0' : '-translate-x-full',
        ]"
    >
        <!-- Sidebar header with logo -->
        <div class="flex h-16 items-center border-b border-indigo-100 px-4">
            <Link
                :href="countryRoute('home')"
                class="flex items-center gap-2"
                @click="closeSidebar"
            >
                <SvgLogo class="h-8 w-auto fill-current text-indigo-800" />
                <span class="font-semibold text-indigo-900">Admin</span>
            </Link>
        </div>

        <!-- Mobile close button (X) in top-right corner -->
        <button
            class="absolute top-4 right-4 rounded-lg p-2 text-indigo-600 hover:bg-indigo-50 md:hidden"
            @click="closeSidebar"
        >
            <DynamicIcon name="x-mark" class="h-6 w-6" />
        </button>

        <!-- Scrollable navigation -->
        <nav class="h-[calc(100vh-4rem)] overflow-y-auto px-3 py-4">
            <!-- Dashboard (always visible) -->
            <Link
                :href="dashboardUrl"
                :class="[
                    'mb-2 flex items-center gap-3 rounded-lg px-3 py-2',
                    isDashboardActive
                        ? 'bg-indigo-50 text-indigo-900'
                        : 'text-indigo-700 hover:bg-indigo-50',
                ]"
                @click="closeSidebar"
            >
                <DynamicIcon name="home" class="h-5 w-5" />
                <span class="font-medium">Dashboard</span>
            </Link>

            <!-- Analytics Section -->
            <AdminSidebarSection title="Analytics" icon="chart-bar">
                <AdminSidebarItem
                    :href="countryRoute('admin.traffic-analytics.index')"
                    label="Traffic Analytics"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="countryRoute('admin.domain-analytics.index')"
                    label="Domain Analytics"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>

            <!-- Users & Visitors Section -->
            <AdminSidebarSection title="Users & Visitors" icon="users">
                <AdminSidebarItem
                    :href="countryRoute('admin.users.index')"
                    label="Users"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="countryRoute('admin.visitors.index')"
                    label="Visitors"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>

            <!-- Prompts Section -->
            <AdminSidebarSection title="Prompts" icon="chat-bubble-oval">
                <AdminSidebarItem
                    :href="countryRoute('admin.tasks.index')"
                    label="Tasks"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>

            <!-- Questions Section -->
            <AdminSidebarSection title="Questions" icon="question-mark-circle">
                <AdminSidebarItem
                    :href="countryRoute('admin.questions.index')"
                    label="Question Bank"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="countryRoute('admin.questions.create')"
                    label="Create Question"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>

            <!-- Workflows Section -->
            <AdminSidebarSection title="Workflows" icon="cog-6-tooth">
                <AdminSidebarItem
                    :href="countryRoute('workflows.index')"
                    label="Workflow Hub"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="
                        countryRoute('workflows.show', {
                            workflowNumber: 0,
                        })
                    "
                    label="Workflow 0 (Pre-Analysis)"
                    :active="isWorkflowActive(0)"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="
                        countryRoute('workflows.show', {
                            workflowNumber: 1,
                        })
                    "
                    label="Workflow 1 (Analysis)"
                    :active="isWorkflowActive(1)"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="
                        countryRoute('workflows.show', {
                            workflowNumber: 2,
                        })
                    "
                    label="Workflow 2 (Generation)"
                    :active="isWorkflowActive(2)"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="countryRoute('workflows.docs.index')"
                    label="Reference Docs"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>

            <!-- Experiments Section -->
            <AdminSidebarSection title="Experiments" icon="beaker">
                <AdminSidebarItem
                    :href="countryRoute('admin.experiments.index')"
                    label="All Experiments"
                    @click="closeSidebar"
                />
                <AdminSidebarItem
                    :href="countryRoute('admin.experiments.create')"
                    label="Create Experiment"
                    @click="closeSidebar"
                />
            </AdminSidebarSection>
        </nav>
    </aside>
</template>
