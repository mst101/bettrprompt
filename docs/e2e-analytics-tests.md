# E2E Analytics Tests Documentation

## Overview

This document describes the comprehensive end-to-end tests created for verifying analytics tracking in the PromptBuilder flow. These tests ensure that user interactions are properly tracked in the `analytics_events` table with correct properties.

## Test File

**Location:** `/tests-frontend/e2e/prompt-builder-analytics.e2e.ts`

## What We're Testing

### 1. Tab Navigation Analytics

Tests verify that `tab_viewed` events are tracked when users manually switch between tabs in the PromptBuilder.

#### Key Behaviours Tested

- **Manual tab switches generate events**: When a user clicks on a tab (Your Task, Framework, Questions, Prompt), a `tab_viewed` event should be created
- **Programmatic switches do NOT generate events**: When the application auto-switches tabs (e.g., after workflow completion), no events should be generated
- **No spurious page_view events**: Switching to the Questions tab should NOT trigger a page reload or `page_view` event (this was a bug that was fixed)
- **Sequential tracking**: Multiple tab switches should generate separate events with correct `previous_tab` tracking

#### Event Properties Verified

```typescript
{
    name: 'tab_viewed',
    properties: {
        tab: 'questions',              // Current tab ID
        previous_tab: 'framework',     // Previous tab ID
        prompt_run_id: 123,           // Associated prompt run
        workflow_stage: '1_completed'  // Current workflow state
    },
    prompt_run_id: 123,               // Denormalized for query performance
    // ... standard fields (visitor_id, user_id, session_id, occurred_at, etc.)
}
```

### 2. Question Answering Analytics (One-at-a-time Mode)

Tests verify that `question_answered` events are tracked when users answer clarifying questions sequentially.

#### Key Behaviours Tested

- **Answering and clicking "Next"**: Tracks event when user submits an answer
- **Multiple answers in sequence**: Each question generates a separate event
- **Question index tracking**: Events include the zero-based question index
- **Answer length tracking**: Events include the character count of the answer

#### Event Properties Verified

```typescript
{
    name: 'question_answered',
    properties: {
        question_index: 0,                    // Zero-based index
        question_id: 'Q0',                    // Question identifier
        answer_length: 45,                    // Character count
        prompt_run_id: 123,                   // Associated prompt run
        total_questions: 3,                   // Total number of questions
        answered_count: 1                     // Number of questions answered so far
    },
    prompt_run_id: 123
}
```

### 3. Question Answering Analytics (Bulk Mode with Auto-save)

Tests verify that `question_answered` events are tracked when users answer questions in "View all questions" mode with auto-save on blur.

#### Key Behaviours Tested

- **Auto-save on blur**: When a user types in a textarea and blurs it, the answer auto-saves
- **Event tracking on blur**: Each auto-save triggers a `question_answered` event
- **No tracking on unchanged blur**: Blurring without changes does NOT track or save
- **Multiple answers in bulk**: Can answer multiple questions, each tracked separately

#### Implementation Details

The auto-save functionality is in `BulkQuestions.vue`:

```typescript
const handleBlur = (index: number, value: string) => {
    // Only save in answering mode, not edit mode
    if (props.isEditMode) {
        return;
    }

    const normalizeValue = (val: string | null | undefined) => {
        if (!val) return null;
        const trimmed = val.trim();
        return trimmed.length ? trimmed : null;
    };

    const currentValue = normalizeValue(value);
    const savedValue = normalizeValue(props.savedAnswers[index]);

    // Only save if the value has actually changed
    if (currentValue !== savedValue) {
        emit('save-answer', index, value);
    }
};
```

### 4. Edit Mode Behaviour

Tests verify that analytics tracking and auto-save are DISABLED when editing answers after prompt completion.

#### Key Behaviours Tested

- **No auto-save in edit mode**: Blurring textareas does NOT trigger auto-save
- **No tracking in edit mode**: Blurring textareas does NOT create `question_answered` events
- **Submit button visible**: Users must explicitly submit edited answers via a button
- **Cancel button visible**: Users can cancel editing without creating a new prompt run

#### Why This Matters

In edit mode, the workflow creates a **child prompt run** when the user submits. We don't want intermediate edits to:
1. Modify the existing completed prompt run
2. Generate spurious analytics events
3. Create confusion about which answers were "final"

## Backend Support

### Test Endpoint

A new test endpoint was added to `TestBroadcastController` to query analytics events:

**Endpoint:** `GET /test/analytics-events`

**Query Parameters:**
- `event_name`: Filter by event name (e.g., 'tab_viewed', 'question_answered')
- `prompt_run_id`: Filter by prompt run ID
- `page_path`: Filter by page path
- `limit`: Maximum number of events to return (default: 100)

**Security:** Protected by `X-Test-Auth: playwright-e2e-tests` header

**Example Usage in Tests:**

```typescript
const analyticsEvents = await authenticatedPage.evaluate(
    async (promptRunIdParam: number) => {
        const response = await fetch(
            `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=tab_viewed`,
            {
                headers: {
                    'X-Test-Auth': 'playwright-e2e-tests',
                },
            },
        );
        return response.json();
    },
    promptRunId,
);
```

## Test Structure

### Test Groups

1. **PromptBuilder Analytics - Tab Navigation**
   - `should track tab_viewed event when manually switching tabs`
   - `should track multiple tab switches in sequence`
   - `should NOT track page_view event when switching to Questions tab`

2. **PromptBuilder Analytics - Question Answering (One-at-a-time)**
   - `should track question_answered event when answering sequentially`
   - `should track multiple question_answered events in sequence`

3. **PromptBuilder Analytics - Question Answering (Bulk Mode)**
   - `should track question_answered event on blur in "View all questions" mode`
   - `should NOT track or save when blurring without changes`
   - `should track multiple answers on blur in bulk mode`

4. **PromptBuilder Analytics - Edit Mode**
   - `should NOT track or auto-save when editing answers after completion`
   - `should show submit button in edit mode without auto-saving`

### Common Patterns

All tests follow this pattern:

```typescript
test('test description', async ({ authenticatedPage }) => {
    // 1. Setup: Create prompt run in specific state
    const promptRunId = await setupAndNavigateToPromptRun(
        authenticatedPage,
        '1_completed',
    );

    // 2. Wait for page to load
    await authenticatedPage.waitForLoadState('domcontentloaded');

    // 3. Perform user action (click tab, answer question, etc.)
    const questionsTab = authenticatedPage.getByTestId('tab-button-questions');
    await questionsTab.click();
    await authenticatedPage.waitForTimeout(500);

    // 4. Query analytics events
    const analyticsEvents = await authenticatedPage.evaluate(
        async (promptRunIdParam: number) => {
            const response = await fetch(
                `/test/analytics-events?prompt_run_id=${promptRunIdParam}&event_name=tab_viewed`,
                {
                    headers: { 'X-Test-Auth': 'playwright-e2e-tests' },
                },
            );
            return response.json();
        },
        promptRunId,
    );

    // 5. Verify events were created with correct properties
    expect(analyticsEvents.length).toBeGreaterThan(0);
    expect(analyticsEvents[0].name).toBe('tab_viewed');
    expect(analyticsEvents[0].properties.tab).toBe('questions');
});
```

## Running the Tests

### Run All Analytics Tests

```bash
npx playwright test tests-frontend/e2e/prompt-builder-analytics.e2e.ts
```

### Run Specific Test Group

```bash
npx playwright test tests-frontend/e2e/prompt-builder-analytics.e2e.ts -g "Tab Navigation"
```

### Run Individual Test

```bash
npx playwright test tests-frontend/e2e/prompt-builder-analytics.e2e.ts -g "should track tab_viewed event"
```

### Run in Headed Mode (Watch Tests)

```bash
npx playwright test tests-frontend/e2e/prompt-builder-analytics.e2e.ts --headed
```

### Run with Playwright UI

```bash
npx playwright test tests-frontend/e2e/prompt-builder-analytics.e2e.ts --ui
```

## Database Schema

The tests verify data in the `analytics_events` table:

```sql
CREATE TABLE analytics_events (
    event_id UUID PRIMARY KEY,                -- Client-generated UUID
    name VARCHAR(100) NOT NULL,               -- Event name ('tab_viewed', 'question_answered')
    type VARCHAR(50) NOT NULL,                -- Event type ('engagement', 'conversion')
    properties JSONB,                         -- Event-specific data
    visitor_id UUID,                          -- Visitor identifier
    user_id BIGINT,                           -- User identifier (if authenticated)
    session_id UUID,                          -- Analytics session ID
    source VARCHAR(10) DEFAULT 'client',      -- 'client' or 'server'
    page_path VARCHAR(255),                   -- Page URL path
    referrer VARCHAR(500),                    -- HTTP referrer
    device_type VARCHAR(20),                  -- 'desktop', 'mobile', 'tablet'
    prompt_run_id BIGINT,                     -- Associated prompt run (if applicable)
    occurred_at TIMESTAMP NOT NULL            -- When event occurred
);
```

## Test Data Setup

Tests use the `setupAndNavigateToPromptRun` fixture helper to create prompt runs in specific workflow states:

- `'1_completed'`: Framework selected, questions available
- `'2_completed'`: Fully completed with optimised prompt

This helper:
1. Calls the `/test/create-prompt-run` endpoint with the desired state
2. Navigates to the prompt run's show page
3. Returns the prompt run ID for assertions

## Known Limitations

1. **Programmatic tab switch detection**: The current tests verify that manual switches generate events, but cannot easily verify that programmatic switches do NOT generate events (because the distinction is internal Vue state)

2. **Real-time analytics flushing**: Tests query events after a short timeout (500-1000ms), assuming the analytics service has flushed events to the backend. In production, events are batched for up to 5 seconds.

3. **E2E vs Unit Tests**: These are E2E tests that verify the full stack (Vue component → analytics service → backend API → database). For testing edge cases in the analytics service itself, consider unit tests.

## Troubleshooting

### Test Fails: "Expected events.length to be greater than 0"

**Possible causes:**
1. Analytics service hasn't flushed events yet → Increase `waitForTimeout` before querying
2. Analytics events are being blocked by consent → Check that test fixtures accept cookies
3. Wrong database being queried → Verify `X-Test-Auth` header is being sent

### Test Fails: "Event properties don't match"

**Possible causes:**
1. Vue component not tracking analytics → Check `analyticsService.track()` calls in component
2. Properties changed in implementation → Update test assertions to match new schema
3. Test using wrong prompt run state → Verify the workflow stage matches expectations

### Test is Flaky

**Possible causes:**
1. Race condition between event tracking and database query → Add longer timeout
2. Page not fully loaded → Add `waitForLoadState('networkidle')` or similar
3. Event batching timing → Flush analytics manually in test if needed

## Related Files

- **Test file**: `/tests-frontend/e2e/prompt-builder-analytics.e2e.ts`
- **Component**: `/resources/js/Pages/PromptBuilder/Show.vue`
- **ClarifyingQuestions**: `/resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/ClarifyingQuestions.vue`
- **BulkQuestions**: `/resources/js/Components/Features/PromptBuilder/ClarifyingQuestions/BulkQuestions.vue`
- **Analytics service**: `/resources/js/services/analytics.ts`
- **Backend controller**: `/app/Http/Controllers/TestBroadcastController.php`
- **Routes**: `/routes/web.php` (test endpoints)
- **Migration**: `/database/migrations/2025_03_01_000010_create_analytics_events_table.php`

## Future Enhancements

1. **Test programmatic tab switches more thoroughly**: Mock the workflow completion and verify NO events are generated
2. **Test analytics batching**: Verify events are sent in batches of 10 or after 5 seconds
3. **Test consent gating**: Verify events are queued when consent is denied and flushed when granted
4. **Test session tracking**: Verify `session_id` is correctly included in events
5. **Test device type detection**: Verify `device_type` is correctly set based on user agent
6. **Test error scenarios**: What happens if the analytics endpoint is down?

## Summary

These E2E tests provide comprehensive coverage of the analytics tracking implementation in the PromptBuilder flow. They verify:

- Manual tab navigation generates `tab_viewed` events with correct properties
- Question answering in both modes generates `question_answered` events
- Auto-save on blur works correctly and tracks analytics
- Edit mode properly disables auto-save and analytics tracking
- Events are persisted to the database with all required properties

The tests follow best practices for E2E testing:
- Use fixtures for authenticated pages and test data
- Query the database to verify backend state
- Wait appropriately for async operations
- Test both happy paths and edge cases
- Include clear descriptions and comments
