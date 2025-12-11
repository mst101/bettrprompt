<script setup lang="ts">
interface InfoItem {
    text: string;
    strong?: string;
}

interface Props {
    title: string;
    items: InfoItem[];
}

defineProps<Props>();
</script>

<template>
    <div>
        <h3 class="mb-4 text-lg font-semibold text-indigo-900">
            {{ title }}
        </h3>
        <ul class="space-y-2 text-sm text-indigo-800">
            <li
                v-for="(item, index) in items"
                :key="index"
                class="flex items-start gap-3"
            >
                <span class="text-indigo-500">•</span>
                <span>
                    <strong v-if="item.strong">{{ item.strong }}</strong>
                    {{ item.text }}
                </span>
            </li>
        </ul>
    </div>
</template>
