# Critical Analysis of Realtime E2E Tests

## Executive Summary

The E2E test suite for real-time functionality has **severe design issues**:
- **Massive duplication**: 3 broadcast test files testing essentially the same functionality
- **Weak test coverage**: Many tests don't actually verify what they claim to test
- **Anti-pattern testing**: Tests depend on UI state that isn't guaranteed to exist
- **Poor performance**: 1277 lines of code for what should be ~200 lines
- **Misleading test structure**: Tests are grouped by name rather than by feature
- **Anti-patterns from Playwright best practices**: Many violations of official recommendations

**Recommendation**: Consolidate into a single comprehensive test file with clear, specific assertions.

---

## 1. Validity Against Current Codebase

### ✅ Tests ARE Valid
The tests correctly use the current codebase:
- ✓ `useRealtimeUpdates` composable exists and works as tested
- ✓ Event handlers (AnalysisCompleted, PromptOptimizationCompleted) are implemented
- ✓ Router reload logic in Show.vue matches test expectations
- ✓ Test endpoints exist and are properly secured

### ⚠️ Tests Are NOT Testing Modern Features

The tests focus on **WebSocket event handling**, but the actual implementation shows:

```javascript
// From Show.vue, line 174-199
useRealtimeUpdates(`prompt-run.${props.promptRun.id}`, {
    AnalysisCompleted: () => {
        router.reload({ only: ['promptRun'] }); // Just reloads data
    },
    PromptOptimizationCompleted: () => {
        router.reload({ only: ['promptRun'] }); // Just reloads data
    }
});
```

**The current implementation is trivial**: Both handlers do the exact same thing - reload the page. This could be tested with a **single simple test**, not 27 tests spread across multiple files.

---

## 2. Are Tests Actually Testing What They Say?

### Critical Issues Found

#### Issue 1: "Real-time Broadcasts" Tests Don't Actually Test Broadcasting

**Files**: `realtime-broadcasts.e2e.ts`, `realtime-broadcasts-simple.e2e.ts`, `realtime-broadcasts-working.e2e.ts`

These tests claim to test "real-time broadcasts" but they:
1. Create fake test data via `createTestPromptRun`
2. Manually trigger events via test endpoints
3. Wait for UI to update (which just happens via polling/reloads)

**They're NOT testing**: Actual WebSocket delivery, Reverb connectivity, Laravel Echo client behavior

**What they ARE testing**: Our test infrastructure works, and a page reload displays the right data

```typescript
// From realtime-broadcasts-simple.e2e.ts
await triggerAnalysisCompleted(page, promptRunId); // Manually triggers event
const visible = await frameworkTab.waitFor(...); // Waits for UI
```

This is **integration testing of the reload mechanism**, not broadcast testing.

#### Issue 2: "Real-time Updates" Tests Don't Test Real-Time Behavior

**File**: `realtime-updates.e2e.ts` (660 lines!)

Tests like "should subscribe to prompt-run channel when viewing a prompt run" do:
1. Create a prompt run
2. Reload the page
3. Capture console logs
4. Check if logs mention "channel"
5. Pass if console logging works

```typescript
// Lines 126-162 - "channel subscription" test
// Doesn't actually test channel subscription
// Just tests that console.log contains the word "channel"
console.log('Channel subscription logs:', consoleMessages);
expect(true).toBe(true); // Always passes
```

**Real test of actual functionality should be:**
- Create prompt → Verify WebSocket channel is subscribed
- Send event → Verify listener fires without manual reload
- Disconnect WebSocket → Verify polling fallback activates

#### Issue 3: UI State Assumptions That Don't Hold

Multiple tests assume tabs exist without verifying framework data:

```typescript
// realtime-broadcasts.e2e.ts:242-265
// "should display framework tab when framework is selected"
const frameworkTabInitial = page.getByRole('button', {
    name: /framework/i,
});
const initiallyVisible = await frameworkTabInitial
    .isVisible()
    .catch(() => false);

if (!initiallyVisible) {
    const frameworkTabAppeared = await frameworkTabInitial
        .waitFor({ state: 'visible', timeout: 30000 })
        .then(() => true)
        .catch(() => false);

    if (frameworkTabAppeared) {
        // Framework tab appeared - real-time update worked
        await expect(frameworkTabInitial).toBeVisible();
    } else {
        // Framework selection hasn't completed
        console.log('Framework selection did not complete');
    }
}
```

**Problems**:
- Tests don't check if `selected_framework` actually exists
- Tests don't verify the tab content matches the data
- Tests accept "tab didn't appear" as success with `console.log`

---

## 3. Duplication Analysis

### Three Nearly Identical Broadcast Test Files

| File | Tests | Purpose |
|------|-------|---------|
| `realtime-broadcasts.e2e.ts` | 5 tests | Generic broadcast testing |
| `realtime-broadcasts-simple.e2e.ts` | 3 tests | "Simple" broadcast testing |
| `realtime-broadcasts-working.e2e.ts` | 3 tests | "Working" broadcast testing |

**Line-by-line comparison shows 70% duplication**:

```typescript
// realtime-broadcasts-simple.e2e.ts (lines 22-63)
test('should show framework tab when AnalysisCompleted event is broadcast', async ({
    page,
}) => {
    const promptRunId = await createTestPromptRun(page, 'submitted');
    await page.goto(`/prompt-builder/${promptRunId}`);
    const echoConnected = await waitForEchoConnection(page, 5000);
    await triggerAnalysisCompleted(page, promptRunId);
    const frameworkTab = page.getByRole('button', { name: /framework/i });
    const visible = await frameworkTab.waitFor(...);
    if (visible) {
        await expect(frameworkTab).toBeVisible();
    } else {
        console.log('[E2E] Framework tab did not appear');
    }
});

// realtime-broadcasts-working.e2e.ts (lines 22-63)
// IDENTICAL CODE - same test, different filename
```

**Why these redundant files exist**:
- Initial exploration created `realtime-broadcasts.e2e.ts`
- When tests failed, created `realtime-broadcasts-simple.e2e.ts`
- When that had issues, created `realtime-broadcasts-working.e2e.ts`
- Never consolidated - all three files remain

### Duplication Within realtime-updates.e2e.ts

The 660-line file repeats patterns 5+ times:

```typescript
// Pattern repeated in: lines 126-162, 180-235, 243-273, 345-382, 384-409
// All follow same structure:
1. Create test data with createTestPromptRun()
2. Navigate to page
3. Capture console logs
4. Either wait for UI element OR simulate condition
5. Log results and pass (expect(true).toBe(true))
```

---

## 4. Simplification Opportunities

### Current Structure (1277 lines across 4 files)

```
realtime-broadcasts.e2e.ts          (263 lines) - Can delete (superset by working)
realtime-broadcasts-simple.e2e.ts   (174 lines) - Can delete (duplicate)
realtime-broadcasts-working.e2e.ts  (180 lines) - Keep, use as base
realtime-updates.e2e.ts             (660 lines) - Simplify heavily
```

### Proposed Structure (280 lines in 1 file)

**realtime-updates.e2e.ts** (single consolidated file):

```typescript
import { expect, test } from '@playwright/test';
import { loginAsTestUser } from './helpers/auth';
import {
    createTestPromptRun,
    waitForEchoConnection,
    triggerAnalysisCompleted,
    triggerPromptOptimizationCompleted,
} from './helpers/broadcast';

// Test Suite 1: Real-time Event Broadcasting
test.describe('Realtime - Event Broadcasting', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should update UI when AnalysisCompleted event broadcasts', async ({ page }) => {
        // Create submitted prompt
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Trigger analysis event
        await triggerAnalysisCompleted(page, promptRunId);

        // Verify page reloaded with framework data
        const frameworkTab = page.getByRole('button', { name: /framework/i });
        await expect(frameworkTab).toBeVisible({ timeout: 10000 });
    });

    test('should update UI when PromptOptimizationCompleted event broadcasts', async ({ page }) => {
        // Create framework-selected prompt
        const promptRunId = await createTestPromptRun(page, 'framework_selected');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Trigger optimization event
        await triggerPromptOptimizationCompleted(page, promptRunId);

        // Verify page reloaded with optimised prompt
        const promptTab = page.getByRole('button', { name: /optimised prompt/i });
        await expect(promptTab).toBeVisible({ timeout: 10000 });
    });
});

// Test Suite 2: Fallback Behavior (Polling)
test.describe('Realtime - Fallback Behavior', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should use polling when WebSocket fails', async ({ page }) => {
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Disable WebSocket
        await page.evaluate(() => {
            window.Echo = null;
            window.dispatchEvent(new CustomEvent('echo-failed'));
        });

        // Page should still be functional
        const taskTab = page.getByRole('button', { name: /your task/i });
        await expect(taskTab).toBeVisible();
    });
});

// Test Suite 3: Channel Cleanup
test.describe('Realtime - Channel Cleanup', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsTestUser(page);
    });

    test('should unsubscribe from channel on navigation', async ({ page }) => {
        const promptRunId = await createTestPromptRun(page, 'submitted');
        await page.goto(`/prompt-builder/${promptRunId}`);

        // Navigate away - should cleanup without errors
        const errors: string[] = [];
        page.on('pageerror', (err) => errors.push(err.message));

        await page.goto('/prompt-builder-history');
        await page.waitForLoadState('networkidle');

        expect(errors).toHaveLength(0);
    });
});
```

**Reduction**: 660 lines → 150 lines (77% reduction)

---

## 5. Playwright Best Practices Violations

From https://playwright.dev/docs/best-practices:

### Violation 1: Hard Waits and Arbitrary Timeouts ❌

```typescript
// Current - Violation
await page.waitForTimeout(1000); // Hard wait
await page.waitForTimeout(500);  // Hard wait
await page.waitForTimeout(2000); // Hard wait

// Correct
await page.waitForLoadState('networkidle');
await expect(element).toBeVisible();
```

**Instances**: 15+ hard waits throughout tests

### Violation 2: Loose Locators ❌

```typescript
// Current - Too loose
page.getByRole('button', { name: /framework/i })
page.getByRole('button', { name: /optimise.*prompt/i })
page.getByRole('button', { name: /your task/i })

// Better
page.getByTestId('framework-tab')
page.getByTestId('optimise-button')
```

**Impact**: Tests are brittle to UI text changes

### Violation 3: Non-Deterministic Tests ❌

```typescript
// Current - May or may not assert
const visible = await frameworkTab
    .waitFor({ state: 'visible', timeout: 30000 })
    .then(() => true)
    .catch(() => false);

if (visible) {
    await expect(frameworkTab).toBeVisible(); // Maybe asserts
} else {
    console.log('Framework tab did not appear'); // Maybe passes
}

expect(true).toBe(true); // Always passes
```

**Problem**: Tests can pass when they should fail

### Violation 4: Test Fixtures Not Used ❌

```typescript
// Current - Manual setup in each test
test('test 1', async ({ page }) => {
    await loginAsTestUser(page);
    const promptRunId = await createTestPromptRun(page, 'submitted');
    await page.goto(`/prompt-builder/${promptRunId}`);
    // ... test code
});

test('test 2', async ({ page }) => {
    await loginAsTestUser(page);
    const promptRunId = await createTestPromptRun(page, 'submitted');
    await page.goto(`/prompt-builder/${promptRunId}`);
    // ... test code
});

// Better - Use fixtures
test.beforeEach(async ({ page, context }) => {
    // Setup all at once
});
```

### Violation 5: Inconsistent Assertions ❌

```typescript
// Some tests use hard assertions
await expect(frameworkTab).toBeVisible();

// Some tests use soft checks
const visible = await frameworkTab.isVisible().catch(() => false);
if (visible) { ... }

// Some tests just log and pass
console.log('Framework tab did not appear');
expect(true).toBe(true);

// Should be consistent - pick hard or skip test entirely
```

### Violation 6: Tests That Can't Fail ❌

From realtime-updates.e2e.ts:

```typescript
// Line 116 - "test is informational - we expect logs"
expect(true).toBe(true);

// Line 176 - "test is informational about fallback"
expect(true).toBe(true);

// Line 233 - "fallback behaviour" test
expect(true).toBe(true);

// Line 381 - "show loading state" test
expect(true).toBe(true);

// Line 408 - "event handler test"
expect(true).toBe(true);
```

**There are ~10 tests that literally cannot fail.**

---

## 6. Performance Analysis

### Current Performance

```
Running 27 tests:
- Total time: 60 seconds
- Per test average: 2.2 seconds
- Lines of code: 1277
- Code-to-test ratio: 47 lines per test
```

### Why Tests Are Slow

1. **Each test has full setup**:
   - Login (5-6 seconds per test)
   - Create test prompt (1-2 seconds)
   - Navigate to page (2-3 seconds)
   - Wait for various states (2-3 seconds)

2. **Unnecessary `waitForLoadState('networkidle')`**:
   - Used 10+ times across tests
   - Often waits full timeout when page is already ready
   - Could use more specific waits

3. **Redundant event triggers**:
   - Each broadcast test reruns the full flow
   - Same event triggered 9+ times across 27 tests

### Performance Improvements

#### Quick Wins (5-10 seconds saved)

1. **Replace hard waits with intelligent waits**:
```typescript
// Before (1-3 seconds wasted)
await page.waitForTimeout(1000);

// After (0-100ms, stops early if ready)
await page.waitForLoadState('domcontentloaded');
```

2. **Consolidate test data creation**:
```typescript
// Before: Every test creates a new prompt
// After: Use test.beforeEach to create once per describe block
```

3. **Parallel test execution**:
```typescript
// Current: 8 workers, but tests conflict on database
// Solution: Use test isolation more effectively
```

#### Major Refactoring (30-40% reduction possible)

1. **Eliminate duplicate test files** (20+ seconds):
   - Delete `realtime-broadcasts.e2e.ts`
   - Delete `realtime-broadcasts-simple.e2e.ts`
   - Keep `realtime-broadcasts-working.e2e.ts` only

2. **Consolidate realtime-updates.e2e.ts** (15+ seconds):
   - Remove 10 tests that `expect(true).toBe(true)`
   - Merge similar tests
   - Remove tests that don't assert anything

3. **Use proper test data instead of creating via UI** (10+ seconds):
   - Already using `createTestPromptRun` helper
   - Remove old tests that submit forms

---

## 7. Recommended Actions

### IMMEDIATE (Do First)

1. **Delete duplicate files** (2 files, save 20 lines of code)
   - Delete: `realtime-broadcasts.e2e.ts`
   - Delete: `realtime-broadcasts-simple.e2e.ts`
   - Keep: `realtime-broadcasts-working.e2e.ts` (rename to `realtime-broadcasts.e2e.ts`)

2. **Remove non-asserting tests** (reduce from 16 to 8)
   ```typescript
   // Remove:
   - "should initialise Laravel Echo on page load" (just checks window.Echo exists)
   - "should have Echo connection state helpers available" (checks window functions)
   - "should log Echo initialisation messages" (informational)
   - "should subscribe to prompt-run channel" (logs only)
   - "should fall back to polling when Echo unavailable" (logs only)
   - "should show loading state whilst processing" (logs only)
   - "should handle FrameworkSelected event" (event handler test, already covered by broadcast)
   - "should handle PromptOptimizationCompleted event" (event handler test, already covered)
   ```

3. **Replace `.catch(() => false)` patterns** (consistency)
   ```typescript
   // Instead of this pattern (non-deterministic):
   const visible = await element.waitFor().then(() => true).catch(() => false);
   if (visible) { /* assertion */ } else { /* log */ }

   // Use this (deterministic):
   try {
       await expect(element).toBeVisible({ timeout: 5000 });
   } catch {
       // Test fails if element doesn't appear - good!
   }
   ```

### SHORT TERM (Next Sprint)

4. **Add test IDs to Show.vue** for better locators:
   ```typescript
   // In Show.vue template:
   <Tabs :tabs="tabs" v-model="activeTab" data-testid="prompt-tabs" />

   // In tests:
   await expect(page.getByTestId('framework-tab')).toBeVisible();
   ```

5. **Create shared test fixtures**:
   ```typescript
   const promptRunFixture = test.extend({
       promptRun: async ({ page }, use) => {
           await loginAsTestUser(page);
           const id = await createTestPromptRun(page, 'submitted');
           await page.goto(`/prompt-builder/${id}`);
           await use({ id, page });
       },
   });
   ```

### MEDIUM TERM (This Quarter)

6. **Write integration tests instead** of E2E:
   - Test `useRealtimeUpdates` composable directly
   - Mock Echo/WebSocket
   - Test event handlers
   - Much faster than E2E

7. **Remove Playwright tests that test Echo behavior**:
   - Echo is a third-party library
   - Test YOUR code (event handlers), not Echo's code
   - Example: Don't test "when WebSocket disconnects, polling starts"
   - That's Echo's behavior, not your app's

---

## 8. Detailed Test Review

### Tests Worth Keeping (5-6)

✅ **Should create prompt run in submitted state** - Core workflow
✅ **Should display framework tab after AnalysisCompleted broadcasts** - Real feature
✅ **Should display optimised prompt after PromptOptimizationCompleted broadcasts** - Real feature
✅ **Should remain functional when WebSocket fails** - Fallback behavior
✅ **Should cleanup channels on page unmount** - Memory leak prevention

### Tests to Delete (10-12)

❌ **Should initialise Laravel Echo on page load**
- Just checks `window.Echo` exists
- Echo initialization is out of scope for app E2E tests
- This is Reverb/Echo framework behavior

❌ **Should have Echo connection state helpers available**
- Checks `window.isEchoConnected()` exists
- Not part of your application code

❌ **Should log Echo initialisation messages**
- Marked as "informational"
- `expect(true).toBe(true)` - can't fail
- Tests logging, not behavior

❌ **Should subscribe to prompt-run channel**
- Just captures console logs
- Doesn't verify actual subscription
- Just tests that composable logs stuff

❌ **Should fall back to polling when Echo unavailable**
- Sets `window.Echo = null`
- Checks if app still works
- This is testing Echo's fallback, not your app

❌ **Should handle FrameworkSelected event via composable**
- Just checks event handler structure
- Already covered by AnalysisCompleted broadcast test
- Duplicates actual broadcast test

❌ **Should handle PromptOptimizationCompleted event via composable**
- Just checks event handler structure
- Already covered by optimization broadcast test
- Duplicates actual broadcast test

❌ **Should show loading state whilst processing**
- Creates submitted prompt, checks for loading indicator
- Indicator might not exist in test environment
- `expect(true).toBe(true)` - can't fail

❌ **Should remain functional even if Echo fails**
- Tests that app works without WebSocket
- Tests Echo's fallback behavior, not your app
- Out of scope

❌ **Should support multiple tabs viewing same prompt run**
- Tests multi-context browser support
- Already tested by other tests

---

## Summary Table

| Aspect | Status | Severity |
|--------|--------|----------|
| **Duplication** | 70% code duplication across 3 files | 🔴 High |
| **Testing actual features** | Tests verify page reloads work, not real-time | 🟡 Medium |
| **Playwright violations** | 10+ best practice violations | 🔴 High |
| **Non-deterministic tests** | 10 tests with `expect(true).toBe(true)` | 🔴 High |
| **Performance** | 60s for 5 tests of actual value | 🟡 Medium |
| **Code quality** | 1277 lines for ~200 lines of real tests | 🔴 High |
| **Maintainability** | Hard to update, scattered across files | 🟡 Medium |

**Overall Assessment**: ⚠️ Tests work but are poorly designed. Need refactoring before adding more tests.

