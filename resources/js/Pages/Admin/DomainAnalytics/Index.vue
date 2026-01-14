<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FrameworkAnalytics from './Partials/FrameworkAnalytics.vue';
import QuestionAnalytics from './Partials/QuestionAnalytics.vue';
import WorkflowAnalytics from './Partials/WorkflowAnalytics.vue';

const { t } = useI18n();

defineOptions({
    layout: AppLayout,
});

const tabs = ['frameworks', 'questions', 'workflows'];
const activeTab = ref('frameworks');
const dateRange = ref(new Date().toISOString().split('T')[0]);

const refreshData = () => {
    // Trigger refresh on child components
    window.location.reload();
};

const onDataLoaded = () => {
    // Placeholder for data load handling
};
</script>

<template>
    <Head :title="t('admin.domain_analytics.title')" />

    <div class="space-y-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">
                {{ t('admin.domain_analytics.title') }}
            </h1>
            <div class="flex items-center gap-4">
                <input
                    v-model="dateRange"
                    type="date"
                    class="rounded-lg border border-gray-300 px-3 py-2"
                />
                <button
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                    @click="refreshData"
                >
                    <RefreshCw :size="16" />
                    {{ t('admin.domain_analytics.refresh') }}
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <div class="flex gap-8">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    :class="[
                        'border-b-2 px-1 py-4 font-medium transition-colors',
                        activeTab === tab
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-600 hover:text-gray-900',
                    ]"
                    @click="activeTab = tab"
                >
                    {{ t(`admin.domain_analytics.tab_${tab}`) }}
                </button>
            </div>
        </div>

        <!-- Content -->
        <div>
            <FrameworkAnalytics
                v-if="activeTab === 'frameworks'"
                :date-range="dateRange"
                @data-loaded="onDataLoaded"
            />
            <QuestionAnalytics
                v-else-if="activeTab === 'questions'"
                :date-range="dateRange"
                @data-loaded="onDataLoaded"
            />
            <WorkflowAnalytics
                v-else-if="activeTab === 'workflows'"
                :date-range="dateRange"
                @data-loaded="onDataLoaded"
            />
        </div>
    </div>
</template>
