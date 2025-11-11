<script setup lang="ts">
import Card from '@/Components/Card.vue';
import DynamicIcon from '@/Components/DynamicIcon.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import type { PromptRunResource } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    parent?: PromptRunResource | null;
    children?: PromptRunResource[];
}

const props = defineProps<Props>();

const hasRelations =
    props.parent || (props.children && props.children.length > 0);

const truncateText = (text: string, maxLength: number = 60) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
};
</script>

<template>
    <Card v-if="hasRelations">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">
            Related Prompt Optimisations
        </h3>

        <div class="space-y-4">
            <!-- Parent Link -->
            <div
                v-if="parent"
                class="rounded-lg border border-gray-200 bg-gray-50 p-4"
            >
                <div class="mb-2 flex items-center gap-2">
                    <DynamicIcon
                        name="arrow-up"
                        class="h-4 w-4 text-gray-500"
                    />
                    <span class="text-sm font-medium text-gray-600">
                        Parent Optimisation
                    </span>
                    <StatusBadge :status="parent.status" />
                </div>
                <Link
                    :href="route('prompt-optimizer.show', parent.id)"
                    class="block text-sm text-indigo-600 hover:text-indigo-800 hover:underline"
                >
                    {{ truncateText(parent.taskDescription) }}
                </Link>
                <p class="mt-1 text-xs text-gray-500">
                    Created
                    {{ new Date(parent.createdAt).toLocaleDateString() }}
                </p>
            </div>

            <!-- Children Links -->
            <div v-if="children && children.length > 0" class="space-y-2">
                <div
                    class="flex items-center gap-2 text-sm font-medium text-gray-600"
                >
                    <DynamicIcon
                        name="arrow-down"
                        class="h-4 w-4 text-gray-500"
                    />
                    <span>Child Optimisations ({{ children.length }})</span>
                </div>
                <div
                    v-for="child in children"
                    :key="child.id"
                    class="ml-6 rounded-lg border border-gray-200 bg-white p-3"
                >
                    <div class="mb-1 flex items-center gap-2">
                        <StatusBadge :status="child.status" />
                    </div>
                    <Link
                        :href="route('prompt-optimizer.show', child.id)"
                        class="block text-sm text-indigo-600 hover:text-indigo-800 hover:underline"
                    >
                        {{ truncateText(child.taskDescription) }}
                    </Link>
                    <p class="mt-1 text-xs text-gray-500">
                        Created
                        {{ new Date(child.createdAt).toLocaleDateString() }}
                    </p>
                </div>
            </div>
        </div>
    </Card>
</template>
