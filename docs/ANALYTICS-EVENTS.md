# Analytics Events Tracking

This document describes all analytics events tracked in the BettrPrompt application for conversion analysis, user journey understanding, and engagement metrics.

## Overview

The analytics system uses a **3-phase implementation approach**:
- **Phase 1: High Priority** - Core conversion events (implemented) ✅
- **Phase 2: Medium Priority** - User journey funnel tracking (implemented) ✅
- **Phase 3: Low Priority** - Engagement insights (implemented) ✅

---

## Event Categories

### 1. Authentication Flow Events

Track user registration, login, and password reset actions across the application.

#### `registration_started` ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** When registration modal opens with a fresh form
- **Location:** `resources/js/Components/Base/Modal/ModalRegister.vue` (line 40)
- **Properties:**
  - `modal_opened: boolean` - Whether modal was just opened
- **Use Case:** Track funnel entry point for registration
- **Status:** ✅ IMPLEMENTED

#### `registration_completed` ✅
- **Priority:** 🔴 High (IMPLEMENTED)
- **Type:** Backend event (server-side)
- **Trigger:** User successfully creates account via email
- **Location:** `app/Http/Controllers/Auth/RegisteredUserController.php` (line 93)
- **Properties:**
  - `registration_method: string` - "email"
- **Alternative Tracking:** Google OAuth registration
- **Location:** `app/Http/Controllers/Auth/OAuthController.php` (line 188)
- **Properties:**
  - `registration_method: string` - "google"
- **Use Case:** Calculate registration conversion rate
- **Status:** ✅ IMPLEMENTED

#### `login_completed` ✅
- **Priority:** 🟢 Low
- **Type:** Backend event (server-side)
- **Trigger:** User successfully logs in
- **Location:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php` (lines 28-39)
- **Properties:**
  - `login_method: string` - "email"
- **Use Case:** Track returning user sessions
- **Status:** ✅ IMPLEMENTED

#### `password_reset_requested` ✅
- **Priority:** 🟢 Low
- **Type:** Backend event (server-side)
- **Trigger:** User requests password reset link
- **Location:** `app/Http/Controllers/Auth/PasswordResetLinkController.php` (lines 45-55)
- **Properties:**
  - `email: string` - User's email (hashed/pseudonymized)
- **Use Case:** Understand account recovery flow
- **Status:** ✅ IMPLEMENTED

---

### 2. Prompt Builder Flow Events

Track the entire prompt creation and generation workflow across all 3 stages.

#### `prompt_started` ✅
- **Priority:** 🟡 Medium
- **Type:** Backend event (server-side)
- **Trigger:** User submits initial task description
- **Location:** `app/Http/Controllers/PromptBuilderController.php` (line 187)
- **Properties:**
  - `task_description_length: integer` - Length of task description
  - `has_personality_type: boolean` - Whether personality is set
- **Workflow Stage:** 0_processing
- **Use Case:** Entry point for prompt creation funnel
- **Status:** ✅ IMPLEMENTED

#### `prompt_completed` ✅
- **Priority:** 🔴 High (IMPLEMENTED)
- **Type:** Frontend event
- **Trigger:** Final optimised prompt is generated (Workflow Stage 2 complete)
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue` (line 330)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `workflow_stage: integer` - Always 2 for completion
  - `personality_type: string | null` - User's personality type
  - `framework_used: string | null` - Selected framework code
- **Use Case:** Track successful prompt generation rate
- **Status:** ✅ IMPLEMENTED

#### `prompt_copied` ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User clicks "Copy to Clipboard" button
- **Location:** `resources/js/Components/Features/PromptBuilder/OptimisedPrompt/OptimisedPrompt.vue` (line 63)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `prompt_length: integer` - Length of copied prompt
- **Use Case:** Measure prompt engagement/usefulness
- **Status:** ✅ IMPLEMENTED

#### `prompt_edited` ✅
- **Priority:** 🟢 Low
- **Type:** Frontend event
- **Trigger:** User saves edits to the prompt
- **Location:** `resources/js/Components/Features/PromptBuilder/OptimisedPrompt/OptimisedPrompt.vue` (lines 123-131)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `original_length: integer` - Original prompt length
  - `edited_length: integer` - Edited prompt length
  - `edit_percentage: float` - Percentage of text changed
- **Use Case:** Understand refinement patterns
- **Status:** ✅ IMPLEMENTED

#### `workflow_stage_completed` (Stage 0, 1, 2) ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event (WebSocket)
- **Trigger:** Each of the 3 workflow stages completes
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue` (lines 302, 327, 365)
  - **Stage 0 (Pre-Analysis):** Line 302
  - **Stage 1 (Main Analysis):** Line 327
  - **Stage 2 (Generation):** Line 365
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `stage: integer` - Stage number (0, 1, or 2)
  - `workflow_name: string` - Human-readable name ("pre_analysis", "main_analysis", "prompt_generation")
  - `framework_selected: string | null` - For Stage 1 only
- **Use Case:** Identify workflow bottlenecks and completion rates by stage
- **Status:** ✅ IMPLEMENTED

---

### 3. Subscription Flow Events

Track the complete subscription lifecycle from pricing page to renewal.

#### `subscription_started` ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User clicks "Subscribe" button on pricing page
- **Location:** `resources/js/Pages/Pricing.vue` (line 99)
- **Properties:**
  - `tier: string` - "pro" or "private"
  - `interval: string` - "monthly" or "yearly"
  - `currency: string` - Currency code (e.g., "GBP", "USD")
  - `source: string` - Always "pricing_page"
- **Use Case:** Track subscription funnel entry
- **Status:** ✅ IMPLEMENTED

#### `checkout_initiated` ✅
- **Priority:** 🟡 Medium
- **Type:** Backend event (server-side)
- **Trigger:** Stripe checkout session created
- **Location:** `app/Http/Controllers/SubscriptionController.php` (line 112)
- **Properties:**
  - `tier: string` - "pro" or "private"
  - `interval: string` - "monthly" or "yearly"
  - `stripe_session_id: string` - Stripe session ID for linking
- **Use Case:** Track users proceeding to Stripe checkout
- **Status:** ✅ IMPLEMENTED

#### `subscription_completed` ✅
- **Priority:** 🔴 High (IMPLEMENTED)
- **Type:** Backend event (server-side)
- **Trigger:** User completes Stripe payment successfully
- **Location:** `app/Http/Controllers/SubscriptionController.php` (line 130)
- **Properties:**
  - `tier: string` - "pro" or "private"
  - `previous_tier: string` - Previous tier ("free" for new subscriptions)
- **Use Case:** Calculate subscription conversion rate
- **Status:** ✅ IMPLEMENTED

#### `subscription_activated` ✅
- **Priority:** 🔴 High (IMPLEMENTED)
- **Type:** Backend event (server-side) via Stripe Webhook
- **Trigger:** Stripe confirms subscription creation (webhook event)
- **Location:** `app/Http/Controllers/StripeWebhookController.php` (line 31)
- **Properties:**
  - `tier: string` - "pro" or "private"
  - `stripe_subscription_id: string` - Stripe subscription ID
  - `billing_interval: string` - "month" or "year"
- **Use Case:** Validate subscription via Stripe source of truth
- **Status:** ✅ IMPLEMENTED

#### `checkout_cancelled` ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User lands on cancelled page after abandoning Stripe checkout
- **Location:** `resources/js/Pages/Subscription/Cancelled.vue` (lines 25-32)
- **Properties:**
  - `source: string` - Always "stripe_checkout"
- **Use Case:** Measure checkout abandonment rate
- **Status:** ✅ IMPLEMENTED

#### `subscription_cancelled` ✅
- **Priority:** 🟢 Low
- **Type:** Backend event (server-side)
- **Trigger:** User initiates subscription cancellation from settings
- **Location:** `app/Http/Controllers/SubscriptionController.php` (lines 215-227)
- **Properties:**
  - `tier: string` - Current tier being cancelled
  - `cancellation_source: string` - "settings_page"
- **Use Case:** Understand churn reasons
- **Status:** ✅ IMPLEMENTED

#### `upgrade_cta_clicked` ✅
- **Priority:** 🟢 Low
- **Type:** Frontend event
- **Trigger:** User clicks "Upgrade" call-to-action in-app
- **Location:** Multiple locations
  - `resources/js/Components/Common/UsageIndicator.vue` (lines 21-30)
  - `resources/js/Components/Common/UpgradePromptModal.vue` (lines 23-29)
- **Properties:**
  - `source: string` - "usage_indicator" or "upgrade_prompt_modal"
  - `current_tier: string` - "free"
  - `interval: string` - For upgrade_prompt_modal only ("monthly" or "yearly")
- **Use Case:** Track in-app upsell effectiveness
- **Status:** ✅ IMPLEMENTED

---

## Implementation Status

### ✅ Phase 1: High Priority (COMPLETE)
- [x] `registration_completed` - Email (backend)
- [x] `registration_completed` - Google OAuth (backend)
- [x] `subscription_completed` (backend)
- [x] `subscription_activated` (webhook)
- [x] `prompt_completed` (frontend)

**Expected Impact:** Can now calculate conversion rates for registration and subscription

### ✅ Phase 2: Medium Priority (COMPLETE)
- [x] `registration_started` (frontend)
- [x] `subscription_started` (frontend)
- [x] `checkout_initiated` (backend)
- [x] `checkout_cancelled` (frontend)
- [x] `prompt_started` (backend)
- [x] `prompt_copied` (frontend)
- [x] `workflow_stage_completed` (frontend - 3 stages)

**Expected Impact:** Can identify drop-off points in all major funnels

### ✅ Phase 3: Low Priority (COMPLETE)
- [x] `prompt_edited` (frontend)
- [x] `login_completed` (backend)
- [x] `subscription_cancelled` (backend)
- [x] `password_reset_requested` (backend)
- [x] `upgrade_cta_clicked` (frontend - 2 locations)

**Expected Impact:** Full visibility into user engagement patterns

---

## Event Properties Guide

### Common Properties (Automatically Added by Backend)
All backend-created events automatically include:
- `visitor_id` - Server-derived from cookie
- `user_id` - From authenticated session
- `source` - Always "server"
- `occurred_at` - When event was created

All frontend-created events automatically include:
- `event_id` - Client-generated UUID (for idempotency)
- `session_id` - Analytics session ID from header
- `occurred_at_ms` - Client timestamp in milliseconds

### Property Naming Convention
- Use snake_case for all property names
- Use lowercase string values: "pro", "monthly", "email"
- Use full names, not abbreviations: "registration_method" not "reg_method"
- Include contextual identifiers: "prompt_run_id", "stripe_subscription_id"

---

## Data Flow

### Frontend Events
```
Vue Component
    ↓
analyticsService.track()
    ↓
Event Batch (5s or 10 events)
    ↓
POST /api/analytics/events
    ↓
AnalyticsEventController (validates)
    ↓
Dispatches ProcessAnalyticsEvents Job
    ↓
Returns 200 Immediately
```

### Backend Events
```
Controller Action
    ↓
AnalyticsEvent::create([...])
    ↓
INSERT INTO analytics_events
```

### Backend Event Processing
```
analytics_events (raw events)
    ↓
ProcessAnalyticsEvents Job
    ↓
SessionProcessorService
    ↓
analytics_sessions (derived sessions)
    ↓
analytics_event_experiments (attribution)
```

---

## SQL Queries for Analysis

### Registration Funnel
```sql
SELECT
    COUNT(CASE WHEN name = 'registration_started' THEN 1 END) as started,
    COUNT(CASE WHEN name = 'registration_completed' THEN 1 END) as completed,
    ROUND(COUNT(CASE WHEN name = 'registration_completed' THEN 1 END)::numeric /
          NULLIF(COUNT(CASE WHEN name = 'registration_started' THEN 1 END), 0) * 100, 2) as conversion_rate
FROM analytics_events
WHERE name IN ('registration_started', 'registration_completed')
AND occurred_at > NOW() - INTERVAL '7 days';
```

### Subscription Funnel
```sql
SELECT
    COUNT(CASE WHEN name = 'subscription_started' THEN 1 END) as started,
    COUNT(CASE WHEN name = 'checkout_initiated' THEN 1 END) as checkout,
    COUNT(CASE WHEN name = 'subscription_completed' THEN 1 END) as completed,
    COUNT(CASE WHEN name = 'checkout_cancelled' THEN 1 END) as cancelled
FROM analytics_events
WHERE name IN ('subscription_started', 'checkout_initiated', 'subscription_completed', 'checkout_cancelled')
AND occurred_at > NOW() - INTERVAL '7 days';
```

### Prompt Workflow Funnel
```sql
SELECT
    COUNT(CASE WHEN name = 'prompt_started' THEN 1 END) as started,
    COUNT(CASE WHEN name = 'workflow_stage_completed' AND properties->>'stage' = '0' THEN 1 END) as stage_0_complete,
    COUNT(CASE WHEN name = 'workflow_stage_completed' AND properties->>'stage' = '1' THEN 1 END) as stage_1_complete,
    COUNT(CASE WHEN name = 'prompt_completed' THEN 1 END) as completed,
    COUNT(CASE WHEN name = 'prompt_copied' THEN 1 END) as copied
FROM analytics_events
WHERE name IN ('prompt_started', 'workflow_stage_completed', 'prompt_completed', 'prompt_copied')
AND occurred_at > NOW() - INTERVAL '7 days';
```

### By Day
```sql
SELECT
    DATE(occurred_at) as date,
    COUNT(*) as total_events,
    COUNT(CASE WHEN name = 'registration_completed' THEN 1 END) as registrations,
    COUNT(CASE WHEN name = 'subscription_completed' THEN 1 END) as subscriptions,
    COUNT(CASE WHEN name = 'prompt_completed' THEN 1 END) as prompts_completed
FROM analytics_events
WHERE occurred_at > NOW() - INTERVAL '30 days'
GROUP BY DATE(occurred_at)
ORDER BY date DESC;
```

---

## Best Practices

### Event Tracking
1. **Track at system boundaries** - Backend for conversions (can't be blocked), frontend for engagement
2. **Include context in properties** - Makes analysis clearer without needing joins
3. **Use consistent naming** - All event names lowercase, past tense, underscore-separated
4. **Generate event IDs client-side** - Prevents duplicates if requests retry

### Data Quality
1. **Validate before tracking** - Don't track partial/failed actions
2. **Include timestamps** - Both client (occurred_at_ms) and server (received_at)
3. **Track in success handlers** - Ensures action actually completed
4. **Log unexpected states** - For debugging failed events

### Privacy & Compliance
1. **No PII in event properties** - Only use IDs (visitor_id, user_id, prompt_run_id)
2. **Respect analytics consent** - Frontend service gates all tracking
3. **Use hashed emails** - If email needed for deduplication
4. **Audit event retention** - Keep only necessary data for your analysis window

---

## Monitoring & Alerts

Watch for these metrics to ensure analytics health:

1. **Event Volume:** Expect 50-200+ events per day (depends on traffic)
2. **Failed Queue Jobs:** Should be 0 or close to 0
3. **Session Creation:** Every analytics consent should create one session
4. **Event-to-Session Ratio:** Should be between 5:1 and 20:1
5. **Null Values:** visitor_id/user_id should rarely be null after enrichment

Check Horizon dashboard for queue job failures related to analytics processing.

---

## Future Enhancements

Potential events for future implementation:

- **Feedback Events:** `feedback_submitted`, `feedback_rating_given`
- **Experiment Events:** `variant_exposed`, `variant_converted` (already auto-tracked)
- **Error Events:** `error_occurred`, `api_error` (for debugging)
- **Engagement Events:** `email_opened`, `link_clicked` (if email campaigns added)
- **Feature Usage:** `feature_used`, `feature_abandoned` (for product insights)

---

## References

- Architecture: `/docs/unified-analytics-experimentation-architecture.md`
- Session tracking: `app/Services/SessionProcessorService.php`
- Frontend service: `resources/js/services/analytics.ts`
- Backend controller: `app/Http/Controllers/Api/AnalyticsEventController.php`
- Job processing: `app/Jobs/ProcessAnalyticsEvents.php`

## Testing

- **`tests/Feature/AnalyticsEventsControllerTest.php`** exercises `/api/analytics/events` with a valid payload (visitor cookie + headers) to ensure it returns the queued response and rejects empty event lists. `Bus::fake()` keeps the job from running, so the assertion focuses on the controller layer.
- **`tests/Unit/ProcessAnalyticsEventsTest.php`** reflects into `ProcessAnalyticsEvents` to verify enrichment of visitor, session, and page context, the rejection of malformed payloads, and the event-type derivation logic that keeps the documented categories (`conversion`, `error`, `system`, `engagement`) consistent.
- Run the checks with the lightweight SQLite command below to avoid needing Postgres locally:

```bash
DB_CONNECTION=sqlite DB_DATABASE=:memory: ./vendor/bin/pest tests/Feature/AnalyticsEventsControllerTest.php tests/Unit/ProcessAnalyticsEventsTest.php
```
