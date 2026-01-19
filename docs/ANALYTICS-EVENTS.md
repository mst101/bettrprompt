# Analytics Events Tracking

This document describes all analytics events tracked in the BettrPrompt application for conversion analysis, user journey understanding, and engagement metrics.

## Overview

The analytics system uses a **phased implementation approach** aligned with the **Event Catalog** in `unified-analytics-experimentation-architecture.md`:

### Event Coverage Status
- ✅ **Implemented:** 20 events from Event Catalog + 1 critical bug fix + 2 property enhancements
- ⚠️ **Implementation-Specific:** 6 additional events (checkout, upgrade CTA, questions, etc.)
- ⏳ **Planned:** 3 new events + enhancements to existing events
- **Total Catalog Events:** 29

**Key Status Changes:**
- ✅ `consent_granted` & `consent_revoked` now implemented with proper categories array (not just boolean)
- 🐛 `page_view` bug fixed - was recorded 3x per page view, now fixed to 1x per page
- ✨ `prompt_started` enhanced - now stores actual `personality_type` instead of just boolean

**Revised Implementation Plan:** For details on the 3 planned events and implementation roadmap, see `docs/MISSING-EVENTS-IMPLEMENTATION-PLAN.md`

### Implementation Phases
- **Phase 1: High Priority** - Core conversion events (✅ implemented)
- **Phase 2: Medium Priority** - User journey funnel tracking (✅ implemented)
- **Phase 3: Low Priority** - Engagement insights (✅ implemented)
- **Phase 4-8: Event Catalog Completeness** (⏳ planned - see plan document)

---

## Event Categories

### 0. Lifecycle Events (Consent & Session)

Track analytics consent and session lifecycle.

#### `consent_granted` ✅
- **Priority:** 🔴 High
- **Type:** System event (frontend)
- **Trigger:** User grants analytics consent via cookie banner
- **Location:** `resources/js/Composables/analytics/useAnalyticsInit.ts` (line 52)
- **Properties:**
  - `categories: string[]` - Consent categories granted (e.g., ["analytics", "functional", "essential"])
  - ⚠️ Note: `initial_page_path` not stored in properties; use dedicated `page_path` field instead
- **Use Case:** Track consent flow and ensure GDPR compliance
- **Status:** ✅ IMPLEMENTED (enhanced with categories array)

#### `consent_revoked` ✅
- **Priority:** 🔴 High
- **Type:** System event (frontend)
- **Trigger:** User revokes analytics consent
- **Location:** `resources/js/Composables/analytics/useAnalyticsInit.ts` (line 76)
- **Properties:**
  - `categories: string[]` - Always empty array `[]` when revoked (all categories removed)
  - ⚠️ Previously named `consent_denied` (renamed for Event Catalog alignment)
- **Use Case:** Ensure compliance when users opt-out
- **Status:** ✅ IMPLEMENTED (renamed from consent_denied)

#### `session_start` ⏳ PLANNED
- **Priority:** 🔴 High
- **Type:** System event (server-side)
- **Trigger:** Analytics session begins (post-consent)
- **Properties:**
  - `referrer?: string` - HTTP referrer (stored in dedicated field, not properties)
- **Use Case:** Track session entry points and traffic source
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

#### `page_view` ✅
- **Priority:** 🔴 High
- **Type:** Engagement event (frontend)
- **Trigger:** User views a page (initial load + navigation)
- **Location:** `resources/js/Composables/analytics/usePageTracking.ts` (line 95-117)
- **Properties:**
  - `title?: string` - Page title (optional)
  - ⚠️ `page_path` and `referrer` stored in dedicated fields, not properties
- **Use Case:** Track user navigation and page engagement
- **Status:** ✅ IMPLEMENTED (🐛 BUG FIX: was recorded 3x per page, now fixed to 1x)

---

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
  - `personality_type: string | null` - Applied personality type (e.g., "INTJ") or null if not set
- **Workflow Stage:** 0_processing
- **Use Case:** Entry point for prompt creation funnel + personality tracking
- **Status:** ✅ IMPLEMENTED (✨ enhanced with personality_type instead of boolean)

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

#### `framework_recommended` ⚠️ NEW
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** Framework recommendation shown to user after workflow 1 completes
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue` (framework watch)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `recommended_framework: string` - Framework code/slug
  - `task_category: string` - Task category
  - `personality_type: string | null` - User's personality type
- **Use Case:** Track framework recommendation acceptance and effectiveness
- **Status:** ⚠️ NEW IMPLEMENTATION

#### `framework_switched` ⚠️ NEW
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User chooses alternative framework
- **Location:** `resources/js/Components/Features/PromptBuilder/Framework/AlternativeFrameworks.vue` (handleSwitchFramework)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `from_framework: string` - Previous framework code
  - `to_framework: string` - New framework code
  - `personality_type: string | null` - User's personality type
  - `task_category: string` - Task category
- **Use Case:** Understand framework selection patterns and recommendation accuracy
- **Status:** ⚠️ NEW IMPLEMENTATION

#### `questions_presented` ⚠️ NEW
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** Framework-specific questions shown (fires once per prompt run)
- **Location:** `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue` (questions watch)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `question_ids: string[]` - Array of question IDs
  - `question_count: integer` - Total number of questions
  - `display_mode: string` - 'one-at-a-time' or 'show-all'
  - `personality_type: string | null` - User's personality type
  - `task_category: string` - Task category
- **Use Case:** Track question funnel entry and display preference impact
- **Status:** ⚠️ NEW IMPLEMENTATION

#### `question_answered` (Updated) ✅
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User provides answer to a question
- **Location:** `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue` (saveAnswer)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `question_index: integer` - Index in question array
  - `question_id: string` - Question ID
  - `answer_length: integer` - Length of answer text
  - `time_to_answer_ms: integer | null` - **NEW** Time from shown to answered
  - `display_mode: string` - **NEW** 'one-at-a-time' or 'show-all'
  - `question_category: string | null` - **NEW** Question category
  - `total_questions: integer` - Total number of questions
  - `answered_count: integer` - Number answered so far
- **Use Case:** Analyse response patterns, timing, and completion rates
- **Status:** ✅ ENHANCED WITH NEW FIELDS

#### `question_skipped` ⚠️ NEW
- **Priority:** 🟢 Low
- **Type:** Frontend event
- **Trigger:** User skips question (any question without answer when submitted)
- **Location:** `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue` (submitAllAnswers)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `question_index: integer` - Index in question array
  - `question_id: string` - Question ID
  - `question_category: string | null` - Question category
  - `personality_type: string | null` - User's personality type
  - `display_mode: string` - 'one-at-a-time' or 'show-all'
- **Use Case:** Identify confusing or unhelpful questions
- **Status:** ⚠️ NEW IMPLEMENTATION

#### `prompt_rated` ⚠️ NEW
- **Priority:** 🟡 Medium
- **Type:** Frontend event
- **Trigger:** User submits rating for optimised prompt
- **Location:** `resources/js/Components/Features/PromptBuilder/OptimisedPrompt/OptimisedPrompt.vue` (handleRatingSubmit)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `rating: integer` - Rating value (1-5 stars)
  - `has_explanation: boolean` - Whether explanation provided
  - `explanation_length: integer` - Length of explanation text
  - `prompt_length: integer` - Length of generated prompt
- **Database Persistence:** Also saved directly to `prompt_quality_metrics` table
- **Use Case:** Track prompt quality and user satisfaction
- **Status:** ⚠️ NEW IMPLEMENTATION

#### `question_rated` ⚠️ NEW (Optional)
- **Priority:** 🟢 Low
- **Type:** Frontend event
- **Trigger:** User submits rating for individual question
- **Location:** `resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue` (handleQuestionRatingSubmit)
- **Properties:**
  - `prompt_run_id: number` - The prompt run ID
  - `question_id: string` - Question ID
  - `question_index: integer` - Index in question array
  - `rating: integer` - Rating value (1-5 stars)
  - `has_explanation: boolean` - Whether explanation provided
  - `explanation_length: integer` - Length of explanation text
  - `question_category: string | null` - Question category
  - `was_answered: boolean` - Whether user answered the question
- **Database Persistence:** Also saved to `question_analytics` table
- **Use Case:** Improve question bank quality
- **Status:** ⚠️ NEW IMPLEMENTATION (OPTIONAL)

---

### 3. Subscription Flow Events

Track the complete subscription lifecycle from pricing page to renewal.

**Note:** `pricing_page_viewed` can be derived from `page_view` events where `page_path LIKE '%/pricing'` - no separate event needed (follows DRY principle).

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

### 4. Events Covered by Dedicated Tables

The following events are already tracked in dedicated database tables (not as events). Query these tables instead of creating separate events (follows DRY principle):

#### `workflow_failed`
- **Query Table:** `workflow_analytics` where `status = 'failed'`
- **Rationale:** Dedicated table already tracks all workflow state changes with full context
- **Status:** ✅ COVERED (use table query, no event needed)

#### `experiment_exposure`
- **Query Table:** `experiment_exposures` (dedicated structured table)
- **Rationale:** Dedicated table tracks all variant exposures with full experiment metadata
- **Status:** ✅ COVERED (use table query, no event needed)

---

### 5. Planned New Events

#### `client_error` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** System event (frontend)
- **Trigger:** Client-side error occurs (uncaught exception)
- **Location:** Frontend - error boundary / global error handler
- **Properties:**
  - `error_type: string` - Type of error (e.g., "ReferenceError", "TypeError")
  - `message: string` - Error message
  - `stack?: string` - Stack trace (optional, for dev)
- **Use Case:** Monitor application stability and debug client-side issues
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

---

## Implementation Status

### Event Catalog Alignment (Revised)

This section tracks alignment with the **Event Catalog** defined in `unified-analytics-experimentation-architecture.md` (lines 1092-1154).

**Status Summary (Revised):**
- ✅ **Fully Implemented:** 20 events (from Event Catalog)
- 🐛 **Bug Fixed:** 1 critical event (page_view - was 3x, now fixed to 1x)
- ✨ **Enhanced:** 2 existing events with new properties (consent_granted categories, prompt_started personality_type)
- ⚠️ **Implementation-Specific:** 6 events (framework_recommended, framework_switched, questions_presented, question_answered, question_skipped, prompt_rated)
- ✅ **Covered by Tables:** 2 events (workflow_failed, experiment_exposure already in dedicated tables)
- ✅ **Derivable:** 1 event (pricing_page_viewed can be derived from page_view - DRY principle)
- ⏳ **Planned:** 1 new event (client_error)
- **Total in Catalog:** 29 events

**Key Changes from Initial Plan:**
- Removed 12 "missing" events that were either redundant or already covered
- Fixed critical page_view triplication bug
- Enhanced existing events instead of creating new ones (DRY principle)
- Events involving dedicated tables now query tables instead (avoiding duplication)

**Implementation Plan:** See `docs/MISSING-EVENTS-IMPLEMENTATION-PLAN.md`

### ✅ Phase 1-3: Core Events (COMPLETE - 17 Events)
Conversion, funnel, and engagement tracking:
- [x] `registration_started`, `registration_completed` (both methods)
- [x] `subscription_started`, `checkout_initiated`, `subscription_completed`, `subscription_activated`, `checkout_cancelled`, `subscription_cancelled`
- [x] `prompt_started`, `prompt_completed`, `prompt_copied`, `prompt_edited`
- [x] `login_completed`, `password_reset_requested`
- [x] `upgrade_cta_clicked`
- [x] `workflow_stage_completed` (3 stages: 0, 1, 2)

**Impact:** Core conversion funnels and engagement metrics

### ✅ Phase 4: Lifecycle & Consent Events (COMPLETE - 3 Events)
Session and consent tracking:
- [x] `consent_granted` - ✨ Enhanced with categories array
- [x] `consent_revoked` - Renamed from consent_denied
- [x] `page_view` - 🐛 Bug fixed (was 3x per page, now 1x)

**Impact:** Full consent compliance and page navigation tracking

### ⏳ Phase 5: Additional Tracking (PARTIAL - 6+3 Events)
Framework interaction and question engagement:
- [x] `framework_recommended`, `framework_switched` (implementation-specific)
- [x] `questions_presented`, `question_answered`, `question_skipped` (implementation-specific)
- [x] `prompt_rated` (implementation-specific)
- ⏳ `session_start` - Session lifecycle tracking (planned)
- ⏳ `client_error` - Error monitoring (planned)

**Impact:** Detailed journey mapping and application monitoring

### ✅ Table-Based Tracking (COMPLETE - 2 Events)
Events covered by dedicated database tables (no separate event needed):
- [x] `workflow_failed` → Query `workflow_analytics` table
- [x] `experiment_exposure` → Query `experiment_exposures` table

**Impact:** Event tracking via structured tables (DRY principle)

### ✅ Derivable Events (COMPLETE - 1 Event)
Events that can be derived from existing events:
- [x] `pricing_page_viewed` → Derive from `page_view` where `page_path LIKE '%/pricing'`

**Impact:** No duplication, analysis via queries

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
