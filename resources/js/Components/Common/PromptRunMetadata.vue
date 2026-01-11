<script setup lang="ts">
import CompactMetadataCard, {
    type MetadataItem,
} from '@/Components/Common/CompactMetadataCard.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    workflowStage: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    personalityType: string | null;
    createdAt: string;
}

const props = defineProps<Props>();
const { t } = useI18n({ useScope: 'global' });
const { countryRoute } = useCountryRoute();

const metadataItems = computed<MetadataItem[]>(() => {
    const items: MetadataItem[] = [];

    if (props.user) {
        items.push({
            label: t('components.common.promptRunMetadata.user'),
            value: props.user.name,
            url: countryRoute('admin.users.show', { user: props.user.id }),
        });
    }

    if (props.personalityType) {
        items.push({
            label: t('components.common.promptRunMetadata.personality'),
            value: props.personalityType,
            badge: true,
            badgeColor: 'purple',
        });
    }

    items.push({
        label: t('components.common.promptRunMetadata.created'),
        value: new Date(props.createdAt).toLocaleString(),
    });

    return items;
});
</script>

<template>
    <div class="space-y-3">
        <!-- Metadata items -->
        <CompactMetadataCard :items="metadataItems" :user-id="user?.id" />

        <!-- Workflow Stage (displayed separately for special styling) -->
        <div>
            <div
                class="text-xs font-semibold tracking-wider text-indigo-600 uppercase sm:hidden"
            >
                {{ $t('components.common.promptRunMetadata.status') }}
            </div>
            <div class="mt-1 sm:mt-0">
                <StatusBadge :workflow-stage="workflowStage" />
            </div>
        </div>
    </div>
</template>
