<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;
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
            $user->update([
                'subscription_tier' => 'pro',
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
     * Get user by Stripe customer ID
     */
    protected function getUserByStripeId(string $stripeId): ?User
    {
        return User::where('stripe_id', $stripeId)->first();
    }
}
