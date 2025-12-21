<script setup lang="ts">
import ExampleDemonstration from '@/Components/Common/ExampleDemonstration.vue';
import FeatureCard from '@/Components/Common/FeatureCard.vue';
import HeroCTA from '@/Components/Common/HeroCTA.vue';
import StepCard from '@/Components/Common/StepCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Welcome to BettrPrompt" />

    <div>
        <!-- Hero Section -->
        <div
            class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-16 lg:px-8 lg:py-24"
        >
            <div class="text-center">
                <!-- Main Heading -->
                <h1
                    class="text-3xl font-bold tracking-tight text-indigo-900 sm:text-4xl lg:text-5xl xl:text-6xl"
                >
                    The Simple Way to Solve
                    <span
                        data-testid="hero-gradient-text"
                        class="bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                        >Complex Problems</span
                    >
                </h1>

                <!-- Subheading -->
                <p
                    class="text-md mx-auto mt-6 max-w-2xl text-indigo-700 sm:text-lg lg:text-xl"
                >
                    Great AI results start with great questions. BettrPrompt
                    turns your vague ideas into structured prompts that actually
                    work - by asking the right questions first.
                </p>

                <!-- CTA Button -->
                <HeroCTA />
            </div>

            <!-- Features Grid -->
            <div class="mt-8 sm:mt-16">
                <h2 class="sr-only">Features</h2>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        icon="sparkles"
                        title="Smart Framework Selection"
                        description="Our AI analyses your task and selects from 60+ proven frameworks. Get structured, effective prompts without needing to study prompt engineering."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="chat"
                        title="Clarifying Questions That Matter"
                        description="Not sure where to start? BettrPrompt asks targeted questions to uncover what you actually need, turning half-formed ideas into prompts that get results."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="user"
                        title="Calibrated to How You Think"
                        description="Add your personality type and we'll adapt our questions to how you think. When your thinking style matters, we'll show you why we're asking what we're asking."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- Why BettrPrompt Works -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
                    >
                        Why BettrPrompt Works
                    </h2>
                </div>

                <div
                    class="mt-4 grid gap-8 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <FeatureCard
                        icon="magnifying-glass"
                        title="Quality Input = Quality Output"
                        description="Vague questions produce generic answers. BettrPrompt helps you articulate precisely what you need, ensuring AI understands your context and delivers relevant, actionable responses instead of surface-level suggestions."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="wrench"
                        title="Chooses the Right Tool for the Job"
                        description="Our system analyses your task and selects the most appropriate framework from our curated library. You'll see a few carefully chosen alternatives - the best fits without the paralysis of too many choices."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="cube"
                        title="Built on Proven Frameworks"
                        description="Every prompt is structured using established methodologies that professionals use to think through complex problems. These aren't experimental approaches, they're time-tested and proven to work."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- How It Works -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold tracking-tight text-indigo-900"
                    >
                        How It Works
                    </h2>
                    <p class="mt-4 text-lg text-indigo-700">
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
                        title="We Analyse and Ask Relevant Questions"
                        description="Our AI analyses your task and selects the best framework from our curated library. Then we ask targeted questions to build the context your prompt needs - so you get results first time, not after three rounds of back-and-forth."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Optimised Prompt"
                        description="Receive a complete prompt ready to copy into ChatGPT, Claude, or any AI tool. Built using proven frameworks and enriched with your specific context. If you've added your personality type, we'll tailor the approach accordingly."
                        bg-colour="bg-indigo-600"
                    />
                </div>
            </div>

            <!-- See BettrPrompt in Action -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold tracking-tight text-indigo-900 sm:text-4xl"
                    >
                        See BettrPrompt in Action
                    </h2>
                    <p class="mt-4 text-lg text-indigo-600">
                        Follow along with a real example: creating a marketing
                        strategy
                    </p>
                </div>

                <div class="mx-auto mt-8 max-w-3xl sm:mt-12">
                    <ExampleDemonstration />
                </div>
            </div>

            <!-- CTA Button -->
            <HeroCTA />
        </div>
    </div>
</template>
