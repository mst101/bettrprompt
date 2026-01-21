# Implementation Plan: New 4-Tier Pricing Structure

## Overview

Replace the current 3-tier pricing structure (Free/Pro/Private) with a new 4-tier model (Free/Starter/Pro/Premium). This
includes updating prompt limits, moving privacy to Premium tier, implementing region-based currency restrictions, and
updating all Stripe integrations.

**Key Changes:**

- Free: 10 prompts/month (unchanged)
- **Starter** (NEW): 25 prompts/month at £9.99/month, £99/year
- Pro: 90 prompts/month at £24.99/month, £249/year (changed from unlimited at £12/month)
- **Premium** (renamed from Private): Unlimited prompts at £49.99/month, £499/year + privacy + priority support

**Currency Restrictions:**

- Remove currency switcher from pricing page
- Auto-detect region: UK → GBP, EU → EUR, Rest of World → USD
- Users can't manually switch currencies (Stripe handles VAT based on billing location)

---

## Current State Analysis

### Database Structure

**`prices` table**: Stores pricing for each tier/currency/interval combination

- Currently: 12 rows (pro/private × monthly/yearly × GBP/EUR/USD)
- **New**: 18 rows (starter/pro/premium × monthly/yearly × GBP/EUR/USD)

**`users` table** - Subscription fields:

- `subscription_tier`: 'free', 'pro', 'private' → **Add 'starter', 'premium'**
- `monthly_prompt_count`: Tracks free tier usage → **Extend to Starter/Pro**
- `privacy_enabled`: Currently Pro+ → **Move to Premium-only**

### User Model Methods

Current tier checks:

```php
isFree() → subscription_tier === 'free'
isPro() → subscription_tier === 'pro'
isPrivate() → subscription_tier === 'private'
isPaid() → isPro() || isPrivate()
```

**New tier checks needed:**

```php
isStarter() → subscription_tier === 'starter'
isPro() → subscription_tier === 'pro'
isPremium() → subscription_tier === 'premium'
isPaid() → isStarter() || isPro() || isPremium()
```

### Prompt Limit Enforcement

**Current**: `EnforcePromptLimit` middleware

- Free: 10/month (from config)
- Pro/Private: Unlimited

**New**:

- Free: 10/month
- Starter: 25/month
- Pro: 90/month
- Premium: Unlimited

### Privacy Implementation

**Current**: Pro+ can enable (`canEnablePrivacy()` checks `isPro()`)
**New**: Premium-only (`canEnablePrivacy()` checks `isPremium()`)

### Pricing Page

**Current**: 3 cards (Free, Pro, Private) + Currency switcher
**New**: 4 cards (Free, Starter, Pro, Premium) + NO currency switcher

---

## Implementation Tasks

### Task 1: Database Migration - Add New Tier Prices

**File**: `database/migrations/YYYY_MM_DD_add_new_pricing_tiers.php`

**Up Migration**:

```php
public function up()
{
    // 1. Remove old 'private' tier prices
    DB::table('prices')->where('tier', 'private')->delete();

    // 2. Update existing 'pro' tier prices to new amounts
    $proPrices = [
        ['currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 24.99],
        ['currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 249.00],
        ['currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 27.99],
        ['currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 279.00],
        ['currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 29.99],
        ['currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 299.00],
    ];

    foreach ($proPrices as $price) {
        DB::table('prices')
            ->where('tier', 'pro')
            ->where('currency_code', $price['currency_code'])
            ->where('interval', $price['interval'])
            ->update(['amount' => $price['amount']]);
    }

    // 3. Insert new 'starter' tier prices
    $starterPrices = [
        ['tier' => 'starter', 'currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 9.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_GBP')],
        ['tier' => 'starter', 'currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 99.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_GBP')],
        ['tier' => 'starter', 'currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 11.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_USD')],
        ['tier' => 'starter', 'currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 119.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_USD')],
        ['tier' => 'starter', 'currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 11.99, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_MONTHLY_EUR')],
        ['tier' => 'starter', 'currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 119.00, 'stripe_price_id' => env('STRIPE_PRICE_STARTER_YEARLY_EUR')],
    ];

    foreach ($starterPrices as $price) {
        DB::table('prices')->insert($price + ['created_at' => now(), 'updated_at' => now()]);
    }

    // 4. Insert new 'premium' tier prices
    $premiumPrices = [
        ['tier' => 'premium', 'currency_code' => 'GBP', 'interval' => 'monthly', 'amount' => 49.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_GBP')],
        ['tier' => 'premium', 'currency_code' => 'GBP', 'interval' => 'yearly', 'amount' => 499.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_GBP')],
        ['tier' => 'premium', 'currency_code' => 'USD', 'interval' => 'monthly', 'amount' => 54.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_USD')],
        ['tier' => 'premium', 'currency_code' => 'USD', 'interval' => 'yearly', 'amount' => 549.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_USD')],
        ['tier' => 'premium', 'currency_code' => 'EUR', 'interval' => 'monthly', 'amount' => 59.99, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_MONTHLY_EUR')],
        ['tier' => 'premium', 'currency_code' => 'EUR', 'interval' => 'yearly', 'amount' => 599.00, 'stripe_price_id' => env('STRIPE_PRICE_PREMIUM_YEARLY_EUR')],
    ];

    foreach ($premiumPrices as $price) {
        DB::table('prices')->insert($price + ['created_at' => now(), 'updated_at' => now()]);
    }

    // 5. Migrate existing 'private' tier users to 'premium'
    DB::table('users')
        ->where('subscription_tier', 'private')
        ->update(['subscription_tier' => 'premium']);
}
```

**Down Migration**: Reverse changes (restore old prices, migrate premium → private)

---

### Task 2: Update Config for New Tiers

**File**: `config/stripe.php`

**Add new prompt limits**:

```php
'tiers' => [
    'free' => [
        'monthly_prompt_limit' => env('FREE_TIER_PROMPT_LIMIT', 10),
    ],
    'starter' => [
        'monthly_prompt_limit' => env('STARTER_TIER_PROMPT_LIMIT', 25),
    ],
    'pro' => [
        'monthly_prompt_limit' => env('PRO_TIER_PROMPT_LIMIT', 90),
    ],
    'premium' => [
        'monthly_prompt_limit' => null, // Unlimited
    ],
],
```

**Update Stripe price IDs** (add to `.env`):

```
# Starter Tier
STRIPE_PRICE_STARTER_MONTHLY_GBP=price_xxx
STRIPE_PRICE_STARTER_YEARLY_GBP=price_yyy
STRIPE_PRICE_STARTER_MONTHLY_EUR=price_xxx
STRIPE_PRICE_STARTER_YEARLY_EUR=price_yyy
STRIPE_PRICE_STARTER_MONTHLY_USD=price_xxx
STRIPE_PRICE_STARTER_YEARLY_USD=price_yyy

# Pro Tier (NEW PRICES - update existing env vars)
STRIPE_PRICE_PRO_MONTHLY_GBP=price_xxx (update)
STRIPE_PRICE_PRO_YEARLY_GBP=price_yyy (update)
STRIPE_PRICE_PRO_MONTHLY_EUR=price_xxx (update)
STRIPE_PRICE_PRO_YEARLY_EUR=price_yyy (update)
STRIPE_PRICE_PRO_MONTHLY_USD=price_xxx (update)
STRIPE_PRICE_PRO_YEARLY_USD=price_yyy (update)

# Premium Tier (renamed from Private)
STRIPE_PRICE_PREMIUM_MONTHLY_GBP=price_xxx
STRIPE_PRICE_PREMIUM_YEARLY_GBP=price_yyy
STRIPE_PRICE_PREMIUM_MONTHLY_EUR=price_xxx
STRIPE_PRICE_PREMIUM_YEARLY_EUR=price_yyy
STRIPE_PRICE_PREMIUM_MONTHLY_USD=price_xxx
STRIPE_PRICE_PREMIUM_YEARLY_USD=price_yyy
```

---

### Task 3: Update User Model Tier Methods

**File**: `app/Models/User.php`

**Add new tier check methods**:

```php
public function isStarter(): bool
{
    return $this->subscription_tier === 'starter';
}

public function isPremium(): bool
{
    return $this->subscription_tier === 'premium';
}

// Update existing isPaid() to include new tiers
public function isPaid(): bool
{
    return in_array($this->subscription_tier, ['starter', 'pro', 'premium']);
}
```

**Update prompt limit methods**:

```php
public function getPromptLimit(): int
{
    return match($this->subscription_tier) {
        'free' => config('stripe.tiers.free.monthly_prompt_limit', 10),
        'starter' => config('stripe.tiers.starter.monthly_prompt_limit', 25),
        'pro' => config('stripe.tiers.pro.monthly_prompt_limit', 90),
        'premium' => PHP_INT_MAX,
        default => config('stripe.tiers.free.monthly_prompt_limit', 10),
    };
}

public function canCreatePrompt(): bool
{
    // Premium tier is unlimited
    if ($this->isPremium()) {
        return true;
    }

    // Pro tier has 90 prompt limit
    if ($this->isPro()) {
        return $this->getPromptsRemaining() > 0;
    }

    // Starter tier has 25 prompt limit
    if ($this->isStarter()) {
        return $this->getPromptsRemaining() > 0;
    }

    // Free tier has 10 prompt limit
    return $this->getPromptsRemaining() > 0;
}
```

**Update privacy check**:

```php
public function canEnablePrivacy(): bool
{
    // Privacy is now Premium-only
    return $this->isPremium() && !$this->privacy_enabled;
}
```

**Update subscription status methods** (for frontend):

```php
public function getSubscriptionStatus(): array
{
    return [
        'tier' => $this->subscription_tier,
        'isPaid' => $this->isPaid(),
        'isFree' => $this->isFree(),
        'isStarter' => $this->isStarter(),
        'isPro' => $this->isPro(),
        'isPremium' => $this->isPremium(),
        'promptsUsed' => $this->monthly_prompt_count,
        'promptsRemaining' => $this->getPromptsRemaining(),
        'promptLimit' => $this->getPromptLimit(),
        'daysUntilReset' => $this->getDaysUntilPromptReset(),
        'subscriptionEndsAt' => $this->subscription_ends_at?->toIso8601String(),
        'onGracePeriod' => $this->onGracePeriod(),
    ];
}
```

---

### Task 4: Update Subscription Controller

**File**: `app/Http/Controllers/SubscriptionController.php`

**Update `pricing()` method**:

```php
public function pricing(Request $request): Response
{
    $country = $request->route('country') ?? SetCountry::detectCountry($request);
    $setCountry = new SetCountry;
    $currencyCode = $setCountry->resolveCurrencyCode($country, $request);

    // Fetch prices from database
    $pricesData = Price::where('currency_code', $currencyCode)->get();

    // Fetch currency symbol
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
        ];
    }

    return Inertia::render('Pricing', [
        'plans' => $plans,
        'currency' => $currencyCode,
        'currencySymbol' => $currencySymbol,
        // REMOVE availableCurrencies - no currency switcher
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
}
```

**Update `checkout()` method**:

```php
public function checkout(Request $request)
{
    $validated = $request->validate([
        'tier' => ['required', 'in:starter,pro,premium'],
        'interval' => ['required', 'in:monthly,yearly'],
    ]);

    // Get Stripe price ID from database
    $currencyCode = $this->resolveCurrencyCode($request);
    $price = Price::where('tier', $validated['tier'])
        ->where('currency_code', $currencyCode)
        ->where('interval', $validated['interval'])
        ->firstOrFail();

    // Create Stripe checkout session...
}
```

---

### Task 5: Update Stripe Webhook Handler

**File**: `app/Http/Controllers/StripeWebhookController.php`

**Update tier detection** in `handleSubscriptionCreated()` and `handleSubscriptionUpdated()`:

```php
protected function determineTierFromPriceId(string $priceId): ?string
{
    // Query database to find tier
    $price = \App\Models\Price::where('stripe_price_id', $priceId)->first();

    if (!$price) {
        \Log::warning("Unknown Stripe price ID: {$priceId}");
        return null;
    }

    return $price->tier; // 'starter', 'pro', or 'premium'
}
```

**Update subscription tier assignment**:

```php
$user->update([
    'subscription_tier' => $tier, // Can now be 'starter', 'pro', or 'premium'
]);
```

---

### Task 6: Update Frontend Pricing Page

**File**: `resources/js/Pages/Pricing.vue`

**Remove currency switcher** from template:

```vue
<!-- REMOVE THIS SECTION -->
<!-- Currency Switcher -->
<div class="mb-8 flex justify-center gap-2">
    <button v-for="curr in availableCurrencies" ...>
        {{ curr }}
    </button>
</div>
```

**Update props interface**:

```typescript
interface Props {
    plans: PricingPlans;
    featureKeys: {
        free: string[];
        starter: string[]; // NEW
        pro: string[];
        premium: string[]; // Renamed from 'private'
    };
    currency: string;
    currencySymbol: string;
    // REMOVE: availableCurrencies: string[];
}
```

**Update tier cards** (change grid from 3 to 4 columns):

```vue

<div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
    <!-- Free Tier Card -->
    <!-- Starter Tier Card (NEW) -->
    <!-- Pro Tier Card (updated) -->
    <!-- Premium Tier Card (renamed from Private) -->
</div>
```

**Add Starter tier card**:

```vue

<div class="rounded-2xl border border-indigo-200 bg-white p-8 shadow-sm">
    <h2 class="mb-2 text-2xl font-bold text-indigo-900">
        {{ $t('pricing.starter.name') }}
    </h2>
    <div class="mb-6">
        <div class="text-4xl font-bold text-indigo-900">
            {{ currencySymbol }}{{ starterPrice }}
            <span class="text-lg font-normal text-indigo-500">
                /{{ selectedPlan === 'yearly' ? $t('pricing.period.year') : $t('pricing.period.month') }}
            </span>
        </div>
    </div>
    <ul class="mb-8 space-y-3">
        <li v-for="featureKey in featureKeys.starter" :key="featureKey">
            <DynamicIcon name="check" class="h-5 w-5 text-green-500" />
            {{ $t(featureKey) }}
        </li>
    </ul>
    <ButtonPrimary @click="subscribe('starter')">
        {{ $t('pricing.starter.cta') }}
    </ButtonPrimary>
</div>
```

**Update Premium card** (highlight as recommended):

```vue

<div class="relative rounded-2xl border-2 border-indigo-500 bg-white p-8 shadow-md">
    <div
        class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-indigo-500 px-4 py-1 text-sm font-medium text-white">
        {{ $t('pricing.recommendedBadge') }}
    </div>
    <h2>{{ $t('pricing.premium.name') }}</h2>
    <!-- ... -->
</div>
```

**Add encryption notice**:

```vue

<div class="mt-8 text-center text-sm text-gray-600">
    {{ $t('pricing.encryptionNotice') }}
</div>
```

**Remove currency switcher function**:

```typescript
// DELETE function updateCurrency() entirely
```

---

### Task 7: Update Translations

**File**: `resources/js/i18n/locales/en-GB.json`

**Add new pricing keys**:

```json
"pricing": {
"encryptionNotice": "All plans include encryption at rest for your data. VAT included in GBP and EUR prices.",
"recommendedBadge": "Most Popular",
"starter": {
"name": "Starter",
"price": "9.99",
"priceYearly": "99",
"cta": "Get Started",
"yearlySavings": "Save 17% - only {amount}/{period}"
},
"pro": {
"name": "Pro",
"price": "24.99",
"priceYearly": "249",
"cta": "Go Pro",
"yearlySavings": "Save 17% - only {amount}/{period}"
},
"premium": {
"name": "Premium",
"price": "49.99",
"priceYearly": "499",
"cta": "Go Premium",
"yearlySavings": "Save 17% - only {amount}/{period}"
},
"features": {
"free": {
"limit": "10 prompts per month",
"calibration": "Personality-calibrated prompts",
"optimization": "AI prompt optimization"
},
"starter": {
"limit": "25 prompts per month",
"calibration": "Personality-calibrated prompts",
"optimization": "AI prompt optimization",
"history": "Prompt history"
},
"pro": {
"limit": "90 prompts per month",
"calibration": "Personality-calibrated prompts",
"optimization": "AI prompt optimization",
"history": "Unlimited prompt history",
"priority_frameworks": "Priority framework suggestions"
},
"premium": {
"unlimited": "Unlimited prompts",
"calibration": "Personality-calibrated prompts",
"optimization": "AI prompt optimization",
"history": "Unlimited prompt history",
"priority_frameworks": "Priority framework suggestions",
"privacy": "Privacy mode - your data never used for AI training",
"support": "Priority email support"
}
}
}
```

**Update messages**:

```json
"subscription": {
"welcome_starter": "Welcome to BettrPrompt Starter!",
"welcome_pro": "Welcome to BettrPrompt Pro!",
"welcome_premium": "Welcome to BettrPrompt Premium! Enjoy unlimited prompts and priority support.",
"prompt_limit_reached": "You've reached your {limit} prompt limit for this month.",
"prompt_limit_reached_upgrade": "Upgrade to {tier} for {prompts} prompts per month."
}
```

---

### Task 8: Update Privacy Controller

**File**: `app/Http/Controllers/PrivacyController.php`

**Update tier check in `setup()` method**:

```php
public function setup(Request $request)
{
    if (!$request->user()->canEnablePrivacy()) {
        return back()->withErrors([
            'privacy' => __('privacy.premium_required'),
        ]);
    }
    // ... rest of setup
}
```

**Update translation key**:

```json
"privacy": {
"premium_required": "You must be a Premium subscriber to enable privacy encryption."
}
```

---

### Task 9: Update Middleware - Enforce Prompt Limit

**File**: `app/Http/Middleware/EnforcePromptLimit.php`

No changes needed! The middleware uses `User::canCreatePrompt()` which we updated in Task 3.

However, update error messages to be tier-aware:

```php
public function handle(Request $request, Closure $next)
{
    $user = $request->user();

    if (!$user->canCreatePrompt()) {
        $limit = $user->getPromptLimit();
        $tier = $user->subscription_tier;

        // Suggest upgrade based on current tier
        $suggestedTier = match($tier) {
            'free' => 'starter',
            'starter' => 'pro',
            'pro' => 'premium',
            default => null,
        };

        $message = __('subscription.prompt_limit_reached', ['limit' => $limit]);

        if ($suggestedTier) {
            $promptsForNext = config("stripe.tiers.{$suggestedTier}.monthly_prompt_limit");
            $message .= ' ' . __('subscription.prompt_limit_reached_upgrade', [
                'tier' => ucfirst($suggestedTier),
                'prompts' => $promptsForNext ?? 'unlimited',
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('pricing')->with('error', $message);
    }

    return $next($request);
}
```

---

### Task 10: Update Tests

**File**: `tests/Feature/Business/SubscriptionTest.php`

**Update tier test data**:

```php
public function test_user_subscription_tiers()
{
    $freeUser = User::factory()->create(['subscription_tier' => 'free']);
    $starterUser = User::factory()->create(['subscription_tier' => 'starter']);
    $proUser = User::factory()->create(['subscription_tier' => 'pro']);
    $premiumUser = User::factory()->create(['subscription_tier' => 'premium']);

    $this->assertTrue($freeUser->isFree());
    $this->assertFalse($freeUser->isPaid());

    $this->assertTrue($starterUser->isStarter());
    $this->assertTrue($starterUser->isPaid());

    $this->assertTrue($proUser->isPro());
    $this->assertTrue($proUser->isPaid());

    $this->assertTrue($premiumUser->isPremium());
    $this->assertTrue($premiumUser->isPaid());
}
```

**Update prompt limit tests**:

```php
public function test_prompt_limits_by_tier()
{
    $free = User::factory()->create(['subscription_tier' => 'free', 'monthly_prompt_count' => 0]);
    $this->assertEquals(10, $free->getPromptLimit());
    $this->assertEquals(10, $free->getPromptsRemaining());

    $starter = User::factory()->create(['subscription_tier' => 'starter', 'monthly_prompt_count' => 0]);
    $this->assertEquals(25, $starter->getPromptLimit());
    $this->assertEquals(25, $starter->getPromptsRemaining());

    $pro = User::factory()->create(['subscription_tier' => 'pro', 'monthly_prompt_count' => 0]);
    $this->assertEquals(90, $pro->getPromptLimit());
    $this->assertEquals(90, $pro->getPromptsRemaining());

    $premium = User::factory()->create(['subscription_tier' => 'premium']);
    $this->assertEquals(PHP_INT_MAX, $premium->getPromptLimit());
    $this->assertTrue($premium->canCreatePrompt());
}

public function test_free_tier_reaches_limit()
{
    $user = User::factory()->create([
        'subscription_tier' => 'free',
        'monthly_prompt_count' => 10,
    ]);

    $this->assertFalse($user->canCreatePrompt());
}

public function test_starter_tier_reaches_limit()
{
    $user = User::factory()->create([
        'subscription_tier' => 'starter',
        'monthly_prompt_count' => 25,
    ]);

    $this->assertFalse($user->canCreatePrompt());
}

public function test_pro_tier_reaches_limit()
{
    $user = User::factory()->create([
        'subscription_tier' => 'pro',
        'monthly_prompt_count' => 90,
    ]);

    $this->assertFalse($user->canCreatePrompt());
}
```

**Update checkout tests**:

```php
public function test_checkout_creates_session_for_starter()
{
    $user = User::factory()->create(['subscription_tier' => 'free']);

    $response = $this->actingAs($user)
        ->post(route('subscription.checkout'), [
            'tier' => 'starter',
            'interval' => 'monthly',
        ]);

    $response->assertOk();
    $response->assertJsonStructure(['url']);
}

public function test_checkout_validates_tier()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('subscription.checkout'), [
            'tier' => 'invalid_tier',
            'interval' => 'monthly',
        ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['tier']);
}
```

**File**: `tests/Feature/Middleware/EnforcePromptLimitTest.php`

**Update middleware tests for new tiers**:

```php
public function test_starter_tier_blocked_when_limit_reached()
{
    $user = User::factory()->create([
        'subscription_tier' => 'starter',
        'monthly_prompt_count' => 25,
    ]);

    $response = $this->actingAs($user)
        ->post(route('prompt-builder.pre-analyse'), [
            'task' => 'Test task',
        ]);

    $response->assertRedirect(route('pricing'));
    $response->assertSessionHas('error');
}

public function test_pro_tier_blocked_when_limit_reached()
{
    $user = User::factory()->create([
        'subscription_tier' => 'pro',
        'monthly_prompt_count' => 90,
    ]);

    $response = $this->actingAs($user)
        ->post(route('prompt-builder.pre-analyse'), [
            'task' => 'Test task',
        ]);

    $response->assertRedirect(route('pricing'));
}

public function test_premium_tier_never_blocked()
{
    $user = User::factory()->create([
        'subscription_tier' => 'premium',
        'monthly_prompt_count' => 999,
    ]);

    $response = $this->actingAs($user)
        ->post(route('prompt-builder.pre-analyse'), [
            'task' => 'Test task',
        ]);

    $response->assertOk(); // Should not be blocked
}
```

**File**: `tests/Feature/PrivacyControllerTest.php`

**Update privacy tier requirement tests**:

```php
public function test_only_premium_users_can_enable_privacy()
{
    $starter = User::factory()->create(['subscription_tier' => 'starter']);
    $pro = User::factory()->create(['subscription_tier' => 'pro']);
    $premium = User::factory()->create(['subscription_tier' => 'premium']);

    $this->assertFalse($starter->canEnablePrivacy());
    $this->assertFalse($pro->canEnablePrivacy());
    $this->assertTrue($premium->canEnablePrivacy());
}

public function test_starter_tier_cannot_access_privacy_setup()
{
    $user = User::factory()->create(['subscription_tier' => 'starter']);

    $response = $this->actingAs($user)
        ->get(route('privacy.setup'));

    $response->assertRedirect();
    $response->assertSessionHasErrors(['privacy']);
}
```

---

### Task 11: Update User Factory

**File**: `database/factories/UserFactory.php`

**Update subscription tier definition**:

```php
public function definition(): array
{
    return [
        // ...
        'subscription_tier' => 'free', // Default to free
        'monthly_prompt_count' => 0,
        // ...
    ];
}

// Add trait methods for convenience
public function starter(): static
{
    return $this->state(fn (array $attributes) => [
        'subscription_tier' => 'starter',
    ]);
}

public function pro(): static
{
    return $this->state(fn (array $attributes) => [
        'subscription_tier' => 'pro',
    ]);
}

public function premium(): static
{
    return $this->state(fn (array $attributes) => [
        'subscription_tier' => 'premium',
    ]);
}
```

---

## Critical Files to Modify

| File                                                       | Purpose                                          | Est. Lines Changed |
|------------------------------------------------------------|--------------------------------------------------|--------------------|
| `database/migrations/YYYY_MM_DD_add_new_pricing_tiers.php` | Add new tier prices to database                  | ~150               |
| `config/stripe.php`                                        | Add tier prompt limits                           | +20                |
| `.env` / `.env.example`                                    | Add new Stripe price IDs                         | +18                |
| `app/Models/User.php`                                      | Add new tier check methods, update prompt limits | ~50                |
| `app/Http/Controllers/SubscriptionController.php`          | Update pricing page, checkout validation         | ~30                |
| `app/Http/Controllers/StripeWebhookController.php`         | Update tier detection from price ID              | ~20                |
| `app/Http/Controllers/PrivacyController.php`               | Restrict privacy to Premium tier                 | ~10                |
| `app/Http/Middleware/EnforcePromptLimit.php`               | Update error messages for tiers                  | ~20                |
| `resources/js/Pages/Pricing.vue`                           | Add Starter tier, remove currency switcher       | ~100               |
| `resources/js/i18n/locales/en-GB.json`                     | Add new tier translations                        | ~50                |
| `tests/Feature/Business/SubscriptionTest.php`              | Update tier and limit tests                      | ~100               |
| `tests/Feature/Middleware/EnforcePromptLimitTest.php`      | Add tier-specific tests                          | ~50                |
| `tests/Feature/PrivacyControllerTest.php`                  | Update privacy tier tests                        | ~30                |
| `database/factories/UserFactory.php`                       | Add tier factory methods                         | ~20                |

**Total**: ~668 lines of changes across 14 files

---

## Implementation Order

### Phase 1: Database & Config (Foundation)

1. **Create migration** for new tier prices
2. **Update config/stripe.php** with tier prompt limits
3. **Add Stripe price IDs** to .env (requires creating products in Stripe dashboard first)
4. **Run migration**: `sail artisan migrate`

### Phase 2: Backend Logic (Core)

5. **Update User model** with new tier methods
6. **Update SubscriptionController** pricing and checkout methods
7. **Update StripeWebhookController** tier detection
8. **Update PrivacyController** tier requirement
9. **Update EnforcePromptLimit** middleware error messages

### Phase 3: Frontend (UI)

10. **Update Pricing.vue** - add Starter tier, remove currency switcher
11. **Add translations** to en-GB.json
12. **Test pricing page** at `/gb/pricing`, `/us/pricing`, `/de/pricing`

### Phase 4: Testing (Validation)

13. **Update SubscriptionTest** with new tier tests
14. **Update EnforcePromptLimitTest** with tier limits
15. **Update PrivacyControllerTest** with Premium requirement
16. **Update UserFactory** with tier trait methods
17. **Run all tests**: `sail test`

### Phase 5: Stripe Configuration (External)

18. **Create Stripe products** for Starter and Premium tiers
19. **Create price points** (monthly/yearly × 3 currencies × 2 new tiers = 12 new prices)
20. **Update existing Pro prices** in Stripe to new amounts
21. **Copy price IDs to .env**
22. **Verify webhook events** work with new price IDs

---

## Verification Plan

### 1. Database Verification

```bash
sail artisan tinker
>>> Price::count() # Should be 18 (3 tiers × 2 intervals × 3 currencies)
>>> Price::where('tier', 'starter')->count() # Should be 6
>>> Price::where('tier', 'premium')->count() # Should be 6
>>> User::where('subscription_tier', 'premium')->count() # Old 'private' users migrated
```

### 2. Pricing Page Verification

```bash
# Visit pricing pages in different regions
open https://app.localhost/gb/pricing  # Should show GBP, no currency switcher
open https://app.localhost/us/pricing  # Should show USD, no currency switcher
open https://app.localhost/de/pricing  # Should show EUR, no currency switcher

# Verify:
- 4 tier cards displayed (Free, Starter, Pro, Premium)
- Premium card has "Most Popular" badge
- Correct prices for each tier in correct currency
- Encryption notice at bottom
- NO currency switcher buttons
- Starter: 25 prompts listed
- Pro: 90 prompts listed
- Premium: "Unlimited" prompts listed
```

### 3. Subscription Flow Verification

```bash
# Test checkout for each tier
1. Click "Get Started" on Starter tier
2. Should redirect to Stripe checkout with correct price
3. Complete checkout (use Stripe test card: 4242 4242 4242 4242)
4. Redirect back to app
5. User should have subscription_tier = 'starter'
6. Verify prompt limit is 25

# Repeat for Pro and Premium tiers
```

### 4. Prompt Limit Verification

```bash
sail artisan tinker

# Test Starter tier
>>> $user = User::factory()->create(['subscription_tier' => 'starter', 'monthly_prompt_count' => 0])
>>> $user->getPromptLimit() # Should be 25
>>> $user->canCreatePrompt() # Should be true
>>> $user->monthly_prompt_count = 25
>>> $user->canCreatePrompt() # Should be false

# Test Pro tier
>>> $user = User::factory()->create(['subscription_tier' => 'pro', 'monthly_prompt_count' => 0])
>>> $user->getPromptLimit() # Should be 90
>>> $user->canCreatePrompt() # Should be true
>>> $user->monthly_prompt_count = 90
>>> $user->canCreatePrompt() # Should be false

# Test Premium tier
>>> $user = User::factory()->create(['subscription_tier' => 'premium', 'monthly_prompt_count' => 999])
>>> $user->canCreatePrompt() # Should be true (unlimited)
```

### 5. Privacy Feature Verification

```bash
# Test Premium tier can enable privacy
sail artisan tinker
>>> $premium = User::factory()->premium()->create()
>>> $premium->canEnablePrivacy() # Should be true

# Test Pro tier CANNOT enable privacy
>>> $pro = User::factory()->pro()->create()
>>> $pro->canEnablePrivacy() # Should be false

# Test Starter tier CANNOT enable privacy
>>> $starter = User::factory()->starter()->create()
>>> $starter->canEnablePrivacy() # Should be false
```

### 6. Test Suite Verification

```bash
sail test --filter=Subscription
sail test --filter=EnforcePromptLimit
sail test --filter=Privacy
sail test # Run all tests

# All tests should pass
```

### 7. Webhook Verification

```bash
# Use Stripe CLI to test webhooks
stripe listen --forward-to https://app.localhost/stripe/webhook

# In another terminal, trigger test events
stripe trigger customer.subscription.created
stripe trigger customer.subscription.updated
stripe trigger customer.subscription.deleted

# Verify in logs that tier is correctly detected from price ID
```

---

## Edge Cases Handled

1. **Existing 'private' tier users** → Migrated to 'premium' in migration
2. **Old Stripe price IDs** → Webhook still handles them, logs warning if unknown
3. **Currency mismatch** → User's currency preference respected, Stripe adds VAT based on billing address
4. **User tries to access privacy with Pro tier** → Redirected with error message
5. **Prompt count exceeds limit** → Middleware blocks, suggests appropriate upgrade tier
6. **User downgrades from Premium to Pro** → Privacy features remain accessible (grace period)
7. **Migration rollback** → Down migration restores old structure
8. **Missing Stripe price ID in .env** → Migration fails gracefully with clear error
9. **User manually changes URL to different currency** → Stripe still charges correct VAT based on billing location
10. **Free user tries to access paid feature** → Redirected to pricing page with appropriate message

---

## Rollback Plan

If issues arise after deployment:

1. **Immediate**: Roll back database migration
   ```bash
   sail artisan migrate:rollback
   ```

2. **Restore old prices**: Previous migration restores old price structure

3. **User tier data**: Down migration converts 'premium' → 'private', removes 'starter'

4. **Frontend**: Previous commit restores 3-tier pricing page with currency switcher

5. **Stripe**: Old price IDs remain active, webhooks still work

---

## Post-Deployment Tasks

1. **Monitor Stripe webhook events** for correct tier assignment
2. **Check error logs** for any prompt limit enforcement issues
3. **Verify conversion rates** from Free → Starter vs Free → Pro
4. **Update documentation** with new tier structure
5. **Send email to existing users** about pricing changes (if applicable)
6. **Create Stripe coupons** for migrations/grandfathering if needed
7. **Update FAQ** on pricing page with new tier explanations

---

## Success Criteria

✅ Database has 18 price records (3 tiers × 2 intervals × 3 currencies)
✅ All existing 'private' users migrated to 'premium' tier
✅ Pricing page displays 4 tiers with correct prices
✅ Currency switcher removed, auto-detection by region works
✅ Stripe checkout works for all 3 paid tiers (Starter, Pro, Premium)
✅ Prompt limits enforced correctly: Free (10), Starter (25), Pro (90), Premium (unlimited)
✅ Privacy feature restricted to Premium tier only
✅ Encryption notice displayed on pricing page
✅ All tests pass (subscription, prompt limits, privacy)
✅ Webhooks correctly assign tiers from new Stripe price IDs
✅ Error messages suggest appropriate upgrade tier based on current tier
