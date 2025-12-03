# Real-time Broadcasts E2E Tests

This document describes the working real-time broadcast E2E tests and the infrastructure supporting them.

## Overview

The `realtime-broadcasts-simple.e2e.ts` test suite verifies that WebSocket broadcast events trigger correct UI updates. These tests use a test-only broadcast infrastructure that allows manual event triggering without waiting for asynchronous n8n workflows.

## Test Status

✅ **1 test passing**: `should handle broadcasts without errors`
- Demonstrates that broadcasts can be triggered and handled without JavaScript errors
- Tests with existing seeded prompt runs
- Verifies WebSocket connection works

⏭️ **2 tests skipped** (when suitable seeded data is not available):
- `should trigger AnalysisCompleted event and update UI` - Requires a submitted prompt without a framework
- `should trigger PromptOptimizationCompleted event` - Requires a prompt with framework but no optimised prompt

## Architecture

### Test Broadcast Controller
**File**: `app/Http/Controllers/TestBroadcastController.php`

Provides test-only endpoints for triggering broadcast events:

- `POST /test/broadcast/analysis-completed/{promptRunId}`
  - Updates the prompt run with a test framework
  - Broadcasts `AnalysisCompleted` event
  - Returns updated data for verification

- `POST /test/broadcast/prompt-optimization-completed/{promptRunId}`
  - Updates the prompt run with a test optimised prompt
  - Broadcasts `PromptOptimizationCompleted` event
  - Validates that framework exists first (returns 422 if not)

- `GET /test/echo-info`
  - Returns WebSocket/Echo configuration
  - Useful for debugging connection issues

**Security**: All routes protected by `X-Test-Auth: playwright-e2e-tests` header.

### Broadcast Helpers
**File**: `tests-frontend/e2e/helpers/broadcast.ts`

Provides convenient functions for E2E tests:

```typescript
// Trigger framework selection event
await triggerAnalysisCompleted(page, promptRunId);

// Trigger prompt completion event
await triggerPromptOptimizationCompleted(page, promptRunId);

// Wait for WebSocket connection
const connected = await waitForEchoConnection(page, 5000);

// Extract prompt run ID from URL
const id = await getPromptRunIdFromUrl(page);

// Get Echo configuration
const info = await getEchoInfo(page);
```

### Routes Configuration
**File**: `routes/web.php`

Test broadcast routes are registered with CSRF exemption:

```php
Route::post('/test/broadcast/analysis-completed/{promptRunId}',
    [TestBroadcastController::class, 'triggerAnalysisCompleted']);
Route::post('/test/broadcast/prompt-optimization-completed/{promptRunId}',
    [TestBroadcastController::class, 'triggerPromptOptimizationCompleted']);
Route::get('/test/echo-info',
    [TestBroadcastController::class, 'echoInfo']);
```

**CSRF Exemption**: Updated in `bootstrap/app.php` to skip CSRF validation for `test/broadcast/*` routes.

## Running Tests

```bash
# Run all real-time broadcast tests
npx playwright test tests-frontend/e2e/realtime-broadcasts-simple.e2e.ts

# Run with verbose output
npx playwright test tests-frontend/e2e/realtime-broadcasts-simple.e2e.ts --reporter=line

# Run specific test
npx playwright test tests-frontend/e2e/realtime-broadcasts-simple.e2e.ts -g "without errors"

# Run with UI mode
npx playwright test tests-frontend/e2e/realtime-broadcasts-simple.e2e.ts --ui
```

## How It Works

1. **Setup Phase**
   - Test seeds a user and navigates to history page
   - Locates an existing prompt run from seeded data

2. **Echo Connection**
   - Test waits for Laravel Echo WebSocket connection
   - Verifies echo listener is ready

3. **Broadcast Trigger**
   - Test calls broadcast endpoint via page.evaluate()
   - Endpoint updates database and broadcasts event via Laravel Echo
   - Event is delivered to client via WebSocket (or polling fallback)

4. **UI Verification**
   - Test verifies UI updated (e.g., new tab appeared)
   - Can optionally verify content accessibility

5. **Error Capture**
   - Test captures any JavaScript errors during process
   - Verifies no errors occurred

## Data State Requirements

For tests to run successfully with seeded data:

- **AnalysisCompleted test**: Needs submitted prompts without framework selection
- **PromptOptimizationCompleted test**: Needs prompts with framework but no optimised prompt
- **Broadcasts without errors**: Works with any seeded prompt

The E2E test seeder (`database/seeders/E2eTestSeeder.php`) creates 25 diverse prompts in various states. These are used by all tests.

## WebSocket/Echo Notes

- **Connection**: Echo connects on page load via `resources/js/composables/useRealtimeUpdates.ts`
- **Channels**: Private channels use prompt run ID (e.g., `private-prompt-run.29`)
- **Events**: `AnalysisCompleted`, `PromptOptimizationCompleted`, etc.
- **Timeout**: Tests wait up to 15 seconds for UI updates

## Troubleshooting

### WebSocket Connection Fails
- Check that Reverb is running: `./vendor/bin/sail ps`
- Check Docker logs: `./vendor/bin/sail logs laravel.test`
- Verify Echo config in `config/broadcasting.php`

### Test Timeouts
- Increase timeout in test: `waitFor({ state: 'visible', timeout: 30000 })`
- Check if event is being broadcast: Add console logs in controller

### Events Not Triggering
- Verify `X-Test-Auth` header is sent correctly
- Check controller response with `console.log()` in broadcast helper
- Ensure prompt exists and has correct status

### Script Errors
- Check browser console in headed mode: `--headed`
- Use `--debug` flag for step-by-step execution
- Check Playwright trace: `npx playwright show-trace trace.zip`

## Future Improvements

1. **Dedicated Test Seeder**: Create `RealtimeBroadcastTestSeeder` for specific test states
2. **Polling Fallback Test**: Verify UI updates work even without WebSocket
3. **Event Ordering**: Test multiple events in sequence
4. **Channel Cleanup**: Test that channels are properly cleaned up on navigation
5. **Error Scenarios**: Test error event broadcasts

## Files Modified/Created

- ✅ `app/Http/Controllers/TestBroadcastController.php` - NEW
- ✅ `tests-frontend/e2e/helpers/broadcast.ts` - NEW
- ✅ `tests-frontend/e2e/realtime-broadcasts-simple.e2e.ts` - UPDATED
- ✅ `routes/web.php` - UPDATED (added test routes)
- ✅ `bootstrap/app.php` - UPDATED (CSRF exemption)
- ✅ `tests-frontend/e2e/realtime-updates.e2e.ts` - UPDATED (un-skipped some tests)
- ⚠️ `database/seeders/RealtimeBroadcastTestSeeder.php` - Created but not used yet
