<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetCountry;
use App\Models\AnalyticsEvent;
use App\Models\Price;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display pricing page
     */
    public function pricing(Request $request): Response
    {
        $country = $request->route('country') ?? SetCountry::detectCountry($request);
        $setCountry = new SetCountry;
        $currencyCode = $setCountry->resolveCurrencyCode($country, $request);

        // Fetch prices from database
        $pricesData = Price::where('currency_code', $currencyCode)->get();

        // Fetch currency symbol from database (only active currencies)
        $currency = \App\Models\Currency::where('id', $currencyCode)
            ->where('active', true)
            ->first();
        $currencySymbol = $currency?->symbol ?? '£';

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

        $response = Inertia::render('Pricing', [
            'plans' => $plans,
            'currency' => $currencyCode,
            'currencySymbol' => $currencySymbol,
            'featureKeys' => [
                'free' => [
                    'pricing.features.free.limit',
                    'pricing.features.free.calibration',
                    'pricing.features.free.optimization',
                ],
                'starter' => [
                    'pricing.features.starter.limit',
                    'pricing.features.starter.calibration',
                    'pricing.features.starter.optimization',
                    'pricing.features.starter.history',
                ],
                'pro' => [
                    'pricing.features.pro.limit',
                    'pricing.features.pro.calibration',
                    'pricing.features.pro.optimization',
                    'pricing.features.pro.history',
                    'pricing.features.pro.priority_frameworks',
                ],
                'premium' => [
                    'pricing.features.premium.unlimited',
                    'pricing.features.premium.calibration',
                    'pricing.features.premium.optimization',
                    'pricing.features.premium.history',
                    'pricing.features.premium.priority_frameworks',
                    'pricing.features.premium.privacy',
                    'pricing.features.premium.support',
                ],
            ],
        ]);

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Create Stripe Checkout session
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'tier' => 'required|in:starter,pro,premium',
            'interval' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $tier = $request->string('tier')->toString();
        $interval = $request->string('interval')->toString();
        $country = $request->route('country');

        $priceId = $user->getCheckoutPriceId($tier, $interval);

        if (! $priceId) {
            return response()->json(['error' => __('messages.subscription.invalid_plan')], 422);
        }

        $checkout = $user
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success', ['country' => $country, 'tier' => $tier, 'session_id' => '{CHECKOUT_SESSION_ID}']),
                'cancel_url' => route('subscription.cancelled', ['country' => $country]),
                'customer_update' => [
                    'address' => 'auto',
                ],
                'tax_id_collection' => [
                    'enabled' => true,
                ],
                'allow_promotion_codes' => true,
            ]);

        // Track checkout initiation
        $context = $this->getAnalyticsContext($request);
        AnalyticsEvent::create([
            'event_id' => (string) Str::uuid(),
            'name' => 'checkout_initiated',
            'visitor_id' => $request->cookie('visitor_id'),
            'user_id' => $user->id,
            'source' => 'server',
            'occurred_at' => now(),
            'session_id' => $context['session_id'],
            'page_path' => $context['page_path'],
            'referrer' => $context['referrer'],
            'device_type' => $context['device_type'],
            'properties' => [
                'tier' => $tier,
                'interval' => $interval,
                'stripe_session_id' => $checkout->id,
            ],
        ]);

        return response()->json(['url' => $checkout->url]);
    }

    /**
     * Handle successful subscription
     */
    public function success(Request $request): Response
    {
        $user = $request->user();
        $tier = $request->string('tier', 'pro')->toString();

        // Validate tier and update subscription tier
        if (! in_array($tier, ['starter', 'pro', 'premium'])) {
            $tier = 'pro';
        }

        $user->update(['subscription_tier' => $tier]);

        // Track subscription completion
        $context = $this->getAnalyticsContext($request);
        AnalyticsEvent::create([
            'event_id' => (string) Str::uuid(),
            'name' => 'subscription_completed',
            'visitor_id' => $request->cookie('visitor_id'),
            'user_id' => $user->id,
            'source' => 'server',
            'occurred_at' => now(),
            'session_id' => $context['session_id'],
            'page_path' => $context['page_path'],
            'referrer' => $context['referrer'],
            'device_type' => $context['device_type'],
            'properties' => [
                'tier' => $tier,
                'previous_tier' => 'free',
            ],
        ]);

        $messageKey = match ($tier) {
            'starter' => 'messages.subscription.welcome_starter',
            'premium' => 'messages.subscription.welcome_premium',
            default => 'messages.subscription.welcome_pro',
        };

        return Inertia::render('Subscription/Success', [
            'message' => __($messageKey),
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
            countryRoute('settings.subscription')
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

            // Track subscription cancellation
            $context = $this->getAnalyticsContext($request);
            AnalyticsEvent::create([
                'event_id' => (string) Str::uuid(),
                'name' => 'subscription_cancelled',
                'visitor_id' => $request->cookie('visitor_id'),
                'user_id' => $user->id,
                'source' => 'server',
                'occurred_at' => now(),
                'session_id' => $context['session_id'],
                'page_path' => $context['page_path'],
                'referrer' => $context['referrer'],
                'device_type' => $context['device_type'],
                'properties' => [
                    'tier' => $user->subscription_tier,
                    'cancellation_source' => 'settings_page',
                ],
            ]);

            // Set grace period end date
            $user->update([
                'subscription_ends_at' => $subscription->ends_at,
            ]);
        }

        return redirect(countryRoute('settings.subscription'))
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

        return redirect(countryRoute('settings.subscription'))
            ->with('success', __('messages.subscription.resumed'));
    }
}
