<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    tier: 'full' | 'partial' | 'none';
    adjustments: string[];
}

const props = defineProps<Props>();
const { t } = useI18n({ useScope: 'global' });
const tierLabel = computed(() =>
    t(`promptBuilder.components.personalityAdjustments.tiers.${props.tier}`),
);
</script>

<template>
    <Card class="space-y-4">
        <h2 class="text-lg font-semibold text-indigo-900">
            {{ $t('promptBuilder.components.personalityAdjustments.title') }}
        </h2>

        <div>
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t('promptBuilder.components.personalityAdjustments.level')
                }}
            </h3>
            <div class="rounded-lg bg-indigo-50 p-3">
                <span class="text-indigo-900">{{ tierLabel }}</span>
            </div>
        </div>

        <div v-if="adjustments.length > 0">
            <h3 class="mb-2 text-sm font-medium text-indigo-700">
                {{
                    $t(
                        'promptBuilder.components.personalityAdjustments.applied',
                    )
                }}
            </h3>
            <ul class="space-y-2">
                <li
                    v-for="(adjustment, index) in adjustments"
                    :key="index"
                    class="flex items-start rounded-lg bg-indigo-50 p-3"
                >
                    <DynamicIcon
                        name="check-circle"
                        class="mt-0.5 mr-2 h-5 w-5 shrink-0 text-blue-600"
                    />
                    <span class="text-indigo-900">{{ adjustment }}</span>
                </li>
            </ul>
        </div>
    </Card>
</template>
