# Visitor Tracking Implementation Plan

## Overview

This document outlines the `visitor_id` UUID-based tracking system used for attribution tracking, guest prompt runs, and seamless Fullstory integration.

---

## Proposed Architecture

### New Cookie: `visitor_id`
- **Type**: UUID (e.g., `550e8400-e29b-41d4-a916-446655440000`)
- **Purpose**: Unique visitor identity for attribution, analytics, and guest sessions
- **Storage**: HTTP-only, secure cookie (server-side only)
- **Expiry**: 2 years (1,051,200 minutes)
- **Benefits**: Attribution tracking, guest prompt runs, Fullstory integration, marketing analytics

### Database: `visitors` Table

**Purpose**: Store visitor metadata, attribution data, and link to eventual user accounts

**Schema**:
```php
Schema::create('visitors', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

    // Attribution tracking
    $table->string('utm_source')->nullable();
    $table->string('utm_medium')->nullable();
    $table->string('utm_campaign')->nullable();
    $table->string('utm_term')->nullable();
    $table->string('utm_content')->nullable();
    $table->string('referrer')->nullable();
    $table->string('landing_page')->nullable();

    // Device/Browser information
    $table->string('user_agent')->nullable();
    $table->ipAddress('ip_address')->nullable();

    // Visit tracking
    $table->timestamp('first_visit_at');
    $table->timestamp('last_visit_at');
    $table->timestamp('converted_at')->nullable(); // When user_id was set

    $table->timestamps();

    // Indexes for performance
    $table->index(['user_id', 'first_visit_at']);
    $table->index('converted_at');
});
```

### Update `prompt_runs` Table

Add visitor tracking to link anonymous prompt runs:

```php
Schema::table('prompt_runs', function (Blueprint $table) {
    $table->uuid('visitor_id')->nullable()->after('user_id');
    $table->foreign('visitor_id')
          ->references('id')
          ->on('visitors')
          ->onDelete('set null');

    $table->index('visitor_id');
});
```

---

## User Journey Flow

### 1. Anonymous Visitor (First Visit)

**Request**: GET `/`

**Middleware** (`TrackVisitor`):
1. Check for `visitor_id` cookie → Not found
2. Generate new UUID: `550e8400-e29b-41d4-a916-446655440000`
3. Create `Visitor` record:
   ```php
   Visitor::create([
       'id' => $visitorId,
       'utm_source' => $request->query('utm_source'),
       'utm_medium' => $request->query('utm_medium'),
       'utm_campaign' => $request->query('utm_campaign'),
       'utm_term' => $request->query('utm_term'),
       'utm_content' => $request->query('utm_content'),
       'referrer' => $request->header('referer'),
       'landing_page' => $request->fullUrl(),
       'user_agent' => $request->userAgent(),
       'ip_address' => $request->ip(),
       'first_visit_at' => now(),
       'last_visit_at' => now(),
   ]);
   ```
4. Set cookie: `visitor_id` = UUID (HTTP-only, 2-year expiry)

**Frontend** (`resources/js/app.ts`):
1. Send to Fullstory:
   ```javascript
   FS.identify(visitorId, { isGuest: true });
   ```

**Database State**:
```
visitors:
  - id: 550e8400-e29b-41d4-a916-446655440000
  - user_id: null
  - utm_source: 'google'
  - converted_at: null

prompt_runs: (empty)
users: (empty)
```

### 2. Anonymous Visitor Creates Guest Prompt Run

**Request**: POST `/prompt-optimizer`

**Controller** (`PromptOptimizerController@store`):
```php
$visitorId = $request->cookie('visitor_id');
$userId = auth()->id(); // null for guests

$promptRun = PromptRun::create([
    'user_id' => $userId,
    'visitor_id' => $visitorId,
    'task_description' => $validated['task_description'],
    'status' => 'processing',
    'workflow_stage' => 'submitted',
]);
```

**Database State**:
```
visitors:
  - id: 550e8400-e29b-41d4-a916-446655440000
  - user_id: null
  - visit_count: 1

prompt_runs:
  - id: 1
  - user_id: null
  - visitor_id: 550e8400-e29b-41d4-a916-446655440000
  - task_description: "Help me write a product roadmap"
  - status: processing
```

**Fullstory Event**:
```javascript
FS.event('Guest Prompt Created', {
  visitorId: '550e8400-e29b-41d4-a916-446655440000',
  taskLength: 35
});
```

### 3. Visitor Registers

**Request**: POST `/register`

**Controller** (`RegisteredUserController@store`):
```php
$user = User::create([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'password' => Hash::make($validated['password']),
]);

// Link visitor to user
$visitorId = $request->cookie('visitor_id');
if ($visitorId) {
    $visitor = Visitor::find($visitorId);
    if ($visitor && !$visitor->user_id) {
        $visitor->update([
            'user_id' => $user->id,
            'converted_at' => now(),
        ]);

        // Claim all guest prompt runs
        PromptRun::where('visitor_id', $visitorId)
                 ->whereNull('user_id')
                 ->update(['user_id' => $user->id]);
    }
}

Auth::login($user);
```

**Database State**:
```
visitors:
  - id: 550e8400-e29b-41d4-a916-446655440000
  - user_id: 1  // LINKED!
  - visit_count: 1
  - converted_at: 2025-01-13 10:30:00

users:
  - id: 1
  - email: user@example.com
  - name: John Doe

prompt_runs:
  - id: 1
  - user_id: 1  // CLAIMED!
  - visitor_id: 550e8400-e29b-41d4-a916-446655440000
```

**Fullstory Update**:
```javascript
FS.identify(user.id, {
  email: user.email,
  displayName: user.name,
  visitorId: '550e8400-e29b-41d4-a916-446655440000', // Links history
  registrationDate: user.created_at
});
```

### 4. Returning Visitor

**Request**: GET `/` (next day)

**Middleware** (`TrackVisitor`):
1. Check for `visitor_id` cookie → Found: `550e8400-...`
2. Update existing Visitor record:
   ```php
   $visitor->update([
       'last_visit_at' => now(),
       'visit_count' => $visitor->visit_count + 1,
   ]);
   ```
3. Cookie already set (persist)

**Database State**:
```
visitors:
  - id: 550e8400-e29b-41d4-a916-446655440000
  - user_id: 1
  - visit_count: 2  // INCREMENTED
  - last_visit_at: 2025-01-14 09:00:00
```

---

## Cookie vs localStorage Strategy

### Primary Storage: HTTP-only Cookie

**Advantages**:
- Sent automatically with every request (server-side accessible)
- HTTP-only flag prevents JavaScript tampering
- Secure flag ensures HTTPS-only transmission
- Cannot be deleted by malicious scripts

**Implementation**:
```php
// In middleware
$cookie = cookie(
    'visitor_id',
    $visitorId,
    1051200, // 2 years in minutes
    '/',
    null,
    true, // secure (HTTPS only)
    true, // httpOnly
    false,
    'lax' // sameSite
);

return $response->withCookie($cookie);
```

### localStorage (Deprecated)

**Note**: localStorage backups have been removed from this system. See data-retention-and-table-growth-plan.md Part A for details.

**Advantages**:
- Persists longer (no expiry)
- Survives cookie clearing tools
- Can restore server-side cookie if deleted

**Implementation**:
```javascript
// resources/js/app.ts
const visitorId = getCookie('visitor_id');

if (visitorId) {
    // Cookie exists → backup to localStorage
    localStorage.setItem('visitor_id_backup', visitorId);
} else {
    // Cookie deleted → check localStorage
    const backupId = localStorage.getItem('visitor_id_backup');

    if (backupId) {
        // Send to server to recreate cookie
        fetch('/api/restore-visitor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ visitor_id: backupId })
        });
    }
}
```

### Restore API Endpoint

**Route**: `routes/api.php`
```php
Route::post('/restore-visitor', function (Request $request) {
    $validated = $request->validate([
        'visitor_id' => ['required', 'uuid', 'exists:visitors,id']
    ]);

    $visitor = Visitor::find($validated['visitor_id']);

    if ($visitor) {
        $cookie = cookie('visitor_id', $visitor->id, 1051200, '/', null, true, true, false, 'lax');

        return response()->json(['restored' => true])->withCookie($cookie);
    }

    return response()->json(['restored' => false], 404);
})->middleware('throttle:10,1'); // Prevent abuse
```

---

## Fullstory Integration

### Anonymous Visitors

When user is not authenticated but has visitor_id:

```javascript
// resources/js/app.ts or AppLayout.vue
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const auth = computed(() => page.props.auth);
const visitorId = getCookie('visitor_id');

if (window.FS) {
    if (!auth.value.user && visitorId) {
        // Anonymous visitor
        FS.identify(visitorId, {
            isGuest: true,
            hasCreatedPrompt: false, // Update after prompt creation
        });
    }
}
```

### Registered Users

Link to visitor_id for complete journey tracking:

```javascript
if (window.FS && auth.value.user) {
    FS.identify(auth.value.user.id.toString(), {
        email: auth.value.user.email,
        displayName: auth.value.user.name,
        personalityType: auth.value.user.personality_type,
        registrationDate: auth.value.user.created_at,

        // Link to pre-registration behaviour
        visitorId: visitorId,

        // Can also add visitor metadata
        utmSource: visitor?.utm_source,
        utmCampaign: visitor?.utm_campaign,
    });
}
```

### Event Tracking with Visitor Context

```javascript
// When guest creates prompt
FS.event('Guest Prompt Created', {
    visitorId: visitorId,
    taskLength: taskDescription.length,
    hasVisitedBefore: visitCount > 1
});

// When visitor converts to user
FS.event('Visitor Converted', {
    visitorId: visitorId,
    daysSinceFirstVisit: daysSince,
    promptsCreatedAsGuest: guestPromptCount,
    registrationMethod: 'email' | 'google'
});
```

---

## Implementation Checklist

### Phase 1: Database Setup

- [ ] Create migration: `create_visitors_table.php`
- [ ] Create migration: `add_visitor_id_to_prompt_runs_table.php`
- [ ] Create `Visitor` model with fillable fields and relationships
- [ ] Run migrations: `php artisan migrate`

### Phase 2: Middleware

- [ ] Update `TrackVisitor` middleware:
  - [ ] Generate UUID if no `visitor_id` cookie
  - [ ] Create or update `Visitor` record
  - [ ] Capture UTM parameters from query string
  - [ ] Capture referrer from headers
  - [ ] Set HTTP-only cookie with 2-year expiry
  - [ ] Increment `visit_count` on subsequent visits
- [ ] Test middleware with various scenarios

### Phase 3: Controllers

- [ ] Update `PromptOptimizerController@store`:
  - [ ] Capture `visitor_id` from cookie
  - [ ] Save `visitor_id` to `prompt_runs` table
  - [ ] Handle both authenticated and guest users
- [ ] Update `RegisteredUserController@store`:
  - [ ] Link `Visitor` to newly created `User`
  - [ ] Set `converted_at` timestamp
  - [ ] Claim all guest `PromptRun` records
  - [ ] Update `user_id` on guest prompt runs

### Phase 4: Fullstory Integration

- [ ] Update Fullstory identification in `app.ts`:
  - [ ] Identify anonymous visitors with `visitor_id`
  - [ ] Include `visitorId` in authenticated user properties
  - [ ] Add visitor metadata (UTM params, timestamps)
- [ ] Add event tracking:
  - [ ] `Guest Prompt Created` event
  - [ ] `Visitor Converted` event on registration
- [ ] Test Fullstory session continuity across anonymous → registered

### Phase 5: Analytics & Reporting

- [ ] Create database query helpers:
  - [ ] Conversion rate (visitors with `converted_at` / total visitors)
  - [ ] Attribution report (group by UTM source/medium/campaign)
  - [ ] Time to conversion (avg `converted_at - first_visit_at`)
- [ ] Optional: Create analytics dashboard in app
- [ ] Document Fullstory segments to create

### Phase 7: Testing

- [ ] Test anonymous visitor flow:
  - [ ] First visit creates visitor record
  - [ ] Cookie is set correctly
  - [ ] localStorage backup works
  - [ ] UTM parameters captured
- [ ] Test guest prompt creation:
  - [ ] `visitor_id` saved to `prompt_runs`
  - [ ] Works without authentication
- [ ] Test registration/conversion:
  - [ ] Visitor linked to user
  - [ ] Guest prompt runs claimed
  - [ ] `converted_at` timestamp set
- [ ] Test localStorage restore:
  - [ ] Clear cookies
  - [ ] Reload page
  - [ ] Cookie recreated from localStorage
- [ ] Test Fullstory integration:
  - [ ] Anonymous visitor identified
  - [ ] User registration links sessions
  - [ ] Events include visitor_id

---

## Privacy & Compliance

### GDPR Considerations

**Personal Data Collected**:
- IP address (pseudonymous identifier)
- User agent (browser/device info)
- Visitor UUID (pseudonymous identifier)

**Lawful Basis**:
- Legitimate interest (analytics, fraud prevention)
- Consent (if using cookie banner)

**Required Actions**:
- [ ] Update Privacy Policy to mention visitor tracking
- [ ] Add to Cookie Policy
- [ ] Ensure cookie banner includes visitor_id cookie
- [ ] Provide opt-out mechanism if required by jurisdiction

### Data Retention

**Visitor Records**:
- Keep indefinitely if linked to user (`user_id` not null)
- Delete after 2 years if never converted (`converted_at` is null)

For an implementation-ready, tiered retention and archiving plan (including SEO-safe bot noise reduction and retention for other high-growth analytics tables), see:

- `docs/data-retention-and-table-growth-plan.md`

**Cleanup Command**:
```php
// app/Console/Commands/CleanupOldVisitors.php
public function handle()
{
    $deleted = Visitor::whereNull('user_id')
        ->where('last_visit_at', '<', now()->subYears(2))
        ->delete();

    $this->info("Deleted {$deleted} old visitor records.");
}
```

**Schedule** (in `app/Console/Kernel.php`):
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('visitors:cleanup')->monthly();
}
```

---

## Attribution Reporting Examples

### Conversion Rate by UTM Source

```php
$attribution = Visitor::selectRaw('
        utm_source,
        COUNT(*) as total_visitors,
        COUNT(converted_at) as conversions,
        ROUND(COUNT(converted_at) * 100.0 / COUNT(*), 2) as conversion_rate
    ')
    ->groupBy('utm_source')
    ->orderByDesc('conversions')
    ->get();
```

### Time to Conversion

```php
$avgTimeToConversion = Visitor::whereNotNull('converted_at')
    ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, first_visit_at, converted_at)) as avg_hours')
    ->value('avg_hours');
```

### Guest Prompts Before Conversion

```php
$guestPromptStats = Visitor::whereNotNull('converted_at')
    ->with(['promptRuns' => fn($q) => $q->whereNull('user_id')])
    ->get()
    ->map(fn($v) => [
        'visitor_id' => $v->id,
        'guest_prompts' => $v->promptRuns->count(),
    ]);
```

---

## Troubleshooting

### Issue: Cookie Not Set

**Symptoms**: `visitor_id` cookie doesn't appear in browser

**Check**:
1. Middleware in correct position in stack
2. HTTPS enabled in production (secure flag)
3. Response actually goes through middleware
4. No exceptions thrown in middleware

**Debug**:
```php
Log::info('TrackVisitor middleware executed', [
    'has_cookie' => $request->hasCookie('visitor_id'),
    'generated_id' => $visitorId,
]);
```

### Issue: localStorage Not Restoring Cookie

**Symptoms**: Cookie deleted but not recreated from localStorage

**Check**:
1. `/api/restore-visitor` endpoint accessible
2. CSRF token included in request
3. `visitor_id` exists in `visitors` table
4. Network tab shows successful API call

**Debug**:
```javascript
console.log('Visitor ID from cookie:', getCookie('visitor_id'));
console.log('Visitor ID from localStorage:', localStorage.getItem('visitor_id_backup'));
```

### Issue: Prompt Runs Not Claimed on Registration

**Symptoms**: Guest prompt runs still have `user_id = null` after user registers

**Check**:
1. `visitor_id` cookie exists during registration
2. `Visitor` record has correct ID
3. `PromptRun` records have matching `visitor_id`
4. Update query actually executes

**Debug**:
```php
Log::info('Claiming guest prompt runs', [
    'visitor_id' => $visitorId,
    'user_id' => $user->id,
    'prompt_runs_claimed' => PromptRun::where('visitor_id', $visitorId)
        ->whereNull('user_id')
        ->count(),
]);
```

---

## Summary

This visitor tracking system provides:

✅ **Unique visitor identity** via UUID cookie
✅ **Attribution tracking** via UTM parameters
✅ **Guest prompt runs** linked to eventual user accounts
✅ **Fullstory integration** with continuous session tracking
✅ **Marketing analytics** on conversion funnels
✅ **localStorage backup** for cookie restoration
✅ **Privacy compliance** with data retention policies

**Next Steps**: Review this plan, then proceed with implementation phase-by-phase.
