<script setup lang="ts">
import InfoSection from '@/Components/Features/Workflow/InfoSection.vue';
import PageHeader from '@/Components/Features/Workflow/PageHeader.vue';
import WorkflowCard from '@/Components/Features/Workflow/WorkflowCard.vue';
import { useLocaleRoute } from '@/Composables/useLocaleRoute';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n({ useScope: 'global' });
const { localeRoute } = useLocaleRoute();

defineOptions({
    layout: WorkflowLayout,
});

const infoItems = computed(() => [
    {
        strong: t('workflow.index.infoPages.title'),
        text: t('workflow.index.infoPages.description'),
    },
    {
        strong: t('workflow.index.infoDocuments.title'),
        text: t('workflow.index.infoDocuments.description'),
    },
    {
        strong: t('workflow.index.infoFrameworks.title'),
        text: t('workflow.index.infoFrameworks.description'),
    },
]);
</script>

<template>
    <div>
        <PageHeader
            :title="t('workflow.index.title')"
            :subtitle="t('workflow.index.subtitle')"
        />

        <div class="grid gap-6 md:grid-cols-2">
            <WorkflowCard
                :href="localeRoute('workflow.show', { workflowNumber: 0 })"
                :title="t('workflow.index.workflow0.title')"
                :subtitle="t('workflow.index.workflow0.subtitle')"
                :description="t('workflow.index.workflow0.description')"
            />

            <WorkflowCard
                :href="localeRoute('workflow.show', { workflowNumber: 1 })"
                :title="t('workflow.index.workflow1.title')"
                :subtitle="t('workflow.index.workflow1.subtitle')"
                :description="t('workflow.index.workflow1.description')"
            />

            <WorkflowCard
                :href="localeRoute('workflow.show', { workflowNumber: 2 })"
                :title="t('workflow.index.workflow2.title')"
                :subtitle="t('workflow.index.workflow2.subtitle')"
                :description="t('workflow.index.workflow2.description')"
            />

            <WorkflowCard
                :href="localeRoute('workflow.docs.index')"
                :title="t('workflow.index.referenceDocs.title')"
                :subtitle="t('workflow.index.referenceDocs.subtitle')"
                :description="t('workflow.index.referenceDocs.description')"
                icon="📄"
            />
        </div>

        <InfoSection
            class="mt-8"
            :title="t('workflow.index.howToUse')"
            :items="infoItems"
        />
    </div>
</template>
