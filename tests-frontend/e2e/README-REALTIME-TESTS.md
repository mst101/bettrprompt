# Real-time Updates Testing Guide

## Overview

The file `realtime-updates.e2e.ts` contains end-to-end tests for the real-time update functionality using Laravel Echo and WebSockets (Reverb).

## What Real-time Updates Do

The application uses WebSockets to push updates to the browser when background processes complete:

1. **FrameworkSelected Event**: When n8n finishes selecting a framework, the browser receives an event and displays the Framework tab automatically
2. **PromptOptimizationCompleted Event**: When the optimised prompt is ready, it appears without requiring a manual page refresh

## Testing Challenges with WebSockets in Playwright

Testing real-time WebSocket functionality in Playwright has several limitations:

### **Challenge 1: Real WebSocket Server Required**
- Playwright runs in a real browser environment that expects actual WebSocket connections
- You cannot easily mock WebSocket behaviour like you can with HTTP requests
- The Reverb WebSocket server must be running and accessible

### **Challenge 2: Triggering Events**
- Tests need a way to trigger server-side events (FrameworkSelected, PromptOptimizationCompleted)
- Options include:
  - Running actual n8n workflows (slow, requires full integration)
  - Creating API endpoints to manually dispatch events (requires backend changes)
  - Using Laravel's broadcasting system in tests (requires test infrastructure)

### **Challenge 3: Timing and Async Behaviour**
- WebSocket events are asynchronous and may arrive at unpredictable times
- Tests must handle scenarios where events arrive during test execution
- Race conditions between test assertions and real-time updates

### **Challenge 4: Environment Differences**
- Local development: WebSocket server may not be running
- CI/CD: WebSocket infrastructure may be unavailable
- Tests must be resilient to missing WebSocket connectivity

## Our Testing Approach

Given these challenges, our tests focus on what **can** be reliably tested in Playwright:

### ✅ **What We Test**

1. **Echo Initialisation**
   - Verify `window.Echo` is initialised on page load
   - Check that connection state helpers exist (`isEchoConnected`, `getEchoConnectionState`)
   - Verify Echo reports a valid connection state

2. **Channel Subscription Logic**
   - Verify the `useRealtimeUpdates` composable is invoked
   - Check that channels are subscribed to (via console logs)
   - Confirm channel names are correct (`prompt-run.{id}`)

3. **UI State Updates**
   - Verify UI updates when data changes (regardless of update mechanism)
   - Test that framework tab appears when framework is selected
   - Test that optimised prompt displays when completed
   - Check loading states during processing

4. **Fallback Behaviour**
   - Verify polling fallback activates when WebSockets unavailable
   - Test that manual page refresh works as fallback
   - Ensure application remains functional without WebSockets

5. **Channel Cleanup**
   - Verify channels are unsubscribed on navigation
   - Check for memory leaks after multiple navigations
   - Test event listener cleanup

6. **Multi-tab Support**
   - Verify multiple tabs can view the same prompt run
   - Check initial state synchronisation (both show same data)
   - Note: Real-time synchronisation across tabs requires event triggering

### ❌ **What We Don't Test**

1. **Actual WebSocket Event Transmission**
   - Cannot reliably trigger server-side events in Playwright alone
   - Cannot verify events propagate from server to client in isolation
   - Requires integration with backend event dispatching

2. **Real-time Synchronisation Between Tabs**
   - Can verify both tabs show same initial state
   - Cannot easily trigger events and verify both tabs update simultaneously
   - Requires backend coordination to dispatch events during test

3. **WebSocket Connection Recovery**
   - Cannot reliably simulate network failures and recovery
   - Cannot test reconnection logic under various failure scenarios
   - Would require network manipulation tools

## Running the Tests

### Prerequisites

For basic tests (Echo setup, UI state, fallback):
```bash
# Start Laravel Sail (includes app and database)
./vendor/bin/sail up -d

# Run development server
composer dev
```

For full integration tests (including event triggering):
```bash
# Additionally start Reverb WebSocket server
php artisan reverb:start

# In another terminal, start n8n (via Sail)
# n8n should already be running if you used `sail up -d`
```

### Run Tests

```bash
# Run all real-time update tests
npx playwright test realtime-updates.e2e.ts

# Run with browser visible (headed mode)
npx playwright test realtime-updates.e2e.ts --headed

# Run specific test
npx playwright test realtime-updates.e2e.ts -g "should initialise Laravel Echo"

# Debug mode
npx playwright test realtime-updates.e2e.ts --debug
```

## Interpreting Test Results

### Expected Behaviour

**When WebSocket Server is Running:**
- Echo connection state: `connected`
- Console logs show: "WebSocket connected"
- Channel subscriptions succeed
- Real-time updates may work (if n8n is integrated)

**When WebSocket Server is Not Running:**
- Echo connection state: `failed` or `disconnected`
- Console logs show: "polling fallback"
- Tests should still pass (fallback behaviour)
- UI updates work via polling or manual refresh

### Test Philosophy

These tests verify that:
1. The client-side real-time infrastructure is correctly set up
2. The application handles WebSocket availability gracefully
3. The UI updates correctly regardless of update mechanism
4. Users can always see their data (even without real-time updates)

They **do not** verify that WebSocket events are transmitted from server to client (this requires integration testing).

## Full Integration Testing

For complete real-time update testing, you need:

### **1. Backend Event Triggering**

Create a test helper to dispatch events:

```php
// tests/Helpers/BroadcastHelper.php
use App\Events\FrameworkSelected;
use App\Events\PromptOptimizationCompleted;

class BroadcastHelper
{
    public static function triggerFrameworkSelected(PromptRun $promptRun): void
    {
        event(new FrameworkSelected($promptRun));
    }

    public static function triggerOptimizationCompleted(PromptRun $promptRun): void
    {
        event(new PromptOptimizationCompleted($promptRun));
    }
}
```

### **2. API Endpoints for Test Events**

Create test-only routes to trigger events:

```php
// routes/web.php (only in testing environment)
if (app()->environment('testing')) {
    Route::post('/test/trigger-event/{promptRun}/{event}', function (PromptRun $promptRun, string $event) {
        match ($event) {
            'framework-selected' => event(new FrameworkSelected($promptRun)),
            'optimization-completed' => event(new PromptOptimizationCompleted($promptRun)),
            default => abort(404),
        };

        return response()->json(['triggered' => true]);
    });
}
```

### **3. Enhanced Playwright Tests**

```typescript
test('should update UI when FrameworkSelected event is triggered', async ({ page }) => {
    // Create prompt run
    const { id } = await createPromptRun(page);

    // Navigate to show page
    await page.goto(`/prompt-optimizer/${id}`);

    // Framework tab should not exist yet
    const frameworkTab = page.getByRole('button', { name: /framework/i });
    await expect(frameworkTab).not.toBeVisible();

    // Trigger event via API
    await page.request.post(`/test/trigger-event/${id}/framework-selected`);

    // Wait for real-time update
    await expect(frameworkTab).toBeVisible({ timeout: 5000 });
});
```

### **4. Manual Testing Checklist**

- [ ] Submit a prompt and leave the page open
- [ ] Watch the browser console for "WebSocket connected"
- [ ] Wait for framework selection (should appear without refresh)
- [ ] Verify framework tab appears automatically
- [ ] Open the same prompt in a second browser tab
- [ ] Both tabs should show framework tab when it updates
- [ ] Disable network and verify polling fallback starts
- [ ] Re-enable network and verify reconnection
- [ ] Complete all questions and watch for optimised prompt (no refresh needed)

## Troubleshooting

### WebSocket Connection Fails

**Symptom:** Echo state is `failed` or `disconnected`

**Causes:**
- Reverb server not running (`php artisan reverb:start`)
- Port conflicts (check port 80/443 availability)
- Firewall blocking WebSocket connections
- SSL/TLS configuration issues

**Solutions:**
```bash
# Check Reverb is running
ps aux | grep reverb

# Check ports
netstat -tulpn | grep :80
netstat -tulpn | grep :443

# Restart Reverb
php artisan reverb:restart

# Check Laravel logs
tail -f storage/logs/laravel.log
```

### Polling Fallback Always Active

**Symptom:** Console shows "polling fallback" even when Echo appears connected

**Causes:**
- Echo connects after composable initialises
- Channel subscription fails
- Event listeners not set up correctly

**Debug:**
```javascript
// In browser console
console.log('Echo:', window.Echo);
console.log('Connected?', window.isEchoConnected());
console.log('State:', window.getEchoConnectionState());

// Check subscribed channels
window.Echo?.connector.channels
```

### Events Not Received

**Symptom:** WebSocket connected but events don't trigger UI updates

**Causes:**
- Event not dispatched from backend
- Channel name mismatch
- Event class namespace incorrect
- Reverb configuration issue

**Debug:**
1. Check backend dispatches event:
   ```php
   Log::info('Dispatching FrameworkSelected', ['prompt_run_id' => $promptRun->id]);
   event(new FrameworkSelected($promptRun));
   ```

2. Check channel name matches:
   ```typescript
   // Frontend: prompt-run.{id}
   // Backend event: shouldBroadcastOn() returns "prompt-run.{$this->promptRun->id}"
   ```

3. Monitor Reverb logs for event transmission

## Future Improvements

1. **Mock WebSocket Server**: Use a tool like `mock-socket` or `ws` to create a mock WebSocket server for deterministic testing

2. **Backend Test Helpers**: Create Laravel test traits to dispatch events during Playwright tests

3. **Shared Test State**: Use Redis or database to coordinate between Playwright and Laravel for event triggering

4. **Visual Regression Testing**: Capture screenshots before/after events to verify UI updates

5. **Performance Testing**: Measure time between event dispatch and UI update

## Related Files

- **Composable**: `/home/mark/repos/personality/resources/js/Composables/useRealtimeUpdates.ts`
- **Bootstrap**: `/home/mark/repos/personality/resources/js/bootstrap.ts`
- **Show Page**: `/home/mark/repos/personality/resources/js/Pages/PromptOptimizer/Show.vue`
- **Backend Events**: (Check Laravel `app/Events/` directory)
- **Reverb Config**: `/home/mark/repos/personality/config/reverb.php`

## Summary

The real-time updates tests verify that:
- ✅ Client-side WebSocket infrastructure is correctly set up
- ✅ Fallback mechanisms work when WebSockets unavailable
- ✅ UI updates correctly when data changes
- ✅ Application remains functional in all connectivity scenarios

Full event-driven testing requires backend coordination, which is documented above for future implementation.
