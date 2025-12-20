<script setup lang="ts">
import AlertDialog from '@/Components/Base/AlertDialog.vue';
import ButtonDarkMode from '@/Components/Base/Button/ButtonDarkMode.vue';
import ButtonHamburger from '@/Components/Base/Button/ButtonHamburger.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import Dropdown from '@/Components/Base/Dropdown.vue';
import DropdownLink from '@/Components/Base/DropdownLink.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ModalForgotPassword from '@/Components/Base/Modal/ModalForgotPassword.vue';
import ModalLogin from '@/Components/Base/Modal/ModalLogin.vue';
import ModalRegister from '@/Components/Base/Modal/ModalRegister.vue';
import NavLink from '@/Components/Base/NavLink.vue';
import ResponsiveNavLink from '@/Components/Base/ResponsiveNavLink.vue';
import CookieBanner from '@/Components/Common/CookieBanner.vue';
import Footer from '@/Components/Common/Footer.vue';
import NotificationCenter from '@/Components/Common/NotificationCenter.vue';
import { useSessionTimeout } from '@/Composables/features/useSessionTimeout';
import SvgLogo from '@/Icons/SvgLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    provide,
    ref,
    watch,
} from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
    isAdmin: boolean;
}

const page = usePage<{
    auth?: { user?: User };
    visitorHasCompletedPrompts?: boolean;
}>();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isAdmin = computed(() => page.props.auth?.user?.isAdmin ?? false);

// Initialize session timeout tracking for authenticated users
// This prevents 419 CSRF errors by logging users out before session expires
if (isAuthenticated.value) {
    useSessionTimeout();
}

// Determine logo destination based on admin status and current route
const logoDestination = computed(() => {
    const currentRoute = route().current() || '';

    // If admin user and on admin pages, go to main site
    if (isAdmin.value && currentRoute.startsWith('admin.')) {
        return route('home');
    }

    // If admin user and on main site, go to admin dashboard
    if (isAdmin.value) {
        return route('admin.dashboard');
    }

    // Non-admin users always go to home/main site
    return route('home');
});

const showingNavigationDropdown = ref(false);
const showLoginModal = ref(false);
const showRegisterModal = ref(false);
const showForgotPasswordModal = ref(false);
const userDropdown = ref<InstanceType<typeof Dropdown> | null>(null);
const firstMobileNavLink = ref<InstanceType<typeof ResponsiveNavLink> | null>(
    null,
);
const firstGuestMobileButton = ref<HTMLButtonElement | null>(null);

// Close dropdown on navigation
const closeDropdownOnNavigate = () => {
    userDropdown.value?.close();
};

// Store the cleanup function returned by router.on
let removeRouterListener: (() => void) | null = null;

const openLogin = () => {
    showRegisterModal.value = false;
    showForgotPasswordModal.value = false;
    showLoginModal.value = true;
};

const openRegister = () => {
    showLoginModal.value = false;
    showForgotPasswordModal.value = false;
    showRegisterModal.value = true;
};

const openForgotPassword = () => {
    showLoginModal.value = false;
    showRegisterModal.value = false;
    showForgotPasswordModal.value = true;
};

onMounted(() => {
    // router.on returns a cleanup function
    removeRouterListener = router.on('start', closeDropdownOnNavigate);

    // Handle modal query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');

    if (modalParam === 'login') {
        openLogin();
    } else if (modalParam === 'register') {
        openRegister();
    }
});

onUnmounted(() => {
    // Call the cleanup function to remove the listener
    if (removeRouterListener) {
        removeRouterListener();
    }
});

// Provide modal controls to child components
provide('openLoginModal', openLogin);
provide('openRegisterModal', openRegister);

// Focus management for mobile navigation
watch(showingNavigationDropdown, async (isOpen) => {
    if (isOpen) {
        await nextTick();
        // Focus the first interactive element in the mobile navigation
        if (isAuthenticated.value) {
            firstMobileNavLink.value?.focus();
        } else {
            firstGuestMobileButton.value?.focus();
        }
    }
});
</script>

<template>
    <div
        class="flex min-h-screen flex-col bg-linear-to-br from-indigo-100 via-white to-purple-100 dark:from-indigo-50 dark:to-purple-50"
    >
        <div class="flex flex-1 flex-col">
            <nav class="bg-white shadow-xs shadow-indigo-50">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="logoDestination"
                                    class="flex items-center gap-1 rounded-md px-2 py-1 transition hover:opacity-80 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                    @click="showingNavigationDropdown = false"
                                >
                                    <SvgLogo
                                        class="block h-10 w-auto fill-current text-indigo-800"
                                        size="lg"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links (Authenticated) -->
                            <div
                                v-if="isAuthenticated"
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex sm:items-center"
                            >
                                <NavLink
                                    :href="route('prompt-builder.index')"
                                    :active="
                                        route().current('prompt-builder.index')
                                    "
                                >
                                    Prompt Builder
                                </NavLink>
                                <NavLink
                                    :href="route('prompt-builder.history')"
                                    :active="
                                        route().current(
                                            'prompt-builder.history',
                                        )
                                    "
                                >
                                    Prompt History
                                </NavLink>
                                <NavLink
                                    :href="route('feedback.create')"
                                    :active="route().current('feedback.*')"
                                >
                                    Feedback
                                </NavLink>
                                <NavLink
                                    v-if="isAdmin"
                                    :href="route('workflow.index')"
                                    :active="route().current('workflow.*')"
                                >
                                    Workflows
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right Side Navigation -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <ButtonDarkMode class="mr-2 size-10 shrink-0 p-2" />

                            <!-- Authenticated User Dropdown -->
                            <div v-if="isAuthenticated" class="relative ms-3">
                                <Dropdown
                                    ref="userDropdown"
                                    align="right"
                                    width="48"
                                >
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-3 text-sm leading-4 font-medium text-indigo-700 transition duration-150 ease-in-out hover:bg-indigo-50 hover:text-indigo-800 focus:text-indigo-800 focus:ring-2 focus:ring-indigo-500 focus:outline-hidden"
                                                aria-label="User menu"
                                            >
                                                {{
                                                    $page.props.auth!.user!.name
                                                }}

                                                <DynamicIcon
                                                    name="chevron-down"
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                />
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Guest Buttons -->
                            <nav v-else class="flex items-center gap-4">
                                <ButtonSecondary @click="openLogin">
                                    Log in
                                </ButtonSecondary>
                            </nav>
                        </div>

                        <!-- Hamburger (Mobile) -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <ButtonDarkMode />

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
                            class="fixed top-16 right-0 left-0 z-40 bg-white shadow-lg sm:hidden"
                        >
                            <!-- Authenticated Mobile Nav -->
                            <template v-if="isAuthenticated">
                                <div class="space-y-1 pt-2 pb-3">
                                    <ResponsiveNavLink
                                        ref="firstMobileNavLink"
                                        :href="route('prompt-builder.index')"
                                        :active="
                                            route().current(
                                                'prompt-builder.index',
                                            )
                                        "
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Prompt Builder
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="route('prompt-builder.history')"
                                        :active="
                                            route().current(
                                                'prompt-builder.history',
                                            )
                                        "
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Prompt History
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="route('feedback.create')"
                                        :active="route().current('feedback.*')"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Feedback
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        v-if="isAdmin"
                                        :href="route('workflow.index')"
                                        :active="route().current('workflow.*')"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Workflows
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="route('profile.edit')"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Profile
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        Log Out
                                    </ResponsiveNavLink>
                                </div>
                            </template>

                            <!-- Guest Mobile Nav -->
                            <template v-else>
                                <div class="space-y-1 pt-2 pb-3">
                                    <button
                                        ref="firstGuestMobileButton"
                                        class="block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-indigo-600 transition duration-150 ease-in-out hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-800 focus:border-indigo-300 focus:bg-indigo-50 focus:text-indigo-800 focus:outline-hidden"
                                        @click="
                                            openLogin();
                                            showingNavigationDropdown = false;
                                        "
                                    >
                                        Log in
                                    </button>
                                </div>
                            </template>
                        </div>
                    </Transition>
                </Teleport>
            </nav>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>

        <!-- Footer -->
        <Footer />

        <!-- Auth Modals -->
        <ModalLogin
            :show="showLoginModal"
            @close="showLoginModal = false"
            @switch-to-register="openRegister"
            @switch-to-forgot-password="openForgotPassword"
        />

        <ModalRegister
            :show="showRegisterModal"
            @close="showRegisterModal = false"
            @switch-to-login="openLogin"
        />

        <ModalForgotPassword
            :show="showForgotPasswordModal"
            @close="showForgotPasswordModal = false"
            @switch-to-login="openLogin"
        />

        <!-- Cookie Banner -->
        <CookieBanner />

        <!-- Mobile Navigation Overlay -->
        <Teleport to="body">
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 z-30 sm:hidden"
                @click="showingNavigationDropdown = false"
            ></div>
        </Teleport>

        <!-- Notification Center (handles both server flash messages and client notifications) -->
        <NotificationCenter />

        <!-- Global Alert Dialog -->
        <AlertDialog />
    </div>
</template>
