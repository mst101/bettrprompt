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
                <div class="rounded bg-white p-3 text-indigo-700">
                    I want to create a marketing strategy for my business
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
                    <p class="mb-2 font-medium text-purple-950">
                        What's your business type? Please specify industry, or
                        product/service.
                    </p>
                    <div class="rounded bg-white p-2 text-indigo-800">
                        B2B SaaS startup offering project management and team
                        collaboration tools
                    </div>
                </div>
                <div>
                    <p class="mb-2 font-medium text-purple-950">
                        Who is the target audience for this marketing strategy?
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-4 w-4 items-center justify-center rounded-full bg-purple-600"
                            >
                                <div class="h-2 w-2 rounded-full bg-white" />
                            </div>
                            <span class="text-purple-950"
                                >Early stage startup</span
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
            description="We've chosen the best approach for your task"
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <div
                    class="rounded bg-indigo-100 p-2 font-medium text-indigo-900"
                >
                    RACE Framework
                </div>
                <div class="flex flex-wrap gap-1">
                    <span
                        class="rounded-lg bg-blue-200 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Reach</span
                    >
                    <span
                        class="rounded-lg bg-blue-200 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Act</span
                    >
                    <span
                        class="rounded-lg bg-blue-200 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Convert</span
                    >
                    <span
                        class="rounded-lg bg-blue-200 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >Engage</span
                    >
                </div>
                <p class="text-indigo-700">
                    Best for creating customer-focused marketing strategies that
                    drive measurable results
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
                    <div class="rounded bg-white p-2 text-purple-950">
                        Low brand awareness in a crowded market; struggling to
                        reach decision-makers
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        2. What marketing channels do you currently use?
                    </p>
                    <div class="rounded bg-white p-2 text-purple-950">
                        Email list, LinkedIn, and some paid ads
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        3. What is your monthly marketing budget?
                    </p>
                    <div class="rounded bg-white p-2 text-purple-950">
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
                <div class="rounded bg-white p-2 text-sm text-indigo-800">
                    "Create a comprehensive marketing strategy for a B2B SaaS
                    early-stage startup using the RACE framework (Reach, Act,
                    Convert, Engage). Target audience: mid-size tech companies.
                    Current channels: Email, LinkedIn, paid ads. Budget: £2,000
                    - £5,000/month..."
                </div>
                <div class="pt-1">
                    <AIProviderLinks prompt="" />
                </div>
            </div>
        </ExampleStageCard>
    </div>
</template>
