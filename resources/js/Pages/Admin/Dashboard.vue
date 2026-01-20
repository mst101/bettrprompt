<script setup lang="ts">
import DateRangePicker from '@/Components/Admin/DateRangePicker.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    traffic: {
        unique_visitors: number;
        total_sessions: number;
        total_page_views: number;
        avg_session_duration: number;
        avg_bounce_rate: number;
        daily_trend: Array<{
            date: string;
            visitors: number;
            sessions: number;
        }>;
    };
    conversions: {
        registrations: number;
        pro_subscriptions: number;
        private_subscriptions: number;
        conversion_rate: number;
    };
    prompts: {
        prompts_started: number;
        prompts_completed: number;
        completion_rate: number;
        avg_rating: number;
    };
    topSources: Record<string, { sessions: number; conversions: number }>;
    topCountries: Record<string, { sessions: number; conversions: number }>;
    dateRange: {
        start: string;
        end: string;
    };
}

defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();

const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat('en-GB').format(num);
};
</script>

<template>
    <Head title="Analytics Dashboard - Admin" />

    <HeaderPage title="Analytics Dashboard" />

    <ContainerPage>
        <!-- Date Range Picker -->
        <DateRangePicker
            :start-date="dateRange.start"
            :end-date="dateRange.end"
        />

        <!-- Traffic Metrics -->
        <div class="mb-6">
            <h2 class="mb-3 text-lg font-semibold text-indigo-900">
                Traffic Metrics
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Unique Visitors"
                    :value="formatNumber(traffic.unique_visitors)"
                    icon="users"
                    icon-colour="blue"
                />
                <StatCard
                    title="Total Sessions"
                    :value="formatNumber(traffic.total_sessions)"
                    icon="chart-line"
                    icon-colour="green"
                />
                <StatCard
                    title="Page Views"
                    :value="formatNumber(traffic.total_page_views)"
                    icon="eye"
                    icon-colour="purple"
                />
                <StatCard
                    title="Avg Duration"
                    :value="formatDuration(traffic.avg_session_duration)"
                    icon="clock"
                    icon-colour="indigo"
                />
            </div>
        </div>

        <!-- Conversion Metrics -->
        <div class="mb-6">
            <h2 class="mb-3 text-lg font-semibold text-indigo-900">
                Conversion Metrics
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Registrations"
                    :value="formatNumber(conversions.registrations)"
                    icon="user-group"
                    icon-colour="blue"
                />
                <StatCard
                    title="Pro Subscriptions"
                    :value="formatNumber(conversions.pro_subscriptions)"
                    icon="star"
                    icon-colour="orange"
                />
                <StatCard
                    title="Bounce Rate"
                    :value="`${traffic.avg_bounce_rate}%`"
                    icon="arrow-down"
                    icon-colour="red"
                />
                <StatCard
                    title="Conversion Rate"
                    :value="`${conversions.conversion_rate}%`"
                    icon="arrow-up"
                    icon-colour="green"
                />
            </div>
        </div>

        <!-- Prompt Metrics -->
        <div class="mb-6">
            <h2 class="mb-3 text-lg font-semibold text-indigo-900">
                Prompt Metrics
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Prompts Started"
                    :value="formatNumber(prompts.prompts_started)"
                    icon="play-circle"
                    icon-colour="blue"
                />
                <StatCard
                    title="Prompts Completed"
                    :value="formatNumber(prompts.prompts_completed)"
                    icon="check-circle"
                    icon-colour="green"
                />
                <StatCard
                    title="Completion Rate"
                    :value="`${prompts.completion_rate}%`"
                    icon="chart-bar"
                    icon-colour="purple"
                />
                <StatCard
                    title="Avg Rating"
                    :value="`${prompts.avg_rating}/5`"
                    icon="star"
                    icon-colour="orange"
                />
            </div>
        </div>

        <!-- Traffic Trend Chart -->
        <div class="mb-6">
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Traffic Trend
                </h3>
                <div class="h-64 overflow-x-auto">
                    <div class="min-w-full">
                        <!-- Simple text-based chart - can be replaced with Chart.js later -->
                        <div class="space-y-1">
                            <div
                                v-for="day in traffic.daily_trend"
                                :key="day.date"
                                class="flex items-center text-sm"
                            >
                                <span class="w-24 text-indigo-600">{{
                                    day.date
                                }}</span>
                                <div
                                    class="ml-2 h-6 rounded bg-indigo-500"
                                    :style="{
                                        width: `${Math.max((day.visitors / Math.max(...traffic.daily_trend.map((d) => d.visitors))) * 100, 2)}%`,
                                    }"
                                ></div>
                                <span class="ml-2 text-indigo-700">{{
                                    formatNumber(day.visitors)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Top Sources and Countries -->
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <!-- Top Sources -->
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Top Sources
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Source
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Sessions
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conversions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100">
                            <tr
                                v-for="(data, source) in topSources"
                                :key="source"
                            >
                                <td class="px-4 py-3 text-sm text-indigo-900">
                                    {{ source }}
                                </td>
                                <td class="px-4 py-3 text-sm text-indigo-700">
                                    {{ formatNumber(data.sessions) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-indigo-700">
                                    {{ formatNumber(data.conversions) }}
                                </td>
                            </tr>
                            <tr v-if="Object.keys(topSources).length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-3 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <!-- Top Countries -->
            <Card>
                <h3 class="mb-4 text-lg font-semibold text-indigo-900">
                    Top Countries
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-indigo-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Country
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Sessions
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium tracking-wider text-indigo-700 uppercase"
                                >
                                    Conversions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100">
                            <tr
                                v-for="(data, country) in topCountries"
                                :key="country"
                            >
                                <td class="px-4 py-3 text-sm text-indigo-900">
                                    {{ country }}
                                </td>
                                <td class="px-4 py-3 text-sm text-indigo-700">
                                    {{ formatNumber(data.sessions) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-indigo-700">
                                    {{ formatNumber(data.conversions) }}
                                </td>
                            </tr>
                            <tr v-if="Object.keys(topCountries).length === 0">
                                <td
                                    colspan="3"
                                    class="px-4 py-3 text-center text-sm text-indigo-500"
                                >
                                    No data available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>

        <!-- Quick Links -->
        <div>
            <h2 class="mb-3 text-lg font-semibold text-indigo-900">
                Quick Links
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Traffic Analytics -->
                <Link :href="countryRoute('admin.traffic-analytics.index')">
                    <Card
                        class="transition-shadow duration-200 hover:shadow-lg"
                    >
                        <div class="flex items-center">
                            <DynamicIcon
                                name="chart-bar"
                                class="h-8 w-8 text-blue-600"
                            />
                            <div class="ml-4">
                                <h3 class="font-semibold text-indigo-900">
                                    Traffic Analytics
                                </h3>
                                <p class="text-sm text-indigo-500">
                                    Sources, countries, devices
                                </p>
                            </div>
                        </div>
                    </Card>
                </Link>

                <!-- Domain Analytics -->
                <Link :href="countryRoute('admin.domain-analytics.index')">
                    <Card
                        class="transition-shadow duration-200 hover:shadow-lg"
                    >
                        <div class="flex items-center">
                            <DynamicIcon
                                name="chart-line"
                                class="h-8 w-8 text-green-600"
                            />
                            <div class="ml-4">
                                <h3 class="font-semibold text-indigo-900">
                                    Domain Analytics
                                </h3>
                                <p class="text-sm text-indigo-500">
                                    Frameworks, workflows, funnels
                                </p>
                            </div>
                        </div>
                    </Card>
                </Link>

                <!-- Visitors -->
                <Link :href="countryRoute('admin.visitors.index')">
                    <Card
                        class="transition-shadow duration-200 hover:shadow-lg"
                    >
                        <div class="flex items-center">
                            <DynamicIcon
                                name="eye"
                                class="h-8 w-8 text-purple-600"
                            />
                            <div class="ml-4">
                                <h3 class="font-semibold text-indigo-900">
                                    Visitors
                                </h3>
                                <p class="text-sm text-indigo-500">
                                    Search and view session history
                                </p>
                            </div>
                        </div>
                    </Card>
                </Link>

                <!-- Users -->
                <Link :href="countryRoute('admin.users.index')">
                    <Card
                        class="transition-shadow duration-200 hover:shadow-lg"
                    >
                        <div class="flex items-center">
                            <DynamicIcon
                                name="users"
                                class="h-8 w-8 text-indigo-600"
                            />
                            <div class="ml-4">
                                <h3 class="font-semibold text-indigo-900">
                                    Users
                                </h3>
                                <p class="text-sm text-indigo-500">
                                    Manage users and accounts
                                </p>
                            </div>
                        </div>
                    </Card>
                </Link>
            </div>
        </div>
    </ContainerPage>
</template>
