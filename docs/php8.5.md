# PHP 8.5 Modernisation Plan

## Overview

Leverage PHP 8.5's new features to improve code quality, readability, and maintainability across the BettrPrompt
codebase. Focus on the two most impactful features:

1. **Property Hooks** - Convert 21+ getter methods to computed properties
2. **Asymmetric Visibility** - Improve encapsulation for service configuration

## PHP 8.5 Features Assessment

### ✅ High Impact: Property Hooks

Convert getter methods to computed properties for cleaner, more intuitive APIs.

**Benefits:**

- Cleaner syntax: `$user->isPaid` instead of `$user->isPaid()`
- Better integration with Inertia.js/Vue (properties serialize automatically)
- Maintains encapsulation with computed values
- Type-safe with IDE support

### ⚠️ Medium Impact: Asymmetric Visibility

Use `public private(set)` for read-only service configuration.

**Benefits:**

- Explicit public read / private write semantics
- Useful for exposing internal state without setters
- Limited applicability in Laravel's DI architecture

### ❌ Low Impact: New Without Parentheses

No current patterns found in codebase. Style preference for future code.

---

## Implementation Strategy

**Approach:** Incremental adoption in three phases

- **Phase 1:** High-value models (User, PromptRun) - Maximum impact
- **Phase 2:** Supporting models and DTOs - Good developer experience improvements
- **Phase 3:** Service layer - Architectural improvements (optional)

**Timeline:** 2-3 days

---

## Phase 1: High-Value Models (Day 1)

### 1.1 User Model - Subscription Properties

**File:** `/home/mark/repos/bettrprompt/app/Models/User.php`

**Lines:** 508-553

Convert 6 subscription checking methods to property hooks:

```php
// ❌ Before:
public function isPaid(): bool
{
    return $this->subscribed('default') ||
           ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) ||
           in_array($this->subscription_tier, ['starter', 'pro', 'premium']);
}

public function isStarter(): bool
{
    return $this->subscription_tier === 'starter';
}

public function isPro(): bool
{
    return $this->subscription_tier === 'pro';
}

// ... (3 more methods)

// ✅ After:
public bool $isPaid {
    get => $this->subscribed('default') ||
           ($this->subscription_ends_at && $this->subscription_ends_at->isFuture()) ||
           in_array($this->subscription_tier, ['starter', 'pro', 'premium']);
}

public bool $isStarter {
    get => $this->subscription_tier === 'starter';
}

public bool $isPro {
    get => $this->subscription_tier === 'pro';
}

public bool $isPremium {
    get => $this->subscription_tier === 'premium';
}

public bool $isFree {
    get => ! $this->isPaid;
}

public bool $isOnGracePeriod {
    get => $this->subscription()?->onGracePeriod() ?? false;
}
```

**Impact:**

- Controllers can use `$user->isPro` instead of `$user->isPro()`
- Inertia.js resources automatically serialize properties
- More intuitive for frontend developers

---

### 1.2 User Model - Utility Properties

**File:** `/home/mark/repos/bettrprompt/app/Models/User.php`

**Lines:** 173-180, 253-256

Convert utility methods with lazy initialization:

```php
// ❌ Before:
public function getReferralCode(): string
{
    if (! $this->referral_code) {
        return $this->generateReferralCode();
    }
    return $this->referral_code;
}

public function getUiComplexity(): string
{
    return $this->ui_complexity ?? 'advanced';
}

// ✅ After:
public string $referralCode {
    get => $this->referral_code ?? $this->generateReferralCode();
}

public string $uiComplexity {
    get => $this->ui_complexity ?? 'advanced';
}
```

**Note:** Update all usages from `$user->getReferralCode()` to `$user->referralCode`

---

### 1.3 PromptRun Model - Status Properties

**File:** `/home/mark/repos/bettrprompt/app/Models/PromptRun.php`

**Lines:** 165-184

Convert workflow status methods to properties:

```php
// ❌ Before:
public function isProcessing(): bool
{
    return $this->workflow_stage?->isProcessing() ?? false;
}

public function isCompleted(): bool
{
    return $this->workflow_stage === WorkflowStage::GenerationCompleted;
}

public function isFailed(): bool
{
    return $this->workflow_stage?->isFailed() ?? false;
}

// ✅ After:
public bool $isProcessing {
    get => $this->workflow_stage?->isProcessing() ?? false;
}

public bool $isCompleted {
    get => $this->workflow_stage === WorkflowStage::GenerationCompleted;
}

public bool $isFailed {
    get => $this->workflow_stage?->isFailed() ?? false;
}
```

**Impact:**

- Consistent property access pattern across models
- Better serialization in Inertia.js responses
- Frontend can use `promptRun.isCompleted` directly

---

### 1.4 Verification (Phase 1)

```bash
# Run model tests
./vendor/bin/sail test tests/Unit/Models/UserTest.php
./vendor/bin/sail test tests/Unit/Models/PromptRunTest.php

# Run integration tests
./vendor/bin/sail test tests/Feature/

# Expected: All tests pass (property access is backwards compatible)
```

---

## Phase 2: Supporting Models & DTOs (Day 2)

### 2.1 Visitor Model

**File:** `/home/mark/repos/bettrprompt/app/Models/Visitor.php`

**Lines:** 120-157

```php
// ❌ Before:
public function hasConverted(): bool
{
    return $this->user_id !== null && $this->converted_at !== null;
}

public function isReturning(): bool
{
    if ($this->first_visit_at && $this->last_visit_at) {
        return $this->first_visit_at->diffInHours($this->last_visit_at) >= 1;
    }
    return false;
}

public function hasCompletedPrompts(): bool
{
    return $this->promptRuns()
        ->where('workflow_stage', WorkflowStage::GenerationCompleted->value)
        ->whereNotNull('optimized_prompt')
        ->exists();
}

public function hasLocationData(): bool
{
    return ! is_null($this->country_code) && ! is_null($this->timezone);
}

// ✅ After:
public bool $hasConverted {
    get => $this->user_id !== null && $this->converted_at !== null;
}

public bool $isReturning {
    get {
        if ($this->first_visit_at && $this->last_visit_at) {
            return $this->first_visit_at->diffInHours($this->last_visit_at) >= 1;
        }
        return false;
    }
}

public bool $hasCompletedPrompts {
    get => $this->promptRuns()
        ->where('workflow_stage', WorkflowStage::GenerationCompleted->value)
        ->whereNotNull('optimized_prompt')
        ->exists();
}

public bool $hasLocationData {
    get => ! is_null($this->country_code) && ! is_null($this->timezone);
}
```

---

### 2.2 Price Model - Formatted Display

**File:** `/home/mark/repos/bettrprompt/app/Models/Price.php`

**Lines:** 33-52

```php
// ❌ Before:
public function getFormattedAttribute(): string
{
    $currency = $this->currency;
    if (! $currency) {
        return (string) $this->amount;
    }

    $symbol = $currency->symbol;
    $amount = number_format(
        $this->amount,
        $currency->decimal_digits,
        $currency->decimal_separator,
        $currency->thousands_separator
    );

    if ($currency->symbol_on_left) {
        $glue = $currency->space_between_amount_and_symbol ? ' ' : '';
        return $symbol.$glue.$amount;
    } else {
        $glue = $currency->space_between_amount_and_symbol ? ' ' : '';
        return $amount.$glue.$symbol;
    }
}

// ✅ After:
public string $formatted {
    get {
        $currency = $this->currency;
        if (! $currency) {
            return (string) $this->amount;
        }

        $symbol = $currency->symbol;
        $amount = number_format(
            $this->amount,
            $currency->decimal_digits,
            $currency->decimal_separator,
            $currency->thousands_separator
        );

        if ($currency->symbol_on_left) {
            $glue = $currency->space_between_amount_and_symbol ? ' ' : '';
            return $symbol.$glue.$amount;
        } else {
            $glue = $currency->space_between_amount_and_symbol ? ' ' : '';
            return $amount.$glue.$symbol;
        }
    }
}
```

**Note:** Update blade templates/components from `{{ $price->formatted }}` (accessor) to same syntax (property hooks
maintain compatibility)

---

### 2.3 LocationData DTO

**File:** `/home/mark/repos/bettrprompt/app/DTOs/LocationData.php`

**Lines:** 63-78

```php
// ❌ Before:
public function isComplete(): bool
{
    return ! is_null($this->countryCode) && ! is_null($this->timezone);
}

public function getSummary(): string
{
    if (is_null($this->city)) {
        return "$this->region, $this->countryName";
    }
    return "$this->city, $this->region, $this->countryName";
}

// ✅ After:
public bool $isComplete {
    get => ! is_null($this->countryCode) && ! is_null($this->timezone);
}

public string $summary {
    get => is_null($this->city)
        ? "$this->region, $this->countryName"
        : "$this->city, $this->region, $this->countryName";
}
```

---

### 2.4 Experiment Model

**File:** `/home/mark/repos/bettrprompt/app/Models/Experiment.php`

**Lines:** 108-113

```php
// ❌ Before:
public function isRunning(): bool
{
    return $this->status === 'running' &&
        $this->started_at?->isPast() &&
        (! $this->ended_at || $this->ended_at->isFuture());
}

// ✅ After:
public bool $isRunning {
    get => $this->status === 'running' &&
           $this->started_at?->isPast() &&
           (! $this->ended_at || $this->ended_at->isFuture());
}
```

---

### 2.5 Verification (Phase 2)

```bash
# Run all model tests
./vendor/bin/sail test tests/Unit/Models/

# Run feature tests
./vendor/bin/sail test tests/Feature/

# Expected: All tests pass
```

---

## Phase 3: Service Layer (Optional - Day 3)

### 3.1 N8nWorkflowClient - Configuration Properties

**File:** `/home/mark/repos/bettrprompt/app/Services/N8nWorkflowClient.php`

**Lines:** 20-40

**Current:**

```php
private string $n8nBaseUrl;
private string $apiKey;
private bool $isEnabled;

public function __construct()
{
    $this->n8nBaseUrl = config('services.n8n.url');
    $this->apiKey = config('services.n8n.api_key');
    $this->isEnabled = config('services.n8n.enabled', true);
}
```

**With Asymmetric Visibility:**

```php
public private(set) string $n8nBaseUrl;
public private(set) string $apiKey;
public private(set) bool $isEnabled;

public function __construct()
{
    $this->n8nBaseUrl = config('services.n8n.url');
    $this->apiKey = config('services.n8n.api_key');
    $this->isEnabled = config('services.n8n.enabled', true);
}
```

**Benefit:** Exposes configuration for debugging/logging while preventing modification

---

### 3.2 GeolocationService - Reader Instance

**File:** `/home/mark/repos/bettrprompt/app/Services/GeolocationService.php`

**Line:** 14

**Current:**

```php
private ?Reader $reader = null;
```

**With Asymmetric Visibility:**

```php
public private(set) ?Reader $reader = null;
```

**Benefit:** Limited - mostly for internal inspection. Consider skipping if not needed.

---

### 3.3 Verification (Phase 3)

```bash
# Run service tests
./vendor/bin/sail test tests/Unit/Services/

# Expected: All tests pass
```

---

## Update Strategy for Controllers/Resources

### Controller Updates

Search for method calls that now become property accesses:

```bash
# Find usage of old method calls
grep -r "->isPaid()" app/Http/Controllers/
grep -r "->isProcessing()" app/Http/Controllers/
grep -r "->hasConverted()" app/Http/Controllers/
grep -r "->getReferralCode()" app/Http/Controllers/
```

**Update pattern:**

```php
// ❌ Before:
if ($user->isPaid()) {
    // ...
}

// ✅ After:
if ($user->isPaid) {
    // ...
}
```

### Resource Updates

**File:** `/home/mark/repos/bettrprompt/app/Http/Resources/UserResource.php`

Property hooks automatically serialize in resources:

```php
// Property hooks work seamlessly:
return [
    'id' => $this->id,
    'isPaid' => $this->isPaid,  // Works with property hook
    'isStarter' => $this->isStarter,  // Works with property hook
    'referralCode' => $this->referralCode,  // Works with property hook
];
```

---

## Critical Files Summary

### Phase 1 - High-Value Models

- `/home/mark/repos/bettrprompt/app/Models/User.php` (6 subscription properties, 2 utility properties)
- `/home/mark/repos/bettrprompt/app/Models/PromptRun.php` (3 status properties)

### Phase 2 - Supporting Models

- `/home/mark/repos/bettrprompt/app/Models/Visitor.php` (4 status properties)
- `/home/mark/repos/bettrprompt/app/Models/Price.php` (1 formatted property)
- `/home/mark/repos/bettrprompt/app/DTOs/LocationData.php` (2 computed properties)
- `/home/mark/repos/bettrprompt/app/Models/Experiment.php` (1 status property)

### Phase 3 - Services (Optional)

- `/home/mark/repos/bettrprompt/app/Services/N8nWorkflowClient.php` (3 config properties)
- `/home/mark/repos/bettrprompt/app/Services/GeolocationService.php` (1 reader property)

---

## Backwards Compatibility

### Property Hooks

✅ **Fully backwards compatible** - Property access works the same way:

- Method calls: `$user->isPaid()` ❌ (will fail after conversion)
- Property access: `$user->isPaid` ✅ (works before and after)

**Migration path:**

1. Update method to property hook
2. Search and replace method calls `->isPaid()` with property access `->isPaid`
3. Run tests to verify

### Asymmetric Visibility

✅ **Additive only** - No breaking changes:

- Private properties become readable externally
- No existing code relies on write access
- Can be gradually adopted

---

## Testing Strategy

### Unit Tests

Property hooks work seamlessly in tests:

```php
// ✅ Works with property hooks:
$user = User::factory()->create(['subscription_tier' => 'pro']);
$this->assertTrue($user->isPro);
$this->assertFalse($user->isFree);

$promptRun = PromptRun::factory()->processing()->create();
$this->assertTrue($promptRun->isProcessing);
```

### Integration Tests

No changes needed - Inertia.js resources automatically serialize properties.

### E2E Tests

Frontend code can access properties directly:

```typescript
// Vue component (TypeScript)
if (user.isPaid) {
    // Access upgraded features
}

if (promptRun.isCompleted) {
    // Show optimised prompt
}
```

---

## Expected Outcomes

**Code Quality:**

- ✅ **21 methods** converted to property hooks
- ✅ Cleaner API surface (property access vs method calls)
- ✅ Better Inertia.js integration
- ✅ More intuitive for frontend developers

**Performance:**

- ⚪ Neutral - Property hooks have same performance as methods
- ⚪ No lazy loading overhead (computed on access)

**Maintainability:**

- ✅ Consistent property-based API across models
- ✅ Type-safe with IDE support
- ✅ Automatic serialization in resources

---

## Risk Mitigation

1. **Method to Property Migration:** Use search/replace to update all call sites
2. **Test Coverage:** Existing tests work unchanged (property access compatible)
3. **Gradual Rollout:** Can be done model-by-model over time
4. **Rollback:** Easy to revert individual models if issues arise

---

## Recommended Implementation Order

**Day 1 - High Impact:**

1. User model subscription properties (most widely used)
2. PromptRun status properties (used in controllers and frontend)
3. Update controllers and resources

**Day 2 - Good Developer Experience:**

1. Visitor model properties
2. Price model formatted property
3. LocationData DTO properties
4. Experiment model property

**Day 3 - Optional Polish:**

1. Service layer asymmetric visibility (if beneficial)
2. Any remaining models discovered during implementation

---

## Notes

- Property hooks maintain Laravel's magic property behavior
- Eloquent relationships still work as before
- No database migrations required
- Frontend TypeScript types may need updating to reflect property vs method syntax
