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
            :title="$t('components.common.exampleDemonstration.stage1.title')"
            :description="
                $t('components.common.exampleDemonstration.stage1.description')
            "
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <label class="font-medium text-indigo-700">{{
                    $t('components.common.exampleDemonstration.stage1.label')
                }}</label>
                <div
                    class="mt-2 rounded border border-indigo-100 bg-white p-2 text-indigo-800 dark:bg-indigo-100"
                >
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage1.example',
                        )
                    }}
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 2: Quick Queries -->
        <ExampleStageCard
            :stage="2"
            :title="$t('components.common.exampleDemonstration.stage2.title')"
            :description="
                $t('components.common.exampleDemonstration.stage2.description')
            "
            bg-colour="bg-purple-600"
            content-bg-colour="bg-purple-50"
        >
            <div class="space-y-3">
                <div>
                    <p class="font-medium text-purple-950">
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage2.question1',
                            )
                        }}
                    </p>
                    <div
                        class="mt-2 rounded border border-purple-100 bg-white p-2 text-indigo-800 dark:bg-purple-100"
                    >
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage2.answer1',
                            )
                        }}
                    </div>
                </div>
                <div>
                    <p class="font-medium text-purple-950">
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage2.question2',
                            )
                        }}
                    </p>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div
                                class="flex h-4 w-4 items-center justify-center rounded-full bg-purple-600"
                            >
                                <div class="h-2 w-2 rounded-full bg-white" />
                            </div>
                            <span class="text-purple-950">{{
                                $t(
                                    'components.common.exampleDemonstration.stage2.answer2',
                                )
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 3: Framework -->
        <ExampleStageCard
            :stage="3"
            :title="$t('components.common.exampleDemonstration.stage3.title')"
            :description="
                $t('components.common.exampleDemonstration.stage3.description')
            "
            bg-colour="bg-indigo-600"
            content-bg-colour="bg-indigo-50"
        >
            <div class="space-y-2">
                <div
                    class="rounded bg-indigo-100 p-2 font-medium text-indigo-900"
                >
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage3.frameworkName',
                        )
                    }}
                </div>
                <div class="flex flex-wrap gap-2">
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >{{
                            $t(
                                'components.common.exampleDemonstration.stage3.chipChallenge',
                            )
                        }}</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >{{
                            $t(
                                'components.common.exampleDemonstration.stage3.chipObjective',
                            )
                        }}</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >{{
                            $t(
                                'components.common.exampleDemonstration.stage3.chipActions',
                            )
                        }}</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >{{
                            $t(
                                'components.common.exampleDemonstration.stage3.chipStrategy',
                            )
                        }}</span
                    >
                    <span
                        class="rounded-lg bg-blue-100 px-3 py-1 text-blue-900 dark:bg-blue-300"
                        >{{
                            $t(
                                'components.common.exampleDemonstration.stage3.chipTactics',
                            )
                        }}</span
                    >
                </div>
                <p class="text-indigo-700">
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage3.rationale',
                        )
                    }}
                </p>
            </div>
        </ExampleStageCard>

        <!-- Stage 4: Clarifying Questions -->
        <ExampleStageCard
            :stage="4"
            :title="$t('components.common.exampleDemonstration.stage4.title')"
            :description="
                $t('components.common.exampleDemonstration.stage4.description')
            "
            bg-colour="bg-purple-600"
            content-bg-colour="bg-purple-50"
        >
            <div class="space-y-3">
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.question1',
                            )
                        }}
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.answer1',
                            )
                        }}
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.question2',
                            )
                        }}
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.answer2',
                            )
                        }}
                    </div>
                </div>
                <div>
                    <p class="mb-1 font-medium text-purple-950">
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.question3',
                            )
                        }}
                    </p>
                    <div
                        class="rounded border border-purple-100 bg-white p-2 text-purple-950 dark:bg-purple-100"
                    >
                        {{
                            $t(
                                'components.common.exampleDemonstration.stage4.answer3',
                            )
                        }}
                    </div>
                </div>
            </div>
        </ExampleStageCard>

        <!-- Stage 5: Optimised Prompt -->
        <ExampleStageCard
            :stage="5"
            :title="$t('components.common.exampleDemonstration.stage5.title')"
            :description="
                $t('components.common.exampleDemonstration.stage5.description')
            "
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
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage5.ready',
                        )
                    }}
                </div>
                <div
                    class="rounded border border-indigo-100 bg-white p-2 text-sm text-indigo-800 dark:bg-indigo-100"
                >
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptIntro',
                        )
                    }}
                    <br />
                    <strong>{{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptChallengeLabel',
                        )
                    }}</strong>
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptChallengeText',
                        )
                    }}
                    <br />
                    <strong>{{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptObjectiveLabel',
                        )
                    }}</strong>
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptObjectiveText',
                        )
                    }}
                    <br />
                    {{
                        $t(
                            'components.common.exampleDemonstration.stage5.promptEtc',
                        )
                    }}
                </div>
                <div class="pt-1">
                    <AIProviderLinks prompt="" />
                </div>
            </div>
        </ExampleStageCard>
    </div>
</template>
