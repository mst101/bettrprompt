# Privacy-First Paid Tier Implementation Plan

## Executive Summary

This plan makes **encryption at rest the default for everyone** (users and visitors), and then layers a **paid “Private mode”** on top that reduces data access and use for product improvement.

Key idea: **encryption at rest is baseline security, not the premium feature**. The paid tier is about **access control, purpose limitation (no training/improvement), and stronger key separation**.

### Pricing Structure

| Tier | Price | Prompts | Privacy | Data Usage |
|------|-------|---------|---------|------------|
| **Free** | £0 | 10/month | Standard | May be used to improve the service (with clear disclosure/consent) |
| **Unlimited** | £12/month or £120/year | Unlimited | Standard | May be used to improve the service (with clear disclosure/consent) |
| **Private** | £20/month or £200/year | Unlimited | Private mode | Not used for training/improvement; restricted internal access |

**Annual discount**: ~17% (Unlimited: £120/year = £10/month vs £12/month; Private: £200/year = £16.67/month vs £20/month)

**Suggested localized price points (fixed per currency, not live FX):**

| Tier | GBP | EUR | USD |
|------|-----|-----|-----|
| **Unlimited (Monthly)** | £12/month | €13.99/month | $15.99/month |
| **Unlimited (Annual)** | £120/year | €139/year | $159/year |
| **Private (Monthly)** | £20/month | €22.99/month | $26.99/month |
| **Private (Annual)** | £200/year | €229/year | $269/year |

### Three-Tier Data Model

| Tier | Data Storage | Analytics Access | Use Case |
|------|-------------|------------------|----------|
| **Free** | Encrypted at rest (platform-managed keys) | ✅ Allowed (policy + consent) | Default - enables product improvement while meeting baseline security expectations |
| **Unlimited** | Encrypted at rest (platform-managed keys) | ✅ Allowed (policy + consent) | Pay for usage; still supports product improvement under clear disclosure/consent |
| **Private (Paid)** | Encrypted at rest + stronger key separation (user-controlled envelope key) | ❌ Disabled by default | Users needing confidentiality for sensitive prompts; “private mode” behaviour |

**Key Point**: Free-tier data is still encrypted at rest, but may be accessed/used under strict controls for:

- System improvement and prompt quality analysis
- ML/training only with appropriate consent and vendor controls
- Usage pattern analysis
- Debugging and support

### Key Decisions Made

- ✅ **10 prompts/month free tier** - enough to try the service
- ✅ **£12/month unlimited tier** - pay for usage, not privacy
- ✅ **£120/year annual unlimited option** - ~17% discount for commitment
- ✅ **£20/month private tier** - premium for confidentiality and reduced data use
- ✅ **£200/year annual private option** - ~17% discount for commitment
- ✅ **Encryption at rest for all tiers** - baseline protection for sensitive prompt/personality data
- ✅ **Cross-device access** required - rules out client-side only storage
- ✅ **Recovery phrase** option - balance security with usability
- ✅ **Prompt history** preserved - users iterate on past prompts
- ✅ **Private tier** - restrict internal access and disallow training/improvement use

---

## Current Data Landscape

### Highest Sensitivity (User-Generated Content)

| Table | Column | Description |
|-------|--------|-------------|
| `prompt_runs` | `task_description` | User's original task/question |
| `prompt_runs` | `optimized_prompt` | Generated personality-calibrated prompt |
| `prompt_runs` | `pre_analysis_answers` | Answers to clarifying questions |
| `prompt_runs` | `clarifying_answers` | Answers to framework questions |

### High Sensitivity (PII)

| Table | Column | Description |
|-------|--------|-------------|
| `users`/`visitors` | `email` | Email address |
| `visitors` | `ip_address` | IP address |
| `users`/`visitors` | Location fields | Country, city, lat/long, timezone |
| `users`/`visitors` | `personality_type` + `trait_percentages` | Complete MBTI profile |
| `users` | Professional fields | Job title, industry, experience level |

### Data Flow to External Services

- **n8n workflows**: Receive task description, personality data, user context
- **OpenAI Whisper**: Audio files (deleted immediately after transcription)
- **Anthropic Claude**: Called through n8n (not directly from Laravel)

---

## Key Constraint

**True end-to-end encryption is incompatible with the AI processing model.**

The n8n workflows (and ultimately Claude) MUST see the plaintext task description to generate prompts. Any encryption can only protect data "at rest" - during processing, data must be decrypted.

---

## What Changes With This Updated Approach

### Baseline (All Users + Visitors): Encrypt At Rest By Default

Encrypt sensitive fields for everyone (including free users and visitors). This reduces breach impact and is easier to justify under “appropriate security” expectations for high-sensitivity data.

This baseline encryption is **platform-managed** (the application can decrypt), so it does not prevent legitimate uses (support, abuse handling, service improvement) where you have a lawful basis and have disclosed it.

### Private Tier: “Private Mode” (Purpose Limitation + Stronger Key Separation)

Private does not merely “turn on encryption”. It enforces:

- **No training/improvement use** of Private-tier prompt content by default
- **Restricted internal access** (default-deny; audited “break-glass” for support if you choose to offer it)
- **Stronger key separation** (envelope encryption where the app cannot decrypt Private-tier content without a user-held secret/session-unlock)

Pragmatically: your workflows still process plaintext during generation, but you reduce what is retained and who can access it afterwards.

---

## Support & Investigation Access Model (Serious-Systems Pattern)

We explicitly avoid a universal “admin backdoor” for private-mode content. Instead, we support two controlled paths:

1. **User-consented support session** (Private tier / Private mode)
2. **Admin break-glass** (Free tier only, audited + restricted)

This keeps baseline security strong while still allowing operational support and legitimate investigations.

### Principle Summary

- **Default-deny access** to user content for staff.
- **Least privilege + scoped access** (specific user, specific prompt run IDs, time-limited).
- **Auditable + attributable** (who accessed what, when, why).
- **User transparency** (clear UI, confirmations, and notifications).

### Private Tier (Private Mode): User-Consented Support Session

Private mode assumes the user’s content cannot be decrypted by staff unless the user explicitly consents.

**User experience**
- Support creates a **support access request** tied to a ticket/reference (reason required).
- User sees the request in-app (and optionally via email) and can:
  - approve or deny
  - choose scope (specific prompt run(s) / date range)
  - choose duration (e.g., 15 minutes / 1 hour / 24 hours)
  - revoke at any time
- Approval requires a fresh re-auth (password re-entry; 2FA later).

**Technical requirements**
- **No standing access**: staff cannot decrypt private-mode data without an active, approved support session.
- On approval, the user explicitly “unlocks” their key for the duration:
  - backend stores an **ephemeral decrypt capability** in a short-lived store (e.g. Redis) with TTL
  - capability is scoped to the approved scope (user_id + prompt_run_ids)
  - capability is revoked immediately on expiry or user revocation
- Every content view/download is logged to an immutable audit log.

**Constraints/clarifications**
- AI generation still requires plaintext during processing; private mode focuses on *retention and access after the fact*.
- Any debugging that would require “server-side viewing” of decrypted content must be behind a consented session and fully audited.

### Free Tier: Admin Break-Glass (Audited + Restricted)

Free-tier data uses platform-managed encryption keys, so the application can decrypt. We still treat access as exceptional.

**Requirements**
- Break-glass access is limited to:
  - authenticated free-tier users (not anonymous visitors), unless a separate policy is defined
  - specific user + specific records (no “browse all users” view)
  - a short duration (e.g. 15–60 minutes)
- Break-glass requires:
  - reason + ticket/reference ID
  - explicit “break glass” confirmation UX
  - optional two-person approval for high-sensitivity actions (recommended)
  - a feature flag / configuration toggle to keep break-glass off by default in early stages
- Break-glass is **read-only** by default:
  - no export, bulk download, or API dumping without an additional approval step
  - redaction defaults for highly sensitive fields where possible
- User notification:
  - notify the user after access (in-app + email) unless prohibited by law (handle separately)
- Immutable audit logs for:
  - session creation, approvals, and expirations
  - every record opened/viewed
  - any export attempt (even if blocked)

**Suggested workflow**
1. Admin creates a break-glass request (free tier only) with reason + ticket reference.
2. System requires a second admin approval for “view content” (recommended) and always for export.
3. Approved break-glass session becomes active for a short TTL.
4. Every access is logged; session auto-expires; user is notified after the fact.

### Law Enforcement / Legal Requests (Policy Requirement)

Define a strict process:
- require a valid legal basis/order and internal review
- produce only what you have and are permitted to disclose
- for private mode, design assumes you may be **technically unable** to decrypt content without user consent; disclose this reality in policy

---

## Recommended Solution: Envelope Encryption

### How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│                     ENVELOPE ENCRYPTION                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  User Password ───► PBKDF2 ───► Password-Derived Key            │
│                                        │                        │
│                                        ▼                        │
│                              ┌─────────────────┐                │
│                              │ Encrypt DEK     │                │
│  DEK (generated once) ──────►│ with pwd key    │──► encrypted_dek
│         │                    └─────────────────┘                │
│         │                                                       │
│         │                    ┌─────────────────┐                │
│         └───────────────────►│ Encrypt DEK     │──► recovery_dek
│                              │ with recovery   │                │
│  Recovery Phrase ──► PBKDF2 ─┘                                  │
│                                                                 │
│  DEK encrypts: task_description, optimized_prompt, answers      │
└─────────────────────────────────────────────────────────────────┘
```

### Key Properties

- **DEK** (Data Encryption Key): Random 256-bit key, generated once per user
- **Password change**: Only re-wraps DEK, doesn't re-encrypt all data
- **Forgot password**: Use recovery phrase to unwrap DEK, then set new password
- **Admin access**: Restricted by design in Private mode (you should not be able to decrypt without a user-held secret)

### What Gets Encrypted

| Field | Encrypted | Reason |
|-------|-----------|--------|
| `task_description` | ✅ Yes | User's actual question/task |
| `optimized_prompt` | ✅ Yes | Generated prompt output |
| `pre_analysis_answers` | ✅ Yes | User's clarifying answers |
| `clarifying_answers` | ✅ Yes | User's framework answers |
| `pre_analysis_context` | ✅ Yes | Extracted context from answers |
| `workflow_stage` | ❌ No | Needed for queries/filtering |
| `created_at` | ❌ No | Needed for sorting |
| `personality_type` | ✅ Yes (recommended) | Sensitive profile attribute; use derived aggregates instead of plaintext storage where possible |

### Important Constraint

**n8n still processes plaintext during workflow execution.** Encryption protects data "at rest" in the database. During active processing, data is decrypted in memory.

### n8n Execution Data for Private Mode Users

n8n stores full execution history (inputs, outputs, errors) in its database, visible in the Executions tab. For Private mode users, we **disable execution saving**:

1. **Pass `privacy_enabled` flag** in webhook payload from Laravel (internal flag for Private mode)
2. **Conditional execution saving** in n8n workflow - don't save if privacy user
3. **Fallback: Global short retention** - `EXECUTIONS_DATA_PRUNE=true`, `EXECUTIONS_DATA_MAX_AGE=1` (1 hour)

This means Private mode users lose:

- Execution history visibility
- Retry from failed execution
- Debugging via n8n UI

Trade-off accepted for true privacy.

---

## Technical Implementation

### Database Changes

```sql
-- Migration: add_subscription_fields_to_users_table
ALTER TABLE users ADD COLUMN subscription_tier VARCHAR(20) DEFAULT 'free';
ALTER TABLE users ADD COLUMN subscription_status VARCHAR(20) DEFAULT NULL;
ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN stripe_subscription_id VARCHAR(255) DEFAULT NULL;
ALTER TABLE users ADD COLUMN trial_ends_at TIMESTAMP DEFAULT NULL;
ALTER TABLE users ADD COLUMN subscription_ends_at TIMESTAMP DEFAULT NULL;

-- Migration: add_privacy_fields_to_users_table
ALTER TABLE users ADD COLUMN privacy_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN encrypted_dek TEXT;
ALTER TABLE users ADD COLUMN recovery_dek TEXT;
ALTER TABLE users ADD COLUMN dek_created_at TIMESTAMP;

-- Migration: add_usage_tracking_to_users_table
ALTER TABLE users ADD COLUMN monthly_prompt_count INTEGER DEFAULT 0;
ALTER TABLE users ADD COLUMN prompt_count_reset_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Migration: add_encryption_flag_to_prompt_runs
ALTER TABLE prompt_runs ADD COLUMN is_encrypted BOOLEAN DEFAULT FALSE;

-- Migration: add_support_access_tables (requirements for support + break-glass)
-- support_access_requests: created by staff/user, must be approved by user for Private mode
-- support_access_sessions: active grants with strict TTL and scope
-- data_access_audit_logs: immutable audit trail for any content access
```

#### Support Access Table Requirements (Recommended Shape)

These tables are intentionally generic so they can support both:
- **Private-mode user-consented support sessions** (Private)
- **Free-tier break-glass sessions** (admin-only)

**`support_access_requests`**
- `id`
- `user_id` (the subject)
- `requested_by_admin_id` (nullable; set when staff initiates)
- `requested_by_user_id` (nullable; set when user initiates “share with support”)
- `tier_at_request` (`free`/`pro`)
- `reason` (required)
- `ticket_reference` (required)
- `scope` (JSON: `prompt_run_ids[]`, optional date range, optional “include profile fields”)
- `duration_minutes` (required)
- `status` (`pending`, `approved`, `denied`, `revoked`, `expired`, `cancelled`)
- `approved_at`, `approved_by_user_id` (nullable)
- `expires_at`, `revoked_at`
- `created_at`, `updated_at`

**`support_access_sessions`**
- `id`
- `support_access_request_id`
- `mode` (`user_consented` or `break_glass`)
- `session_token_hash` (one-time token, never store plaintext)
- `scope` (copy of approved scope, immutable)
- `starts_at`, `expires_at`, `revoked_at`
- `created_by_admin_id` (for break-glass) / `created_by_user_id` (for user-consented)
- `last_used_at`
- `created_at`, `updated_at`

**`data_access_audit_logs`** (append-only)
- `id`
- `actor_type` (`admin`, `system`) and `actor_id`
- `user_id` (the subject)
- `action` (`view`, `decrypt`, `export_attempt`, `export`, `create_request`, `approve_request`, `revoke_session`, etc.)
- `resource_type` + `resource_id` (e.g. `prompt_run` + id)
- `support_access_request_id` (nullable)
- `support_access_session_id` (nullable)
- `ip_address`, `user_agent`
- `metadata` (JSON; avoid raw content)
- `created_at`

### New Files to Create

```
app/
├── Services/
│   ├── EncryptionService.php        # DEK generation, wrapping/unwrapping
│   ├── PrivacyKeyService.php        # Session key management
│   ├── SupportAccessService.php     # User-consented sessions + break-glass lifecycle
│   └── DataAccessAuditService.php   # Append-only audit logging helpers
│   └── SubscriptionService.php      # Subscription logic, usage tracking
├── Casts/
│   └── UserEncrypted.php            # Custom Eloquent cast for encrypted fields
├── Http/
│   ├── Middleware/
│   │   ├── LoadPrivacyKey.php       # Loads DEK into session on login
│   │   ├── CheckSubscription.php    # Verify subscription status
│   │   └── TrackPromptUsage.php     # Count prompts for free tier
│   └── Controllers/
│       ├── SubscriptionController.php
│       ├── SupportAccessController.php      # User approve/deny/revoke; support request UI
│       ├── Admin/BreakGlassController.php   # Admin-only break-glass workflow (free tier)
│       └── WebhookController.php    # Stripe webhooks
├── Console/Commands/
│   ├── MigrateUserToPrivacy.php     # One-time migration for existing users
│   └── ResetMonthlyPromptCounts.php # Scheduled command
└── Listeners/
    └── StripeEventListener.php      # Handle Stripe events

resources/js/
├── Pages/
│   ├── Pricing.vue                  # Public pricing page
│   └── Settings/
│       ├── Subscription.vue         # Manage subscription
│       └── Privacy.vue              # Privacy settings
│       └── SupportAccess.vue         # Approve/deny/revoke support access
├── Components/
│   ├── SubscriptionBadge.vue        # Show current plan
│   ├── UpgradePrompt.vue            # Prompt to upgrade
│   ├── RecoveryPhrase.vue           # Recovery phrase display/confirm
│   └── UsageIndicator.vue           # Show prompts remaining
```

### EncryptionService Implementation

```php
class EncryptionService
{
    // Generate new DEK for user
    public function generateDek(): string;

    // Wrap DEK with password-derived key
    public function wrapDekWithPassword(string $dek, string $password): string;

    // Wrap DEK with recovery phrase
    public function wrapDekWithRecovery(string $dek, string $recoveryPhrase): string;

    // Unwrap DEK using password
    public function unwrapDekWithPassword(string $encryptedDek, string $password): string;

    // Unwrap DEK using recovery phrase
    public function unwrapDekWithRecovery(string $recoveryDek, string $recoveryPhrase): string;

    // Encrypt data with DEK
    public function encrypt(string $plaintext, string $dek): string;

    // Decrypt data with DEK
    public function decrypt(string $ciphertext, string $dek): string;

    // Generate recovery phrase (BIP39 or similar)
    public function generateRecoveryPhrase(): string;
}
```

### User Flows

**1. Enable Private Mode (New Subscription)**

```
User subscribes to Private tier
    → Generate DEK
    → Generate recovery phrase
    → Show recovery phrase (user must confirm they saved it)
    → Wrap DEK with password → encrypted_dek
    → Wrap DEK with recovery → recovery_dek
    → Set privacy_enabled = true
    → Encrypt existing prompt_runs (background job)
```

**2. Login (Privacy User)**

```
User enters password
    → Authenticate normally
    → Unwrap DEK using password
    → Store DEK in session (encrypted with session key)
    → DEK available for request lifecycle
```

**3. Password Change**

```
User changes password
    → Unwrap DEK with old password
    → Re-wrap DEK with new password
    → Update encrypted_dek
    → (recovery_dek unchanged)
```

**4. Forgot Password (Recovery)**

```
User clicks "Forgot Password"
    → If privacy_enabled, show recovery phrase input
    → User enters recovery phrase
    → Unwrap DEK with recovery phrase
    → User sets new password
    → Re-wrap DEK with new password
    → Update encrypted_dek
```

### Custom Eloquent Cast

```php
// app/Casts/UserEncrypted.php
class UserEncrypted implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (!$value || !$model->is_encrypted) {
            return $value;
        }

        $dek = app('privacy.dek'); // Set by middleware
        if (!$dek) {
            throw new EncryptionKeyMissingException();
        }

        return app(EncryptionService::class)->decrypt($value, $dek);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        $user = $model->user ?? auth()->user();

        if (!$user?->privacy_enabled) {
            return $value;
        }

        $dek = app('privacy.dek');
        if (!$dek) {
            throw new EncryptionKeyMissingException();
        }

        return app(EncryptionService::class)->encrypt($value, $dek);
    }
}
```

---

## Billing Provider Analysis

### Your Context

- **Location**: UK-based company
- **Compliance**: EU law (GDPR), UK VAT
- **Pricing**:
  - Unlimited: £12/month or £120/year
  - Private: £20/month or £200/year
- **Year 1**: ~500 private subscribers = ~£100,000 - £120,000 revenue
- **Year 2**: ~5,000 private subscribers = ~£1,000,000 - £1,200,000 revenue

### Comprehensive Provider Comparison

| Provider | Type | Base Fee | UK Cards | Intl Cards | VAT Handling | Laravel Support |
|----------|------|----------|----------|------------|--------------|-----------------|
| **Stripe** | Processor | None | 1.5% + 20p | 2.5% + 20p | DIY + Tax tool | ⭐ Excellent (Cashier) |
| **Paddle** | MoR | None | 5% + 50p | 5% + 50p | ✅ Included | Fair (API) |
| **GoCardless** | Direct Debit | £0-50/mo | 1% + 20p | 2% + 20p | DIY | Fair (API) |
| **Mollie** | Processor | None | 1.8% + 25p | 2.5% + 25p | DIY | Fair (Package) |
| **Worldpay** | Processor | £19.95/mo | 1.5% | 2.5% | DIY | Poor |
| **LemonSqueezy** | MoR | None | 5% + 50p | 5% + 50p | ✅ Included | Fair (API) |

**MoR = Merchant of Record** (they handle VAT/tax compliance for you)

### UK-Specific Considerations

**GoCardless (UK-based):**
- Excellent for Direct Debit (lower fees for recurring)
- However: No card payments, limited to bank transfers
- Best combined with another provider for card payments

**Paddle (UK-based):**
- Acts as Merchant of Record - handles all VAT globally
- Higher fees (5% + 50p) but zero VAT admin work
- Good for global sales without tax complexity

**Mollie (EU-based, Netherlands):**
- Good rates, supports UK and EU
- Strong European presence
- Less established Laravel ecosystem

### Cost Breakdown (Updated Private Pricing: £20/month, £200/year)

Assuming 50/50 monthly/annual split, 500 paying users Year 1:

**Stripe:**
- Monthly (£20): ~£0.50 fee per transaction (2.5%)
- Annual (£200): ~£3.20 fee per transaction (1.6%)
- Year 1: 250×12×£0.50 + 250×£3.20 = **~£2,300**
- Year 2 (5,000 users): **~£23,000**

**Paddle/LemonSqueezy:**
- Monthly (£20): £1.50 fee per transaction (7.5%)
- Annual (£200): £10.50 fee per transaction (5.25%)
- Year 1: 250×12×£1.50 + 250×£10.50 = **~£7,125**
- Year 2 (5,000 users): **~£71,250**

**GoCardless (Direct Debit only):**
- Monthly (£20): £0.40 fee per transaction (2.0%)
- Annual (£200): £2.20 fee per transaction (1.1%)
- Year 1: **~£1,750** (if all via DD)

### Recommendation: **Stripe** (Confirmed)

**Why Stripe remains the best choice:**

1. **Cost efficiency**: Saves ~£4,800/year vs Paddle in Year 1, ~£48,000/year in Year 2

2. **Laravel Cashier**: First-class integration
   - Subscriptions, trials, invoices out of the box
   - Well-documented, battle-tested
   - Webhook handling built-in
   - Billing portal included

3. **VAT is manageable for UK companies**:
   - UK customers: Charge 20% VAT
   - EU customers: Register for EU One-Stop-Shop (OSS) - single quarterly return
   - Rest of world: No VAT required
   - **Stripe Tax**: Automate VAT calculation for £0.50/transaction

4. **UK entity support**: Stripe has UK entity, GBP settlement, UK banking

5. **Customer experience**:
   - Embedded checkout (customers stay on your site)
   - Apple Pay, Google Pay support
   - Strong/Secure Customer Authentication (SCA) built-in

**When to reconsider Paddle:**
- If VAT admin becomes overwhelming (unlikely for simple B2C SaaS)
- If selling to 50+ countries with complex tax rules
- If you have no time for any compliance work

**For a UK SaaS at your projected scale, Stripe + OSS registration is the clear winner.**

### Stripe Setup Checklist

1. **Create Stripe UK account** at stripe.com
2. **Install Laravel Cashier**: `composer require laravel/cashier`
3. **Configure products in Stripe Dashboard**:
   - Product: "BettrPrompt Unlimited"
     - Price 1: £12/month (recurring)
     - Price 2: £120/year (recurring)
   - Product: "BettrPrompt Private"
     - Price 1: £20/month (recurring)
     - Price 2: £200/year (recurring)
4. **Enable Stripe Tax** (optional, £0.50/transaction)
5. **Register for UK VAT** (if not already, threshold £85k)
6. **Register for EU OSS** (for EU customers)

---

## Frontend Implementation

### Pricing Page (`/pricing`)

```vue
<!-- resources/js/Pages/Pricing.vue -->
<template>
    <div class="pricing-page">
        <h1>Simple, transparent pricing</h1>

        <div class="pricing-cards">
            <!-- Free Tier -->
            <div class="pricing-card">
                <h2>Free</h2>
                <div class="price">£0</div>
                <ul class="features">
                    <li>✓ 10 prompts per month</li>
                    <li>✓ Personality calibration</li>
                    <li>✓ Basic support</li>
                    <li>✓ Encryption at rest</li>
                    <li class="disabled">✗ Private mode</li>
                    <li class="disabled">✗ Unlimited prompts</li>
                </ul>
                <button @click="startFree">Get Started</button>
            </div>

            <!-- Unlimited Tier -->
            <div class="pricing-card">
                <h2>Unlimited</h2>
                <div class="price">£12/month</div>
                <div class="savings">£120/year • Save 17%</div>
                <ul class="features">
                    <li>✓ Unlimited prompts</li>
                    <li>✓ Personality calibration</li>
                    <li>✓ Encryption at rest</li>
                    <li class="disabled">✗ Private mode</li>
                </ul>
                <button @click="subscribe('unlimited_monthly')">
                    Start Unlimited
                </button>
            </div>

            <!-- Private Tier -->
            <div class="pricing-card featured">
                <div class="badge">Most Popular</div>
                <h2>Private</h2>
                <div class="price-toggle">
                    <button :class="{ active: !annual }" @click="annual = false">Monthly</button>
                    <button :class="{ active: annual }" @click="annual = true">Annual</button>
                </div>
                <div class="price">
                    <span v-if="annual">£200/year</span>
                    <span v-else>£20/month</span>
                    <span v-if="annual" class="savings">Save 17%</span>
                </div>
                <ul class="features">
                    <li>✓ Unlimited prompts</li>
                    <li>✓ Personality calibration</li>
                    <li>✓ Private mode</li>
                    <li>✓ Priority support</li>
                    <li>✓ Prompt history</li>
                </ul>
                <button @click="subscribe(annual ? 'private_yearly' : 'private_monthly')">
                    Start Private
                </button>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq">
            <h2>Frequently Asked Questions</h2>
            <!-- Privacy explanation, billing FAQ, etc. -->
        </div>
    </div>
</template>
```

### Subscription Management (`/settings/subscription`)

```vue
<!-- resources/js/Pages/Settings/Subscription.vue -->
<template>
    <div class="subscription-settings">
        <h1>Subscription</h1>

        <!-- Current Plan -->
        <div class="current-plan">
            <h2>Current Plan: {{ currentPlan }}</h2>
            <p v-if="isFree">
                {{ promptsRemaining }} of 10 prompts remaining this month
            </p>
            <p v-else>
                Unlimited prompts • {{ billingCycle }} billing
            </p>
        </div>

        <!-- Upgrade Prompt (for free users) -->
        <div v-if="isFree" class="upgrade-section">
            <h3>Upgrade</h3>
            <p>Choose Unlimited for more usage, or Private for maximum confidentiality</p>
            <button @click="showUpgradeModal = true">Upgrade Now</button>
        </div>

        <!-- Manage Subscription (paid tiers) -->
        <div v-else class="manage-section">
            <h3>Manage Subscription</h3>
            <p>Next billing date: {{ nextBillingDate }}</p>
            <button @click="openBillingPortal">Manage Billing</button>
            <button @click="showCancelModal = true" class="danger">
                Cancel Subscription
            </button>
        </div>

        <!-- Privacy Status -->
        <div v-if="isPrivate" class="privacy-section">
            <h3>Private Mode</h3>
            <p v-if="privacyEnabled">
                ✓ Private mode is enabled for your account
            </p>
            <p v-else>
                Enable Private mode to restrict access and disable training/improvement use
            </p>
            <button v-if="!privacyEnabled" @click="enablePrivacy">
                Enable Private mode
            </button>
        </div>
    </div>
</template>
```

### Usage Indicator Component

```vue
<!-- resources/js/Components/UsageIndicator.vue -->
<template>
    <div class="usage-indicator" v-if="isFreeUser">
        <div class="usage-bar">
            <div
                class="usage-fill"
                :style="{ width: usagePercent + '%' }"
                :class="{ warning: usagePercent > 80 }"
            ></div>
        </div>
        <span class="usage-text">
            {{ promptsUsed }}/10 prompts this month
        </span>
        <button v-if="promptsUsed >= 10" @click="showUpgrade">
            Upgrade for unlimited
        </button>
    </div>
</template>
```

### Upgrade Prompt Modal

```vue
<!-- resources/js/Components/UpgradePrompt.vue -->
<template>
    <Modal v-model="show">
        <div class="upgrade-modal">
            <h2>You've reached your monthly limit</h2>
            <p>
                Free accounts are limited to 10 prompts per month.
                Upgrade to Unlimited for more usage, or Private for maximum confidentiality.
            </p>

	            <div class="pricing-options">
	                <div class="option" @click="selectPlan('unlimited_monthly')">
	                    <h3>Unlimited</h3>
	                    <div class="price">£12/month</div>
	                </div>
	                <div class="option" @click="selectPlan('unlimited_yearly')">
	                    <div class="badge">Save 17%</div>
	                    <h3>Unlimited (Annual)</h3>
	                    <div class="price">£120/year</div>
	                    <div class="effective">£10/month</div>
	                </div>
	                <div class="option" @click="selectPlan('private_monthly')">
	                    <h3>Private</h3>
	                    <div class="price">£20/month</div>
	                </div>
	                <div class="option featured" @click="selectPlan('private_yearly')">
	                    <div class="badge">Save 17%</div>
	                    <h3>Private (Annual)</h3>
	                    <div class="price">£200/year</div>
	                    <div class="effective">£16.67/month</div>
	                </div>
	            </div>

            <button @click="subscribe" class="primary">
                Upgrade Now
            </button>
            <button @click="close" class="secondary">
                Maybe Later
            </button>
        </div>
    </Modal>
</template>
```

---

## Backend Implementation

### SubscriptionController

```php
// app/Http/Controllers/SubscriptionController.php
class SubscriptionController extends Controller
{
	    public function pricing()
	    {
	        return Inertia::render('Pricing', [
	            'plans' => [
	                'unlimited_monthly' => [
	                    'price_id' => config('stripe.prices.GBP.unlimited_monthly'),
	                    'price' => 1200, // pence (£12)
	                    'interval' => 'month',
	                ],
	                'unlimited_yearly' => [
	                    'price_id' => config('stripe.prices.GBP.unlimited_yearly'),
	                    'price' => 12000, // pence (£120)
	                    'interval' => 'year',
	                ],
	                'private_monthly' => [
	                    'price_id' => config('stripe.prices.GBP.private_monthly'),
	                    'price' => 2000, // pence (£20)
	                    'interval' => 'month',
	                ],
	                'private_yearly' => [
	                    'price_id' => config('stripe.prices.GBP.private_yearly'),
	                    'price' => 20000, // pence (£200)
	                    'interval' => 'year',
	                ],
	            ],
	        ]);
	    }

	    public function subscribe(Request $request)
	    {
	        $request->validate([
	            'plan' => 'required|in:unlimited_monthly,unlimited_yearly,private_monthly,private_yearly',
	        ]);

	        $user = $request->user();
	        $currency = 'GBP';
	        $priceId = config("stripe.prices.{$currency}.{$request->plan}");

        return $user->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success'),
                'cancel_url' => route('subscription.cancel'),
            ]);
    }

    public function success(Request $request)
    {
        return redirect()->route('settings.subscription')
            ->with('success', 'Subscription activated!');
    }

    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(
            route('settings.subscription')
        );
    }

    public function cancel(Request $request)
    {
        $request->user()->subscription('default')->cancel();

        return redirect()->route('settings.subscription')
            ->with('success', 'Subscription cancelled. You will retain access until the end of your billing period.');
    }
}
```

### CheckSubscription Middleware

```php
// app/Http/Middleware/CheckSubscription.php
class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Share subscription status with all Inertia responses
        Inertia::share([
            'subscription' => [
                'tier' => $user->subscription_tier,
                'isPaid' => $user->subscribed('default') || ($user->subscription_ends_at && $user->subscription_ends_at->isFuture()),
                'isUnlimited' => $user->subscription_tier === 'unlimited',
                'isPrivate' => $user->subscription_tier === 'private',
                'promptsUsed' => $user->monthly_prompt_count,
                'promptsRemaining' => $user->subscribed('default') ? PHP_INT_MAX : max(0, 10 - $user->monthly_prompt_count),
                'privacyEnabled' => $user->privacy_enabled,
            ],
        ]);

        return $next($request);
    }
}
```

### TrackPromptUsage Middleware

```php
// app/Http/Middleware/TrackPromptUsage.php
class TrackPromptUsage
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Reset count if new month
        if ($user->prompt_count_reset_at->isLastMonth()) {
            $user->update([
                'monthly_prompt_count' => 0,
                'prompt_count_reset_at' => now(),
            ]);
        }

        // Check if free user has reached limit
        if (!$user->subscribed('default') && $user->monthly_prompt_count >= 10) {
            return response()->json([
                'error' => 'Monthly prompt limit reached',
                'upgrade_url' => route('pricing'),
            ], 403);
        }

        $response = $next($request);

        // Increment count after successful prompt generation
        if ($response->isSuccessful()) {
            $user->increment('monthly_prompt_count');
        }

        return $response;
    }
}
```

### Stripe Webhook Handler

```php
// app/Http/Controllers/WebhookController.php
class WebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        switch ($event->type) {
            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdate($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionCancelled($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }

        return response('OK', 200);
    }

    private function handleSubscriptionUpdate($subscription)
    {
        $user = User::where('stripe_customer_id', $subscription->customer)->first();

        if ($user) {
            $user->update([
                'subscription_tier' => 'pro',
                'subscription_status' => $subscription->status,
                'stripe_subscription_id' => $subscription->id,
                'subscription_ends_at' => Carbon::createFromTimestamp(
                    $subscription->current_period_end
                ),
            ]);
        }
    }

    private function handleSubscriptionCancelled($subscription)
    {
        $user = User::where('stripe_subscription_id', $subscription->id)->first();

        if ($user) {
            $user->update([
                'subscription_tier' => 'free',
                'subscription_status' => 'cancelled',
                'privacy_enabled' => false, // Disable privacy on downgrade
            ]);

            // Queue job to decrypt user's data
            DecryptUserData::dispatch($user);
        }
    }
}
```

### Routes

```php
// routes/web.php
Route::get('/pricing', [SubscriptionController::class, 'pricing'])->name('pricing');

Route::middleware(['auth'])->group(function () {
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::get('/subscription/success', [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::get('/billing-portal', [SubscriptionController::class, 'billingPortal'])->name('billing.portal');
});

// routes/api.php
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');
```

### Configuration

```php
// config/stripe.php
return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    'prices' => [
        'monthly' => env('STRIPE_PRICE_MONTHLY'),
        'yearly' => env('STRIPE_PRICE_YEARLY'),
    ],

    'tax' => [
        'enabled' => env('STRIPE_TAX_ENABLED', false),
        'automatic' => true,
    ],
];
```

---

## Implementation Phases

### Phase 1: Stripe Integration (3-4 days)

1. Install Laravel Cashier
2. Create Stripe account and configure products/prices
3. Add subscription columns to users table
4. Implement SubscriptionController
5. Set up Stripe webhook handler
6. **Files**: User.php, SubscriptionController.php, WebhookController.php, migrations

### Phase 2: Usage Tracking (2 days)

1. Add monthly_prompt_count to users table
2. Create TrackPromptUsage middleware
3. Create scheduled command to reset counts monthly
4. Implement rate limiting for free tier
5. **Files**: TrackPromptUsage.php, ResetMonthlyPromptCounts.php

### Phase 3: Frontend - Pricing & Subscription (3-4 days)

1. Create Pricing.vue page
2. Create Settings/Subscription.vue page
3. Implement UpgradePrompt modal
4. Create UsageIndicator component
5. Add subscription badge to navigation
6. **Files**: Vue components, CSS

### Phase 4: Encryption Service (3-4 days)

1. Create EncryptionService with DEK management
2. Implement PBKDF2 key derivation
3. Implement AES-256-GCM encryption
4. Generate BIP39-style recovery phrases
5. Write comprehensive tests
6. **Files**: EncryptionService.php, tests

### Phase 5: Privacy Onboarding Flow (2-3 days)

1. Create "Enable Privacy" flow in settings
2. Show recovery phrase with confirmation
3. Generate and store wrapped DEKs
4. Add LoadPrivacyKey middleware
5. **Files**: Privacy.vue, RecoveryPhrase.vue, middleware

### Phase 6: Encrypted Storage (3-4 days)

1. Create UserEncrypted cast
2. Apply cast to sensitive PromptRun fields
3. Add is_encrypted flag to prompt_runs
4. Update PromptRun queries to handle encryption
5. **Files**: UserEncrypted.php, PromptRun.php

### Phase 7: Password/Recovery Flows (2-3 days)

1. Update password change to re-wrap DEK
2. Create recovery phrase login flow
3. Update forgot password for Private mode users
4. **Files**: Auth controllers, Vue components

### Phase 8: Data Migration & Downgrade Handling (2-3 days)

1. Create migration command for existing users
2. Background job to encrypt existing prompt_runs
3. Handle subscription cancellation (exit Private mode; re-encrypt under baseline keys)
4. Handle edge cases (OAuth-only users)
5. **Files**: Console commands, jobs

### Phase 9: n8n Privacy Configuration (1 day)

1. Add `privacy_enabled` flag to webhook payloads (Private mode)
2. Update n8n workflows to check flag
3. Configure n8n execution pruning
4. Test execution data handling
5. **Files**: N8nClient.php, n8n workflow JSON files

### Phase 10: Testing & Polish (2-3 days)

1. End-to-end subscription flow testing
2. Encryption/decryption testing
3. Webhook testing with Stripe CLI
4. Error handling and edge cases
5. Documentation

**Total Estimated Effort: 24-33 days**

---

## Security Considerations

### Key Storage

- DEK is stored encrypted (never plaintext) in database
- DEK is held in session during request lifecycle only
- Session encryption uses Laravel's session encryption
- Consider Redis session driver for better security

### Cryptographic Choices

- **Key Derivation**: PBKDF2 with 100,000+ iterations (or Argon2id)
- **Encryption**: AES-256-GCM (authenticated encryption)
- **Recovery Phrase**: BIP39 wordlist (12-24 words)

### Attack Vectors Mitigated

- ✅ Database breach → data encrypted, no plaintext keys
- ✅ Admin access (Private mode) → staff cannot decrypt without an approved, user-consented support session
- ✅ Admin access (Free tier) → break-glass access is time-limited and audited (reduces insider/credential-compromise impact)
- ✅ Password reset attack → requires recovery phrase

### Attack Vectors NOT Mitigated

- ⚠️ Server memory dump during active session (DEK in memory)
- ⚠️ Active processing interception (n8n processes plaintext briefly)

### n8n Data Protection

- ✅ Execution data not saved for Private mode users
- ✅ Short retention fallback (1 hour max)
- ⚠️ n8n processes plaintext in memory during workflow execution (unavoidable)

---

## OAuth User Considerations

Users who sign in via Google OAuth don't have a password. Options:

1. **Require password for Private mode** - Force OAuth users to set a password
2. **Use Google token as key derivation source** - Complex, ties to Google
3. **Generate standalone privacy passphrase** - Separate from auth password

**Recommendation**: Option 1 - require password. Simpler and users understand passwords.

---

## Team Pricing (Future)

**Not implementing now** - wait until team collaboration features exist.

**Planned pricing when ready:**

| Plan | Per Seat | Volume Discounts |
|------|----------|------------------|
| Team Monthly | £10/month | 5+ seats: 10% off |
| Team Annual | £85/year | 10+ seats: 20% off |

**Features to build first:**
- Shared prompt library
- Team personality profiles
- Admin dashboard
- Seat management
- SSO (enterprise)

---

## Files Summary

| File | Action | Purpose |
|------|--------|---------|
| `database/migrations/*_add_subscription_fields.php` | Create | Subscription columns |
| `database/migrations/*_add_privacy_fields.php` | Create | Privacy/encryption columns |
| `database/migrations/*_add_usage_tracking.php` | Create | Prompt counting |
| `app/Models/User.php` | Modify | Add Billable trait, subscription methods |
| `app/Models/PromptRun.php` | Modify | Add encrypted casts |
| `app/Services/EncryptionService.php` | Create | Core encryption logic |
| `app/Services/SubscriptionService.php` | Create | Subscription business logic |
| `app/Casts/UserEncrypted.php` | Create | Eloquent cast for encrypted fields |
| `app/Http/Controllers/SubscriptionController.php` | Create | Subscription endpoints |
| `app/Http/Controllers/WebhookController.php` | Create | Stripe webhook handling |
| `app/Http/Middleware/CheckSubscription.php` | Create | Share subscription status |
| `app/Http/Middleware/TrackPromptUsage.php` | Create | Count prompts for free tier |
| `app/Http/Middleware/LoadPrivacyKey.php` | Create | Load DEK on auth |
| `resources/js/Pages/Pricing.vue` | Create | Public pricing page |
| `resources/js/Pages/Settings/Subscription.vue` | Create | Manage subscription |
| `resources/js/Pages/Settings/Privacy.vue` | Create | Privacy settings UI |
| `resources/js/Components/UpgradePrompt.vue` | Create | Upgrade modal |
| `resources/js/Components/UsageIndicator.vue` | Create | Show prompts remaining |
| `resources/js/Components/RecoveryPhrase.vue` | Create | Recovery phrase display |
| `config/stripe.php` | Create | Stripe configuration |
| `tests/Feature/SubscriptionTest.php` | Create | Subscription tests |
| `tests/Feature/EncryptionTest.php` | Create | Encryption tests |
