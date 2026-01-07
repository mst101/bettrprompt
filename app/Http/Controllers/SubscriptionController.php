<?php

namespace App\Http\Controllers;

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
        return Inertia::render('Pricing', [
            'plans' => [
                'monthly' => [
                    'priceId' => config('stripe.prices.monthly'),
                    'price' => 12,
                    'currency' => 'GBP',
                    'interval' => 'month',
                    'description' => 'Billed monthly',
                ],
                'yearly' => [
                    'priceId' => config('stripe.prices.yearly'),
                    'price' => 99,
                    'currency' => 'GBP',
                    'interval' => 'year',
                    'description' => 'Billed annually (save 18%)',
                    'monthlyEquivalent' => 8.25,
                ],
            ],
            'features' => [
                'free' => [
                    '10 prompts per month',
                    'Personality calibration',
                    'Basic prompt optimisation',
                ],
                'pro' => [
                    'Unlimited prompts',
                    'Personality calibration',
                    'Advanced prompt optimisation',
                    'Privacy encryption',
                    'Prompt history',
                    'Priority support',
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
            'plan' => 'required|in:monthly,yearly',
        ]);

        $user = $request->user();
        $priceId = config("stripe.prices.{$request->plan}");

        if (! $priceId) {
            return back()->withErrors(['plan' => 'Invalid plan selected']);
        }

        return $user
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
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

        // Update subscription tier
        $user->update(['subscription_tier' => 'pro']);

        return Inertia::render('Subscription/Success', [
            'message' => 'Welcome to BettrPrompt Pro!',
        ]);
    }

    /**
     * Handle cancelled checkout
     */
    public function cancelled(): Response
    {
        return Inertia::render('Subscription/Cancelled', [
            'message' => 'Subscription checkout was cancelled.',
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
            ->with('success', 'Your subscription has been cancelled. You will retain Pro access until '.$subscription->ends_at->format('j F Y').'.');
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
            ->with('success', 'Your subscription has been resumed.');
    }
}
