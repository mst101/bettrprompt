<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import type {
    AmplifiedTraitResource,
    CounterbalancedTraitResource,
    NeutralTraitResource,
} from '@/Types';

interface Props {
    alignment: {
        amplified: AmplifiedTraitResource[];
        counterbalanced: CounterbalancedTraitResource[];
        neutral: NeutralTraitResource[];
    };
}

defineProps<Props>();
</script>

<template>
    <Card class="space-y-6">
        <h2 class="text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.taskTraitAlignment.title') }}
        </h2>

        <!-- Amplified Traits -->
        <div v-if="alignment.amplified && alignment.amplified.length > 0">
            <h3 class="mb-3 text-sm font-medium text-blue-700">
                {{
                    $t('promptBuilder.components.taskTraitAlignment.amplified')
                }}
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.amplified"
                    :key="index"
                    class="rounded-lg bg-blue-100 p-4"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <div
                            class="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-medium text-blue-50"
                        >
                            {{ item.trait }}
                        </div>
                        <div
                            class="ml-2 flex-1 text-sm font-medium text-blue-900"
                        >
                            {{
                                $t(
                                    'promptBuilder.components.taskTraitAlignment.alignsWith',
                                    {
                                        requirement: item.requirementAligned,
                                    },
                                )
                            }}
                        </div>
                    </div>
                    <p class="text-sm text-blue-800">
                        {{ item.reason }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Counterbalanced Traits -->
        <div
            v-if="
                alignment.counterbalanced &&
                alignment.counterbalanced.length > 0
            "
        >
            <h3 class="mb-3 text-sm font-medium text-indigo-700">
                {{
                    $t(
                        'promptBuilder.components.taskTraitAlignment.counterbalanced',
                    )
                }}
            </h3>
            <div class="space-y-3">
                <div
                    v-for="(item, index) in alignment.counterbalanced"
                    :key="index"
                    class="rounded-lg bg-indigo-50 p-4"
                >
                    <div class="mb-2">
                        <span
                            class="inline-block rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-medium text-indigo-50"
                        >
                            {{ item.trait }}
                        </span>
                        <span class="ml-2 text-sm font-medium text-indigo-900">
                            {{
                                $t(
                                    'promptBuilder.components.taskTraitAlignment.opposes',
                                    {
                                        requirement: item.requirementOpposed,
                                    },
                                )
                            }}
                        </span>
                    </div>
                    <p class="mb-2 text-sm text-indigo-700">
                        {{ item.reason }}
                    </p>
                    <div
                        class="rounded border-l-4 border-indigo-400 bg-indigo-100 p-3"
                    >
                        <p class="text-xs font-medium text-indigo-900">
                            {{
                                $t(
                                    'promptBuilder.components.taskTraitAlignment.adjustmentApplied',
                                )
                            }}
                        </p>
                        <p class="mt-1 text-sm text-indigo-950">
                            {{ item.injection }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Neutral Traits -->
        <div v-if="alignment.neutral && alignment.neutral.length > 0">
            <details class="group">
                <summary
                    class="cursor-pointer text-sm font-medium text-indigo-600 hover:text-indigo-700"
                >
                    {{
                        $t(
                            'promptBuilder.components.taskTraitAlignment.neutral',
                            {
                                count: alignment.neutral.length,
                            },
                        )
                    }}
                </summary>
                <div class="mt-3 space-y-2">
                    <div
                        v-for="(item, index) in alignment.neutral"
                        :key="index"
                        class="rounded-lg border border-indigo-100 bg-indigo-50 p-3"
                    >
                        <span
                            class="inline-block rounded-full bg-indigo-400 px-2 py-0.5 text-xs font-medium text-white"
                        >
                            {{ item.trait }}
                        </span>
                        <p class="mt-1 text-sm text-indigo-600">
                            {{ item.reason }}
                        </p>
                    </div>
                </div>
            </details>
        </div>
    </Card>
</template>
