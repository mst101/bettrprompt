<?php

namespace App\Http\Controllers;

use App\Models\Price;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display pricing page
     */
    public function pricing(Request $request): Response
    {
        $user = $request->user();
        $currencyCode = $user?->currency_code ?? 'GBP';

        // Fetch prices from database
        $pricesData = Price::where('currency_code', $currencyCode)->get();

        // Build pricing plans from database
        $plans = [];
        foreach ($pricesData as $price) {
            $key = $price->tier.'_'.$price->interval;
            $plans[$key] = [
                'priceId' => $price->stripe_price_id,
                'price' => $price->amount,
                'currency' => $currencyCode,
                'interval' => $price->interval === 'monthly' ? 'month' : 'year',
                'description' => __("pricing.{$price->tier}.price".ucfirst($price->interval)),
            ];
        }

        return Inertia::render('Pricing', [
            'plans' => $plans,
            'currency' => $currencyCode,
            'availableCurrencies' => ['GBP', 'EUR', 'USD'],
            'features' => [
                'free' => [
                    __('pricing.features.free.limit'),
                    __('pricing.features.free.calibration'),
                    __('pricing.features.free.optimization'),
                ],
                'pro' => [
                    __('pricing.features.pro.unlimited'),
                    __('pricing.features.pro.calibration'),
                    __('pricing.features.pro.optimization'),
                    __('pricing.features.pro.history'),
                ],
                'private' => [
                    __('pricing.features.private.unlimited'),
                    __('pricing.features.private.calibration'),
                    __('pricing.features.private.optimization'),
                    __('pricing.features.private.mode'),
                    __('pricing.features.private.support'),
                    __('pricing.features.private.history'),
                ],
            ],
        ]);
    }

    /**
     * Create Stripe Checkout session
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'tier' => 'required|in:pro,private',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $tier = $request->string('tier')->toString();
        $interval = $request->string('interval')->toString();

        $priceId = $user->getCheckoutPriceId($tier, $interval);

        if (! $priceId) {
            return back()->withErrors(['plan' => __('messages.subscription.invalid_plan')]);
        }

        return $user
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success', ['tier' => $tier, 'session_id' => '{CHECKOUT_SESSION_ID}']),
                'cancel_url' => route('subscription.cancelled'),
                'customer_update' => [
                    'address' => 'auto',
                ],
                'tax_id_collection' => [
                    'enabled' => true,
                ],
                'allow_promotion_codes' => true,
            ]);
    }

    /**
     * Handle successful subscription
     */
    public function success(Request $request): Response
    {
        $user = $request->user();
        $tier = $request->string('tier', 'pro')->toString();

        // Validate tier and update subscription tier
        if (! in_array($tier, ['pro', 'private'])) {
            $tier = 'pro';
        }

        $user->update(['subscription_tier' => $tier]);

        return Inertia::render('Subscription/Success', [
            'message' => $tier === 'private'
                ? __('messages.subscription.welcome_private')
                : __('messages.subscription.welcome_pro'),
            'tier' => $tier,
        ]);
    }

    /**
     * Handle cancelled checkout
     */
    public function cancelled(): Response
    {
        return Inertia::render('Subscription/Cancelled', [
            'message' => __('messages.subscription.checkout_cancelled'),
        ]);
    }

    /**
     * Display subscription management page
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Subscription', [
            'subscription' => $user->getSubscriptionStatus(),
            'invoices' => $user->invoices()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'date' => $invoice->date()->toFormattedDateString(),
                'total' => $invoice->total(),
                'url' => $invoice->invoicePdf(),
            ]),
        ]);
    }

    /**
     * Redirect to Stripe billing portal
     */
    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(
            route('settings.subscription')
        );
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if ($subscription) {
            $subscription->cancel();

            // Set grace period end date
            $user->update([
                'subscription_ends_at' => $subscription->ends_at,
            ]);
        }

        return redirect()->route('settings.subscription')
            ->with('success', __('messages.subscription.cancelled_pro_until', ['date' => $subscription->ends_at->format('j F Y')]));
    }

    /**
     * Resume cancelled subscription
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();

            $user->update([
                'subscription_ends_at' => null,
            ]);
        }

        return redirect()->route('settings.subscription')
            ->with('success', __('messages.subscription.resumed'));
    }
}
