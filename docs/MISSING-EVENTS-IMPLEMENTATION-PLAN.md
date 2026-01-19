# Analytics Events Implementation Plan (Revised)

## Overview

This document details the **revised plan** to implement missing or enhance incomplete events from the **Event Catalog** in `unified-analytics-experimentation-architecture.md`.

This revision is based on:
- Actual investigation of tracked events in production
- DRY principle (avoid duplicating data in dedicated tables/fields)
- Bug fixes (page_view triplication)
- Redundancy elimination (events that duplicate existing tracking)

---

## Event Audit Results (Revised)

### ✅ Already Implemented & Documented (17 events)
- registration_started, registration_completed
- login_completed
- password_reset_requested
- subscription_started, subscription_completed, subscription_cancelled
- prompt_started, prompt_completed, prompt_copied, prompt_edited, prompt_rated
- questions_presented, question_answered, question_skipped
- framework_recommended, framework_switched

### ✅ Already Tracked - Just Needs Enhancement (2 events)
1. **`consent_granted`** - Tracked ✅, needs `categories: string[]` array in properties
2. **`page_view`** - Tracked ✅, but **CRITICAL BUG**: recorded 3 times per page (needs fix)

### ✅ Already Tracked - Just Needs Naming (1 event)
- **`consent_revoked`** - Currently tracked as `consent_denied`, just rename it

### ✅ Already Covered by Dedicated Tables (2 events)
Don't need new events; query existing tables instead:
1. **`workflow_failed`** → Query `workflow_analytics` table where `status = 'failed'`
2. **`experiment_exposure`** → Query `experiment_exposures` table (dedicated structured table)

### ⏳ Worth Implementing (1 event)
1. **`client_error`** - Client-side errors (not yet tracked)

### ❌ Dropped from Plan (9 events)
These are redundant or violate DRY principles:

| Event | Reason | Alternative |
|-------|--------|-------------|
| `pricing_page_viewed` | Can derive from page_view | Query `page_view` where `page_path LIKE '%/pricing'` |
| `task_entered` | Redundant | Use `prompt_started` (captures same entry point) |
| `personality_applied` | Redundant | Enhance `prompt_started` properties instead |
| `prompt_generated` | Covered by existing event | Use `workflow_stage_completed` where `stage = 2` |
| `prompt_abandoned` | Unreliable on mobile, better calculated | Derive: prompts with `started` but no `completed` in time window |
| `session_start` | Redundant with analytics_sessions table | Query `analytics_sessions` table directly (each row = session start) |
| `subscription_started.source` | Always "pricing_page" (constant) | Remove property, store nowhere if meaningless |
| Initial page properties in events | Stored in dedicated fields | Use `page_path`, `referrer` fields (not properties) |

---

## Implementation Plan (Simplified)

### Priority 1: Bug Fixes & Critical Issues

#### Task 1.1: Fix `page_view` Triplication Bug

**Problem:** page_view recorded exactly 3 times per page view (verified)

**Root Cause:** Multiple tracking hooks in `usePageTracking.ts`:
- Line 83-91: `onMounted()` with fallback timer
- Line 95-104: `router.on('start')` event
- Line 107-132: `router.on('finish')` event

**Solution:** Debug and consolidate tracking logic to fire once per unique page path

**Files:**
- `resources/js/Composables/analytics/usePageTracking.ts`

**Testing:**
- Verify only 1 page_view event per navigation
- Check no duplicates within 1 second window
- Verify same visitor/session/path don't duplicate

---

#### Task 1.2: Enhance `consent_granted` with Categories

**Current State:** Event exists but properties are `null`

**Change:** Add `categories: string[]` array to properties

**Implementation:**
1. Find where `consent_granted` event is created
2. Extract consent categories from cookie banner
3. Store as array: `["analytics", "marketing"]` or similar
4. DO NOT store `initial_page_path` (already in `page_path` field)

**Files:**
- Backend: Consent tracking middleware/controller
- `tests/` - Verify categories populated

**Testing:**
- Verify categories array populated
- Verify common categories tracked

---

### Priority 2: Enhancement & Property Fixes

#### Task 2.1: Rename `consent_denied` → `consent_revoked`

**Current State:** Event exists as `consent_denied`

**Change:** Rename to match Event Catalog

**Implementation:**
1. Update event name when tracking consent denial
2. Update ANALYTICS-EVENTS.md documentation

**Files:**
- Consent tracking code
- ANALYTICS-EVENTS.md

**Testing:**
- Verify old name removed, new name used

---

#### Task 2.2: Enhance `prompt_started` Properties

**Current State:** Has `has_personality_type: boolean`

**Better State:** `personality_type: string | null` (more informative)

**Change:** Store actual personality type, not just boolean

**Implementation:**
1. Modify `prompt_started` event creation
2. Store `personality_type: string` (e.g., "INTJ") or `null` if not set
3. **Drop** `has_personality_type` boolean

**Benefits:**
- More useful for analysis
- Removes need for separate `personality_applied` event
- Allows immediate personality → prompt correlation

**Files:**
- `app/Http/Controllers/PromptBuilderController.php` (line 197)
- Tests

**Testing:**
- Verify personality_type stored correctly
- Verify null when no personality

---

### Priority 3: New Event Implementations

#### Task 3.1: Implement `client_error` Event

**Type:** System event (frontend)

**Trigger:** Uncaught client-side error

**Location:** Frontend error handler / Vue error boundary

**Properties:**
- `error_type: string` - e.g., "ReferenceError", "TypeError"
- `message: string` - Error message
- `stack?: string` - Stack trace (optional)

**Implementation:**
1. Create global error handler
2. Track `window.onerror` events
3. Debounce same errors (prevent flooding)
4. Filter out intentional 404s, etc.

**Files:**
- `resources/js/plugins/errorHandler.ts` (or similar)
- Tests

**Testing:**
- Errors captured
- Debouncing works
- Stack traces included when available

---

## ANALYTICS-EVENTS.md Updates Required

### Remove/Update Sections
1. Remove: `task_entered` section
2. Remove: `personality_applied` section
3. Remove: `prompt_generated` section
4. Update: `pricing_page_viewed` section → move to "Can be derived" section
5. Remove: Error Events section (workflow_failed, client_error from "missing")
6. Update: Implementation Status phases

### Add New Content
1. Add explicit warning about page_view triplication bug
2. Add notes about consent_granted categories enhancement
3. Add session_start section (planned)
4. Add client_error section (planned)
5. Add table showing which catalog events are covered by dedicated tables

### Update Coverage Summary
- Previously: 12 missing events
- Now: 3 worth implementing + 1 critical bug fix + 2 enhancements
- Total simplified from 12 down to 3 new + 3 fixes/enhancements

---

## Success Criteria

✅ **Bug Fix:**
- page_view events reduce from 3x to 1x per page

✅ **Enhancements:**
- consent_granted has categories array
- prompt_started has personality_type instead of boolean
- consent_denied renamed to consent_revoked

✅ **New Events:**
- session_start implemented and tested
- client_error implemented and tested
- Both fire at correct times with valid properties

✅ **Documentation:**
- ANALYTICS-EVENTS.md reflects revised scope
- Redundant events removed from plan
- Implementation notes clear and complete

✅ **Data Quality:**
- No duplicate events
- Properties use dedicated fields (page_path, referrer) not properties
- All tests passing
- No performance impact

---

## Implementation Order

1. **Day 1:** Fix page_view triplication bug (critical)
2. **Day 1:** Enhance consent_granted with categories
3. **Day 1:** Rename consent_denied → consent_revoked
4. **Day 2:** Enhance prompt_started with personality_type
5. **Day 2:** Implement session_start event
6. **Day 2:** Implement client_error event
7. **Day 3:** Update ANALYTICS-EVENTS.md
8. **Day 3:** Testing & validation

---

## Files Modified

| File | Change | Lines |
|------|--------|-------|
| `resources/js/Composables/analytics/usePageTracking.ts` | Fix triplication bug | ~20 |
| `app/Http/Controllers/PromptBuilderController.php` | Enhance properties | ~5 |
| Consent middleware/controller | Enhance consent_granted | ~10 |
| `resources/js/plugins/errorHandler.ts` | New client_error handler | ~50 |
| Session middleware | New session_start event | ~20 |
| `docs/ANALYTICS-EVENTS.md` | Update documentation | ~100 |
| Tests | Add/update tests | ~150 |

---

## Risk Mitigation

| Risk | Mitigation |
|------|-----------|
| Removing events breaks analysis | Only removing events that are redundant or non-functional |
| Page_view fix causes regression | Comprehensive testing on different navigation scenarios |
| Property changes break existing queries | Property names remain same, just enhanced with more data |
| Client_error floods database | Implement debouncing from start, sample large traces |
| Session_start timing issues | Test with and without consent, verify single firing |

---

## References

- Event Catalog: `docs/unified-analytics-experimentation-architecture.md` (lines 1092-1154)
- Current Events Docs: `docs/ANALYTICS-EVENTS.md`
- Tracking Implementation: `resources/js/Composables/analytics/usePageTracking.ts`
- Event Model: `app/Models/AnalyticsEvent.php`
