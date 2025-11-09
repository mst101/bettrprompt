<script setup lang="ts">
import FeatureCard from '@/Components/FeatureCard.vue';
import StepCard from '@/Components/StepCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { inject, onMounted } from 'vue';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    isReturningVisitor: boolean;
    modal?: 'login' | 'register';
}>();

// Access the modal controls from AppLayout
const openLoginModal = inject<() => void>('openLoginModal');
const openRegisterModal = inject<() => void>('openRegisterModal');

// Open modal based on query parameter
onMounted(() => {
    if (props.modal === 'login' && openLoginModal) {
        openLoginModal();
    } else if (props.modal === 'register' && openRegisterModal) {
        openRegisterModal();
    }
});
</script>

<template>
    <Head title="Welcome to AI Buddy" />

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
                        data-testid="hero-gradient-text"
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                        >Your Personality</span
                    >
                </h1>

                <!-- Subheading -->
                <p
                    class="mx-auto mt-6 max-w-2xl text-lg text-gray-600 sm:text-xl"
                >
                    Get better AI responses tailored to your unique personality
                    type. AI Buddy transforms your tasks into optimised prompts
                    based on the <span class="font-bold italic">task</span> you
                    wish to accomplish and your
                    <span class="font-bold italic">personality</span>.
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
                        <!-- Returning visitor -->
                        <button
                            v-if="isReturningVisitor"
                            @click="openLoginModal?.()"
                            class="inline-flex items-center justify-center rounded-md border border-indigo-600 bg-white px-6 py-3 text-base font-medium text-indigo-600 shadow-sm transition hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Welcome back! Log in to continue
                        </button>
                        <!-- New visitor -->
                        <button
                            v-else
                            @click="openRegisterModal?.()"
                            class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Get Started for Free
                        </button>
                    </template>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-24">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        icon="user"
                        title="Personality-Tailored"
                        description="Based on your <a class='underline underline-offset-2 hover:text-purple-800' target='_blank' href='https://www.16personalities.com'>16personalities.com</a> type (INTJ-A, ENFP-J, etc.), we craft prompts that match your communication style and thinking patterns."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="sparkles"
                        title="Intelligent Optimisation"
                        description="Our AI analyses your task and personality to create prompts using proven frameworks like SMART, RICE, and COAST."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                    />

                    <FeatureCard
                        icon="document"
                        title="Save & Track History"
                        description="All your optimised prompts are saved. Review your history, copy successful prompts, and iterate on what works best."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- How It Works -->
            <div class="mt-24">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                        How It Works
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Three simple steps to better AI prompts
                    </p>
                </div>

                <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <StepCard
                        :number="1"
                        title="Enter Your Personality"
                        description="Select your <a class='underline underline-offset-2 hover:text-purple-800' target='_blank' href='https://www.16personalities.com'>16personalities.com</a> type and optionally add trait percentages for even better results."
                        bg-colour="bg-indigo-600"
                    />

                    <StepCard
                        :number="2"
                        title="Describe Your Task"
                        description="Tell us what you're trying to accomplish with AI. Be specific about your goals and requirements."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Optimised Prompt"
                        description="Receive a tailored prompt you can use with any AI tool. Copy it and get better results!"
                        bg-colour="bg-indigo-600"
                    />
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
</template>
