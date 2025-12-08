# WebSocket Broadcasting Troubleshooting Guide

## Current Status
- Real-time updates for Prompt Builder now flow over WebSockets again. Quick Queries, analysis progress, and optimization events land without refreshing the page.
- Laravel broadcasts, the Reverb server, and the browser are finally pointing at the same host/namespace, so Echo receives events immediately after they are dispatched.

## Symptoms We Saw
- User submits a task → backend jobs complete and events are dispatched (`storage/logs/laravel.log`).
- Browser connects to `wss://app.localhost/app/{key}` and subscribes to `prompt-run.{id}` successfully.
- Despite the connection, no `[useRealtimeUpdates] Event listener callback fired` log appeared and the UI stayed on “Analysing your Task” until the page was refreshed.

## Root Cause Analysis
### 1. Backend Broadcast Host Was Still `localhost`
- The Laravel app (queue workers + jobs) was posting broadcasts to `http://localhost:8080`, which spins up an *entirely different* Reverb process inside the worker container.
- Browsers connect to the dedicated `reverb` container exposed through Caddy. That instance never received the broadcast payloads, so it had no channels or socket IDs to route to.
- **Fix**: point `REVERB_HOST` at the docker service name so `config/broadcasting.php` targets the same server clients use.
  - `.env:119` → `REVERB_HOST=reverb`
  - Restart queue workers / `reverb` after changing env so config cache picks it up.

### 2. Echo Namespaced Event Names That Didn’t Exist
- Out of the box Echo prefixes every event with your Laravel namespace (`App.Events.PreAnalysisCompleted`).
- Our events explicitly call `broadcastAs('PreAnalysisCompleted')`, and `useRealtimeUpdates` registers listeners for `PreAnalysisCompleted` (no namespace).
- Because we never set `namespace: null`, Echo subscribed to names that Reverb never emits, so the JS handlers never fired even though the payload hit the socket.
- **Fix**: in `resources/js/bootstrap.ts` set `namespace: null` when constructing the Echo/Reverb config. Echo now listens for the literal custom event names.

## How To Verify The Fix
1. Visit `/prompt-builder/{id}` and submit a task.
2. Browser console should show:
   - `[Echo] WebSocket connected successfully`
   - `[useRealtimeUpdates] Event listener callback fired for: PreAnalysisCompleted`
3. `storage/logs/laravel.log` should include:
   - `EventDispatcher channel lookup ... "channel_found":true`
   - `EventDispatcher calling broadcast ... "connection_ids":["<socket_id>"]`
4. UI should swap from “Analysing your Task” to the Quick Queries without reloading.

## Local Vendor Patches (Why We Made Them)
### Signature Verification Patch — **Required Until Upstream Fixes**
- File: `vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php`
- Change: only include `body_md5` in the HMAC string if the broadcaster explicitly provided it. Also log the verification details.
- Reason: Laravel’s Pusher broadcaster never adds `body_md5`, so Reverb’s automatic inclusion caused every broadcast to be rejected with `Authentication signature invalid`.
- Status: keep this patch until Reverb ships the fix; otherwise no events will ever be accepted.

### Temporary Instrumentation — **Safe To Remove After Debugging**
- Files: `vendor/laravel/reverb/src/Protocols/Pusher/EventDispatcher.php`, `vendor/laravel/reverb/src/Protocols/Pusher/EventHandler.php`, `vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php`, plus `routes/channels.php`.
- Change: added verbose `Log::info(...)` calls that print subscribe/unsubscribe details, channel lookups, and dispatcher activity so we could correlate browser socket IDs with server state.
- Reason: helped confirm whether the channel existed and whether broadcasts were routed to the same socket ID.
- Status: no longer required for day-to-day use; the next commit removes these debug statements so that future `composer update` runs don’t have surprising diffs.

## Related Files
- `.env` — runtime host/port/scheme for backend broadcasts.
- `resources/js/bootstrap.ts` — Echo/Reverb bootstrap config (set `namespace: null`).
- `resources/js/Composables/useRealtimeUpdates.ts` — subscribes to `prompt-run.{id}` events.
- `storage/logs/laravel.log` — confirm subscription/broadcast timeline.
