<script setup lang="ts">
import Card from '@/Components/Base/Card.vue';
import { Link } from '@inertiajs/vue3';

export interface MetadataItem {
    label?: string;
    value: string | number;
    badge?: boolean;
    badgeColor?: 'purple' | 'indigo';
    url?: string;
}

interface Props {
    items: MetadataItem[];
    userId?: number;
}

defineProps<Props>();
</script>

<template>
    <Card>
        <div class="space-y-4">
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

            <!-- Show Profile Button -->
            <Link
                v-if="userId"
                :href="localeRoute('admin.users.show', { user: userId })"
                class="inline-flex items-center gap-2 rounded-md border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                Show Profile
            </Link>
        </div>
    </Card>
</template>
