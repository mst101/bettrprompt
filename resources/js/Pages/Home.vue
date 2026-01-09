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
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

defineOptions({
    layout: AppLayout,
});

const { t, tm } = useI18n({ useScope: 'global' });

const pageTitle = computed(() => t('home.title'));

type UseCaseItem = CarouselItem & AccordionItem;

// Build use cases from i18n translations
const useCases = computed<UseCaseItem[]>(() => {
    return [
        {
            id: 'work-career',
            icon: 'building-office',
            title: t('home.useCases.workCareer.title'),
            subtitle: t('home.useCases.workCareer.subtitle'),
            bullets: tm('home.useCases.workCareer.bullets') as string[],
        },
        {
            id: 'personal-decisions',
            icon: 'user',
            title: t('home.useCases.personalDecisions.title'),
            subtitle: t('home.useCases.personalDecisions.subtitle'),
            bullets: tm('home.useCases.personalDecisions.bullets') as string[],
        },
        {
            id: 'learning-development',
            icon: 'book-open',
            title: t('home.useCases.learningDevelopment.title'),
            subtitle: t('home.useCases.learningDevelopment.subtitle'),
            bullets: tm(
                'home.useCases.learningDevelopment.bullets',
            ) as string[],
        },
        {
            id: 'creative-projects',
            icon: 'light-bulb',
            title: t('home.useCases.creativeProjects.title'),
            subtitle: t('home.useCases.creativeProjects.subtitle'),
            bullets: tm('home.useCases.creativeProjects.bullets') as string[],
        },
        {
            id: 'business-strategy',
            icon: 'trending-up',
            title: t('home.useCases.businessStrategy.title'),
            subtitle: t('home.useCases.businessStrategy.subtitle'),
            bullets: tm('home.useCases.businessStrategy.bullets') as string[],
        },
        {
            id: 'relationships-communication',
            icon: 'user-group',
            title: t('home.useCases.relationshipsCommunication.title'),
            subtitle: t('home.useCases.relationshipsCommunication.subtitle'),
            bullets: tm(
                'home.useCases.relationshipsCommunication.bullets',
            ) as string[],
        },
    ];
});

const activeUseCaseIndex = ref(0);
const expandedUseCases = ref<string[]>([]);
</script>

<template>
    <Head :title="pageTitle" />

    <div>
        <!-- Hero Section -->
        <div
            class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-16 lg:px-8 lg:py-24"
        >
            <div class="text-center">
                <!-- Main Heading -->
                <h1
                    class="text-3xl font-bold tracking-tight text-indigo-900 sm:text-4xl lg:text-5xl xl:text-6xl"
                >
                    {{ $t('home.hero.title') }}
                    <span
                        data-testid="hero-gradient-text"
                        class="bg-linear-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent"
                        >{{ $t('home.hero.titleGradient') }}</span
                    >
                    {{ $t('home.hero.titleSuffix') }}
                </h1>

                <!-- Subheading -->
                <p
                    class="text-md mx-auto mt-6 max-w-2xl text-indigo-700 sm:text-lg lg:text-2xl"
                >
                    {{ $t('home.hero.subtitle') }}
                </p>

                <!-- CTA Button -->
                <HeroCTA />
            </div>

            <!-- Features Grid -->
            <div class="mt-8 sm:mt-16">
                <h2 class="sr-only">{{ $t('home.features.heading') }}</h2>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <FeatureCard
                        icon="sparkles"
                        :title="$t('home.features.feature1.title')"
                        :description="$t('home.features.feature1.description')"
                        icon-bg-colour="bg-indigo-100"
                        icon-colour="text-indigo-600"
                    />

                    <FeatureCard
                        icon="chat"
                        :title="$t('home.features.feature2.title')"
                        :description="$t('home.features.feature2.description')"
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="user"
                        :title="$t('home.features.feature3.title')"
                        :description="$t('home.features.feature3.description')"
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
                        {{ $t('home.comparison.title') }}
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-indigo-700">
                        {{ $t('home.comparison.subtitle') }}
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
                                {{ $t('home.comparison.chatgpt.title') }}
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $t('home.comparison.labels.you') }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-700"
                                >
                                    "{{
                                        $t(
                                            'home.comparison.chatgpt.yourPrompt',
                                        )
                                    }}"
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $t('home.comparison.chatgpt.label') }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-600"
                                >
                                    {{ $t('home.comparison.chatgpt.response') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $t('home.comparison.labels.youGet') }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-gray-600"
                                >
                                    {{ $t('home.comparison.chatgpt.result') }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center gap-2 text-sm text-gray-500"
                        >
                            <DynamicIcon name="clock" class="h-4 w-4" />
                            <span>{{
                                $t('home.comparison.chatgpt.footer')
                            }}</span>
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
                                    name="hash"
                                    class="h-5 w-5 text-indigo-600"
                                />
                            </div>
                            <h3 class="text-lg font-semibold text-indigo-900">
                                {{ $t('home.comparison.bettrprompt.title') }}
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    {{ $t('home.comparison.labels.you') }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-800"
                                >
                                    "{{
                                        $t(
                                            'home.comparison.bettrprompt.yourPrompt',
                                        )
                                    }}"
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    {{
                                        $t('home.comparison.bettrprompt.label')
                                    }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-700"
                                >
                                    {{
                                        $t(
                                            'home.comparison.bettrprompt.response',
                                        )
                                    }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-600">
                                    {{ $t('home.comparison.labels.youGet') }}
                                </p>
                                <p
                                    class="mt-1 rounded bg-white p-3 text-indigo-700"
                                >
                                    {{
                                        $t('home.comparison.bettrprompt.result')
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="mt-4 flex items-center gap-2 text-sm text-indigo-600"
                        >
                            <DynamicIcon name="check-circle" class="h-4 w-4" />
                            <span>{{
                                $t('home.comparison.bettrprompt.footer')
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- The Key Insight -->
                <div class="mx-auto mt-8 max-w-3xl text-center">
                    <i18n-t
                        keypath="home.comparison.keyInsight"
                        scope="global"
                        tag="p"
                        class="text-lg text-indigo-800"
                    >
                        <strong>{{
                            $t('home.comparison.keyInsightEmphasis')
                        }}</strong>
                    </i18n-t>
                </div>

                <!-- Secondary Objections -->
                <div class="mx-auto mt-12 grid max-w-4xl gap-6 sm:grid-cols-2">
                    <FeatureCard
                        icon="document-text"
                        :title="$t('home.objections.templates.title')"
                        :description="
                            $t('home.objections.templates.description')
                        "
                        icon-bg-colour="bg-purple-100"
                        icon-colour="text-purple-600"
                        dark-bg-colour="dark:bg-purple-50"
                    />

                    <FeatureCard
                        icon="academic-cap"
                        :title="$t('home.objections.learning.title')"
                        :description="
                            $t('home.objections.learning.description')
                        "
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
                        {{ $t('home.howItWorks.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-indigo-700">
                        {{ $t('home.howItWorks.subtitle') }}
                    </p>
                </div>

                <div class="mt-4 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <StepCard
                        :number="1"
                        :title="$t('home.howItWorks.step1.title')"
                        :description="$t('home.howItWorks.step1.description')"
                        bg-colour="bg-indigo-600"
                    />

                    <StepCard
                        :number="2"
                        :title="$t('home.howItWorks.step2.title')"
                        :description="$t('home.howItWorks.step2.description')"
                        bg-colour="bg-purple-600"
                    />

                    <StepCard
                        :number="3"
                        :title="$t('home.howItWorks.step3.title')"
                        :description="$t('home.howItWorks.step3.description')"
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
                        {{ $t('home.useCases.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-indigo-700">
                        {{ $t('home.useCases.subtitle') }}
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
                        {{ $t('home.example.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-indigo-600">
                        {{ $t('home.example.subtitle') }}
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
