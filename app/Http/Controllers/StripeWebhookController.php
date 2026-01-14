<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierController
{
    /**
     * Handle customer subscription created
     */
    protected function handleCustomerSubscriptionCreated(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            $subscription = $payload['data']['object'];
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
            $tier = $this->determineTierFromPriceId($priceId);

            $user->update([
                'subscription_tier' => $tier,
            ]);

            // Track subscription activation
            AnalyticsEvent::create([
                'event_id' => (string) Str::uuid(),
                'name' => 'subscription_activated',
                'user_id' => $user->id,
                'source' => 'server',
                'occurred_at' => now(),
                'properties' => [
                    'tier' => $tier,
                    'stripe_subscription_id' => $subscription['id'] ?? null,
                    'billing_interval' => $subscription['items']['data'][0]['price']['recurring']['interval'] ?? null,
                ],
            ]);
        }

        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Handle customer subscription updated
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $subscription = $payload['data']['object'];
        $user = $this->getUserByStripeId($subscription['customer']);

        if ($user) {
            // Update subscription end date if cancelled
            if ($subscription['cancel_at_period_end']) {
                $user->update([
                    'subscription_ends_at' => Carbon::createFromTimestamp($subscription['current_period_end']),
                ]);
            } else {
                $user->update([
                    'subscription_ends_at' => null,
                ]);
            }
        }

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Handle customer subscription deleted
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            $user->update([
                'subscription_tier' => 'free',
                'subscription_ends_at' => null,
            ]);

            // Future: Dispatch job to handle privacy data on downgrade
            // DecryptUserData::dispatch($user);
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle invoice payment failed
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            // Future: Send notification about failed payment
            // $user->notify(new PaymentFailed());
        }

        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Determine subscription tier from Stripe price ID
     *
     * Checks the configured prices to find which tier this price belongs to.
     * Defaults to 'pro' if not found.
     */
    protected function determineTierFromPriceId(?string $priceId): string
    {
        if (! $priceId) {
            return 'pro';
        }

        $stripeConfig = config('stripe.prices', []);

        foreach ($stripeConfig as $currency => $tiers) {
            foreach ($tiers as $tier => $intervals) {
                foreach ($intervals as $interval => $configuredPriceId) {
                    if ($configuredPriceId === $priceId) {
                        return $tier; // 'pro' or 'private'
                    }
                }
            }
        }

        return 'pro'; // Safe default
    }

    /**
     * Get user by Stripe customer ID
     */
    protected function getUserByStripeId($stripeId)
    {
        return User::where('stripe_id', $stripeId)->first();
    }
}
