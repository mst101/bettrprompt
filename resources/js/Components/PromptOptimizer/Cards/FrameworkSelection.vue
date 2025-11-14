<script setup lang="ts">
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';

interface Props {
    framework: string;
    reasoning: string;
    personalityApproach?: string | null;
}

defineProps<Props>();

const getApproachLabel = (approach: string | null | undefined): string => {
    if (!approach) return '';
    return approach === 'amplify' ? 'Amplify' : 'Counterbalance';
};

const getApproachDescription = (
    approach: string | null | undefined,
): string => {
    if (!approach) return '';
    if (approach === 'amplify') {
        return 'Leveraging your natural personality strengths';
    }
    return 'Providing structure to compensate for potential blind spots';
};

const getApproachColor = (approach: string | null | undefined): string => {
    if (!approach) return 'bg-gray-100 text-gray-700';
    return approach === 'amplify'
        ? 'bg-green-100 text-green-800'
        : 'bg-blue-100 text-blue-800';
};
</script>

<template>
    <Card data-testid="framework-selection-display">
        <div class="space-y-4">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <div class="rounded-lg bg-indigo-100 p-2 text-indigo-600">
                        <DynamicIcon name="clipboard-check" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Selected Framework</p>
                        <h3
                            data-testid="framework-name"
                            class="text-lg font-semibold text-gray-900"
                        >
                            {{ framework }}
                        </h3>
                    </div>
                </div>
                <div
                    v-if="personalityApproach"
                    :class="[
                        'rounded-full px-3 py-1 text-sm font-medium',
                        getApproachColor(personalityApproach),
                    ]"
                    data-testid="personality-approach-badge"
                >
                    {{ getApproachLabel(personalityApproach) }}
                </div>
            </div>

            <div v-if="personalityApproach" class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm font-medium text-blue-900">
                    Personality Approach
                </p>
                <p class="mt-1 text-sm text-blue-800">
                    {{ getApproachDescription(personalityApproach) }}
                </p>
            </div>

            <div class="rounded-lg bg-gray-50 p-4">
                <p class="text-sm font-medium text-gray-700">
                    Why this framework?
                </p>
                <p
                    data-testid="framework-reasoning"
                    class="mt-1 text-sm text-gray-600"
                >
                    {{ reasoning }}
                </p>
            </div>
        </div>
    </Card>
</template>
