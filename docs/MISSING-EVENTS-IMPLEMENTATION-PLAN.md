# Missing Events Implementation Plan

## Overview

This document details the plan to implement all missing events from the **Event Catalog** in `unified-analytics-experimentation-architecture.md` that are not yet being recorded or documented in `ANALYTICS-EVENTS.md`.

---

## Event Audit Results

### ✅ Already Implemented (17 events)
- registration_started
- registration_completed
- login_completed
- password_reset_requested
- subscription_started (named "subscription_started" in code, "subscription_initiated" in catalog)
- subscription_completed (named "subscription_completed" in code, "subscription_success" in catalog)
- subscription_cancelled
- prompt_started
- prompt_completed
- prompt_copied
- prompt_edited
- prompt_rated (recently added)
- questions_presented (recently added)
- question_answered (recently added)
- question_skipped (recently added)
- framework_recommended (recently added)
- framework_switched (recently added)

### ⚠️ Extra Implementation Events Not in Catalog (4 events)
These are implemented but not in the original Event Catalog:
- checkout_initiated
- checkout_cancelled
- subscription_activated (webhook)
- upgrade_cta_clicked
- workflow_stage_completed (3 events for stages 0, 1, 2)
- question_rated (optional, for individual questions)

### ❌ Missing Events from Catalog (12 events)

#### 1. **Lifecycle Events (4 missing)**
- `consent_granted` - User grants analytics consent
- `consent_revoked` - User revokes analytics consent
- `session_start` - Analytics session begins (post-consent)
- `page_view` - User views a page

#### 2. **Subscription Events (1 missing)**
- `pricing_page_viewed` - User views pricing page

#### 3. **Prompt Builder Events (4 missing)**
- `task_entered` - User enters task description (separate from prompt_started)
- `personality_applied` - Personality assessment applied to prompt
- `prompt_generated` - Final prompt generated (separate from prompt_completed)
- `prompt_abandoned` - User leaves without completing

#### 4. **Error Events (2 missing)**
- `workflow_failed` - n8n workflow failed
- `client_error` - Client-side error occurred

#### 5. **Experiment Events (1 missing)**
- `experiment_exposure` - Variant rendered to user (partially auto-tracked)

---

## Implementation Plan

### Phase 1: Lifecycle Events (High Priority)

These are foundational events required for accurate session tracking.

#### Task 1.1: `consent_granted`

**Type:** System event (server-side)

**Trigger:** User grants analytics consent via cookie banner

**Location:**
- Frontend: Cookie consent banner component (likely in `resources/js/Components/`)
- Backend: Session created via middleware

**Properties:**
- `categories: string[]` - Consent categories (e.g., ["analytics", "marketing"])
- `initial_page_path: string` - Page where consent was given

**Implementation:**
1. Find cookie consent implementation
2. Add backend event tracking when consent is recorded
3. Track via middleware after setting consent cookie
4. Include consent categories in properties

**Testing:**
- Verify event recorded when user accepts consent
- Verify categories array populated correctly
- Verify initial_page_path matches entry point

---

#### Task 1.2: `consent_revoked`

**Type:** System event (server-side)

**Trigger:** User revokes analytics consent

**Location:** Backend - when consent is revoked in settings

**Properties:**
- `categories: string[]` - Consent categories being revoked

**Implementation:**
1. Find consent revocation endpoint
2. Record event before clearing consent cookie
3. Stop all event tracking after revocation
4. Clear analytics session ID from storage

**Testing:**
- Verify event recorded when consent revoked
- Verify no further events tracked after revocation
- Verify consent state persisted correctly

---

#### Task 1.3: `session_start`

**Type:** System event (server-side)

**Trigger:** Analytics session begins (post-consent)

**Location:** Middleware - after consent confirmed

**Properties:**
- `entry_page: string` - Initial page URL
- `referrer?: string` - HTTP referrer (optional)

**Implementation:**
1. Modify SessionProcessorService to emit event
2. Fire after consent confirmed and session ID generated
3. Track once per session
4. Include referrer from request headers

**Testing:**
- Verify one session_start per analytics session
- Verify entry_page correct
- Verify referrer captured when available

---

#### Task 1.4: `page_view`

**Type:** Engagement event (frontend)

**Trigger:** User views a page (on initial load + navigation)

**Location:** Frontend - route/page component lifecycle

**Properties:**
- `path: string` - Current page path (e.g., "/gb/prompt-builder")
- `title?: string` - Page title (optional)
- `referrer?: string` - Referrer from previous page (optional)

**Implementation:**
1. Create Inertia middleware to track page views
2. Fire on initial render + page navigation
3. Use router.before hooks to capture navigation
4. Get referrer from document.referrer
5. Deduplicate consecutive identical page_view events

**Testing:**
- Verify page_view on initial load
- Verify page_view on navigation
- Verify no duplicate events on same page
- Verify path/title correct

---

### Phase 2: Subscription Events (Medium Priority)

#### Task 2.1: `pricing_page_viewed`

**Type:** Funnel event (frontend)

**Trigger:** User navigates to pricing page

**Location:** `resources/js/Pages/Pricing.vue` (or equivalent)

**Properties:**
- None in catalog, but could add:
  - `country: string` - Country code from URL
  - `currency: string` - Currency displayed

**Implementation:**
1. Add event tracking in Pricing page component
2. Fire once on mount
3. Include locale context

**Testing:**
- Verify event fired on pricing page load
- Verify only one event per page visit
- Verify country/currency populated

---

### Phase 3: Prompt Builder Events (Medium Priority)

#### Task 3.1: `task_entered`

**Type:** Engagement event (frontend)

**Trigger:** User enters/modifies task description (not submission)

**Location:** `resources/js/Components/Features/PromptBuilder/` (task input component)

**Properties:**
- `prompt_run_id: uuid` - The prompt run ID
- `task_length: int` - Character length of task description

**Implementation:**
1. Add event on task input component (debounced, ~1s after last keystroke)
2. Track only on first entry + when length changes significantly
3. Fire before prompt_started
4. Include task length for analysis

**Testing:**
- Verify event fires when user starts typing task
- Verify task_length accurate
- Verify fired before form submission

---

#### Task 3.2: `personality_applied`

**Type:** Engagement event (frontend)

**Trigger:** Personality assessment applied to current prompt run

**Location:** `resources/js/Pages/PromptBuilder/Show.vue` (personality application)

**Properties:**
- `prompt_run_id: uuid` - The prompt run ID
- `personality_type: string` - Applied personality type (e.g., "INTJ")

**Implementation:**
1. Track when personality is applied/selected
2. Fire once per prompt run
3. Distinguish from initial assessment (might be same event)
4. Include personality type

**Testing:**
- Verify event fired when personality applied
- Verify personality_type correct
- Verify fires at correct point in flow

---

#### Task 3.3: `prompt_generated`

**Type:** Engagement event (frontend)

**Trigger:** Final prompt generated by workflow 2

**Location:** `resources/js/Pages/PromptBuilder/Show.vue` (after workflow 2 completion)

**Properties:**
- `prompt_run_id: uuid` - The prompt run ID
- `framework: string` - Framework used
- `prompt_length: int` - Character length of generated prompt

**Implementation:**
1. Fire after workflow 2 completes
2. Separate from prompt_completed (which is when user accepts it)
3. Include framework and length
4. Could differentiate from prompt_completed by user action

**Testing:**
- Verify event fires when prompt first generated
- Verify before prompt_completed event
- Verify framework and length correct

---

#### Task 3.4: `prompt_abandoned`

**Type:** Engagement event (frontend)

**Trigger:** User leaves prompt builder without completing

**Location:** `resources/js/Pages/PromptBuilder/Show.vue` (page unload or navigation away)

**Properties:**
- `prompt_run_id: uuid` - The prompt run ID
- `stage: string` - Where they abandoned (e.g., "questions", "framework_selection", "generated")
- `time_spent_ms: int` - Time spent in prompt builder

**Implementation:**
1. Track beforeUnload or route guard
2. Calculate time spent since prompt_started
3. Determine current stage based on prompt_run state
4. Only track if not completed
5. Fire on window.onbeforeunload

**Testing:**
- Verify event fires when navigating away without completion
- Verify stage accurate
- Verify time_spent_ms reasonable
- Verify not fired if prompt_completed

---

### Phase 4: Error Events (Medium Priority)

#### Task 4.1: `workflow_failed`

**Type:** System event (server-side)

**Trigger:** n8n workflow fails at any stage

**Location:** Backend - n8n webhook receiver or workflow tracker

**Properties:**
- `prompt_run_id: uuid` - The prompt run ID
- `workflow_stage: int` - Stage that failed (0, 1, or 2)
- `error_code: string` - Error code from n8n

**Implementation:**
1. Track in n8n webhook receiver
2. When workflow returns error status
3. Extract error code from n8n response
4. Include workflow stage
5. Could add error_message for debugging

**Testing:**
- Verify event fires on workflow error
- Verify error_code from n8n included
- Verify workflow_stage correct

---

#### Task 4.2: `client_error`

**Type:** System event (frontend)

**Trigger:** Client-side error occurs (uncaught exception)

**Location:** Frontend - error boundary / global error handler

**Properties:**
- `error_type: string` - Type of error (e.g., "ReferenceError", "TypeError")
- `message: string` - Error message
- `stack?: string` - Stack trace (optional, for dev)

**Implementation:**
1. Create global error handler (Vue error boundary)
2. Track window.onerror events
3. Include error type, message, and optional stack
4. Debounce to avoid flooding with same errors
5. Only track for non-404, non-intentional errors

**Testing:**
- Verify error events captured
- Verify error_type and message populated
- Verify debouncing works
- Verify stack trace included when available

---

### Phase 5: Experiment Events (Low Priority)

#### Task 5.1: `experiment_exposure`

**Type:** Exposure event (frontend or backend)

**Trigger:** Variant rendered to user

**Location:** Backend or Frontend - wherever experiment logic lives

**Properties:**
- `experiment_slug: string` - Experiment identifier (e.g., "registration_cta_copy_v1")
- `variant_slug: string` - Variant identifier (e.g., "get_started_free", "create_first_prompt")
- `component?: string` - Component name where exposed (optional)

**Implementation:**
1. Integrate with experimentation framework
2. Fire whenever variant is assigned/rendered
3. Include experiment and variant slugs
4. Optional: include component name for debugging
5. Could be auto-tracked or explicit

**Testing:**
- Verify event fires when variant shown
- Verify experiment and variant slugs correct
- Verify one event per exposure
- Verify deduplicated on page reload

---

## Implementation Order

1. **Week 1:** Lifecycle events (consent_granted, consent_revoked, session_start)
2. **Week 1:** Page view event (critical for all analysis)
3. **Week 2:** Pricing page event
4. **Week 2:** Prompt builder events (task_entered, personality_applied)
5. **Week 3:** Prompt completion events (prompt_generated, prompt_abandoned)
6. **Week 3:** Error events (workflow_failed, client_error)
7. **Week 4:** Experiment exposure event

---

## ANALYTICS-EVENTS.md Updates

Once implemented, ANALYTICS-EVENTS.md should be updated to:

1. Add all 12 missing events with full documentation
2. Move `experiment_exposure` from "Future Enhancements" to implemented section
3. Update status indicators:
   - ✅ IMPLEMENTED (for all events once complete)
   - ⚠️ NEW only for recently added events
4. Add implementation status for each event
5. Update Phase completion status
6. Add SQL queries for analyzing new events

---

## Testing & Validation

### Test Coverage
- Unit tests for event creation
- Feature tests for event ingestion
- E2E tests for complete user flows
- Analytics dashboard verification

### Monitoring
- Alert if event rate drops significantly
- Monitor queue job failures
- Check for missing event_ids (duplicates)
- Validate event properties populated correctly

### SQL Validation Queries

```sql
-- Check consent flow
SELECT name, COUNT(*) as count FROM analytics_events
WHERE name IN ('consent_granted', 'consent_revoked')
GROUP BY name;

-- Check page view volume
SELECT DATE(occurred_at) as date, COUNT(*) as page_views
FROM analytics_events
WHERE name = 'page_view'
GROUP BY DATE(occurred_at)
ORDER BY date DESC;

-- Check prompt abandonment
SELECT COUNT(*) as abandoned
FROM analytics_events
WHERE name = 'prompt_abandoned'
  AND DATE(occurred_at) > NOW() - INTERVAL '7 days';

-- Check error events
SELECT name, COUNT(*) as count
FROM analytics_events
WHERE name IN ('workflow_failed', 'client_error')
GROUP BY name;
```

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| High event volume from page_view | Deduplicate consecutive identical events, sample if needed |
| Abandoned prompts hard to identify | Use workflow_stage or UI state to determine stage |
| Client errors flood queue | Debounce same error, sample stack traces |
| Consent_revoked stops tracking | Clear session ID, stop all tracking immediately |
| Experiment exposure timing | Fire before rendering variant, not after |

---

## Success Criteria

- ✅ All 12 missing events implemented
- ✅ Events firing at correct times with valid properties
- ✅ No duplicate events in database
- ✅ Event properties match Event Catalog schema
- ✅ ANALYTICS-EVENTS.md updated with all events
- ✅ Tests passing (unit + feature + E2E)
- ✅ Analytics dashboard shows correct volumes
- ✅ No performance impact from new tracking

---

## References

- Event Catalog: `docs/unified-analytics-experimentation-architecture.md` (lines 1092-1154)
- Current Documentation: `docs/ANALYTICS-EVENTS.md`
- Implementation Examples: `app/Http/Controllers/Auth/`, `tests/Feature/AnalyticsEventsControllerTest.php`
