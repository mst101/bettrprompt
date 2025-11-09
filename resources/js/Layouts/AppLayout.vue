<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import CookieBanner from '@/Components/CookieBanner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import Footer from '@/Components/Footer.vue';
import ForgotPasswordModal from '@/Components/ForgotPasswordModal.vue';
import LoginModal from '@/Components/LoginModal.vue';
import NavLink from '@/Components/NavLink.vue';
import RegisterModal from '@/Components/RegisterModal.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, provide, ref } from 'vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const showingNavigationDropdown = ref(false);
const showLoginModal = ref(false);
const showRegisterModal = ref(false);
const showForgotPasswordModal = ref(false);

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

// Provide modal controls to child components
provide('openLoginModal', openLogin);
provide('openRegisterModal', openRegister);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-gray-100">
        <div class="flex flex-1 flex-col">
            <nav class="border-b border-gray-100 bg-white">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('home')"
                                    class="flex items-center gap-1 transition hover:opacity-80"
                                    @click="showingNavigationDropdown = false"
                                >
                                    <ApplicationLogo
                                        class="block h-10 w-auto fill-current text-indigo-600"
                                    />
                                    <span
                                        class="mt-2 text-xl font-bold text-gray-800"
                                        >AI Buddy</span
                                    >
                                </Link>
                            </div>

                            <!-- Navigation Links (Authenticated) -->
                            <div
                                v-if="isAuthenticated"
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('prompt-optimizer.index')"
                                    :active="
                                        route().current(
                                            'prompt-optimizer.index',
                                        )
                                    "
                                >
                                    Prompt Optimiser
                                </NavLink>
                                <NavLink
                                    :href="route('prompt-optimizer.history')"
                                    :active="
                                        route().current(
                                            'prompt-optimizer.history',
                                        )
                                    "
                                >
                                    Prompt History
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right Side Navigation -->
                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Authenticated User Dropdown -->
                            <div v-if="isAuthenticated" class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm leading-4 font-medium text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-hidden"
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
                                <button
                                    @click="openLogin"
                                    class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                                >
                                    Log in
                                </button>

                                <button
                                    @click="openRegister"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-xs transition hover:bg-indigo-700"
                                >
                                    Get Started
                                </button>
                            </nav>
                        </div>

                        <!-- Hamburger (Mobile) -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-hidden"
                                aria-label="Toggle navigation menu"
                            >
                                <DynamicIcon
                                    v-if="!showingNavigationDropdown"
                                    name="bars-3"
                                    class="h-6 w-6"
                                />
                                <DynamicIcon
                                    v-else
                                    name="x-mark"
                                    class="h-6 w-6"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <!-- Authenticated Mobile Nav -->
                    <template v-if="isAuthenticated">
                        <div class="space-y-1 pt-2 pb-3">
                            <ResponsiveNavLink
                                :href="route('prompt-optimizer.index')"
                                :active="
                                    route().current('prompt-optimizer.index')
                                "
                                @click="showingNavigationDropdown = false"
                            >
                                Prompt Optimiser
                            </ResponsiveNavLink>

                            <ResponsiveNavLink
                                :href="route('prompt-optimizer.history')"
                                :active="
                                    route().current('prompt-optimizer.history')
                                "
                                @click="showingNavigationDropdown = false"
                            >
                                Prompt History
                            </ResponsiveNavLink>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="border-t border-gray-200 pt-4 pb-1">
                            <div class="px-4">
                                <div
                                    class="text-base font-medium text-gray-800"
                                >
                                    {{ $page.props.auth!.user!.name }}
                                </div>
                                <div class="text-sm font-medium text-gray-500">
                                    {{ $page.props.auth!.user!.email }}
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <ResponsiveNavLink
                                    :href="route('profile.edit')"
                                    @click="showingNavigationDropdown = false"
                                >
                                    Profile
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    @click="showingNavigationDropdown = false"
                                >
                                    Log Out
                                </ResponsiveNavLink>
                            </div>
                        </div>
                    </template>

                    <!-- Guest Mobile Nav -->
                    <template v-else>
                        <div class="space-y-1 pt-2 pb-3">
                            <button
                                @click="
                                    openLogin();
                                    showingNavigationDropdown = false;
                                "
                                class="block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-hidden"
                            >
                                Log in
                            </button>
                            <button
                                @click="
                                    openRegister();
                                    showingNavigationDropdown = false;
                                "
                                class="block w-full border-l-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-gray-600 transition duration-150 ease-in-out hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:border-gray-300 focus:bg-gray-50 focus:text-gray-800 focus:outline-hidden"
                            >
                                Get Started
                            </button>
                        </div>
                    </template>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1">
                <slot />
            </main>
        </div>

        <!-- Footer -->
        <Footer />

        <!-- Auth Modals -->
        <LoginModal
            :show="showLoginModal"
            @close="showLoginModal = false"
            @switch-to-register="openRegister"
            @switch-to-forgot-password="openForgotPassword"
        />

        <RegisterModal
            :show="showRegisterModal"
            @close="showRegisterModal = false"
            @switch-to-login="openLogin"
        />

        <ForgotPasswordModal
            :show="showForgotPasswordModal"
            @close="showForgotPasswordModal = false"
            @switch-to-login="openLogin"
        />

        <!-- Cookie Banner -->
        <CookieBanner />
    </div>
</template>
