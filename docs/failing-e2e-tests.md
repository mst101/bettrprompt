● Here's your organized breakdown of the 24 failing tests with 2 workers (72% pass rate):

PATTERN 1: Navigation/API Timeout (1 test)

- prompt-builder.e2e.ts:62 - "should submit a prompt and navigate to show page"
    - Error: Form submits but page never navigates to show page with ID
    - Cause: Pre-analysis API endpoint slow or not responding

PATTERN 2: Missing "View All Questions" Button (4 tests)

All in prompt-builder-analytics.e2e.ts:

- Line 144 - "should track multiple question_answered events in sequence"
- Line 223 - "should track question_answered event on blur in 'View all questions' mode"
- Line 294 - "should NOT track or save when blurring without changes"
- Line 367 - "should track multiple answers on blur in bulk mode"
    - Error: Button with text "View all questions" not appearing within 10s
    - Cause: Conditional rendering—button only shows after questions load

PATTERN 3: Component State/Visibility (15 tests)

PromptRating (5 tests) - prompt-rating.e2e.ts:

- 514 - "uses smaller button size (sm)"
- 575 - "clicking 'Rate this question (optional)' expands UI"
- 608 - "auto-expands when star clicked"
- 654 - "toggle button shows 'Hide rating' when expanded"
- 689 - "can collapse rating UI after expanding"
    - Cause: Event handlers not firing, state not updating

History/Sorting (4 tests) - history.e2e.ts:

- 350 - "should toggle sort direction when clicking column header"
- 384 - "should sort by workflow stage"
- 471 - "should display pagination controls when multiple pages exist"
- 792 - "should show status badge in mobile view"
    - Cause: Table data not loading fast enough

Display Mode Preference (4 tests) - display-mode-preference.e2e.ts:

- 19 - "user can switch from single to bulk mode"
- 53 - "user can switch from bulk back to single mode"
- 169 - "mode preference persists across different prompt runs"
- 227 - "mode preference persists across browser session"
    - Cause: Toggle buttons not responding, localStorage not persisting

Other State Issues (2 tests):

- pricing.e2e.ts:203 - "should allow toggling back to yearly billing"
- language-persistence.e2e.ts:293 - "should close dropdown when clicking outside"

PATTERN 4: Page Loading/Session (5 tests)

- prompt-builder-advanced.e2e.ts:13 - "should display completed prompt with all sections"
- oauth.e2e.ts:134 - "should log out user after OAuth login"
- complete-rating-flow.e2e.ts:288 - "user can rate all questions in bulk view"
- complete-rating-flow.e2e.ts:623 - "rating flow works consistently across page reloads"
- profile-extended.e2e.ts:17 - "should allow updating location fields"
    - Cause: Page content not loading within timeout

  ---                                                                                                                                                                                                                                          

Key insight: These aren't flaky infrastructure failures anymore—they're real test issues where the app is slower under
load and tests time out before content appears. Would you like me to fix these by adding better wait conditions       
(increased timeouts, explicit response waits)? 
