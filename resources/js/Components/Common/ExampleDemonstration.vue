<script setup lang="ts">
import ExampleStageCard from '@/Components/Common/ExampleStageCard.vue';
</script>

<template>
    <div class="space-y-0">
        <!-- Stage 1: Enter Task -->
        <ExampleStageCard
            :stage="1"
            title="Enter Your Task"
            description="Start with a simple description"
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <label class="text-xs font-medium text-indigo-700 sm:text-sm"
                    >Your task:</label
                >
                <div
                    class="rounded bg-white p-3 text-xs text-indigo-700 sm:text-sm"
                >
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
                    <p
                        class="mb-2 text-xs font-medium text-purple-950 sm:text-sm"
                    >
                        What's your business type?
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded-full px-3 py-1 text-xs text-purple-600"
                            >Solopreneur</span
                        >
                        <span
                            class="rounded-full bg-purple-600 px-3 py-1 text-xs text-white"
                            >B2B SaaS</span
                        >
                        <span
                            class="rounded-full px-3 py-1 text-xs text-purple-600"
                            >E-commerce</span
                        >
                    </div>
                </div>
                <div>
                    <p
                        class="mb-2 text-xs font-medium text-purple-950 sm:text-sm"
                    >
                        Company stage?
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 text-xs sm:text-sm">
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
                    class="rounded bg-indigo-100 p-2 text-xs font-medium text-indigo-900 sm:text-sm"
                >
                    RACE Framework
                </div>
                <div class="flex flex-wrap gap-1">
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800"
                        >Reach</span
                    >
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800"
                        >Act</span
                    >
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800"
                        >Convert</span
                    >
                    <span
                        class="rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-800"
                        >Engage</span
                    >
                </div>
                <p class="text-xs text-indigo-700">
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
            <div class="space-y-3 text-xs">
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        1. Who is your target audience?
                    </p>
                    <div class="rounded bg-white p-2 text-purple-950">
                        Mid-size tech companies looking to streamline operations
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
            bg-colour="bg-green-600"
            content-bg-colour="bg-green-50"
        >
            <div class="space-y-2">
                <div
                    class="flex items-center gap-2 text-xs font-medium text-green-700 sm:text-sm"
                >
                    <svg
                        class="h-5 w-5"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Your prompt is ready!
                </div>
                <div class="rounded bg-white p-2 text-xs text-indigo-800">
                    "Create a comprehensive marketing strategy for a B2B SaaS
                    early-stage startup using the RACE framework (Reach, Act,
                    Convert, Engage). Target audience: mid-size tech companies.
                    Current channels: Email, LinkedIn, paid ads. Budget: £2,000
                    - £5,000/month..."
                </div>
                <div class="pt-1">
                    <span
                        class="inline-flex items-center rounded bg-indigo-600 px-3 py-1 text-xs font-medium tracking-wider text-white uppercase"
                        >Copy Prompt</span
                    >
                </div>
            </div>
        </ExampleStageCard>
    </div>
</template>
