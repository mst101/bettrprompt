<script setup lang="ts">
import ButtonPrimary from '@/Components/Base/Button/ButtonPrimary.vue';
import ButtonSecondary from '@/Components/Base/Button/ButtonSecondary.vue';
import DynamicIcon from '@/Components/Base/DynamicIcon.vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Props {
    tier: 'free' | 'starter' | 'pro' | 'premium';
    price: number | string;
    monthlyEquivalent: string | null;
    features: string[];
    currencySymbol: string;
    selectedPlan: 'monthly' | 'yearly';
    isRecommended?: boolean;
    isCurrentPlan?: boolean;
    isLoading?: boolean;
    isFree?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isRecommended: false,
    isCurrentPlan: false,
    isLoading: false,
    isFree: false,
});

const emit = defineEmits<{
    subscribe: [];
    getStarted: [];
}>();

const { t } = useI18n({ useScope: 'global' });

const tierLabel = computed(() => t(`pricing.${props.tier}.name`));
const ctaLabel = computed(() => t(`pricing.${props.tier}.cta`));
const periodLabel = computed(() =>
    props.selectedPlan === 'yearly'
        ? t('pricing.period.year')
        : t('pricing.period.month'),
);

const savingsLabel = computed(() => {
    if (props.selectedPlan !== 'yearly' || !props.monthlyEquivalent)
        return null;
    return t(`pricing.${props.tier}.yearlySavings`, {
        amount: `${props.currencySymbol}${props.monthlyEquivalent}`,
        period: t('pricing.period.month'),
        percent: 17,
    });
});

const cardClasses = computed(() => {
    const base = 'rounded-2xl bg-white p-8 shadow-sm';
    if (props.isRecommended) {
        return `${base} relative border-2 border-indigo-500 shadow-md`;
    }
    return `${base} border border-indigo-200`;
});

const testId = computed(() => `${props.tier}-tier-tab`);

const handleClick = () => {
    if (props.isFree) {
        emit('getStarted');
    } else {
        emit('subscribe');
    }
};
</script>

<template>
    <div :class="cardClasses" :data-testid="testId">
        <!-- Recommended Badge -->
        <div
            v-if="isRecommended"
            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-sm font-medium text-white"
        >
            {{ t('pricing.recommendedBadge') }}
        </div>

        <!-- Tier Name -->
        <h2 class="mb-2 text-2xl font-bold text-indigo-900">
            {{ tierLabel }}
        </h2>

        <!-- Pricing -->
        <div class="mb-6">
            <div class="text-4xl font-bold text-indigo-900">
                {{ currencySymbol }}{{ price }}
                <span class="text-lg font-normal text-indigo-500">
                    /{{ periodLabel }}
                </span>
            </div>
            <div v-if="savingsLabel" class="mt-1 text-sm text-green-600">
                {{ savingsLabel }}
            </div>
        </div>

        <!-- Features List -->
        <ul class="mb-8 space-y-3">
            <li
                v-for="featureKey in features"
                :key="featureKey"
                class="flex items-center gap-2 text-indigo-700"
            >
                <DynamicIcon
                    name="check"
                    class="h-5 w-5 shrink-0 text-green-500"
                />
                {{ t(featureKey) }}
            </li>
            <!-- Show privacy unavailable for non-premium tiers -->
            <li
                v-if="tier !== 'premium'"
                class="flex items-center gap-2 text-indigo-400"
            >
                <DynamicIcon
                    name="x-mark"
                    class="h-5 w-5 shrink-0 text-indigo-300"
                />
                {{ t('pricing.features.privacy') }}
            </li>
        </ul>

        <!-- CTA Button -->
        <ButtonSecondary
            v-if="isFree"
            class="w-full"
            data-testid="get-started-button"
            @click="handleClick"
        >
            {{ ctaLabel }}
        </ButtonSecondary>
        <ButtonPrimary
            v-else
            class="w-full"
            data-testid="subscribe-button"
            :disabled="isLoading || isCurrentPlan"
            :loading="isLoading"
            @click="handleClick"
        >
            <span v-if="isCurrentPlan">
                {{ t('messages.subscription.current_plan') }}
            </span>
            <span v-else-if="isLoading">
                {{ t('pricing.actions.processing') }}
            </span>
            <span v-else>
                {{ ctaLabel }}
            </span>
        </ButtonPrimary>
    </div>
</template>
