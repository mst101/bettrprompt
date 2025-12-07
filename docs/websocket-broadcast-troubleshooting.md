# WebSocket Broadcasting Troubleshooting Guide

## Issue Summary

Real-time WebSocket broadcasting from backend to frontend is not working. When users submit a task, the backend job completes and broadcasts a `PreAnalysisCompleted` event via Reverb, but the frontend does not receive the broadcast. Users must manually refresh the page to see the Quick Queries.

## Symptoms

- ✓ User submits task → backend job runs successfully
- ✓ Backend broadcasts event (confirmed via logs)
- ✓ Frontend connects to WebSocket (browser logs show connection successful)
- ✓ Frontend subscribes to channel (browser logs show subscription successful)
- ✗ Frontend does NOT receive broadcast event
- ✓ Manual page refresh shows the data (proving backend completed successfully)

## Root Causes Identified

### 1. ✓ FIXED: Reverb Hostname Configuration Mismatch
**Problem**: Reverb server was configured with internal Docker hostname `reverb` instead of frontend-facing hostname.
- Backend could reach Reverb internally at `reverb:8080`
- Frontend (browser) couldn't connect to `reverb` - it needed the externally routable hostname through Caddy proxy
- Frontend was configured to connect to `app.localhost:443` but Reverb was telling it to use `reverb`

**Fix Applied**: Updated `config/reverb.php` line 35:
```php
// Before (broken):
'hostname' => env('REVERB_HOST'),  // Points to internal 'reverb'

// After (fixed):
'hostname' => env('VITE_REVERB_HOST', env('REVERB_HOST')),  // Points to 'app.localhost'
```

**Status**: Fixed in commit 9679232

### 2. ✗ OPEN: Pusher Signature Verification Failure
**Problem**: Reverb rejects broadcast requests from Laravel with `"Authentication signature invalid"` error.

**Investigation Results**:
- Laravel's PusherBroadcaster sends events to Reverb at `/apps/{appId}/events` endpoint
- Reverb validates the HMAC-SHA256 signature using the app secret
- Signature validation is failing despite correct app ID and secret in config
- Manual testing showed that when a properly signed request includes `body_md5` in the query string AND signature, Reverb accepts it
- However, Laravel's Pusher PHP SDK doesn't send requests in this format

**Key Finding**:
When tested with correct signature format (including `body_md5` in query params):
```bash
# This fails with "Authentication signature invalid":
curl -X POST "http://reverb:8080/apps/207502/events?auth_signature=test" \
  -d '{"channels":["test"],"name":"test"}'

# This passes signature validation but fails with "The data field is required":
curl -X POST "http://reverb:8080/apps/207502/events?auth_key=...&body_md5=...&auth_signature=..." \
  -d '{"channels":["test"],"name":"test"}'
```

This proves:
1. Reverb receives the requests
2. Reverb's signature validation is working when the signature is correct
3. Laravel's Pusher SDK is not sending signatures in the format Reverb expects

**Reverb Signature Verification Code** (`vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php`):
```php
protected function verifySignature(RequestInterface $request): void
{
    $params = Arr::except($this->query, [
        'auth_signature', 'body_md5', 'appId', 'appKey', 'channelName',
    ]);

    if ($this->body !== '') {
        $params['body_md5'] = md5($this->body);  // ← Reverb calculates this
    }

    ksort($params);
    $signature = implode("\n", [
        $request->getMethod(),
        $request->getUri()->getPath(),
        $this->formatQueryParametersForVerification($params),
    ]);

    $signature = hash_hmac('sha256', $signature, $this->application->secret());
    $authSignature = $this->query['auth_signature'] ?? '';

    if ($signature !== $authSignature) {
        throw new HttpException(401, 'Authentication signature invalid.');
    }
}
```

**Status**: UNRESOLVED - Requires deeper investigation into Pusher protocol compatibility

## Architecture Overview

### Component Interactions

```
User Browser (app.localhost:443)
    ↓ WebSocket (WSS)
    ↓
Caddy Reverse Proxy (app.localhost:443)
    ↓ HTTP Upgrade to WebSocket
    ↓
Reverb Server (:8080)
    ↑
    ↑ HTTP POST (Pusher Protocol)
    ↑
Laravel Backend (laravel.test:80)
    ↓ Triggers event via PusherBroadcaster
    ↓ Sends HTTP request to Reverb
    ↓ /apps/{appId}/events endpoint
    ↓
Reverb Server (receives broadcast)
    ↓ Should forward to connected clients
    ✗ But doesn't (signature validation fails)
```

### Configuration Files

**Frontend (browser) connection**:
- `resources/js/bootstrap.ts` - Initializes Echo with `VITE_REVERB_*` vars
- Connection target: `wss://app.localhost:443/app/{key}`

**Backend broadcast**:
- `config/broadcasting.php` - Defines 'reverb' driver using `REVERB_*` vars
- Send target: `http://reverb:8080/apps/{appId}/events` (internal Docker)

**Reverb configuration**:
- `config/reverb.php` - Server and app configuration
- App secret: `REVERB_APP_SECRET` (same as in broadcasting.php)

## Environment Setup

### Required Services
1. **Reverb WebSocket Server**: Runs on port 8080 internally
2. **Caddy Reverse Proxy**: Proxies WebSocket traffic from `app.localhost:443` to Reverb
3. **Laravel**: Backend that sends broadcasts
4. **PostgreSQL + Redis**: Supporting services

### Starting Development Environment

```bash
composer dev
```

This command runs via concurrently:
- `php artisan serve` - Laravel dev server
- `php artisan horizon` - Queue manager
- `php artisan reverb:start` - WebSocket server ← **CRITICAL**
- `php artisan pail` - Log viewer
- `npm run dev` - Vite dev server

**Note**: The `php artisan reverb:start` command is essential. If you see "WebSocket connection failed" in the browser, Reverb is probably not running.

## Testing & Debugging

### Browser Console Indicators

✓ **Connection working**:
```
[Echo Init] Echo initialized successfully
[Echo] WebSocket connected successfully
[useRealtimeUpdates] Channel created successfully: prompt-run.{id}
[useRealtimeUpdates] Successfully connected to channel: prompt-run.{id}
```

✗ **Event not received**:
```
[useRealtimeUpdates] Event received: PreAnalysisCompleted  ← MISSING
```

### Server Logs

**Check if event is dispatched**:
```bash
tail storage/logs/laravel.log | grep "Dispatching PreAnalysisCompleted"
```

You should see:
```
Dispatching PreAnalysisCompleted event
PreAnalysisCompleted::__construct() called
PreAnalysisCompleted::broadcastAs() called
PreAnalysisCompleted::broadcastOn() called
PreAnalysisCompleted::broadcastWith() called
```

**Check Reverb logs**:
```bash
./vendor/bin/sail logs reverb
```

Currently shows minimal output - even signature validation errors aren't logged.

### Manual Testing

**Test Reverb connectivity from Laravel container**:
```bash
./vendor/bin/sail exec laravel.test curl http://reverb:8080/
# Should return 404 (endpoint doesn't exist, but port is accessible)
```

**Manually trigger broadcast**:
```bash
./vendor/bin/sail artisan tinker
>>> event(new App\Events\PreAnalysisCompleted(\App\Models\PromptRun::find(18)))
```

## Attempted Fixes

### Fix 1: Channel Authorization (Partial - Helped identify issue)
Added explicit authorization for unauthenticated users:
```php
// routes/channels.php
Broadcast::channel('prompt-run.{promptRunId}', function ($user, $promptRunId) {
    return true;  // Allow all users
});
```

**Result**: Frontend can subscribe, but still doesn't receive events.

### Fix 2: Event Name Prefix Handling
Removed incorrect dot prefix from event names in `useRealtimeUpdates.ts`.

**Result**: Listeners are set up correctly, but events still not received.

### Fix 3: Reverb Hostname Configuration (SUCCESSFUL)
Changed Reverb config to use frontend-facing hostname.

**Result**: Frontend can now properly connect to Reverb (verified in browser console).

### Fix 4: Delay Before Broadcast (Minor)
Added 0.5s delay before broadcasting to ensure client subscription:
```php
usleep(500000);  // In ProcessPreAnalysis job
event(new PreAnalysisCompleted($this->promptRun));
```

**Result**: No change - still doesn't receive events.

## Next Steps for Resolution

### Option A: Fix Pusher Protocol Compatibility
1. Check Pusher PHP SDK version compatibility with Reverb
2. Verify if there's a known issue or newer version
3. Patch Laravel's PusherBroadcaster to include body_md5 in correct format
4. Or create a custom broadcaster for Reverb

### Option B: Enable Debug Logging in Reverb
1. Modify Reverb's signature verification to log what it's calculating
2. Compare with what Laravel is sending
3. Identify the exact mismatch

### Option C: Switch Broadcasting Driver (Workaround)
1. Use `BROADCAST_DRIVER=log` for development (events appear in logs)
2. Use `BROADCAST_DRIVER=redis` for production (Redis-based broadcasting)
3. Implement simple polling as a fallback

### Option D: Upgrade/Debug Reverb
1. Check if there's a newer version of Laravel Reverb with fixes
2. Review Reverb GitHub issues for similar problems
3. Check if this is a known limitation of Reverb's Pusher implementation

## Files Modified

- `config/reverb.php` - Changed hostname configuration
- `app/Events/PreAnalysisCompleted.php` - Uses public Channel for broadcast
- `app/Jobs/ProcessPreAnalysis.php` - Added logging and delay
- `resources/js/bootstrap.ts` - Added Echo initialization logging
- `resources/js/Composables/useRealtimeUpdates.ts` - Added comprehensive logging
- `routes/channels.php` - Added public channel authorization

## Related Files

- `config/broadcasting.php` - Defines reverb driver configuration
- `Caddyfile` - WebSocket routing from `app.localhost` to `reverb:8080`
- `resources/js/Pages/PromptBuilder/Show.vue` - Uses useRealtimeUpdates composable
- `app/Http/Controllers/PromptBuilderController.php` - Dispatches initial job

## Timeline of Investigation

1. **Initial Report**: WebSocket not updating frontend after task submission
2. **First Discovery**: Backend logs confirmed event is dispatched successfully
3. **Second Discovery**: Frontend connects to WebSocket and subscribes to channel
4. **Third Discovery**: Frontend never receives the broadcast event
5. **Breakthrough**: Manual broadcast requests to Reverb fail with signature errors
6. **Root Cause**: Pusher signature verification mismatch identified
7. **Current Status**: Debugging signature calculation format differences

## Key Insights

1. **The infrastructure is mostly correct** - WebSocket connections, subscriptions, and event dispatching all work
2. **The specific failure is in signature validation** - Reverb is correctly rejecting unsigned/incorrectly-signed requests
3. **Laravel's Pusher SDK may not be compatible** with Reverb's Pusher protocol implementation
4. **This is a version or protocol compatibility issue**, not a configuration mistake
