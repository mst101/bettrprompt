<script setup lang="ts">
import DynamicIcon from '@/Components/DynamicIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome to AI Buddy" />

    <AppLayout>
        <div class="bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <!-- Hero Section -->
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
                            <p class="text-lg text-gray-600">
                                Get started by logging in or creating an account
                                above
                            </p>
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
                                <DynamicIcon
                                    name="user"
                                    class="h-6 w-6 text-indigo-600"
                                />
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
                                <DynamicIcon
                                    name="sparkles"
                                    class="h-6 w-6 text-purple-600"
                                />
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
                                <DynamicIcon
                                    name="document"
                                    class="h-6 w-6 text-indigo-600"
                                />
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

            <!-- Footer -->
            <footer class="border-t border-gray-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <p class="text-center text-sm text-gray-500">
                        AI Buddy - Personalised AI Prompt Optimisation
                    </p>
                </div>
            </footer>
        </div>
    </AppLayout>
</template>
