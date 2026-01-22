<script setup lang="ts">
import DateRangePicker from '@/Components/Admin/DateRangePicker.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FrameworkAnalytics from './Partials/FrameworkAnalytics.vue';
import PromptRunsAnalytics from './Partials/PromptRunsAnalytics.vue';
import QuestionAnalytics from './Partials/QuestionAnalytics.vue';
import WorkflowAnalytics from './Partials/WorkflowAnalytics.vue';

interface Props {
    dateRange: {
        start: string;
        end: string;
    };
}

const props = defineProps<Props>();

const { t } = useI18n();

defineOptions({
    layout: AdminLayout,
});

const tabs = ['prompt-runs', 'frameworks', 'questions', 'workflows'];
const activeTab = ref('prompt-runs');

const onDataLoaded = () => {
    // Placeholder for data load handling
};
</script>

<template>
    <Head :title="t('admin.domain_analytics.title')" />

    <HeaderPage :title="t('admin.domain_analytics.title')" />

    <ContainerPage>
        <div class="space-y-8">
            <!-- Date Range Picker -->
            <DateRangePicker
                :start-date="props.dateRange.start"
                :end-date="props.dateRange.end"
            />

            <!-- Tab Navigation -->
            <div class="border-b border-indigo-200">
                <div class="flex gap-8">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        :class="[
                            'transition-colours border-b-2 px-1 py-4 font-medium capitalize',
                            activeTab === tab
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-indigo-600 hover:text-indigo-900',
                        ]"
                        @click="activeTab = tab"
                    >
                        {{ tab }}
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div>
                <PromptRunsAnalytics
                    v-if="activeTab === 'prompt-runs'"
                    :date-from="props.dateRange.start"
                    :date-to="props.dateRange.end"
                    @data-loaded="onDataLoaded"
                />
                <FrameworkAnalytics
                    v-else-if="activeTab === 'frameworks'"
                    :date-from="props.dateRange.start"
                    :date-to="props.dateRange.end"
                    @data-loaded="onDataLoaded"
                />
                <QuestionAnalytics
                    v-else-if="activeTab === 'questions'"
                    :date-from="props.dateRange.start"
                    :date-to="props.dateRange.end"
                    @data-loaded="onDataLoaded"
                />
                <WorkflowAnalytics
                    v-else-if="activeTab === 'workflows'"
                    :date-from="props.dateRange.start"
                    :date-to="props.dateRange.end"
                    @data-loaded="onDataLoaded"
                />
            </div>
        </div>
    </ContainerPage>
</template>
