/**
 * Subscription status for the current user
 */
export interface SubscriptionStatus {
    tier: 'free' | 'pro' | 'private';
    isPro: boolean;
    isPrivate: boolean;
    promptsUsed: number;
    promptsRemaining: number;
    promptLimit: number;
    subscriptionEndsAt: string | null;
    onGracePeriod: boolean;
}

/**
 * Pricing plan information
 */
export interface PricingPlan {
    priceId: string;
    price: number;
    currency: string;
    interval: 'month' | 'year';
    description: string;
    monthlyEquivalent?: number;
}

/**
 * Available pricing plans
 */
export type PricingPlans = Record<string, PricingPlan>;

/**
 * Feature list for pricing tiers
 */
export interface PricingFeatures {
    free: string[];
    pro: string[];
}

/**
 * Invoice from Stripe
 */
export interface Invoice {
    id: string;
    date: string;
    total: string;
    url: string;
}

/**
 * Pricing page props
 */
export interface PricingPageProps {
    plans: PricingPlans;
    features: PricingFeatures;
}

/**
 * Subscription settings page props
 */
export interface SubscriptionSettingsPageProps {
    subscription: SubscriptionStatus;
    invoices: Invoice[];
}
