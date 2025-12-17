<script setup lang="ts">
import ExampleDemonstration from '@/Components/ExampleDemonstration.vue';
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
                            Quality Input = Quality Output
                        </h3>
                        <p class="mt-4 text-indigo-900">
                            Vague questions produce generic answers. BettrPrompt
                            helps you articulate precisely what you need,
                            ensuring AI understands your context and delivers
                            relevant, actionable responses instead of
                            surface-level suggestions.
                        </p>
                    </div>

                    <div class="rounded-lg bg-purple-50 p-8">
                        <h3 class="text-xl font-bold text-purple-900">
                            Chooses the Right Tool for the Job
                        </h3>
                        <p class="mt-4 text-purple-900">
                            Our system analyses your task and selects the most
                            appropriate framework from our curated library.
                            You'll see a few carefully chosen alternatives to
                            consider, ensuring the best fit without the
                            paralysis of too many choices.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-8">
                        <h3 class="text-xl font-bold text-indigo-900">
                            Built on Proven Frameworks
                        </h3>
                        <p class="mt-4 text-indigo-900">
                            Every prompt is structured using established
                            methodologies like SMART, RICE, and Chain of
                            Thought. These aren't experimental
                            approaches—they're time-tested frameworks that
                            professionals use to think through complex problems
                            systematically.
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
                        description="Our AI analyses your task type and selects the best framework from our curated library. We then dig deeper by asking targeted questions to build the context needed to craft an effective prompt that captures exactly what you need."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Optimised Prompt"
                        description="Receive a complete, highly effective prompt ready to copy into any AI tool. Built using proven frameworks and enriched with your specific context, it's structured to deliver the results you're looking for. Optionally calibrated to your personality for precision alignment."
                        bg-colour="bg-indigo-600"
                    />
                </div>
            </div>

            <!-- See BettrPrompt in Action -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        See BettrPrompt in Action
                    </h2>
                    <p class="mt-4 text-lg text-gray-600">
                        Follow along with a real example: creating a marketing
                        strategy
                    </p>
                </div>

                <div class="mx-auto mt-8 max-w-3xl sm:mt-12">
                    <ExampleDemonstration />
                </div>
            </div>
        </div>
    </div>
</template>
