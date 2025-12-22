<script setup lang="ts">
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ExampleStageCard from '@/Components/Common/ExampleStageCard.vue';
import AIProviderLinks from '@/Components/Features/PromptBuilder/OptimisedPrompt/AIProviderLinks.vue';
</script>

<template>
    <div class="space-y-0">
        <!-- Stage 1: Enter Task -->
        <ExampleStageCard
            :stage="1"
            title="Enter Your Task"
            description="Describe what you want to achieve <span>(the more information you provide, the better)</span>"
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <label class="font-medium text-indigo-700">Your task:</label>
                <div
                    class="mt-2 rounded border border-purple-100 bg-white p-2 text-indigo-800 dark:bg-indigo-100"
                >
                    I want to create a marketing strategy for my business.
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 2: Quick Queries -->
        <ExampleStageCard
            :stage="2"
            title="Answer Quick Queries"
            description="A few quick questions to understand your context"
            bg-colour="bg-purple-600"
            content-bg-colour="bg-purple-50"
        >
            <div class="space-y-3">
                <div>
                    <p class="font-medium text-purple-950">
                        What type of business are you in, and what are your main
                        products or services?
                    </p>
                    <div
                        class="mt-2 rounded border border-purple-100 bg-white p-2 text-indigo-800 dark:bg-purple-100"
                    >
                        We are a SaaS startup offering project management and
                        team collaboration tools.
                    </div>
                </div>
                <div>
                    <p class="font-medium text-purple-950">
                        Who is your target customer audience?
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-4 w-4 items-center justify-center rounded-full bg-purple-600"
                            >
                                <div class="h-2 w-2 rounded-full bg-white" />
                            </div>
                            <span class="text-purple-950"
                                >Business-to-Business (B2B)</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 3: Framework -->
        <ExampleStageCard
            :stage="3"
            title="Review Selected Framework"
            description="We recommend which framework to use (and suggest a couple of alternatives)"
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <div
                    class="rounded bg-indigo-100 p-2 font-medium text-indigo-900"
                >
                    COAST Framework
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Challenge</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Objective</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Actions</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Strategy</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Tactics</span
                    >
                </div>
                <p class="text-indigo-700">
                    COAST is the optimal framework for this task. It directly
                    maps to strategy development with a clear progression from
                    problem definition.
                </p>
            </div>
        </ExampleStageCard>

        <!-- Stage 4: Clarifying Questions -->
        <ExampleStageCard
            :stage="4"
            title="Answer Clarifying Questions"
            description="Help us understand the details"
            bg-colour="bg-purple-600"
            content-bg-colour="bg-purple-50"
        >
            <div class="space-y-3">
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        1. What's your main customer acquisition challenge right
                        now?
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        Low brand awareness in a crowded market; struggling to
                        reach decision-makers
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        2. What marketing channels do you currently use?
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        Email list, LinkedIn, and some paid ads
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        3. What is your monthly marketing budget?
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        £2,000 - £5,000
                    </div>
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 5: Optimised Prompt -->
        <ExampleStageCard
            :stage="5"
            title="Receive Your Optimised Prompt"
            description="Copy and use in any AI tool"
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <div
                    class="flex items-center gap-2 font-medium text-indigo-700"
                >
                    <DynamicIcon
                        name="check-circle"
                        class="h-5 w-5 text-green-600"
                    />
                    Your prompt is ready!
                </div>
                <div
                    class="rounded border border-indigo-100 bg-white p-2 text-sm text-indigo-800 dark:bg-indigo-100"
                >
                    Using the COAST framework, develop a marketing strategy for
                    our B2B SaaS project management platform.
                    <br />
                    <strong>Challenge:</strong> Low brand awareness in a crowded
                    market; struggling to reach decision-makers at mid-size tech
                    companies.
                    <br />
                    <strong>Objective:</strong> Increase qualified leads and
                    establish thought leadership.
                    <br />
                    etc...
                </div>
                <div class="pt-1">
                    <AIProviderLinks prompt="" />
                </div>
            </div>
        </ExampleStageCard>
    </div>
</template>
