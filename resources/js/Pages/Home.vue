<script setup lang="ts">
import type { AccordionItem } from '@/Components/Base/Accordion.vue';
import Accordion from '@/Components/Base/Accordion.vue';
import type { CarouselItem } from '@/Components/Base/Carousel.vue';
import Carousel from '@/Components/Base/Carousel.vue';
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

            <!-- "But Can't I Just..." Section (Objection Handling) -->
            <div class="mt-24">
                <div class="text-center">
                    <h2
                        class="text-2xl font-bold tracking-tight text-indigo-900 sm:text-3xl lg:text-4xl"
                    >
                        But Can't I Just...?
                    </h2>
                </div>

                <div
                    class="mt-4 grid gap-8 sm:mt-12 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <FeatureCard
                        icon="chat"
                        title="...Ask ChatGPT to Help Me Prompt?"
                        description="You can. But it won't ask the questions you haven't thought of. BettrPrompt interviews you first - surfacing what you actually need before generating anything."
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="document-text"
                        title="...Use a Prompt Template?"
                        description="Templates are generic by design. We generate prompts for your specific situation through dialogue - no two prompts are the same because no two situations are."
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="academic-cap"
                        title="...Learn Prompt Engineering?"
                        description="You could spend hours studying frameworks. Or you could describe what you need and let us handle the structure. We've done the learning so you don't have to."
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
