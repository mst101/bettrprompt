# Pricing Implementation Plan

## Overview

This document details the implementation of BettrPrompt's pricing model using Stripe and Laravel Cashier.

### Pricing Structure

| Tier | Price | Features |
|------|-------|----------|
| **Free** | £0 | 10 prompts/month, encryption at rest, data may be used to improve the service (with clear disclosure/consent) |
| **Unlimited** | £5/month | Unlimited prompts, encryption at rest, data may be used to improve the service (with clear disclosure/consent) |
| **Private Monthly** | £15/month | Unlimited prompts, Private mode (restricted access + no training/improvement use by default, user-consented support sessions) |
| **Private Annual** | £150/year | Unlimited prompts, Private mode (restricted access + no training/improvement use by default, user-consented support sessions, ~17% savings) |

---

## Phase 1: Stripe Setup & Configuration

### 1.1 Create Stripe Account

1. Register at [stripe.com](https://stripe.com) with UK business details
2. Complete identity verification
3. Enable test mode for development

### 1.2 Create Products & Prices in Stripe Dashboard

**Product: BettrPrompt Unlimited**

```
Product Name: BettrPrompt Unlimited
Description: Unlimited prompts with standard privacy protections
```

**Prices:**
- Monthly: £5.00 GBP, recurring monthly

**Product: BettrPrompt Private**

```
Product Name: BettrPrompt Private
Description: Unlimited prompts with Private mode (restricted access + no training/improvement use by default)
```

**Prices:**
- Monthly: £15.00 GBP, recurring monthly
- Annual: £150.00 GBP, recurring yearly

Note the Price IDs (e.g., `price_1ABC...`) for configuration.

### 1.3 Install Laravel Cashier

```bash
composer require laravel/cashier
```

### 1.4 Publish Cashier Migrations

```bash
php artisan vendor:publish --tag="cashier-migrations"
```

### 1.5 Environment Configuration

```env
# .env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Price IDs from Stripe Dashboard
STRIPE_PRICE_UNLIMITED_MONTHLY=price_unlimited_monthly_id_here
STRIPE_PRICE_PRIVATE_MONTHLY=price_private_monthly_id_here
STRIPE_PRICE_PRIVATE_YEARLY=price_private_yearly_id_here
```

### 1.6 Create Stripe Config

```php
// config/stripe.php
<?php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'prices' => [
        'unlimited_monthly' => env('STRIPE_PRICE_UNLIMITED_MONTHLY'),
        'private_monthly' => env('STRIPE_PRICE_PRIVATE_MONTHLY'),
        'private_yearly' => env('STRIPE_PRICE_PRIVATE_YEARLY'),
    ],

    'free_tier' => [
        'monthly_prompt_limit' => 10,
    ],
];
```

---

## Phase 2: Database Migrations

### 2.1 Subscription Fields Migration

```php
// database/migrations/xxxx_add_subscription_fields_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stripe Cashier fields
            $table->string('stripe_id')->nullable()->index();
            $table->string('pm_type')->nullable();
            $table->string('pm_last_four', 4)->nullable();
            $table->timestamp('trial_ends_at')->nullable();

            // Custom subscription tracking
            $table->string('subscription_tier')->default('free');
            $table->timestamp('subscription_ends_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_id',
                'pm_type',
                'pm_last_four',
                'trial_ends_at',
                'subscription_tier',
                'subscription_ends_at',
            ]);
        });
    }
};
```

### 2.2 Usage Tracking Migration

```php
// database/migrations/xxxx_add_usage_tracking_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('monthly_prompt_count')->default(0);
            $table->timestamp('prompt_count_reset_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['monthly_prompt_count', 'prompt_count_reset_at']);
        });
    }
};
```

### 2.3 Run Cashier's Subscriptions Migration

Cashier publishes a `create_subscriptions_table` migration automatically.

```bash
php artisan migrate
```

---

## Phase 3: User Model Updates

### 3.1 Add Billable Trait

```php
// app/Models/User.php
<?php

namespace App\Models;

use Laravel\Cashier\Billable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Billable;

    protected $fillable = [
        // ... existing fields
        'subscription_tier',
        'subscription_ends_at',
        'monthly_prompt_count',
        'prompt_count_reset_at',
    ];

    protected $casts = [
        // ... existing casts
        'subscription_ends_at' => 'datetime',
        'prompt_count_reset_at' => 'datetime',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Check if user is on any paid tier (either via active subscription or grace period)
     */
    public function isPaid(): bool
    {
        return $this->subscribed('default') ||
               ($this->subscription_ends_at && $this->subscription_ends_at->isFuture());
    }

    public function isUnlimited(): bool
    {
        return $this->isPaid() && $this->subscription_tier === 'unlimited';
    }

    public function isPrivate(): bool
    {
        return $this->isPaid() && $this->subscription_tier === 'private';
    }

    /**
     * Check if user is on free tier
     */
    public function isFree(): bool
    {
        return !$this->isPaid();
    }

    /**
     * Get remaining prompts for free tier users
     */
    public function getPromptsRemaining(): int
    {
        if ($this->isPaid()) {
            return PHP_INT_MAX; // Unlimited
        }

        $limit = config('stripe.free_tier.monthly_prompt_limit', 10);
        return max(0, $limit - $this->monthly_prompt_count);
    }

    /**
     * Check if user can create a prompt
     */
    public function canCreatePrompt(): bool
    {
        return $this->isPaid() || $this->getPromptsRemaining() > 0;
    }

    /**
     * Increment prompt count (for free tier tracking)
     */
    public function incrementPromptCount(): void
    {
        // Reset if new month
        if (!$this->prompt_count_reset_at || $this->prompt_count_reset_at->isLastMonth()) {
            $this->update([
                'monthly_prompt_count' => 1,
                'prompt_count_reset_at' => now(),
            ]);
        } else {
            $this->increment('monthly_prompt_count');
        }
    }

    /**
     * Get subscription status for frontend
     */
    public function getSubscriptionStatus(): array
    {
        return [
            'tier' => $this->isPaid() ? $this->subscription_tier : 'free',
            'isPaid' => $this->isPaid(),
            'isUnlimited' => $this->isUnlimited(),
            'isPrivate' => $this->isPrivate(),
            'promptsUsed' => $this->monthly_prompt_count,
            'promptsRemaining' => $this->getPromptsRemaining(),
            'promptLimit' => config('stripe.free_tier.monthly_prompt_limit', 10),
            'subscriptionEndsAt' => $this->subscription_ends_at?->toIso8601String(),
            'onGracePeriod' => $this->subscription('default')?->onGracePeriod() ?? false,
        ];
    }
}
```

---

## Phase 4: Backend Controllers

### 4.1 Subscription Controller

```php
// app/Http/Controllers/SubscriptionController.php
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
	    public function pricing(): Response
	    {
	        return Inertia::render('Pricing', [
	            'plans' => [
	                'unlimited_monthly' => [
	                    'priceId' => config('stripe.prices.unlimited_monthly'),
	                    'price' => 5,
	                    'currency' => 'GBP',
	                    'interval' => 'month',
	                    'description' => 'Unlimited prompts (standard)',
	                ],
	                'private_monthly' => [
	                    'priceId' => config('stripe.prices.private_monthly'),
	                    'price' => 15,
	                    'currency' => 'GBP',
	                    'interval' => 'month',
	                    'description' => 'Private mode',
	                ],
	                'private_yearly' => [
	                    'priceId' => config('stripe.prices.private_yearly'),
	                    'price' => 150,
	                    'currency' => 'GBP',
	                    'interval' => 'year',
	                    'description' => 'Private mode (save ~17%)',
	                    'monthlyEquivalent' => 12.5,
	                ],
	            ],
	            'features' => [
	                'free' => [
	                    '10 prompts per month',
	                    'Personality calibration',
	                    'Basic prompt optimisation',
	                    'Encryption at rest',
	                ],
	                'unlimited' => [
	                    'Unlimited prompts',
	                    'Personality calibration',
	                    'Advanced prompt optimisation',
	                    'Encryption at rest',
	                ],
	                'private' => [
	                    'Unlimited prompts',
	                    'Personality calibration',
	                    'Advanced prompt optimisation',
	                    'Private mode (restricted access)',
	                    'User-consented support sessions',
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
            'plan' => 'required|in:unlimited_monthly,private_monthly,private_yearly',
        ]);

        $user = $request->user();
        $priceId = config("stripe.prices.{$request->plan}");

        if (!$priceId) {
            return back()->withErrors(['plan' => 'Invalid plan selected']);
        }

        return $user
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
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

        // Update subscription tier (simple approach: infer from selected plan)
        $plan = $request->string('plan')->toString();
        $tier = str_starts_with($plan, 'private_') ? 'private' : 'unlimited';
        $user->update(['subscription_tier' => $tier]);

        return Inertia::render('Subscription/Success', [
            'message' => 'Subscription activated!',
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
            ->with('success', 'Your subscription has been cancelled. You will retain Pro access until ' . $subscription->ends_at->format('j F Y') . '.');
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
```

### 4.2 Stripe Webhook Controller

```php
// app/Http/Controllers/StripeWebhookController.php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            $subscription = $payload['data']['object'];
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
            $privatePrices = [
                config('stripe.prices.private_monthly'),
                config('stripe.prices.private_yearly'),
            ];
            $tier = in_array($priceId, $privatePrices, true) ? 'private' : 'unlimited';

            $user->update([
                'subscription_tier' => $tier,
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

            // Optionally: Dispatch job to handle privacy data on downgrade
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
            // Optionally: Send notification about failed payment
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
```

---

## Phase 5: Middleware

### 5.1 Share Subscription Status Middleware

```php
// app/Http/Middleware/ShareSubscriptionStatus.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareSubscriptionStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            Inertia::share('subscription', fn () => $user->getSubscriptionStatus());
        }

        return $next($request);
    }
}
```

### 5.2 Enforce Prompt Limit Middleware

```php
// app/Http/Middleware/EnforcePromptLimit.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePromptLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user can create prompt
        if (!$user->canCreatePrompt()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'prompt_limit_reached',
                    'message' => 'You have reached your monthly prompt limit.',
                    'promptsUsed' => $user->monthly_prompt_count,
                    'promptLimit' => config('stripe.free_tier.monthly_prompt_limit'),
                    'upgradeUrl' => route('pricing'),
                ], 403);
            }

            return redirect()->route('pricing')
                ->with('error', 'You have reached your monthly prompt limit. Upgrade to Pro for unlimited prompts.');
        }

        return $next($request);
    }
}
```

### 5.3 Track Prompt Usage Middleware

```php
// app/Http/Middleware/TrackPromptUsage.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPromptUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track on successful prompt creation
        if ($response->isSuccessful() && $request->user()) {
            $request->user()->incrementPromptCount();
        }

        return $response;
    }
}
```

### 5.4 Register Middleware

```php
// bootstrap/app.php (Laravel 11) or app/Http/Kernel.php (Laravel 10)

// Add to web middleware group or as route middleware
->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        \App\Http\Middleware\ShareSubscriptionStatus::class,
    ]);

    $middleware->alias([
        'prompt.limit' => \App\Http\Middleware\EnforcePromptLimit::class,
        'prompt.track' => \App\Http\Middleware\TrackPromptUsage::class,
    ]);
})
```

---

## Phase 6: Routes

### 6.1 Web Routes

```php
// routes/web.php

use App\Http\Controllers\SubscriptionController;

// Public pricing page
Route::get('/pricing', [SubscriptionController::class, 'pricing'])
    ->name('pricing');

// Subscription routes (authenticated)
Route::middleware(['auth', 'verified'])->group(function () {
    // Checkout
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
        ->name('subscription.checkout');

    Route::get('/subscription/success', [SubscriptionController::class, 'success'])
        ->name('subscription.success');

    Route::get('/subscription/cancelled', [SubscriptionController::class, 'cancelled'])
        ->name('subscription.cancelled');

    // Subscription management
    Route::get('/settings/subscription', [SubscriptionController::class, 'show'])
        ->name('settings.subscription');

    Route::get('/billing-portal', [SubscriptionController::class, 'billingPortal'])
        ->name('billing.portal');

    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
        ->name('subscription.cancel');

    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])
        ->name('subscription.resume');
});

// Apply prompt limit to prompt creation routes
Route::middleware(['auth', 'prompt.limit', 'prompt.track'])->group(function () {
    Route::post('/prompt-runs', [PromptRunController::class, 'store']);
});
```

### 6.2 Webhook Route

```php
// routes/api.php

use App\Http\Controllers\StripeWebhookController;

Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->name('cashier.webhook');
```

---

## Phase 7: Frontend Components

### 7.1 TypeScript Types

```typescript
// resources/js/Types/subscription.ts
export interface SubscriptionStatus {
    tier: 'free' | 'unlimited' | 'private';
    isPaid: boolean;
    isUnlimited: boolean;
    isPrivate: boolean;
    promptsUsed: number;
    promptsRemaining: number;
    promptLimit: number;
    subscriptionEndsAt: string | null;
    onGracePeriod: boolean;
}

export interface PricingPlan {
    priceId: string;
    price: number;
    currency: string;
    interval: 'month' | 'year';
    description: string;
    monthlyEquivalent?: number;
}

export interface Invoice {
    id: string;
    date: string;
    total: string;
    url: string;
}
```

### 7.2 Pricing Page

```vue
<!-- resources/js/Pages/Pricing.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { PricingPlan } from '@/Types/subscription';

interface Props {
    plans: {
        unlimited_monthly: PricingPlan;
        private_monthly: PricingPlan;
        private_yearly: PricingPlan;
    };
    features: {
        free: string[];
        unlimited: string[];
        private: string[];
    };
}

const props = defineProps<Props>();
const page = usePage();

const selectedPlan = ref<
    'unlimited_monthly' | 'private_monthly' | 'private_yearly'
>('private_yearly');
const isLoading = ref(false);

const isAuthenticated = computed(() => !!page.props.auth?.user);
const subscription = computed(() => page.props.subscription);

function subscribe() {
    if (!isAuthenticated.value) {
        // Redirect to register with return URL
        router.visit('/register', {
            data: { redirect: '/subscription/checkout?plan=' + selectedPlan.value },
        });
        return;
    }

    isLoading.value = true;
    router.post('/subscription/checkout', {
        plan: selectedPlan.value,
    });
}

function getStarted() {
    if (!isAuthenticated.value) {
        router.visit('/register');
    } else {
        router.visit('/prompt-builder');
    }
}
</script>

<template>
    <AppLayout title="Pricing">
        <div class="max-w-5xl mx-auto px-4 py-16">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold mb-4">Simple, transparent pricing</h1>
                <p class="text-lg text-gray-600">
                    Start free, upgrade when you need more
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Free Tier -->
                <div class="border rounded-2xl p-8 bg-white">
                    <h2 class="text-2xl font-bold mb-2">Free</h2>
                    <div class="text-4xl font-bold mb-6">£0</div>

	                    <ul class="space-y-3 mb-8">
                        <li v-for="feature in features.free" :key="feature" class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ feature }}
                        </li>
	                        <li class="flex items-center gap-2 text-gray-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
	                            Private mode (restricted access)
	                        </li>
	                    </ul>

                    <button
                        @click="getStarted"
                        class="w-full py-3 px-4 border-2 border-gray-900 rounded-lg font-semibold hover:bg-gray-50 transition"
                    >
                        Get Started
                    </button>
                </div>

                <!-- Unlimited Tier -->
                <div class="border rounded-2xl p-8 bg-white">
                    <h2 class="text-2xl font-bold mb-2">Unlimited</h2>
                    <div class="text-4xl font-bold mb-6">£5<span class="text-lg font-normal text-gray-500">/month</span></div>

                    <ul class="space-y-3 mb-8">
                        <li v-for="feature in features.unlimited" :key="feature" class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            {{ feature }}
                        </li>
                        <li class="flex items-center gap-2 text-gray-400">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Private mode
                        </li>
                    </ul>

                    <button
                        @click="router.post('/subscription/checkout', { plan: 'unlimited_monthly' })"
                        :disabled="isLoading || subscription?.tier === 'unlimited'"
                        class="w-full py-3 px-4 border-2 border-gray-900 rounded-lg font-semibold hover:bg-gray-50 transition disabled:opacity-50"
                    >
                        <span v-if="subscription?.tier === 'unlimited'">Current Plan</span>
                        <span v-else-if="isLoading">Processing...</span>
                        <span v-else>Start Unlimited</span>
                    </button>
                </div>

                <!-- Private Tier -->
                <div class="border-2 border-blue-500 rounded-2xl p-8 bg-white relative">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-500 text-white px-4 py-1 rounded-full text-sm font-medium">
                        Most Popular
                    </div>

                    <h2 class="text-2xl font-bold mb-2">Private</h2>

                    <!-- Plan Toggle -->
                    <div class="flex gap-2 mb-4">
                        <button
                            @click="selectedPlan = 'private_monthly'"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                selectedPlan === 'private_monthly'
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'text-gray-500 hover:bg-gray-100'
                            ]"
                        >
                            Monthly
                        </button>
                        <button
                            @click="selectedPlan = 'private_yearly'"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                selectedPlan === 'private_yearly'
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'text-gray-500 hover:bg-gray-100'
                            ]"
                        >
                            Annual
                        </button>
                    </div>

                    <div class="mb-6">
                        <div class="text-4xl font-bold">
                            £{{ selectedPlan === 'private_yearly' ? '150' : '15' }}
                            <span class="text-lg font-normal text-gray-500">
                                /{{ selectedPlan === 'private_yearly' ? 'year' : 'month' }}
                            </span>
                        </div>
                        <div v-if="selectedPlan === 'private_yearly'" class="text-green-600 text-sm mt-1">
                            £12.50/month • Save 17%
                        </div>
                    </div>

	                    <ul class="space-y-3 mb-8">
	                        <li v-for="feature in features.private" :key="feature" class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
	                            {{ feature }}
	                        </li>
	                    </ul>

                    <button
                        @click="subscribe"
                        :disabled="isLoading || subscription?.tier === 'private'"
                        class="w-full py-3 px-4 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition disabled:opacity-50"
                    >
                        <span v-if="subscription?.tier === 'private'">Current Plan</span>
                        <span v-else-if="isLoading">Processing...</span>
                        <span v-else>Start Private</span>
                    </button>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-16 max-w-3xl mx-auto">
                <h2 class="text-2xl font-bold text-center mb-8">Frequently Asked Questions</h2>
                <!-- Add FAQ items here -->
            </div>
        </div>
    </AppLayout>
</template>
```

### 7.3 Subscription Settings Page

```vue
<!-- resources/js/Pages/Settings/Subscription.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import SettingsLayout from '@/Layouts/SettingsLayout.vue';
import type { SubscriptionStatus, Invoice } from '@/Types/subscription';

interface Props {
    subscription: SubscriptionStatus;
    invoices: Invoice[];
}

const props = defineProps<Props>();

const showCancelModal = ref(false);
const isCancelling = ref(false);

function openBillingPortal() {
    router.visit('/billing-portal');
}

function cancelSubscription() {
    isCancelling.value = true;
    router.post('/subscription/cancel', {}, {
        onFinish: () => {
            isCancelling.value = false;
            showCancelModal.value = false;
        },
    });
}

function resumeSubscription() {
    router.post('/subscription/resume');
}
</script>

<template>
    <SettingsLayout title="Subscription">
        <div class="space-y-8">
            <!-- Current Plan -->
            <div class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Current Plan</h2>

                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold">
                            {{
                                subscription.isPrivate
                                    ? 'Private'
                                    : subscription.isUnlimited
                                      ? 'Unlimited'
                                      : 'Free'
                            }}
                        </div>
                        <div v-if="subscription.isPaid" class="text-gray-500">
                            <span v-if="subscription.onGracePeriod" class="text-amber-600">
                                Cancels on {{ new Date(subscription.subscriptionEndsAt!).toLocaleDateString() }}
                            </span>
                            <span v-else>Active subscription</span>
                        </div>
                        <div v-else class="text-gray-500">
                            {{ subscription.promptsRemaining }} of {{ subscription.promptLimit }} prompts remaining this month
                        </div>
                    </div>

                    <div v-if="!subscription.isPaid">
                        <router-link
                            href="/pricing"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600"
                        >
                            Upgrade
                        </router-link>
                    </div>
                </div>

                <!-- Usage Bar (Free tier only) -->
                <div v-if="!subscription.isPaid" class="mt-4">
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-blue-500 transition-all"
                            :class="{ 'bg-amber-500': subscription.promptsUsed >= 8 }"
                            :style="{ width: (subscription.promptsUsed / subscription.promptLimit * 100) + '%' }"
                        ></div>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        {{ subscription.promptsUsed }}/{{ subscription.promptLimit }} prompts used
                    </div>
                </div>
            </div>

            <!-- Manage Subscription (paid tiers) -->
            <div v-if="subscription.isPaid" class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Manage Subscription</h2>

                <div class="space-y-4">
                    <button
                        @click="openBillingPortal"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                    >
                        Update Payment Method
                    </button>

                    <button
                        v-if="subscription.onGracePeriod"
                        @click="resumeSubscription"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
                    >
                        Resume Subscription
                    </button>

                    <button
                        v-else
                        @click="showCancelModal = true"
                        class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg"
                    >
                        Cancel Subscription
                    </button>
                </div>
            </div>

            <!-- Invoices -->
            <div v-if="invoices.length > 0" class="bg-white rounded-lg border p-6">
                <h2 class="text-lg font-semibold mb-4">Billing History</h2>

                <div class="divide-y">
                    <div
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="py-3 flex items-center justify-between"
                    >
                        <div>
                            <div class="font-medium">{{ invoice.date }}</div>
                            <div class="text-gray-500">{{ invoice.total }}</div>
                        </div>
                        <a
                            :href="invoice.url"
                            target="_blank"
                            class="text-blue-500 hover:underline"
                        >
                            Download
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <Modal v-model="showCancelModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Cancel Subscription?</h3>
                <p class="text-gray-600 mb-6">
                    You will retain Pro access until the end of your current billing period.
                    After that, you'll be moved to the Free plan with 10 prompts per month.
                </p>
                <div class="flex gap-4">
                    <button
                        @click="showCancelModal = false"
                        class="flex-1 px-4 py-2 border rounded-lg"
                    >
                        Keep Subscription
                    </button>
                    <button
                        @click="cancelSubscription"
                        :disabled="isCancelling"
                        class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg disabled:opacity-50"
                    >
                        {{ isCancelling ? 'Cancelling...' : 'Cancel Subscription' }}
                    </button>
                </div>
            </div>
        </Modal>
    </SettingsLayout>
</template>
```

### 7.4 Usage Indicator Component

```vue
<!-- resources/js/Components/UsageIndicator.vue -->
<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const subscription = computed(() => page.props.subscription);

const usagePercent = computed(() => {
    if (!subscription.value || subscription.value.isPaid) return 0;
    return (subscription.value.promptsUsed / subscription.value.promptLimit) * 100;
});

const isWarning = computed(() => usagePercent.value >= 80);
const isExhausted = computed(() => subscription.value?.promptsRemaining === 0);
</script>

<template>
    <div v-if="subscription && !subscription.isPaid" class="flex items-center gap-3">
        <div class="flex-1 max-w-24">
            <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full rounded-full transition-all"
                    :class="isWarning ? 'bg-amber-500' : 'bg-blue-500'"
                    :style="{ width: Math.min(usagePercent, 100) + '%' }"
                ></div>
            </div>
        </div>
        <span class="text-sm text-gray-500 whitespace-nowrap">
            {{ subscription.promptsUsed }}/{{ subscription.promptLimit }}
        </span>
        <button
            v-if="isExhausted"
            @click="router.visit('/pricing')"
            class="text-sm text-blue-500 hover:underline whitespace-nowrap"
        >
            Upgrade
        </button>
    </div>
</template>
```

### 7.5 Upgrade Prompt Modal

```vue
<!-- resources/js/Components/UpgradePromptModal.vue -->
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const selectedPlan = ref<
    'unlimited_monthly' | 'private_monthly' | 'private_yearly'
>('private_yearly');
const isLoading = ref(false);

function subscribe() {
    isLoading.value = true;
    router.post('/subscription/checkout', {
        plan: selectedPlan.value,
    });
}
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-2">You've reached your monthly limit</h2>
            <p class="text-gray-600 mb-6">
                Free accounts are limited to 10 prompts per month.
                Upgrade to Unlimited for more usage, or Private for maximum confidentiality.
            </p>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <button
                    @click="selectedPlan = 'unlimited_monthly'"
                    :class="[
                        'p-4 border-2 rounded-lg text-left transition',
                        selectedPlan === 'unlimited_monthly' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                    ]"
                >
                    <div class="font-semibold">Unlimited</div>
                    <div class="text-2xl font-bold">£5<span class="text-sm font-normal">/mo</span></div>
                </button>

                <button
                    @click="selectedPlan = 'private_monthly'"
                    :class="[
                        'p-4 border-2 rounded-lg text-left transition relative',
                        selectedPlan === 'private_monthly' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                    ]"
                >
                    <div class="font-semibold">Private</div>
                    <div class="text-2xl font-bold">£15<span class="text-sm font-normal">/mo</span></div>
                </button>

                <button
                    @click="selectedPlan = 'private_yearly'"
                    :class="[
                        'p-4 border-2 rounded-lg text-left transition relative',
                        selectedPlan === 'private_yearly' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'
                    ]"
                >
                    <div class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-0.5 rounded-full">
                        Save 17%
                    </div>
                    <div class="font-semibold">Private (Annual)</div>
                    <div class="text-2xl font-bold">£150<span class="text-sm font-normal">/yr</span></div>
                    <div class="text-sm text-gray-500">£12.50/month</div>
                </button>
            </div>

            <div class="flex gap-4">
                <button
                    @click="emit('close')"
                    class="flex-1 px-4 py-3 border rounded-lg font-medium"
                >
                    Maybe Later
                </button>
                <button
                    @click="subscribe"
                    :disabled="isLoading"
                    class="flex-1 px-4 py-3 bg-blue-500 text-white rounded-lg font-medium disabled:opacity-50"
                >
                    {{ isLoading ? 'Processing...' : 'Upgrade Now' }}
                </button>
            </div>
        </div>
    </Modal>
</template>
```

---

## Phase 8: Scheduled Commands

### 8.1 Reset Monthly Prompt Counts

```php
// app/Console/Commands/ResetMonthlyPromptCounts.php
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetMonthlyPromptCounts extends Command
{
    protected $signature = 'prompts:reset-monthly-counts';
    protected $description = 'Reset monthly prompt counts for all users';

    public function handle(): int
    {
        $count = User::query()
            ->where('prompt_count_reset_at', '<', now()->startOfMonth())
            ->update([
                'monthly_prompt_count' => 0,
                'prompt_count_reset_at' => now(),
            ]);

        $this->info("Reset prompt counts for {$count} users.");

        return Command::SUCCESS;
    }
}
```

### 8.2 Schedule the Command

```php
// routes/console.php (Laravel 11) or app/Console/Kernel.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('prompts:reset-monthly-counts')->monthlyOn(1, '00:00');
```

---

## Phase 9: Testing

### 9.1 Subscription Feature Tests

```php
// tests/Feature/SubscriptionTest.php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_user_can_view_pricing_page(): void
    {
        $response = $this->get('/pricing');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Pricing')
                ->has('plans.unlimited_monthly')
                ->has('plans.private_monthly')
                ->has('plans.private_yearly')
        );
    }

    public function test_free_user_has_prompt_limit(): void
    {
        $user = User::factory()->create([
            'monthly_prompt_count' => 10,
            'prompt_count_reset_at' => now(),
        ]);

        $this->assertFalse($user->canCreatePrompt());
        $this->assertEquals(0, $user->getPromptsRemaining());
    }

    public function test_prompt_count_resets_monthly(): void
    {
        $user = User::factory()->create([
            'monthly_prompt_count' => 10,
            'prompt_count_reset_at' => now()->subMonth(),
        ]);

        $user->incrementPromptCount();

        $this->assertEquals(1, $user->monthly_prompt_count);
    }

    public function test_paid_user_has_unlimited_prompts(): void
    {
        $user = User::factory()->create([
            'subscription_tier' => 'unlimited',
        ]);

        // Mock subscription
        $user->newSubscription('default', 'price_xxx')->create('pm_card_visa');

        $this->assertTrue($user->isPaid());
        $this->assertTrue($user->isUnlimited());
        $this->assertTrue($user->canCreatePrompt());
    }
}
```

---

## Implementation Checklist

### Phase 1: Stripe Setup (Day 1-2)
- [ ] Create Stripe account
- [ ] Create Product and Prices in Stripe Dashboard
- [ ] Install Laravel Cashier
- [ ] Configure environment variables
- [ ] Create stripe config file

### Phase 2: Database (Day 2)
- [ ] Create subscription fields migration
- [ ] Create usage tracking migration
- [ ] Run migrations

### Phase 3: Backend (Day 3-5)
- [ ] Update User model with Billable trait
- [ ] Create SubscriptionController
- [ ] Create StripeWebhookController
- [ ] Create middleware (ShareSubscriptionStatus, EnforcePromptLimit, TrackPromptUsage)
- [ ] Configure routes
- [ ] Set up Stripe webhook endpoint

### Phase 4: Frontend (Day 5-7)
- [ ] Create TypeScript types
- [ ] Create Pricing.vue page
- [ ] Create Settings/Subscription.vue page
- [ ] Create UsageIndicator component
- [ ] Create UpgradePromptModal component
- [ ] Integrate usage indicator in navigation

### Phase 5: Testing (Day 7-8)
- [ ] Write feature tests
- [ ] Test subscription flow with Stripe CLI
- [ ] Test webhook handling
- [ ] Test prompt limiting

### Phase 6: Polish (Day 8-9)
- [ ] Error handling
- [ ] Loading states
- [ ] Success/error messages
- [ ] Documentation

**Total Estimated Time: 9-12 days**
