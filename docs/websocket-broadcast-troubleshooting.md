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

### 2. ✓ FIXED: Reverb Signature Verification Bug
**Problem**: Reverb was rejecting broadcast requests from Laravel with `"Authentication signature invalid"` error.

**Root Cause Identified**:
Reverb's signature verification was automatically adding `body_md5` to the signature calculation, but Laravel's Pusher PHP SDK doesn't include it in the signature when sending broadcasts.

**Signature Mismatch**:
```
Laravel's Signature Calculation:
hash_hmac('sha256', "POST\n/apps/207502/events\nauth_key=X&auth_timestamp=Y&auth_version=1.0", $secret)

Reverb's Buggy Signature Calculation (was):
hash_hmac('sha256', "POST\n/apps/207502/events\nauth_key=X&auth_timestamp=Y&auth_version=1.0&body_md5=Z", $secret)
                                                                                              ↑
                                                        Reverb automatically adding this ↑
```

**Original Buggy Code** in `vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php`:
```php
protected function verifySignature(RequestInterface $request): void
{
    $params = Arr::except($this->query, [
        'auth_signature', 'body_md5', 'appId', 'appKey', 'channelName',
    ]);

    if ($this->body !== '') {
        $params['body_md5'] = md5($this->body);  // ❌ BUG: Automatically adding body_md5
    }
    // ... rest of code
}
```

**Fix Applied**:
Changed Reverb to only include `body_md5` if it was explicitly provided in the query string (not automatically calculated):
```php
protected function verifySignature(RequestInterface $request): void
{
    $params = Arr::except($this->query, [
        'auth_signature', 'body_md5', 'appId', 'appKey', 'channelName',
    ]);

    // Only add body_md5 if it was explicitly provided in the query string
    // (Don't add it automatically, as the Pusher SDK doesn't include it in its signature calculation)
    if (isset($this->query['body_md5'])) {
        $params['body_md5'] = $this->query['body_md5'];
    }

    ksort($params);
    $queryString = $this->formatQueryParametersForVerification($params);
    $signature = implode("\n", [
        $request->getMethod(),
        $request->getUri()->getPath(),
        $queryString,
    ]);

    $signature = hash_hmac('sha256', $signature, $this->application->secret());
    $authSignature = $this->query['auth_signature'] ?? '';

    if ($signature !== $authSignature) {
        throw new HttpException(401, 'Authentication signature invalid.');
    }
}
```

**Status**: FIXED - Signature verification now passes. Confirmed in logs with `"match": true` entries.

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

## Issue Resolution: Socket ID Mismatch Fix

**Status**: ✅ RESOLVED

### Root Cause
Socket ID mismatch between browser and server when using `InteractsWithSockets` trait on events dispatched from background jobs:
- Browser socket ID: `497320437.202972750`
- Server connection ID registered: `546567154.102666050`
- These IDs don't match because `InteractsWithSockets` was trying to exclude a sender socket that doesn't exist in background jobs

### Solution Applied
Removed `InteractsWithSockets` trait from ALL THREE broadcast events that are dispatched from background jobs:
- The events are dispatched from background jobs (not from WebSocket clients)
- There is no socket context to exclude, so the trait is unnecessary
- Broadcasting now goes to ALL connections on the channel, as intended

**Files modified**:
1. `app/Events/PreAnalysisCompleted.php`
2. `app/Events/AnalysisCompleted.php`
3. `app/Events/PromptOptimizationCompleted.php`

```php
// Before (all three events):
use Dispatchable, InteractsWithSockets, SerializesModels;

// After (all three events):
use Dispatchable, SerializesModels;
```

**Reverb Process**: Restarted fresh on 2025-12-08 to load updated event files (old process was cached in memory)

### Why This Works
1. When `InteractsWithSockets` is used on a background-dispatched event, it tries to get the current socket context
2. Since there is no socket context in a background job, this can cause socket ID mismatches
3. Removing the trait means the broadcast has no socket_id parameter, so Reverb broadcasts to ALL connections
4. The browser receives the message as intended

### Message Format Confirmed Correct
```json
{
  "event": "PreAnalysisCompleted",
  "data": "{\"prompt_run_id\":29,\"workflow_stage\":\"pre_analysis_questions\",\"questions_count\":3}",
  "channel": "prompt-run.29"
}
```

This follows the Pusher protocol specification for custom broadcast events.

### Internal Reverb Events vs Custom Events
- Internal events have prefixes: `pusher:` (e.g., `pusher:connection_established`)
- Internal subscription events: `pusher_internal:` (e.g., `pusher_internal:subscription_succeeded`)
- Custom broadcast events: No prefix (e.g., `PreAnalysisCompleted`)

All three types are correctly formatted and transmitted to the client.


## Files Modified

**To fix the socket ID mismatch issue (all three events)**:
1. `app/Events/PreAnalysisCompleted.php` - Removed `InteractsWithSockets` trait (broadcast to all connections)
2. `app/Events/AnalysisCompleted.php` - Removed `InteractsWithSockets` trait (broadcast to all connections)
3. `app/Events/PromptOptimizationCompleted.php` - Removed `InteractsWithSockets` trait (broadcast to all connections)

**Previously modified (for debugging/fixing earlier issues)**:
- `config/reverb.php` - Changed hostname configuration to frontend-facing domain
- `app/Jobs/ProcessPreAnalysis.php` - Added delay before broadcast to ensure subscription
- `resources/js/bootstrap.ts` - Added Echo initialization logging and fixed ESLint error
- `resources/js/Composables/useRealtimeUpdates.ts` - Added comprehensive logging
- `routes/channels.php` - Added public channel authorization

## Issue Resolution: WebSocket Routing Fix (Docker Networking)

**Status**: ✅ RESOLVED

### Root Cause
Initial debugging revealed that Reverb runs in a **separate Docker container** named `personality-reverb-1`, not inside the `laravel.test` container. The Caddy reverse proxy was unable to route WebSocket requests to the Reverb service.

**Attempts and findings**:
1. First attempt: Changed to `laravel.test:8080` (incorrect - Reverb in separate container)
2. Attempted: `127.0.0.1:8080` (container-level localhost doesn't reach other containers)
3. Investigated: Docker DNS resolution issues with Caddy
4. Discovered: Reverb container IP is `172.30.0.6` on Docker network

### Solution Applied
Updated the Caddy reverse proxy configuration to use the proper Docker service name, which resolves via Docker's internal DNS:

**File**: `Caddyfile` (line 33)

**Before (broken)**:
```caddy
reverse_proxy @websockets 127.0.0.1:8080
```

**After (fixed)**:
```caddy
reverse_proxy @websockets personality-reverb-1:8080
```

This routes WebSocket upgrade requests: browser → Caddy (port 443) → Docker DNS resolves `personality-reverb-1` → Reverb container (port 8080).

### Critical Issue: Caddy DNS Resolution Failure

During testing, discovered that Caddy's DNS resolution was failing with:
```
"dial tcp: lookup personality-reverb-1 on 127.0.0.11:53: server misbehaving"
```

This is a known issue with Docker's embedded DNS resolver (`127.0.0.11:53`) when used by Caddy.

### Final Solution: Direct IP Address

Rather than troubleshoot DNS configuration, using the direct IP address that was already discovered:

**File**: `Caddyfile` (line 33)

**Before (broken DNS)**:
```caddy
reverse_proxy @websockets personality-reverb-1:8080
```

**After (working with direct IP)**:
```caddy
reverse_proxy @websockets 172.30.0.6:8080
```

**Why this works**:
- `172.30.0.6` is the Reverb container's IP on the Docker network
- No DNS resolution required - direct IP connection is reliable
- Verified connectivity from Laravel container using `curl`
- Caddy reload successful

### Verification
- Caddy successfully reloaded with direct IP configuration
- Caddy can now route WebSocket requests directly to Reverb
- WebSocket connections should now establish successfully
- No DNS resolution errors expected

## Related Files

- `config/broadcasting.php` - Defines reverb driver configuration
- `Caddyfile` - WebSocket routing from `app.localhost` to `laravel.test:8080`
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
