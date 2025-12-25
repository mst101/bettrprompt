<script setup lang="ts">
import type { AccordionItem } from '@/Components/Base/Accordion.vue';
import Accordion from '@/Components/Base/Accordion.vue';
import type { CarouselItem } from '@/Components/Base/Carousel.vue';
import Carousel from '@/Components/Base/Carousel.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ExampleDemonstration from '@/Components/Common/ExampleDemonstration.vue';
import FeatureCard from '@/Components/Common/FeatureCard.vue';
import HeroCTA from '@/Components/Common/HeroCTA.vue';
import StepCard from '@/Components/Common/StepCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({
    layout: AppLayout,
});

type UseCaseItem = CarouselItem & AccordionItem;

const useCases = ref<UseCaseItem[]>([
    {
        id: 'work-career',
        icon: 'building-office',
        title: 'Work & Career',
        subtitle:
            'Navigate the conversations and decisions that shape your professional life.',
        bullets: [
            'Asking your manager for a promotion',
            'Preparing for a difficult performance review',
            'Delegating a complex project to your team',
            'Writing a proposal that convinces sceptical stakeholders',
        ],
    },
    {
        id: 'personal-decisions',
        icon: 'user',
        title: 'Personal Decisions',
        subtitle: 'Bring clarity to the choices that matter most.',
        bullets: [
            'Deciding whether to accept a job offer or stay put',
            'Working through a major life change with your partner',
            "Setting boundaries with someone who's draining you",
            'Planning a difficult conversation with ageing parents',
        ],
    },
    {
        id: 'learning-development',
        icon: 'book-open',
        title: 'Learning & Development',
        subtitle:
            'Build skills and habits in a way that works for how you learn.',
        bullets: [
            'Creating a study plan for a professional qualification',
            'Getting up to speed on a new industry quickly',
            'Starting to learn a new language or skill from scratch',
            'Building a sustainable reading or learning habit',
        ],
    },
    {
        id: 'creative-projects',
        icon: 'light-bulb',
        title: 'Creative Projects',
        subtitle: 'Get unstuck and find your starting point.',
        bullets: [
            'Planning a novel, screenplay, or creative project',
            "Writing a speech that's funny, heartfelt, or both",
            'Creating content for a new business or side project',
            'Designing a memorable event or celebration',
        ],
    },
    {
        id: 'business-strategy',
        icon: 'trending-up',
        title: 'Business & Strategy',
        subtitle: 'Structure your thinking for high-stakes decisions.',
        bullets: [
            'Developing a marketing strategy for a new product',
            'Analysing whether to expand into a new market',
            'Preparing for a board presentation or investor pitch',
            'Improving team productivity or processes',
        ],
    },
    {
        id: 'relationships-communication',
        icon: 'user-group',
        title: 'Relationships & Communication',
        subtitle: 'Handle the human stuff with more confidence.',
        bullets: [
            'Resolving an ongoing conflict with your partner',
            'Giving difficult feedback without damaging a relationship',
            'Having a meaningful conversation with a teenager about their future',
            "Reconnecting with a friend you've drifted apart from",
        ],
    },
]);

const activeUseCaseIndex = ref(0);
const expandedUseCases = ref<string[]>([]);
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
                    class="text-md mx-auto mt-6 max-w-2xl text-indigo-700 sm:text-lg lg:text-2xl"
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
                        description="We analyse your task and choose the right approach from 60+ proven methodologies. You get structured prompts without needing to become a prompt engineer."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="chat"
                        title="Clarifying Questions That Matter"
                        description="Not sure where to start? We ask the questions you haven't thought of yet - so you get useful answers on the first try, not after three rounds of back-and-forth."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="user"
                        title="Calibrated to How You Think"
                        description="Add your personality type and we'll adapt our questions accordingly. An analytical thinker and a creative thinker working on the same problem need different starting points - we'll show you why we're asking what we're asking."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- "But Can't I Just Ask ChatGPT?" Section -->
            <div class="mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
                    >
                        "But Can't I Just Ask ChatGPT?"
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-indigo-700">
                        You can. But here's what actually happens.
                    </p>
                </div>

                <!-- Side-by-side Comparison -->
                <div
                    class="mx-auto mt-8 grid max-w-5xl gap-6 sm:mt-12 lg:grid-cols-2"
                >
                    <!-- ChatGPT Direct -->
                    <div
                        class="rounded-lg border-2 border-gray-200 bg-gray-50 p-6"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200"
                            >
                                <DynamicIcon
                                    name="chat"
                                    class="h-5 w-5 text-gray-600"
                                />
                            </div>
                            <h3 class="text-lg font-semibold text-gray-700">
                                Asking ChatGPT directly
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    You say:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-700"
                                >
                                    "Help me write a prompt for a marketing
                                    strategy"
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    ChatGPT does:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-600"
                                >
                                    Immediately generates a generic prompt based
                                    on assumptions about your business,
                                    audience, budget, and goals.
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    You get:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-600"
                                >
                                    A reasonable-looking prompt that misses your
                                    actual constraints. You iterate 2-3 times,
                                    adding context you didn't think to include
                                    upfront.
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center gap-2 text-sm text-gray-500"
                        >
                            <DynamicIcon name="clock" class="h-4 w-4" />
                            <span>Multiple rounds to get something useful</span>
                        </div>
                    </div>

                    <!-- BettrPrompt -->
                    <div
                        class="rounded-lg border-2 border-indigo-300 bg-indigo-50 p-6"
                    >
                        <div class="mb-4 flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-200"
                            >
                                <DynamicIcon
                                    name="sparkles"
                                    class="h-5 w-5 text-indigo-600"
                                />
                            </div>
                            <h3 class="text-lg font-semibold text-indigo-900">
                                Using BettrPrompt
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    You say:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-800"
                                >
                                    "I need a marketing strategy"
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    BettrPrompt does:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-700"
                                >
                                    Stops. Asks: "What's your budget? Who's your
                                    audience? What channels are you already
                                    using? What's your biggest acquisition
                                    challenge?"
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    You get:
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-700"
                                >
                                    A prompt built from your actual situation —
                                    including context you wouldn't have thought
                                    to provide, structured using a framework
                                    matched to your task.
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center gap-2 text-sm text-indigo-600"
                        >
                            <DynamicIcon name="check-circle" class="h-4 w-4" />
                            <span>Right first time</span>
                        </div>
                    </div>
                </div>

                <!-- The Key Insight -->
                <div class="mx-auto mt-8 max-w-3xl text-center">
                    <p class="text-lg text-indigo-800">
                        <strong class="text-indigo-900"
                            >The difference isn't the output — it's the
                            process.</strong
                        >
                        ChatGPT answers what you ask. It doesn't know what you
                        don't know to ask. BettrPrompt's job is to surface the
                        questions that change the answer.
                    </p>
                </div>

                <!-- Secondary Objections -->
                <div class="mx-auto mt-12 grid max-w-4xl gap-6 sm:grid-cols-2">
                    <FeatureCard
                        icon="document-text"
                        title="What About Prompt Templates?"
                        description="Templates are one-size-fits-all. We generate prompts for your specific situation through dialogue — your budget, your constraints, your context. No two prompts are the same."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="academic-cap"
                        title="Should I Learn Prompt Engineering?"
                        description="You could. But you're busy. BettrPrompt embeds 60+ frameworks so you don't have to memorise them. Describe what you need — we'll handle the structure."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />
                </div>
            </div>

            <!-- How It Works -->
            <div class="mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
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
                        description="What do you want to achieve? Planning a project? Making a decision? Solving a problem? Describe your goal in plain language - we'll handle the rest."
                        bg-colour="bg-indigo-600"
                    />

                    <StepCard
                        :number="2"
                        title="Answer a Few Questions"
                        description="We dig deeper - asking the questions that surface what you actually need, not what you think you need. This is where vague becomes specific."
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        title="Get Your Optimised Prompt"
                        description="Copy your prompt into ChatGPT, Claude, or any AI tool. It's structured for results and shaped by your answers - not a generic template."
                        bg-colour="bg-indigo-600"
                    />
                </div>
            </div>

            <!-- When to Use BettrPrompt -->
            <div class="mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
                    >
                        When to Use BettrPrompt
                    </h2>
                    <p class="mt-4 text-lg text-indigo-700">
                        If you can describe what you're trying to achieve, we'll
                        help you structure it.
                    </p>
                </div>

                <div class="mt-8">
                    <Carousel
                        v-model="activeUseCaseIndex"
                        :items="useCases"
                        class="hidden md:block"
                    />
                    <Accordion
                        v-model="expandedUseCases"
                        :items="useCases"
                        :allow-multiple="true"
                        class="md:hidden"
                    />
                </div>
            </div>

            <!-- See BettrPrompt in Action -->
            <div class="mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
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
