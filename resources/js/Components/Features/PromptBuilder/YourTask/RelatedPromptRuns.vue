<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import LinkText from '@/Components/Base/LinkText.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import type { PromptRunResource } from '@/Types';
import { truncateText } from '@/Utils/formatting/formatters';

interface Props {
    parent?: PromptRunResource | null;
    children?: readonly PromptRunResource[];
}

const props = defineProps<Props>();
const { countryRoute } = useCountryRoute();

const hasRelations =
    (props.parent && props.parent.id) ||
    (props.children && props.children.length > 0);
</script>

<template>
    <Card v-if="hasRelations" class="mt-6">
        <h3 class="mb-4 text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.relatedPromptRuns.title') }}
        </h3>

        <div class="space-y-4">
            <!-- Parent Link -->
            <div
                v-if="parent && parent.id"
                class="rounded-lg bg-indigo-50 p-4 dark:bg-indigo-100"
            >
                <div class="mb-2 flex items-center gap-2">
                    <DynamicIcon
                        name="arrow-up"
                        class="h-4 w-4 text-indigo-700"
                    />
                    <span class="text-sm font-medium text-indigo-800">
                        {{
                            $t(
                                'promptBuilder.components.relatedPromptRuns.parent',
                            )
                        }}
                    </span>
                    <StatusBadge :workflow-stage="parent.workflowStage" />
                </div>
                <LinkText
                    class="text-indigo-800 hover:text-indigo-700"
                    :href="
                        countryRoute('prompt-builder.show', {
                            promptRun: parent.id,
                        })
                    "
                >
                    {{ truncateText(parent.taskDescription) }}
                </LinkText>
                <p class="mt-3 text-xs text-indigo-700">
                    {{
                        $t(
                            'promptBuilder.components.relatedPromptRuns.promptId',
                            {
                                id: parent.id,
                            },
                        )
                    }}
                    •
                    <span v-if="parent.selectedFramework?.name">
                        {{ parent.selectedFramework.name }} •
                    </span>
                    {{
                        $t(
                            'promptBuilder.components.relatedPromptRuns.created',
                            {
                                date: new Date(
                                    parent.createdAt,
                                ).toLocaleDateString(),
                            },
                        )
                    }}
                </p>
            </div>

            <!-- Children Links -->
            <div v-if="children && children.length > 0" class="space-y-2">
                <div
                    class="flex items-center gap-2 text-sm font-medium text-indigo-600"
                >
                    <DynamicIcon
                        name="arrow-down"
                        class="h-4 w-4 text-indigo-500"
                    />
                    <span>
                        {{
                            $t(
                                'promptBuilder.components.relatedPromptRuns.children',
                                {
                                    count: children.length,
                                },
                            )
                        }}
                    </span>
                </div>
                <div
                    v-for="child in children"
                    :key="child.id"
                    class="ml-6 rounded-lg bg-white p-3"
                >
                    <div class="mb-1 flex items-center gap-2">
                        <StatusBadge :workflow-stage="child.workflowStage" />
                    </div>
                    <LinkText
                        class="text-indigo-800 hover:text-indigo-700"
                        :href="
                            countryRoute('prompt-builder.show', {
                                promptRun: child.id,
                            })
                        "
                    >
                        {{ truncateText(child.taskDescription) }}
                    </LinkText>
                    <p class="mt-3 text-xs text-indigo-500">
                        {{
                            $t(
                                'promptBuilder.components.relatedPromptRuns.promptId',
                                {
                                    id: child.id,
                                },
                            )
                        }}
                        •
                        <span v-if="child.selectedFramework?.name">
                            {{ child.selectedFramework.name }} •
                        </span>
                        {{
                            $t(
                                'promptBuilder.components.relatedPromptRuns.created',
                                {
                                    date: new Date(
                                        child.createdAt,
                                    ).toLocaleDateString(),
                                },
                            )
                        }}
                    </p>
                </div>
            </div>
        </div>
    </Card>
</template>
