<script setup lang="ts">
import InfoSection from '@/Components/Workflow/InfoSection.vue';
import PageHeader from '@/Components/Workflow/PageHeader.vue';
import WorkflowCard from '@/Components/Workflow/WorkflowCard.vue';
import WorkflowLayout from '@/Layouts/WorkflowLayout.vue';

defineOptions({
    layout: WorkflowLayout,
});

const infoItems = [
    {
        strong: 'Workflow pages:',
        text: 'Debug individual workflows, inspect input/output, and test JavaScript code',
    },
    {
        strong: 'Reference Documents:',
        text: 'View and edit the markdown documents used in workflows, then embed them into the workflow JSON files',
    },
    {
        strong: 'Framework Templates:',
        text: '64 different prompting frameworks available for use in workflow optimisation',
    },
];
</script>

<template>
    <div>
        <PageHeader
            title="Workflow System"
            subtitle="Debug and manage n8n workflows"
        />

        <div class="grid gap-6 md:grid-cols-2">
            <WorkflowCard
                href="/workflow/0"
                title="Workflow 0"
                subtitle="Pre-Analysis Workflow"
                description="Gathers clarifying questions based on task classification"
            />

            <WorkflowCard
                href="/workflow/1"
                title="Workflow 1"
                subtitle="Question Generation Workflow"
                description="Generates task classification and clarifying questions"
            />

            <WorkflowCard
                href="/workflow/2"
                title="Workflow 2"
                subtitle="Prompt Optimisation Workflow"
                description="Generates optimised prompts using selected frameworks"
            />

            <WorkflowCard
                href="/workflow/docs"
                title="Reference Documents"
                subtitle="Document Management"
                description="View, edit, and manage reference documents"
                icon="📄"
                color-scheme="amber"
            />
        </div>

        <InfoSection class="mt-8" title="How to Use" :items="infoItems" />
    </div>
</template>
