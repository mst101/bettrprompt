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
import ModalResetPassword from '@/Components/Base/Modal/ModalResetPassword.vue';
import NavLink from '@/Components/Base/NavLink.vue';
import ResponsiveNavLink from '@/Components/Base/ResponsiveNavLink.vue';
import CookieBanner from '@/Components/Common/CookieBanner.vue';
import Footer from '@/Components/Common/Footer.vue';
import LanguageSwitcher from '@/Components/Common/LanguageSwitcher.vue';
import NotificationCenter from '@/Components/Common/NotificationCenter.vue';
import { usePageTracking } from '@/Composables/analytics/usePageTracking';
import { useSessionTimeout } from '@/Composables/features/useSessionTimeout';
import { useCountryRoute } from '@/Composables/useCountryRoute';
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
    country?: string;
    locale?: string;
}>();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const isAdmin = computed(() => page.props.auth?.user?.isAdmin ?? false);

// Initialize session timeout tracking for authenticated users
// This prevents 419 CSRF errors by logging users out before session expires
if (isAuthenticated.value) {
    useSessionTimeout();
}

// Use country-aware route generation
const { countryRoute } = useCountryRoute();

// Track page views
usePageTracking();

// Determine logo destination based on admin status and current route
const logoDestination = computed(() => {
    const currentRoute = route().current() || '';

    // If admin user and on admin pages, go to main site
    if (isAdmin.value && currentRoute.startsWith('admin.')) {
        return countryRoute('home');
    }

    // If admin user and on main site, go to admin dashboard
    if (isAdmin.value) {
        return countryRoute('admin.dashboard');
    }

    // Non-admin users always go to home/main site
    return countryRoute('home');
});

const showingNavigationDropdown = ref(false);
const showLoginModal = ref(false);
const showRegisterModal = ref(false);
const showForgotPasswordModal = ref(false);
const showResetPasswordModal = ref(false);
const currentEmail = ref('');
const resetPasswordEmail = ref('');
const resetPasswordToken = ref('');
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

const openLogin = (email?: string) => {
    if (email && typeof email === 'string') {
        currentEmail.value = email;
    }
    showRegisterModal.value = false;
    showForgotPasswordModal.value = false;
    showResetPasswordModal.value = false;
    showLoginModal.value = true;
};

const openRegister = (email?: string) => {
    if (email && typeof email === 'string') {
        currentEmail.value = email;
    }
    showLoginModal.value = false;
    showForgotPasswordModal.value = false;
    showResetPasswordModal.value = false;
    showRegisterModal.value = true;
};

const openForgotPassword = (email?: string) => {
    if (email && typeof email === 'string') {
        currentEmail.value = email;
    }
    showLoginModal.value = false;
    showRegisterModal.value = false;
    showResetPasswordModal.value = false;
    showForgotPasswordModal.value = true;
};

const openResetPassword = (email: string, token: string) => {
    currentEmail.value = email;
    showLoginModal.value = false;
    showRegisterModal.value = false;
    showForgotPasswordModal.value = false;
    resetPasswordEmail.value = email;
    resetPasswordToken.value = token;
    showResetPasswordModal.value = true;
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
    } else if (modalParam === 'reset-password') {
        const email = urlParams.get('email') || '';
        const token = urlParams.get('token') || '';
        openResetPassword(email, token);
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
                <div class="mx-auto max-w-7xl px-4 md:px-6 lg:px-8">
                    <div class="flex h-18 justify-between">
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
                                class="hidden space-x-2 md:-my-px md:ms-6 md:flex md:items-center lg:ms-12 lg:space-x-8"
                            >
                                <NavLink
                                    :href="countryRoute('prompt-builder.index')"
                                    :active="
                                        route().current('prompt-builder.index')
                                    "
                                >
                                    {{ $t('navigation.promptBuilder') }}
                                </NavLink>
                                <NavLink
                                    :href="
                                        countryRoute('prompt-builder.history')
                                    "
                                    :active="
                                        route().current(
                                            'prompt-builder.history',
                                        )
                                    "
                                >
                                    {{ $t('navigation.promptHistory') }}
                                </NavLink>
                                <NavLink
                                    :href="countryRoute('feedback.create')"
                                    :active="route().current('feedback.*')"
                                >
                                    {{ $t('navigation.feedback') }}
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right Side Navigation -->
                        <div
                            class="hidden gap-2 md:ms-6 md:flex md:items-center"
                        >
                            <LanguageSwitcher />
                            <ButtonDarkMode class="size-10 shrink-0 p-2" />

                            <!-- Authenticated User Dropdown -->
                            <div v-if="isAuthenticated" class="relative">
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
                                                :aria-label="
                                                    $t('common.aria.userMenu')
                                                "
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
                                            :href="countryRoute('profile.edit')"
                                        >
                                            {{ $t('common.nav.profile') }}
                                        </DropdownLink>
                                        <DropdownLink
                                            v-if="isAdmin"
                                            :href="
                                                countryRoute('workflows.index')
                                            "
                                        >
                                            {{ $t('navigation.workflows') }}
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

                            <!-- Guest Buttons -->
                            <nav v-else class="flex items-center gap-4">
                                <ButtonSecondary @click="openLogin">
                                    {{ $t('common.nav.login') }}
                                </ButtonSecondary>
                            </nav>
                        </div>

                        <!-- Hamburger (Mobile) -->
                        <div class="-me-2 flex items-center md:hidden">
                            <LanguageSwitcher />
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
                            class="fixed top-16 right-0 left-0 z-40 bg-white shadow-lg md:hidden"
                        >
                            <!-- Authenticated Mobile Nav -->
                            <template v-if="isAuthenticated">
                                <div class="space-y-1 pt-2 pb-3">
                                    <ResponsiveNavLink
                                        ref="firstMobileNavLink"
                                        :href="
                                            countryRoute('prompt-builder.index')
                                        "
                                        :active="
                                            route().current(
                                                'prompt-builder.index',
                                            )
                                        "
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        {{ $t('navigation.promptBuilder') }}
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="
                                            countryRoute(
                                                'prompt-builder.history',
                                            )
                                        "
                                        :active="
                                            route().current(
                                                'prompt-builder.history',
                                            )
                                        "
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        {{ $t('navigation.promptHistory') }}
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="countryRoute('feedback.create')"
                                        :active="route().current('feedback.*')"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        {{ $t('navigation.feedback') }}
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="countryRoute('profile.edit')"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        {{ $t('common.nav.profile') }}
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink
                                        :href="countryRoute('logout')"
                                        method="post"
                                        as="button"
                                        @click="
                                            showingNavigationDropdown = false
                                        "
                                    >
                                        {{ $t('common.nav.logout') }}
                                    </ResponsiveNavLink>
                                </div>
                            </template>

                            <!-- Guest Mobile Nav -->
                            <template v-else>
                                <div class="space-y-1 pt-2 pb-3">
                                    <button
                                        ref="firstGuestMobileButton"
                                        class="block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-indigo-600 transition duration-150 ease-in-out hover:border-indigo-100 hover:bg-indigo-50 hover:text-indigo-800 focus:border-indigo-100 focus:bg-indigo-50 focus:text-indigo-800 focus:outline-hidden"
                                        @click="
                                            openLogin();
                                            showingNavigationDropdown = false;
                                        "
                                    >
                                        {{ $t('common.nav.login') }}
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
            :email="currentEmail"
            @close="showLoginModal = false"
            @switch-to-register="openRegister"
            @switch-to-forgot-password="openForgotPassword"
        />

        <ModalRegister
            :show="showRegisterModal"
            :email="currentEmail"
            @close="showRegisterModal = false"
            @switch-to-login="openLogin"
        />

        <ModalForgotPassword
            :show="showForgotPasswordModal"
            :email="currentEmail"
            @close="showForgotPasswordModal = false"
            @switch-to-login="openLogin"
        />

        <ModalResetPassword
            :show="showResetPasswordModal"
            :email="resetPasswordEmail"
            :token="resetPasswordToken"
            @close="showResetPasswordModal = false"
            @switch-to-login="openLogin"
        />

        <!-- Cookie Banner -->
        <CookieBanner />

        <!-- Mobile Navigation Overlay -->
        <Teleport to="body">
            <div
                v-show="showingNavigationDropdown"
                class="fixed inset-0 z-30 md:hidden"
                @click="showingNavigationDropdown = false"
            ></div>
        </Teleport>

        <!-- Notification Center (handles both server flash messages and client notifications) -->
        <NotificationCenter />

        <!-- Global Alert Dialog -->
        <AlertDialog />
    </div>
</template>
