<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { ref } from 'vue';

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

interface Props {
    session: Session;
}

const props = defineProps<Props>();

const expanded = ref(false);

const toggleExpand = () => {
    expanded.value = !expanded.value;
};

const formatDateTime = (dateStr: string): string => {
    const date = new Date(dateStr);
    return date.toLocaleString('en-GB', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}m ${secs}s`;
};

const getUtmString = (): string => {
    const parts: string[] = [];
    if (props.session.utm_source)
        parts.push(`Source: ${props.session.utm_source}`);
    if (props.session.utm_medium)
        parts.push(`Medium: ${props.session.utm_medium}`);
    if (props.session.utm_campaign)
        parts.push(`Campaign: ${props.session.utm_campaign}`);
    return parts.length > 0 ? parts.join(' • ') : 'Direct';
};
</script>

<template>
    <Card class="mb-3">
        <button
            type="button"
            class="w-full text-left"
            data-testid="session-toggle"
            @click="toggleExpand"
        >
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <DynamicIcon
                            :name="expanded ? 'chevron-down' : 'chevron-right'"
                            class="h-4 w-4 text-indigo-500"
                        />
                        <span class="font-medium text-indigo-900">
                            {{ formatDateTime(session.started_at) }}
                        </span>
                        <span
                            v-if="session.converted"
                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                        >
                            Converted
                        </span>
                        <span
                            v-if="session.is_bounce"
                            class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800"
                        >
                            Bounce
                        </span>
                    </div>
                    <div
                        class="mt-1 ml-6 flex flex-wrap gap-3 text-sm text-indigo-600"
                    >
                        <span>{{ session.page_count }} pages</span>
                        <span>{{
                            formatDuration(session.duration_seconds)
                        }}</span>
                        <span class="capitalize">{{
                            session.device_type
                        }}</span>
                    </div>
                    <div class="mt-1 ml-6 text-xs text-indigo-500">
                        Entry: {{ session.entry_page }}
                        <span v-if="session.exit_page">
                            • Exit: {{ session.exit_page }}
                        </span>
                    </div>
                    <div class="mt-1 ml-6 text-xs text-indigo-500">
                        {{ getUtmString() }}
                    </div>
                </div>
            </div>
        </button>

        <!-- Event Timeline (expanded) -->
        <div
            v-if="expanded && session.events"
            class="mt-4 ml-6 border-l-2 border-indigo-200 pl-4"
        >
            <h4 class="mb-3 text-sm font-semibold text-indigo-900">
                Event Timeline
            </h4>
            <div v-if="session.events.length > 0" class="space-y-2">
                <div
                    v-for="event in session.events"
                    :key="event.event_id"
                    class="flex items-start gap-3 text-sm"
                    data-testid="session-event"
                >
                    <span class="w-32 flex-shrink-0 text-indigo-600">
                        {{ formatDateTime(event.occurred_at) }}
                    </span>
                    <div class="flex-1">
                        <span class="font-medium text-indigo-900">
                            {{ event.name }}
                        </span>
                        <span
                            v-if="event.page_path"
                            class="ml-2 text-indigo-600"
                        >
                            {{ event.page_path }}
                        </span>
                        <div
                            v-if="
                                event.properties &&
                                Object.keys(event.properties).length > 0
                            "
                            class="mt-1 text-xs text-indigo-500"
                        >
                            {{ JSON.stringify(event.properties) }}
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-sm text-indigo-500">
                No events recorded for this session
            </div>
        </div>
    </Card>
</template>
