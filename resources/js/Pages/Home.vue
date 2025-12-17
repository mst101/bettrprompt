<script setup lang="ts">
import FeatureCard from '@/Components/FeatureCard.vue';
import LinkButton from '@/Components/LinkButton.vue';
import StepCard from '@/Components/StepCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Welcome to BettrPrompt" />

    <div class="">
        <!-- Hero Section -->
        <div
            class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-16 lg:px-8 lg:py-24"
        >
            <div class="text-center">
                <!-- Main Heading -->
                <h1
                    class="text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl"
                >
                    The Simple Way to Solve
                    <span
                        data-testid="hero-gradient-text"
                        class="bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                        >Complex Problems</span
                    >
                </h1>

                <!-- Subheading -->
                <p class="mx-auto mt-6 max-w-2xl text-gray-600 sm:text-xl">
                    Great AI results start with great questions. BettrPrompt
                    transforms your messy, half-formed ideas into structured
                    prompts that get results.
                </p>

                <!-- CTA Buttons -->
                <div class="mt-10 flex flex-col items-center gap-4">
                    <LinkButton
                        variant="primary"
                        class="text-lg uppercase"
                        size="lg"
                        :href="route('prompt-builder.index')"
                    >
                        {{
                            $page.props.auth?.user
                                ? 'Try It Now'
                                : 'Get Started for Free'
                        }}
                    </LinkButton>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="mt-8 sm:mt-24">
                <h2 class="sr-only">Features</h2>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        icon="sparkles"
                        title="Smart Framework Selection"
                        description="Our AI analyses your task and selects from 60+ proven frameworks (SMART, RICE, COAST, and more). Get structured, effective prompts without needing to study prompt engineering."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="chat"
                        title="Clarifying Questions That Matter"
                        description="Don't know where to start? BettrPrompt asks the right clarifying questions to uncover what you actually need—turning vague ideas into actionable prompts."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                    />

                    <FeatureCard
                        icon="user"
                        title="Calibrated to How You Think"
                        description="Optional: Add your personality type for precision tuning. Analytical minds get data-driven frameworks. Creative minds get exploratory approaches. Same task, different optimal paths."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- Why BettrPrompt Works -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        Why BettrPrompt Works
                    </h2>
                </div>

                <div
                    class="mt-4 grid gap-8 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div class="rounded-lg bg-indigo-50 p-8">
                        <h3 class="text-xl font-bold text-indigo-900">
                            No Blank Page Paralysis
                        </h3>
                        <p class="mt-4 text-indigo-900">
                            Stop staring at an empty ChatGPT window wondering
                            what to type. Answer a few guided questions about
                            your challenge, and receive a complete, structured
                            prompt ready to use immediately.
                        </p>
                    </div>

                    <div class="rounded-lg bg-purple-50 p-8">
                        <h3 class="text-xl font-bold text-purple-900">
                            Proven Frameworks, Zero Learning Curve
                        </h3>
                        <p class="mt-4 text-indigo-900">
                            Access 60+ prompt engineering frameworks—SMART,
                            RICE, COAST, Chain of Thought, and more—without
                            needing to study them. BettrPrompt selects and
                            applies the right approach for your specific task.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-8">
                        <h3 class="text-xl font-bold text-indigo-900">
                            Consistent, Repeatable Results
                        </h3>
                        <p class="mt-4 text-indigo-900">
                            No more hit-or-miss AI outputs. Every prompt is
                            built using established frameworks and best
                            practices, delivering reliable quality every time
                            you need to solve a problem.
                        </p>
                    </div>
                </div>
            </div>

            <!-- How It Works -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2 class="text-3xl font-bold tracking-tight text-gray-900">
                        How It Works
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Three simple steps to better AI prompts
                    </p>
                </div>

                <div class="mt-4 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <StepCard
                        :number="1"
                        title="Describe Your Challenge"
                        description="What do you want to achieve? Planning a project? Making a decision? Solving a problem? Describe your goal in plain language. BettrPrompt handles the complexity of turning it into an effective prompt."
                        bg-colour="bg-indigo-600"
                    />

                    <StepCard
                        :number="2"
                        title="We Analyse and Structure"
                        description="Our AI analyses your task type, identifies the best framework from our library of 60+ proven approaches, and generates clarifying questions to ensure the prompt captures exactly what you need."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Optimised Prompt"
                        description="Receive a complete, structured prompt ready to copy into any AI tool. Optionally, add your personality type for precision calibration—analytical minds get systematic frameworks, creative minds get exploratory approaches."
                        bg-colour="bg-indigo-600"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
