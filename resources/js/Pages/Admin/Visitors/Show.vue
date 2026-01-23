<script setup lang="ts">
import SessionListItem from '@/Components/Admin/SessionListItem.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { SessionStatsResource, VisitorDetailResource } from '@/Types';
import { formatDuration } from '@/Utils/formatting/formatters';
import { Head, Link } from '@inertiajs/vue3';

interface Props {
    visitor: VisitorDetailResource;
    sessionStats: SessionStatsResource;
}

defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();
</script>

<template>
    <Head :title="`Visitor ${visitor.id.substring(0, 8)} - Admin`" />

    <HeaderPage title="Visitor Details" />

    <ContainerPage>
        <!-- Visitor Header -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow">
            <div
                class="mb-2 flex-col space-y-4 sm:flex sm:flex-row sm:items-center sm:justify-between sm:space-y-0"
            >
                <div>
                    <h2 class="text-xs font-medium text-indigo-600 uppercase">
                        Visitor ID
                    </h2>
                    <p
                        class="font-mono font-semibold text-indigo-900 sm:text-lg"
                    >
                        {{ visitor.id }}
                    </p>
                </div>
                <div class="sm:text-right">
                    <h3 class="text-xs font-medium text-indigo-600 uppercase">
                        Linked User
                    </h3>
                    <Link
                        v-if="visitor.user"
                        :href="
                            countryRoute('admin.users.show', {
                                user: visitor.user.id,
                            })
                        "
                        class="text-lg font-semibold text-indigo-900 hover:text-indigo-700 hover:underline"
                    >
                        {{ visitor.user.name }}
                    </Link>
                    <p v-else class="font-semibold text-indigo-400 sm:text-lg">
                        Anonymous
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-6 text-sm text-indigo-600">
                <div>
                    <span class="font-medium">Country:</span>
                    {{ visitor.countryCode || 'Unknown' }}
                </div>
                <div>
                    <span class="font-medium">First seen:</span>
                    {{ formatDate(visitor.createdAt) }}
                </div>
            </div>
        </div>

        <!-- Session Stats -->
        <div class="mb-6">
            <h3 class="mb-3 text-lg font-semibold text-indigo-900">
                Session Statistics
            </h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard
                    title="Total Sessions"
                    :value="sessionStats.totalSessions"
                    icon="chart-line"
                    icon-colour="blue"
                />
                <StatCard
                    title="Page Views"
                    :value="sessionStats.totalPageViews"
                    icon="eye"
                    icon-colour="green"
                />
                <StatCard
                    title="Avg Duration"
                    :value="formatDuration(sessionStats.avgDuration)"
                    icon="clock"
                    icon-colour="purple"
                />
                <StatCard
                    title="Bounce Rate"
                    :value="`${sessionStats.bounceRate}%`"
                    icon="arrow-down"
                    icon-colour="red"
                />
                <StatCard
                    title="Conversions"
                    :value="sessionStats.converted"
                    icon="check-circle"
                    icon-colour="green"
                />
            </div>
        </div>

        <!-- Session History -->
        <div>
            <h3 class="mb-3 text-lg font-semibold text-indigo-900">
                Session History
            </h3>
            <div v-if="visitor.sessions.length > 0">
                <SessionListItem
                    v-for="session in visitor.sessions"
                    :key="session.id"
                    :session="session"
                />
            </div>
            <div
                v-else
                class="rounded-lg bg-white p-6 text-center text-indigo-500 shadow"
            >
                No sessions recorded for this visitor
            </div>
        </div>
    </ContainerPage>
</template>
