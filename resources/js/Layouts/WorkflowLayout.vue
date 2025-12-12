<script setup lang="ts">
import ButtonDarkMode from '@/Components/ButtonDarkMode.vue';
import ButtonHamburger from '@/Components/ButtonHamburger.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import SvgLogo from '@/Icons/SvgLogo.vue';
import { Link } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Workflow',
});

const showingNavigationDropdown = ref(false);
const firstMobileNavLink = ref<InstanceType<typeof ResponsiveNavLink> | null>(
    null,
);

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

// Focus management for mobile navigation
watch(showingNavigationDropdown, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        firstMobileNavLink.value?.focus();
    }
});
</script>

<template>
    <div class="min-h-screen bg-indigo-50">
        <!-- Navigation Bar -->
        <nav class="border-b border-indigo-200 bg-white shadow-sm">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex shrink-0 items-center">
                        <Link
                            :href="route('home')"
                            class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:opacity-80 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                            @click="showingNavigationDropdown = false"
                        >
                            <SvgLogo
                                class="block h-10 w-auto fill-current text-indigo-600"
                            />
                            <span class="mt-2 text-xl font-bold text-gray-800"
                                >AI Buddy</span
                            >
                        </Link>
                    </div>

                    <!-- Navigation Links (Desktop) -->
                    <div class="hidden space-x-1 md:flex">
                        <Link
                            :href="route('workflow.index')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    route().current('workflow.index'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !route().current('workflow.index'),
                            }"
                        >
                            Index
                        </Link>
                        <Link
                            :href="
                                route('workflow.show', { workflowNumber: 0 })
                            "
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
                            :href="
                                route('workflow.show', { workflowNumber: 1 })
                            "
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
                            :href="
                                route('workflow.show', { workflowNumber: 2 })
                            "
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
                            :href="route('workflow.docs.index')"
                            class="rounded-md px-4 py-2 text-sm font-medium transition"
                            :class="{
                                'bg-indigo-100 text-indigo-900':
                                    route().current('workflow.docs.index'),
                                'text-indigo-600 hover:bg-indigo-50':
                                    !route().current('workflow.docs.index'),
                            }"
                        >
                            Docs
                        </Link>
                    </div>

                    <!-- Right Side Actions -->
                    <div class="flex items-center space-x-2">
                        <!-- Dark Mode Toggle -->
                        <ButtonDarkMode class="size-10 shrink-0 p-2" />

                        <!-- Hamburger (Mobile) -->
                        <div class="md:hidden">
                            <ButtonHamburger
                                :is-open="showingNavigationDropdown"
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <Teleport to="body">
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div
                        v-show="showingNavigationDropdown"
                        class="fixed top-16 right-0 left-0 z-40 bg-white shadow-lg md:hidden"
                    >
                        <div class="space-y-1 pt-2 pb-3">
                            <ResponsiveNavLink
                                ref="firstMobileNavLink"
                                :href="route('workflow.index')"
                                :active="route().current('workflow.index')"
                                @click="showingNavigationDropdown = false"
                            >
                                Index
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="
                                    route('workflow.show', {
                                        workflowNumber: 0,
                                    })
                                "
                                :active="$page.url.includes('/workflow/0')"
                                @click="showingNavigationDropdown = false"
                            >
                                Workflow 0
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="
                                    route('workflow.show', {
                                        workflowNumber: 1,
                                    })
                                "
                                :active="$page.url.includes('/workflow/1')"
                                @click="showingNavigationDropdown = false"
                            >
                                Workflow 1
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="
                                    route('workflow.show', {
                                        workflowNumber: 2,
                                    })
                                "
                                :active="$page.url.includes('/workflow/2')"
                                @click="showingNavigationDropdown = false"
                            >
                                Workflow 2
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="route('workflow.docs.index')"
                                :active="route().current('workflow.docs.index')"
                                @click="showingNavigationDropdown = false"
                            >
                                Docs
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </Transition>
            </Teleport>
        </nav>

        <!-- Mobile Navigation Overlay -->
        <Teleport to="body">
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 z-30 md:hidden"
                @click="showingNavigationDropdown = false"
            ></div>
        </Teleport>

        <!-- Page Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
