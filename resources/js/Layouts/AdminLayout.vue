<script setup lang="ts">
import AdminSidebar from '@/Components/Admin/AdminSidebar.vue';
import AlertDialog from '@/Components/Base/AlertDialog.vue';
import ButtonDarkMode from '@/Components/Base/Button/ButtonDarkMode.vue';
import Dropdown from '@/Components/Base/Dropdown.vue';
import DropdownLink from '@/Components/Base/DropdownLink.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LanguageSwitcher from '@/Components/Common/LanguageSwitcher.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { useThemeStore } from '@/Stores/themeStore';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title?: string;
}

withDefaults(defineProps<Props>(), {
    title: 'Admin',
});

// Initialize theme store to ensure consistent dark mode handling
useThemeStore();

const page = usePage<{
    auth?: { user?: { name: string; isAdmin: boolean } };
}>();

const { countryRoute } = useCountryRoute();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isAdmin = computed(() => page.props.auth?.user?.isAdmin ?? false);
</script>

<template>
    <div
        class="flex h-screen bg-linear-to-br from-indigo-100 via-white to-purple-100 dark:from-indigo-50 dark:to-purple-50"
    >
        <!-- Sidebar -->
        <AdminSidebar />

        <!-- Main content area -->
        <div class="flex flex-1 flex-col overflow-hidden md:ml-64">
            <!-- Top bar -->
            <nav
                class="flex h-16 items-center justify-between bg-white px-6 shadow-sm"
            >
                <!-- Left side (empty for now) -->
                <div />

                <!-- Right side actions -->
                <div class="flex items-center gap-2">
                    <LanguageSwitcher />
                    <ButtonDarkMode class="size-10 shrink-0 p-2" />

                    <!-- User Dropdown -->
                    <div v-if="isAuthenticated" class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-3 text-sm leading-4 font-medium text-indigo-700 transition duration-150 ease-in-out hover:bg-indigo-50 hover:text-indigo-800 focus:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                        :aria-label="$t('common.aria.userMenu')"
                                    >
                                        {{ $page.props.auth!.user!.name }}

                                        <DynamicIcon
                                            name="chevron-down"
                                            class="ms-2 -me-0.5 h-4 w-4"
                                        />
                                    </button>
                                </span>
                            </template>

                            <template #content>
                                <DropdownLink
                                    :href="countryRoute('profile.edit')"
                                >
                                    {{ $t('common.nav.profile') }}
                                </DropdownLink>
                                <DropdownLink
                                    v-if="isAdmin"
                                    :href="countryRoute('admin.dashboard')"
                                >
                                    {{ $t('common.nav.dashboard') }}
                                </DropdownLink>
                                <DropdownLink
                                    :href="countryRoute('logout')"
                                    method="post"
                                    as="button"
                                >
                                    {{ $t('common.nav.logout') }}
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
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
