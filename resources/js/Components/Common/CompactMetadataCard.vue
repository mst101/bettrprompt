<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';

export interface MetadataItem {
    label?: string;
    value: string | number;
    badge?: boolean;
    badgeColor?: 'purple' | 'indigo';
    url?: string;
}

interface Props {
    items: MetadataItem[];
}

defineProps<Props>();
</script>

<template>
    <Card>
        <!-- Mobile: Stack in 2 columns with dividers -->
        <div class="grid grid-cols-2 gap-3 sm:hidden">
            <div v-for="(item, index) in items" :key="index">
                <div
                    v-if="item.label"
                    class="text-xs font-semibold tracking-wider text-indigo-600 uppercase"
                >
                    {{ item.label }}
                </div>
                <div class="mt-1">
                    <a
                        v-if="item.url"
                        :href="item.url"
                        class="text-sm break-words text-indigo-900 hover:text-indigo-600 hover:underline"
                    >
                        {{ item.value }}
                    </a>
                    <span
                        v-else-if="item.badge"
                        :class="[
                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold whitespace-nowrap',
                            item.badgeColor === 'indigo'
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'bg-purple-100 text-purple-800',
                        ]"
                    >
                        {{ item.value }}
                    </span>
                    <span v-else class="text-sm text-indigo-900">
                        {{ item.value }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Desktop: Horizontal layout with dividers -->
        <div
            class="hidden sm:flex sm:flex-wrap sm:items-center sm:gap-4 md:gap-6"
        >
            <div
                v-for="(item, index) in items"
                :key="index"
                class="flex items-center gap-2 border-r border-indigo-200 pr-4 last:border-r-0 md:pr-6"
            >
                <div>
                    <a
                        v-if="item.url"
                        :href="item.url"
                        class="text-sm text-indigo-900 hover:text-indigo-600 hover:underline"
                    >
                        {{ item.value }}
                    </a>
                    <span
                        v-else-if="item.badge"
                        :class="[
                            'inline-flex rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap',
                            item.badgeColor === 'indigo'
                                ? 'bg-indigo-100 text-indigo-700'
                                : 'bg-purple-100 text-purple-800',
                        ]"
                    >
                        {{ item.value }}
                    </span>
                    <span v-else class="text-sm text-indigo-900">
                        {{ item.value }}
                    </span>
                </div>
            </div>
        </div>
    </Card>
</template>
