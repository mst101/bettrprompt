# Analytics Events Tracking

This document describes all analytics events tracked in the BettrPrompt application for conversion analysis, user journey understanding, and engagement metrics.

## Overview

The analytics system uses a **phased implementation approach** aligned with the **Event Catalog** in `unified-analytics-experimentation-architecture.md`:

### Event Coverage Status
- ✅ **Implemented:** 17 events from Event Catalog
- ⚠️ **Implementation-Specific:** 6 additional events (checkout, upgrade CTA, etc.)
- ⏳ **Planned:** 12 events from Event Catalog
- **Total Catalog Events:** 29

**Missing Implementation Plan:** For details on the 12 planned events and implementation roadmap, see `docs/MISSING-EVENTS-IMPLEMENTATION-PLAN.md`

### Implementation Phases
- **Phase 1: High Priority** - Core conversion events (✅ implemented)
- **Phase 2: Medium Priority** - User journey funnel tracking (✅ implemented)
- **Phase 3: Low Priority** - Engagement insights (✅ implemented)
- **Phase 4-8: Event Catalog Completeness** (⏳ planned - see plan document)

---

## Event Categories

### 0. Lifecycle Events (Consent & Session)

Track analytics consent and session lifecycle.

#### `consent_granted` ⏳ PLANNED
- **Priority:** 🔴 High
- **Type:** System event (server-side)
- **Trigger:** User grants analytics consent via cookie banner
- **Properties:**
  - `categories: string[]` - Consent categories (e.g., ["analytics", "marketing"])
  - `initial_page_path: string` - Page where consent was given
- **Use Case:** Track consent flow and ensure GDPR compliance
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

#### `consent_revoked` ⏳ PLANNED
- **Priority:** 🔴 High
- **Type:** System event (server-side)
- **Trigger:** User revokes analytics consent
- **Properties:**
  - `categories: string[]` - Consent categories being revoked
- **Use Case:** Ensure compliance when users opt-out
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

#### `session_start` ⏳ PLANNED
- **Priority:** 🔴 High
- **Type:** System event (server-side)
- **Trigger:** Analytics session begins (post-consent)
- **Properties:**
  - `entry_page: string` - Initial page URL
  - `referrer?: string` - HTTP referrer (optional)
- **Use Case:** Track session entry points and traffic source
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

#### `page_view` ⏳ PLANNED
- **Priority:** 🔴 High
- **Type:** Engagement event (frontend)
- **Trigger:** User views a page (initial load + navigation)
- **Properties:**
  - `path: string` - Current page path (e.g., "/gb/prompt-builder")
  - `title?: string` - Page title (optional)
  - `referrer?: string` - Referrer from previous page (optional)
- **Use Case:** Track user navigation and page engagement
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

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

#### `task_entered` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Engagement event (frontend)
- **Trigger:** User enters/modifies task description (distinct from submission)
- **Location:** Task input component in prompt builder
- **Properties:**
  - `prompt_run_id: uuid` - The prompt run ID
  - `task_length: integer` - Character length of task description
- **Use Case:** Distinguish task entry from prompt submission
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

#### `personality_applied` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Engagement event (frontend)
- **Trigger:** Personality assessment applied to prompt
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue`
- **Properties:**
  - `prompt_run_id: uuid` - The prompt run ID
  - `personality_type: string` - Applied personality type (e.g., "INTJ")
- **Use Case:** Track personality application point in workflow
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

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

#### `prompt_generated` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Engagement event (frontend)
- **Trigger:** Final prompt generated by workflow (distinct from user acceptance)
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue` (after workflow 2 completion)
- **Properties:**
  - `prompt_run_id: uuid` - The prompt run ID
  - `framework: string` - Framework used
  - `prompt_length: integer` - Character length of generated prompt
- **Use Case:** Distinguish generation (system action) from completion (user action)
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

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

#### `prompt_abandoned` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Engagement event (frontend)
- **Trigger:** User leaves prompt builder without completing
- **Location:** `resources/js/Pages/PromptBuilder/Show.vue` (page unload / navigation away)
- **Properties:**
  - `prompt_run_id: uuid` - The prompt run ID
  - `stage: string` - Where abandoned (e.g., "task_entry", "questions", "framework_selection", "generated")
  - `time_spent_ms: integer` - Time spent in prompt builder
- **Use Case:** Identify drop-off points and abandonment reasons
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

---

### 3. Subscription Flow Events

Track the complete subscription lifecycle from pricing page to renewal.

#### `pricing_page_viewed` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Funnel event (frontend)
- **Trigger:** User navigates to pricing page
- **Location:** `resources/js/Pages/Pricing.vue` (or equivalent)
- **Properties:**
  - `country: string` - Country code from URL
  - `currency: string` - Currency displayed
- **Use Case:** Track funnel entry point for subscription
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

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

### 4. Error Events

Track system and client-side errors for debugging and monitoring.

#### `workflow_failed` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** System event (server-side)
- **Trigger:** n8n workflow fails at any stage
- **Location:** Backend - n8n webhook receiver or workflow tracker
- **Properties:**
  - `prompt_run_id: uuid` - The prompt run ID
  - `workflow_stage: integer` - Stage that failed (0, 1, or 2)
  - `error_code: string` - Error code from n8n
- **Use Case:** Monitor workflow health and identify bottlenecks
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

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

### 5. Experiment Events

Track A/B tests and feature experiments.

#### `experiment_exposure` ⏳ PLANNED
- **Priority:** 🟡 Medium
- **Type:** Exposure event (frontend or backend)
- **Trigger:** Variant rendered to user
- **Location:** Backend or Frontend - wherever experiment logic lives
- **Properties:**
  - `experiment_slug: string` - Experiment identifier (e.g., "registration_cta_copy_v1")
  - `variant_slug: string` - Variant identifier (e.g., "get_started_free", "create_first_prompt")
  - `component?: string` - Component name where exposed (optional)
- **Use Case:** Track experiment participation for A/B testing
- **Status:** ⏳ PLANNED (See MISSING-EVENTS-IMPLEMENTATION-PLAN.md)

---

## Implementation Status

### Event Catalog Alignment

This section tracks alignment with the **Event Catalog** defined in `unified-analytics-experimentation-architecture.md` (lines 1092-1154).

**Status Summary:**
- ✅ **Implemented:** 17 events (from Event Catalog)
- ⚠️ **Enhanced/Extra:** 6 events (implementation-specific, not in catalog)
- ⏳ **Planned:** 12 events (from Event Catalog, not yet implemented)
- **Total in Catalog:** 29 events

**Missing Implementation Plan:** See `docs/MISSING-EVENTS-IMPLEMENTATION-PLAN.md`

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

### ⏳ Phase 4: Lifecycle & Consent Events (PLANNED)
- [ ] `consent_granted` - Cookie consent granted
- [ ] `consent_revoked` - Analytics consent revoked
- [ ] `session_start` - Analytics session begins
- [ ] `page_view` - User views a page

**Expected Impact:** Complete session tracking and consent compliance

### ⏳ Phase 5: Subscription & Pricing Events (PLANNED)
- [ ] `pricing_page_viewed` - User views pricing page

**Expected Impact:** Complete subscription funnel visibility

### ⏳ Phase 6: Prompt Builder Journey Events (PLANNED)
- [ ] `task_entered` - User enters task description
- [ ] `personality_applied` - Personality applied to prompt
- [ ] `prompt_generated` - Final prompt generated
- [ ] `prompt_abandoned` - User leaves without completing

**Expected Impact:** Detailed journey mapping and drop-off analysis

### ⏳ Phase 7: Error & Monitoring Events (PLANNED)
- [ ] `workflow_failed` - n8n workflow failed
- [ ] `client_error` - Client-side error occurred

**Expected Impact:** Application health monitoring and debugging

### ⏳ Phase 8: Experiment Events (PLANNED)
- [ ] `experiment_exposure` - Variant rendered to user

**Expected Impact:** A/B testing capability

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
