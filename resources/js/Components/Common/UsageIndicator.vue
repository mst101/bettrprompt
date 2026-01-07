<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const subscription = computed(() => page.props.subscription);

const usagePercent = computed(() => {
    if (!subscription.value || subscription.value.isPro) return 0;
    return (
        (subscription.value.promptsUsed / subscription.value.promptLimit) * 100
    );
});

const isWarning = computed(() => usagePercent.value >= 80);
const isExhausted = computed(() => subscription.value?.promptsRemaining === 0);

function goToPricing() {
    router.visit(route('pricing'));
}
</script>

<template>
    <div
        v-if="subscription && !subscription.isPro"
        class="flex items-center gap-3"
        data-testid="usage-indicator"
    >
        <div class="max-w-24 flex-1">
            <div class="h-1.5 overflow-hidden rounded-full bg-indigo-200">
                <div
                    class="h-full rounded-full transition-all"
                    :class="isWarning ? 'bg-amber-500' : 'bg-indigo-500'"
                    :style="{ width: Math.min(usagePercent, 100) + '%' }"
                />
            </div>
        </div>
        <span class="text-sm whitespace-nowrap text-indigo-500">
            {{ subscription.promptsUsed }}/{{ subscription.promptLimit }}
        </span>
        <button
            v-if="isExhausted"
            type="button"
            class="text-sm whitespace-nowrap text-indigo-600 hover:underline"
            @click="goToPricing"
        >
            Upgrade
        </button>
    </div>
</template>
