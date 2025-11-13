<script setup lang="ts">
import ButtonPrimary from '@/Components/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/ButtonSecondary.vue';
import FeatureCard from '@/Components/FeatureCard.vue';
import StepCard from '@/Components/StepCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { inject, onMounted } from 'vue';

const props = defineProps<{
    isReturningVisitor: boolean;
    modal?: 'login' | 'register';
}>();

defineOptions({
    layout: AppLayout,
});

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

    <div class="bg-linear-to-br from-indigo-50 via-white to-purple-50">
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
                        class="bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
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
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-6 py-3 text-base font-medium text-white shadow-xs transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-hidden"
                    >
                        Try It Now
                    </Link>
                    <template v-else>
                        <!-- Returning visitor -->
                        <ButtonSecondary
                            v-if="isReturningVisitor"
                            size="lg"
                            @click="openLoginModal?.()"
                        >
                            Welcome back! Log in to continue
                        </ButtonSecondary>
                        <!-- New visitor -->
                        <ButtonPrimary
                            v-else
                            size="lg"
                            @click="openRegisterModal?.()"
                        >
                            Get Started for Free
                        </ButtonPrimary>
                    </template>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-24">
                <h2 class="sr-only">Features</h2>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        icon="user"
                        title="Personality-Tailored"
                        description="Based on your 16personalities.com type (INTJ-A, ENFP-J, etc.), we craft prompts that match your communication style and thinking patterns."
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
                        description="Select your 16personalities.com type and optionally add trait percentages for even better results."
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
    </div>
</template>
