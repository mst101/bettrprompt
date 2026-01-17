# Fix Referrer Tracking in Analytics Sessions

## Problem Summary

The `analytics_sessions` table has a `referrer` column, but it's not storing per-session referrer data correctly due to multiple bugs:

1. **Controller Bug**: `AnalyticsEventController` discards event-level referrer data and incorrectly uses the HTTP `Referer` header
2. **Attribution Design Issue**: `SessionProcessorService` always uses `visitor.referrer` (set once on first visit) instead of the session-specific referrer
3. **Result**: All sessions for a visitor show the same referrer (from their very first visit), not the actual referrer for each session

### Current Behaviour
- User first visits from Google → `analytics_sessions.referrer = "https://google.com"`
- User returns from Facebook → `analytics_sessions.referrer = "https://google.com"` ❌ (still shows Google)
- User returns directly → `analytics_sessions.referrer = "https://google.com"` ❌ (still shows Google)

### Expected Behaviour
- User first visits from Google → `analytics_sessions.referrer = "https://google.com"` ✓
- User returns from Facebook → `analytics_sessions.referrer = "https://facebook.com"` ✓
- User returns directly → `analytics_sessions.referrer = null` ✓

## Root Cause Analysis

### Frontend (Working Correctly)
**File**: `resources/js/Composables/analytics/usePageTracking.ts`

- Captures external referrer on initial page load (`document.referrer`)
- Sends referrer with every `page_view` event
- First page view: sends external referrer (e.g., "https://google.com")
- Subsequent page views: sends previous internal page path

### Backend Bug #1: Controller
**File**: `app/Http/Controllers/Api/AnalyticsEventController.php:28`

```php
// Current (WRONG):
$referrer = $request->header('Referer');  // Uses HTTP header (current page)

// The event payload includes referrer data but it's ignored:
// events[].referrer = "https://google.com"  ← Discarded!
```

The controller sets `pageContext['referrer']` to the HTTP `Referer` header (which is the current page URL), not the previous page referrer sent in the event data.

### Backend Bug #2: Session Processor
**File**: `app/Services/SessionProcessorService.php:132`

```php
// Current (WRONG for session-level tracking):
'referrer' => $visitor?->referrer,  // Always uses first-ever referrer

// Should prioritise session-specific referrer:
'referrer' => $sessionReferrer ?? $visitor?->referrer,
```

The session processor always uses `visitor.referrer` (set once on first visit in `TrackVisitor` middleware), ignoring the event-level referrer data that represents the actual entry point for this specific session.

## Implementation Plan

### 1. Fix Controller: Stop Using HTTP Referer Header
**File**: `app/Http/Controllers/Api/AnalyticsEventController.php`

**Changes**:
- Remove lines 27-28 that incorrectly read `$pagePath` and `$referrer` from HTTP headers
- Remove `referrer` from `pageContext` array when dispatching `ProcessAnalyticsEvents` job
- The job already has correct logic to read referrer from event data (no changes needed there)

**Rationale**: The frontend sends referrer with each event. The job correctly reads `events[].referrer`. The controller should not override this with incorrect HTTP header data.

### 2. Update Session Processor: Use Event-Level Referrer
**File**: `app/Services/SessionProcessorService.php`

**Changes**:
- Extract session referrer from first `page_view` event in the session
- Use this session-specific referrer when creating/updating analytics sessions
- Fall back to `visitor.referrer` only if no event-level referrer is available (backwards compatibility)

**Implementation**:
```php
// Around line 76-89 (in processSession method, after finding entry/exit pages):
$sessionReferrer = null;
foreach ($events as $event) {
    if ($event['name'] === 'page_view' && $sessionReferrer === null) {
        $sessionReferrer = $event['referrer'] ?? null;
        break;  // Use first page_view event's referrer
    }
}

// At line 132 (in session->fill() array):
'referrer' => $sessionReferrer ?? $visitor?->referrer,
```

**Rationale**:
- Each session should track its own entry referrer (session-level attribution)
- Fall back to visitor's first referrer if event data is missing (backwards compatibility)
- Enables multi-touch attribution analysis (distinguish first visit vs return visits)

### 3. Write Tests
**New file**: `tests/Feature/AnalyticsSessionReferrerTest.php`

**Test cases**:
1. **First visit from external referrer**: Session referrer should match external referrer
2. **Return visit from different referrer**: Session referrer should match new referrer (not first visit)
3. **Direct visit (no referrer)**: Session referrer should be null
4. **Multiple page views in single session**: Should use first page view's referrer
5. **Backwards compatibility**: If events lack referrer data, fall back to visitor.referrer

**Approach**:
- Use `Http::fake()` to mock analytics event posting
- Create visitor with initial referrer
- Send events with different referrer values
- Process events through `ProcessAnalyticsEvents` job
- Assert `analytics_sessions.referrer` matches event data, not visitor data

### 4. Update Documentation
**File**: `docs/workflow_stages.md` or create `docs/analytics.md`

Document the referrer attribution model:
- Session referrer captures the entry point for that specific session
- Visitor referrer preserves the first-touch attribution
- Use cases: First-touch vs multi-touch attribution analysis

## Critical Files

1. `app/Http/Controllers/Api/AnalyticsEventController.php` - Remove HTTP Referer usage
2. `app/Services/SessionProcessorService.php` - Use event-level referrer
3. `tests/Feature/AnalyticsSessionReferrerTest.php` - New test file
4. `app/Jobs/ProcessAnalyticsEvents.php` - Already correct, no changes needed

## Verification Plan

### 1. Manual Testing
```bash
# Start environment
./vendor/bin/sail up -d

# Run Laravel queue worker to process analytics events
./vendor/bin/sail artisan queue:work

# Frontend: Visit site from different referrers
# - First visit: Open from external link (e.g., Google)
# - Wait 30+ minutes (or use different browser/incognito)
# - Return visit: Open from different external link (e.g., Facebook)
# - Check database:
./vendor/bin/sail artisan tinker
> use App\Models\AnalyticsSession;
> AnalyticsSession::orderBy('started_at', 'desc')->take(5)->get(['id', 'started_at', 'referrer']);
# Should see different referrers for different sessions
```

### 2. Automated Testing
```bash
# Run new test file
./vendor/bin/sail test tests/Feature/AnalyticsSessionReferrerTest.php

# Run all analytics tests to ensure no regressions
./vendor/bin/sail test tests/Feature --filter Analytics
```

### 3. Expected Results
- **Before fix**: All sessions for same visitor show identical referrer (first visit)
- **After fix**: Each session shows its own entry referrer
- New test cases should all pass
- Existing analytics tests should continue passing

## Trade-offs & Decisions

### Attribution Model: Session-Level vs First-Touch
**Decision**: Implement session-level attribution with first-touch fallback

**Rationale**:
- **Session-level** enables multi-touch attribution (which channels drive return visits?)
- **Fallback to first-touch** provides backwards compatibility
- Both models are now supported: use `visitor.referrer` for first-touch, `analytics_sessions.referrer` for session-level

### Optional Enhancement: visitor.current_referrer Column
**Not included in this plan** (can be added later if needed)

Could add a `current_referrer` column to `visitors` table (similar to `current_utm_source`):
- Updates on each visit
- Provides easier access to "most recent referrer" without querying sessions

**Decision**: Skip for now
- Can be derived from latest session
- Reduces migration complexity
- Add later if performance requires it

## Success Criteria

- [ ] Controller no longer uses HTTP `Referer` header for analytics
- [ ] Sessions capture their own entry referrer, not visitor's first referrer
- [ ] All new tests pass
- [ ] No regressions in existing analytics tests
- [ ] Manual verification shows different referrers for different sessions
- [ ] Documentation updated with attribution model explanation
