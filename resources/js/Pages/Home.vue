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
    <Head title="Welcome to AI Buddy" />

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
                    Optimise AI Prompts for
                    <span
                        data-testid="hero-gradient-text"
                        class="bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                        >Your Personality</span
                    >
                </h1>

                <!-- Subheading -->
                <p class="mx-auto mt-6 max-w-2xl text-gray-600 sm:text-xl">
                    Different personality types solve problems differently.
                    Analytical minds need data and structure. Creative minds
                    need context and possibilities. AI Buddy speaks your
                    language - transforming your tasks into optimised prompts
                    that match how
                    <span class="font-bold italic">you</span> think, work, and
                    communicate.
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
                        icon="user"
                        title="Matches How You Think"
                        description="Whether you're an INTJ who needs systematic analysis or an ENFP who thrives on creative exploration, our AI selects frameworks that complement your natural problem-solving style. Same task, different personalities = different optimal approaches."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="sparkles"
                        title="Proven Frameworks, Personalised"
                        description="Our AI analyses your task and personality to select from proven frameworks like SMART (structured goals), RICE (data-driven prioritisation), and COAST (creative exploration). The right framework for the right mind."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                    />

                    <FeatureCard
                        icon="document"
                        title="Learn What Works for You"
                        description="Track your prompts, compare results, and discover which approaches work best for your thinking style. Build a library of techniques that match how you naturally work."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- Built for How You Work -->
            <div class="mt-12 sm:mt-24">
                <div class="text-center">
                    <h2
                        class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl"
                    >
                        Built for How You Work
                    </h2>
                </div>

                <div
                    class="mt-4 grid gap-8 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div class="rounded-lg bg-indigo-50 p-8">
                        <h3 class="text-xl font-bold text-indigo-900">
                            For Analytical Minds
                        </h3>
                        <p class="mt-4 text-gray-700">
                            If you're a Thinking type who loves data, logic, and
                            structure, you'll get prompts with clear frameworks,
                            measurable outcomes, and systematic approaches.
                        </p>
                    </div>

                    <div class="rounded-lg bg-purple-50 p-8">
                        <h3 class="text-xl font-bold text-purple-900">
                            For Creative Explorers
                        </h3>
                        <p class="mt-4 text-gray-700">
                            If you're an Intuitive type who thrives on
                            possibilities and big-picture thinking, you'll get
                            prompts that encourage exploration, connections, and
                            innovative solutions.
                        </p>
                    </div>

                    <div class="rounded-lg bg-indigo-50 p-8">
                        <h3 class="text-xl font-bold text-indigo-900">
                            For Everyone in Between
                        </h3>
                        <p class="mt-4 text-gray-700">
                            With optional trait percentages, AI Buddy calibrates
                            prompts based on trait strength. A highly analytical
                            INTJ (95% Thinking) gets different optimization than
                            a balanced INTJ (55% Thinking) - because not all
                            INTJs think alike.
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
                        title="Tell Us How You Think"
                        description="Select your <a class='underline underline-offset-2' href='https://16personalities.com' target='_blank' rel='noopener noreferrer'>16personalities.com</a> type (like INTJ-A or ENFP-T). Optionally add trait percentages for precision. This psychological insight allows us to optimize your prompt to match how <strong>you</strong> best process information."
                        bg-colour="bg-indigo-600"
                    />

                    <StepCard
                        :number="2"
                        title="Share Your Challenge"
                        description="What do you want to achieve? Whether you're planning a project, making a decision, or solving a problem, describe your goal. Our AI considers both your task and how your personality type naturally approaches it."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Personalised Prompt"
                        description="Receive a prompt crafted for how you think. Analytical types get structured frameworks. Creative types get exploratory approaches. Copy it to any AI tool and experience responses that match your mind."
                        bg-colour="bg-indigo-600"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
