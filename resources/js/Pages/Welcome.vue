<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="Welcome to AI Buddy" />

    <div
        class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50"
    >
        <!-- Header -->
        <header class="relative z-10">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <Link
                        href="/"
                        class="flex items-center gap-3 transition hover:opacity-80"
                    >
                        <ApplicationLogo
                            class="h-12 w-12 fill-current text-indigo-600"
                        />
                        <span class="text-2xl font-bold text-gray-800"
                            >AI Buddy</span
                        >
                    </Link>

                    <!-- Auth Links -->
                    <nav v-if="canLogin" class="flex items-center gap-4">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                        >
                            Dashboard
                        </Link>

                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 transition hover:text-indigo-600"
                            >
                                Log in
                            </Link>

                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700"
                            >
                                Get Started
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="relative">
            <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div class="text-center">
                    <!-- Main Heading -->
                    <h1
                        class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl"
                    >
                        Optimise AI Prompts for
                        <span
                            class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                            >Your Personality</span
                        >
                    </h1>

                    <!-- Subheading -->
                    <p
                        class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 sm:text-xl"
                    >
                        Get better AI responses tailored to your unique
                        personality type. AI Buddy transforms your tasks into
                        optimised prompts based on the 16personalities.com
                        framework.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="mt-10 flex justify-center gap-4">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('prompt-optimizer.index')"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Try It Now
                        </Link>
                        <template v-else>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Get Started Free
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-6 py-3 text-base font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Log In
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Features Grid -->
                <div class="mt-24">
                    <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Feature 1 -->
                        <div
                            class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100"
                            >
                                <svg
                                    class="h-6 w-6 text-indigo-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="mt-4 text-lg font-semibold text-gray-900"
                            >
                                Personality-Tailored
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Based on your 16personalities type (INTJ, ENFP,
                                etc.), we craft prompts that match your
                                communication style and thinking patterns.
                            </p>
                        </div>

                        <!-- Feature 2 -->
                        <div
                            class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100"
                            >
                                <svg
                                    class="h-6 w-6 text-purple-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="mt-4 text-lg font-semibold text-gray-900"
                            >
                                Intelligent Optimisation
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Our AI analyses your task and personality to
                                create prompts using proven frameworks like
                                SMART, RICE, and COAST.
                            </p>
                        </div>

                        <!-- Feature 3 -->
                        <div
                            class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100"
                        >
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100"
                            >
                                <svg
                                    class="h-6 w-6 text-indigo-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <h3
                                class="mt-4 text-lg font-semibold text-gray-900"
                            >
                                Save & Track History
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                All your optimised prompts are saved. Review
                                your history, copy successful prompts, and
                                iterate on what works best.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- How It Works -->
                <div class="mt-24">
                    <div class="text-center">
                        <h2
                            class="text-3xl font-bold tracking-tight text-gray-900"
                        >
                            How It Works
                        </h2>
                        <p class="mt-4 text-lg text-gray-600">
                            Three simple steps to better AI prompts
                        </p>
                    </div>

                    <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        <!-- Step 1 -->
                        <div class="relative text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white"
                            >
                                1
                            </div>
                            <h3
                                class="mt-6 text-lg font-semibold text-gray-900"
                            >
                                Enter Your Personality
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Select your 16personalities type and optionally
                                add trait percentages for even better results.
                            </p>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-purple-600 text-xl font-bold text-white"
                            >
                                2
                            </div>
                            <h3
                                class="mt-6 text-lg font-semibold text-gray-900"
                            >
                                Describe Your Task
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Tell us what you're trying to accomplish with
                                AI. Be specific about your goals and
                                requirements.
                            </p>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative text-center">
                            <div
                                class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-600 text-xl font-bold text-white"
                            >
                                3
                            </div>
                            <h3
                                class="mt-6 text-lg font-semibold text-gray-900"
                            >
                                Get Your Optimised Prompt
                            </h3>
                            <p class="mt-2 text-sm text-gray-600">
                                Receive a tailored prompt you can use with any
                                AI tool. Copy it and get better results!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <p class="text-center text-sm text-gray-500">
                    AI Buddy - Personalised AI Prompt Optimisation
                </p>
            </div>
        </footer>
    </div>
</template>
