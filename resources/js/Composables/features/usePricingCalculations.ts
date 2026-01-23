import { computed, type ComputedRef, type Ref } from 'vue';

export interface PricingPlans {
    [key: string]:
        | {
              price: number;
          }
        | undefined;
}

type PricingTier = 'starter' | 'pro' | 'premium';
type BillingPeriod = 'monthly' | 'yearly';

interface PricingResult {
    getPrice: (tier: PricingTier) => ComputedRef<number>;
    getMonthlyEquivalent: (tier: PricingTier) => ComputedRef<string | null>;
}

/**
 * Composable for calculating pricing tiers with monthly/yearly options
 *
 * @param plans - Pricing plans object keyed by tier_period
 * @param selectedPlan - Reactive ref for selected billing period
 * @returns Object with getPrice and getMonthlyEquivalent methods
 *
 * @example
 * const { getPrice, getMonthlyEquivalent } = usePricingCalculations(
 *     props.plans,
 *     selectedPlan
 * );
 *
 * const starterPrice = getPrice('starter');
 * const starterMonthly = getMonthlyEquivalent('starter');
 */
export function usePricingCalculations(
    plans: PricingPlans,
    selectedPlan: Ref<BillingPeriod>,
): PricingResult {
    /**
     * Get price for a specific tier
     * Removes decimals for yearly prices
     */
    const getPrice = (tier: PricingTier) =>
        computed<number>(() => {
            const key = `${tier}_${selectedPlan.value}`;
            const price = plans[key]?.price ?? 0;
            // Remove decimals for yearly prices
            return selectedPlan.value === 'yearly' ? Math.round(price) : price;
        });

    /**
     * Get monthly equivalent price when yearly is selected
     * Returns null for monthly plan (already at monthly rate)
     */
    const getMonthlyEquivalent = (tier: PricingTier) =>
        computed<string | null>(() => {
            if (selectedPlan.value === 'yearly') {
                const yearlyPrice = getPrice(tier).value;
                return (yearlyPrice / 12).toFixed(2);
            }
            return null;
        });

    return {
        getPrice,
        getMonthlyEquivalent,
    };
}
