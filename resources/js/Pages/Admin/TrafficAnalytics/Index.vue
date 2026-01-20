<script setup lang="ts">
import DateRangePicker from '@/Components/Admin/DateRangePicker.vue';
import Card from '@/Components/Base/Card.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SourceData {
    source: string;
    sessions: number;
    conversions: number;
    visitors: number;
    conversion_rate: number;
}

interface CountryData {
    country: string;
    sessions: number;
    conversions: number;
    visitors: number;
    conversion_rate: number;
}

interface DeviceData {
    device: string;
    sessions: number;
    avg_duration: number;
    bounce_rate: number;
}

interface PageData {
    path: string;
    views: number;
    unique_visitors: number;
}

interface Props {
    sources: SourceData[];
    countries: CountryData[];
    devices: DeviceData[];
    topPages: PageData[];
    dateRange: {
        start: string;
        end: string;
    };
}

defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const tabs = ['sources', 'countries', 'devices', 'pages'];
const activeTab = ref('sources');

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat('en-GB').format(num);
};

const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};
</script>

<template>
    <Head title="Traffic Analytics - Admin" />

    <HeaderPage title="Traffic Analytics" />

    <ContainerPage>
        <!-- Date Range Picker -->
        <DateRangePicker
            :start-date="dateRange.start"
            :end-date="dateRange.end"
        />

        <!-- Tab Navigation -->
        <div class="mb-6 border-b border-indigo-200">
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

        <!-- Sources Tab -->
        <div v-if="activeTab === 'sources'">
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Traffic Sources
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Source
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Visitors
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Sessions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conversions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conv. Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100 bg-white">
                            <tr
                                v-for="source in sources"
                                :key="source.source"
                                class="hover:bg-indigo-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap text-indigo-900"
                                >
                                    {{ source.source }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(source.visitors) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(source.sessions) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(source.conversions) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ source.conversion_rate }}%
                                </td>
                            </tr>
                            <tr v-if="sources.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Countries Tab -->
        <div v-if="activeTab === 'countries'">
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Traffic by Country
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Country
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Visitors
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Sessions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conversions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conv. Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100 bg-white">
                            <tr
                                v-for="country in countries"
                                :key="country.country"
                                class="hover:bg-indigo-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap text-indigo-900"
                                >
                                    {{ country.country }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(country.visitors) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(country.sessions) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(country.conversions) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ country.conversion_rate }}%
                                </td>
                            </tr>
                            <tr v-if="countries.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Devices Tab -->
        <div v-if="activeTab === 'devices'">
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Traffic by Device Type
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Device Type
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Sessions
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Avg Duration
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Bounce Rate
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100 bg-white">
                            <tr
                                v-for="device in devices"
                                :key="device.device"
                                class="hover:bg-indigo-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium whitespace-nowrap text-indigo-900"
                                >
                                    {{ device.device }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(device.sessions) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatDuration(device.avg_duration) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ device.bounce_rate }}%
                                </td>
                            </tr>
                            <tr v-if="devices.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Pages Tab -->
        <div v-if="activeTab === 'pages'">
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Top Pages by Views
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Page Path
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Total Views
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Unique Visitors
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100 bg-white">
                            <tr
                                v-for="page in topPages"
                                :key="page.path"
                                class="hover:bg-indigo-50"
                            >
                                <td
                                    class="px-6 py-4 text-sm font-medium text-indigo-900"
                                >
                                    {{ page.path }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(page.views) }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm whitespace-nowrap text-indigo-700"
                                >
                                    {{ formatNumber(page.unique_visitors) }}
                                </td>
                            </tr>
                            <tr v-if="topPages.length === 0">
                                <td
                                    colspan="3"
                                    class="px-6 py-4 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </ContainerPage>
</template>
