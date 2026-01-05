<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    workflowStage: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    personalityType: string | null;
    createdAt: string;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <div class="flex flex-wrap items-center gap-4 sm:gap-6">
            <!-- User -->
            <div
                v-if="user"
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
            >
                <Link
                    :href="route('admin.users.show', { user: user.id })"
                    class="text-sm text-indigo-900 hover:text-indigo-600 hover:underline"
                >
                    {{ user.name }}
                </Link>
                <span class="text-indigo-500">({{ user.email }})</span>
            </div>

            <!-- Personality Type -->
            <div
                v-if="personalityType"
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 sm:pr-6"
            >
                <span
                    class="inline-flex rounded-full bg-purple-100 px-3 py-1 text-sm font-semibold text-purple-800"
                >
                    {{ personalityType }}
                </span>
            </div>

            <!-- Created Date -->
            <div
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0"
            >
                <span class="text-sm text-indigo-500">
                    {{ new Date(createdAt).toLocaleString() }}
                </span>
            </div>

            <!-- Workflow Stage -->
            <div class="flex items-center gap-2 sm:pr-6">
                <StatusBadge :workflow-stage="workflowStage" />
            </div>
        </div>
    </Card>
</template>
