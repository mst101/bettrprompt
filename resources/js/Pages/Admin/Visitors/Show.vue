<script setup lang="ts">
import SessionListItem from '@/Components/Admin/SessionListItem.vue';
import StatCard from '@/Components/Admin/StatCard.vue';
import ContainerPage from '@/Components/Common/ContainerPage.vue';
import HeaderPage from '@/Components/Common/HeaderPage.vue';
import { useCountryRoute } from '@/Composables/useCountryRoute';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
    subscription_tier: string;
}

interface Event {
    event_id: string;
    name: string;
    page_path: string | null;
    occurred_at: string;
    properties: Record<string, unknown>;
}

interface Session {
    id: string;
    started_at: string;
    ended_at: string | null;
    duration_seconds: number;
    page_count: number;
    entry_page: string;
    exit_page: string | null;
    device_type: string;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    is_bounce: boolean;
    converted: boolean;
    events?: Event[];
}

interface Visitor {
    id: string;
    user: User | null;
    country_code: string;
    created_at: string;
    sessions: Session[];
}

interface SessionStats {
    total_sessions: number;
    total_page_views: number;
    avg_duration: number;
    bounce_rate: number;
    converted: number;
}

interface Props {
    visitor: Visitor;
    sessionStats: SessionStats;
}

defineProps<Props>();

defineOptions({
    layout: AdminLayout,
});

const { countryRoute } = useCountryRoute();

const formatDate = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};

const truncateId = (id: string): string => {
    return id.substring(0, 16) + '...';
};
</script>

<template>
    <Head :title="`Visitor ${visitor.id.substring(0, 8)} - Admin`" />

    <HeaderPage title="Visitor Details" />

    <ContainerPage>
        <!-- Visitor Header -->
        <div class="mb-6 rounded-lg bg-white p-6 shadow">
            <div class="mb-2 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-medium text-indigo-600 uppercase">
                        Visitor ID
                    </h2>
                    <p class="font-mono text-lg font-semibold text-indigo-900">
                        {{ truncateId(visitor.id) }}
                    </p>
                </div>
                <div class="text-right">
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
                    <p v-else class="text-lg font-semibold text-indigo-400">
                        Anonymous
                    </p>
                </div>
            </div>
            <div class="mt-4 flex gap-6 text-sm text-indigo-600">
                <div>
                    <span class="font-medium">Country:</span>
                    {{ visitor.country_code || 'Unknown' }}
                </div>
                <div>
                    <span class="font-medium">First seen:</span>
                    {{ formatDate(visitor.created_at) }}
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
                    :value="sessionStats.total_sessions"
                    icon="chart-line"
                    icon-colour="blue"
                />
                <StatCard
                    title="Page Views"
                    :value="sessionStats.total_page_views"
                    icon="eye"
                    icon-colour="green"
                />
                <StatCard
                    title="Avg Duration"
                    :value="formatDuration(sessionStats.avg_duration)"
                    icon="clock"
                    icon-colour="purple"
                />
                <StatCard
                    title="Bounce Rate"
                    :value="`${sessionStats.bounce_rate}%`"
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
