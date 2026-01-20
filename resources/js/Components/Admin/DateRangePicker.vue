<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Props {
    startDate: string;
    endDate: string;
}

const props = defineProps<Props>();

const selectedRange = ref<string>('30');
const customStartDate = ref(props.startDate);
const customEndDate = ref(props.endDate);
const showCustom = ref(false);

const presets = [
    { label: 'Last 7 days', value: '7' },
    { label: 'Last 30 days', value: '30' },
    { label: 'Last 90 days', value: '90' },
    { label: 'Custom', value: 'custom' },
];

const updateDateRange = (range: string) => {
    selectedRange.value = range;

    if (range === 'custom') {
        showCustom.value = true;
        return;
    }

    showCustom.value = false;
    const days = parseInt(range);
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));

    const startStr = start.toISOString().split('T')[0];
    const endStr = end.toISOString().split('T')[0];

    // Reload page with new dates
    router.get(
        window.location.pathname,
        { start_date: startStr, end_date: endStr },
        { preserveState: true, preserveScroll: true },
    );
};

const applyCustomRange = () => {
    router.get(
        window.location.pathname,
        { start_date: customStartDate.value, end_date: customEndDate.value },
        { preserveState: true, preserveScroll: true },
    );
};
</script>

<template>
    <div class="mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-for="preset in presets"
                :key="preset.value"
                type="button"
                :class="[
                    'transition-colours rounded-lg border px-4 py-2 text-sm font-medium',
                    selectedRange === preset.value
                        ? 'border-indigo-600 bg-indigo-600 text-white'
                        : 'border-indigo-200 bg-white text-indigo-700 hover:border-indigo-300',
                ]"
                @click="updateDateRange(preset.value)"
            >
                {{ preset.label }}
            </button>
        </div>

        <div v-if="showCustom" class="mt-4 flex items-end gap-3">
            <div>
                <label
                    for="start-date"
                    class="mb-1 block text-sm font-medium text-indigo-700"
                >
                    Start date
                </label>
                <input
                    id="start-date"
                    v-model="customStartDate"
                    type="date"
                    class="rounded-lg border border-indigo-200 px-3 py-2 text-sm"
                />
            </div>
            <div>
                <label
                    for="end-date"
                    class="mb-1 block text-sm font-medium text-indigo-700"
                >
                    End date
                </label>
                <input
                    id="end-date"
                    v-model="customEndDate"
                    type="date"
                    class="rounded-lg border border-indigo-200 px-3 py-2 text-sm"
                />
            </div>
            <button
                type="button"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                @click="applyCustomRange"
            >
                Apply
            </button>
        </div>
    </div>
</template>
