<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import type { AdminSessionResource } from '@/Types';
import { ref } from 'vue';

interface Props {
    session: AdminSessionResource;
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
    if (props.session.utmSource)
        parts.push(`Source: ${props.session.utmSource}`);
    if (props.session.utmMedium)
        parts.push(`Medium: ${props.session.utmMedium}`);
    if (props.session.utmCampaign)
        parts.push(`Campaign: ${props.session.utmCampaign}`);
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
                            {{ formatDateTime(session.startedAt) }}
                        </span>
                        <span
                            v-if="session.converted"
                            class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800"
                        >
                            Converted
                        </span>
                        <span
                            v-if="session.isBounce"
                            class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800"
                        >
                            Bounce
                        </span>
                    </div>
                    <div
                        class="mt-1 ml-6 flex flex-wrap gap-3 text-sm text-indigo-600"
                    >
                        <span>{{ session.pageCount }} pages</span>
                        <span>{{
                            formatDuration(session.durationSeconds)
                        }}</span>
                        <span class="capitalize">{{ session.deviceType }}</span>
                    </div>
                    <div class="mt-1 ml-6 text-xs text-indigo-500">
                        Entry: {{ session.entryPage }}
                        <span v-if="session.exitPage">
                            • Exit: {{ session.exitPage }}
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
                    :key="event.eventId"
                    class="flex items-start gap-3 text-sm"
                    data-testid="session-event"
                >
                    <span class="w-32 flex-shrink-0 text-indigo-600">
                        {{ formatDateTime(event.occurredAt) }}
                    </span>
                    <div class="flex-1">
                        <span class="font-medium text-indigo-900">
                            {{ event.name }}
                        </span>
                        <span
                            v-if="event.pagePath"
                            class="ml-2 text-indigo-600"
                        >
                            {{ event.pagePath }}
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
