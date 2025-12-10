# Error Monitoring and Alerting Implementation Plan

## User Request

Implement error monitoring and alerting for the Laravel application. User asked whether to use Sentry or an alternative
solution.

## Recommendation: Start with Sentry Free Tier

After comprehensive research and user feedback, **Sentry's free tier** is the best starting point for this project.

### Why Sentry Free Tier First?

1. **Free for Small Projects** - 5,000 errors/month at no cost (generous for startups)
2. **Industry Standard** - Used by major companies (Disney, Microsoft, Uber)
3. **Official Laravel Partnership** - First-party integration announced in 2025
4. **Full-Stack Monitoring** - Backend (Laravel) + Frontend (Vue 3) in one platform
5. **Complete Feature Set** - Error tracking, release tracking, breadcrumbs, alerts (all free)
6. **Spike Protection** - Won't cut you off if you exceed quota temporarily
7. **Upgrade When Needed** - Only pay when you need more capacity or advanced features

### Sentry Free Tier: What You Get

✅ **5,000 errors per month** - ~160 errors/day (plenty for early stage)

✅ **1 project** - Track your Laravel application

✅ **Unlimited team members** - No per-seat costs

✅ **90 days data retention** - Industry standard

✅ **Full error context** - Stack traces, breadcrumbs, user context, request data

✅ **Release tracking** - See which deployment caused errors

✅ **Email alerts** - Notify team of critical errors

✅ **Basic integrations** - GitHub, Slack, Jira

✅ **Both frontend and backend** - Vue 3 + Laravel tracking

✅ **Distributed tracing basics** - Connect frontend → backend errors

### Sentry Free Tier: Limitations

❌ **No session replay** - Visual user journey (requires Team plan at $26/month)

❌ **No performance monitoring** - APM costs $20/month for 100K transactions

❌ **No priority support** - Community support only

❌ **1 project limit** - Can't track staging + production separately (workaround: use environments)

❌ **5K error cap** - Rate-limited after quota (but has spike protection)

### When to Upgrade to Sentry Team ($26/month)

- You consistently exceed 5,000 errors/month
- You need session replay to debug complex user journeys
- You need performance monitoring (slow query tracking)
- You want multiple projects (staging, production, etc.)
- Error volume justifies the cost

### Alternative: Laravel Flare ($9/month)

Consider **Flare** if Sentry's free tier runs out or you prefer Laravel-specific features:

- AI-powered debugging (Laravel-specific solutions)
- Performance monitoring (free during beta)
- Cheaper than Sentry Team plan ($9 vs $26/month)
- Built specifically for Laravel (deeper integration)
- Unlimited projects from day one

**Trade-off**: No session replay, less mature than Sentry, smaller ecosystem

---

## Current State Analysis

### Existing Error Handling (Already Implemented)

✅ **N8nClient circuit breaker** - Opens after 5 failures, 5-minute cooldown (15 minutes for rate limits)

✅ **Structured error logging** - error_context, retry_count, last_error_at fields in database

✅ **Enhanced webhook validation** - Validates error_type, failed_node, execution_id

✅ **Rate limit detection** - HTTP 429 handling with extended circuit breaker

✅ **Graceful degradation** - Workflow 0 skips pre-analysis on errors

✅ **Laravel logging channels** - Stack, daily, slack, papertrail configured

### What's Missing (Critical Gaps)

❌ Production error aggregation and tracking

❌ Visual error dashboards

❌ Automatic error grouping/deduplication

❌ Frontend (Vue 3) error tracking

❌ Performance insights

❌ Proactive alerting rules and escalation

❌ Error trend analysis

---

## Implementation Plan

### Phase 1: Install and Configure Sentry (Backend)

**Goal**: Set up Sentry for Laravel backend error monitoring

#### Step 1: Install Sentry Laravel SDK

```bash
composer require sentry/sentry-laravel
```

**Files Modified**:

- `composer.json` - Adds Sentry Laravel SDK
- `composer.lock` - Updates lock file

#### Step 2: Publish Sentry Configuration

```bash
php artisan sentry:publish --dsn
```

This creates `config/sentry.php` and prompts for your Sentry DSN.

**Files Created**:

- `config/sentry.php` - Sentry configuration file

#### Step 3: Add Environment Variables

Create a free Sentry account at https://sentry.io/signup/ and create a Laravel project to get your DSN.

Add to `.env`:

```
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.1
```

Add to `.env.example`:

```
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
```

**Files Modified**:

- `.env` - Production DSN key (not committed)
- `.env.example` - Template for team members

#### Step 4: Test Installation

```bash
php artisan sentry:test
```

This sends a test exception to Sentry to verify setup. Check your Sentry dashboard.

---

### Phase 2: Add Context to Critical Error Points

**Goal**: Enhance existing error logging with Sentry-specific context and tags

#### Location 1: N8nClient Circuit Breaker

**File**: `app/Services/N8nClient.php`

**Current Code** (lines 56-67):

```php
if ($circuitOpenUntil && now()->isBefore($circuitOpenUntil)) {
    return true;
}

// Open circuit if too many failures
if ($failureCount >= $this->circuitBreakerThreshold) {
    Cache::put('n8n_circuit_breaker_open_until', now()->addSeconds($this->circuitBreakerTimeout));
    return true;
}
```

**Enhanced with Sentry Context**:

```php
if ($circuitOpenUntil && now()->isBefore($circuitOpenUntil)) {
    return true;
}

// Open circuit if too many failures
if ($failureCount >= $this->circuitBreakerThreshold) {
    Cache::put('n8n_circuit_breaker_open_until', now()->addSeconds($this->circuitBreakerTimeout));

    // Add Sentry context for circuit breaker events
    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($failureCount) {
        $scope->setContext('circuit_breaker', [
            'failure_count' => $failureCount,
            'threshold' => $this->circuitBreakerThreshold,
            'cooldown_seconds' => $this->circuitBreakerTimeout,
            'cooldown_minutes' => $this->circuitBreakerTimeout / 60,
        ]);
        $scope->setTag('error_category', 'circuit_breaker');
    });

    \Log::warning('N8n circuit breaker opened', [
        'failure_count' => $failureCount,
        'threshold' => $this->circuitBreakerThreshold,
        'cooldown_seconds' => $this->circuitBreakerTimeout,
        'cooldown_minutes' => $this->circuitBreakerTimeout / 60,
    ]);

    return true;
}
```

**Benefits**: Track when circuit breaker opens, identify patterns, group by error category

#### Location 2: N8nClient Rate Limit Detection

**File**: `app/Services/N8nClient.php`

**Current Code** (lines 130-150):

```php
// Special handling for rate limits (429)
if ($status === 429) {
    Log::warning('N8n workflow hit Anthropic rate limit', [
        'path' => $path,
        'attempt' => $attempt + 1,
    ]);
    // ... existing code ...
}
```

**Enhanced with Sentry Tags**:

```php
// Special handling for rate limits (429)
if ($status === 429) {
    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($path, $attempt) {
        $scope->setTag('error_category', 'rate_limit');
        $scope->setContext('rate_limit', [
            'path' => $path,
            'attempt' => $attempt + 1,
            'user_id' => auth()->id(),
        ]);
    });

    Log::warning('N8n workflow hit Anthropic rate limit', [
        'path' => $path,
        'attempt' => $attempt + 1,
        'user_id' => auth()->id(),
        'payload' => $payload,
    ]);
    // ... existing code ...
}
```

**Benefits**: Track which users trigger rate limits, identify abuse patterns, filter by rate_limit tag

#### Location 3: Webhook Receiver Error Handling

**File**: `routes/api.php`

**Current Code** (lines 157-178):

```php
} catch (\Illuminate\Database\QueryException $e) {
    Log::error('Database error processing N8n webhook', [
        'error' => $e->getMessage(),
        'payload' => $request->all(),
    ]);
    // ... existing code ...
}
```

**Enhanced with Sentry Context**:

```php
} catch (\Illuminate\Database\QueryException $e) {
    \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($request) {
        $scope->setContext('webhook', [
            'prompt_run_id' => $request->input('prompt_run_id'),
            'workflow_stage' => $request->input('workflow_stage'),
            'error_type' => $request->input('error_context.error_type'),
            'failed_node' => $request->input('error_context.failed_node'),
        ]);
        $scope->setTag('error_category', 'webhook_database');
    });

    Log::error('Database error processing N8n webhook', [
        'error' => $e->getMessage(),
        'payload' => $request->all(),
    ]);
    // ... existing code ...
}
```

**Benefits**: Group webhook errors by workflow stage, identify problematic nodes, filter by tag

---

### Phase 3: Configure Global Exception Handler

**Goal**: Add Sentry context to all exceptions globally

**File**: `bootstrap/app.php`

**Current Code** (lines 50-78):

```php
->withExceptions(function (Exceptions $exceptions) {
    // Handle CSRF token expiration
    $exceptions->render(function (TokenMismatchException $e, Request $request) {
        // ... existing code ...
    });
})
```

**Enhanced with Sentry Context**:

```php
->withExceptions(function (Exceptions $exceptions) {
    // Add global Sentry context to all exceptions
    $exceptions->reportable(function (Throwable $e) {
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) {
            // Add circuit breaker status
            $scope->setContext('circuit_breaker', [
                'failures' => Cache::get('n8n_circuit_breaker_failures', 0),
                'circuit_open' => Cache::has('n8n_circuit_breaker_open_until'),
                'open_until' => Cache::get('n8n_circuit_breaker_open_until'),
            ]);

            // Add user context if authenticated
            if (auth()->check()) {
                $scope->setUser([
                    'id' => auth()->id(),
                    'email' => auth()->user()->email,
                    'username' => auth()->user()->name,
                ]);
            }

            // Add queue context if processing job
            if (app()->runningInConsole() && app()->bound('queue.connection')) {
                $scope->setContext('queue', [
                    'connection' => config('queue.default'),
                    'queue' => 'default',
                ]);
                $scope->setTag('context', 'queue');
            }
        });
    });

    // Handle CSRF token expiration
    $exceptions->render(function (TokenMismatchException $e, Request $request) {
        // ... existing code ...
    });
})
```

**Benefits**: Every error automatically includes circuit breaker status, user details, queue context

---

### Phase 4: Frontend Error Tracking (Essential for Free Tier Value)

**Goal**: Track Vue 3 errors in Sentry (maximise free tier value with full-stack monitoring)

#### Step 1: Install Sentry Vue SDK

```bash
npm install @sentry/vue
```

**Files Modified**:

- `package.json` - Adds @sentry/vue dependency
- `package-lock.json` - Updates lock file

#### Step 2: Configure Sentry in Vue App

**File**: `resources/js/app.ts`

**Current Code** (lines 1-50):

```typescript
import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
// ... existing imports ...
```

**Enhanced with Sentry**:

```typescript
import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import * as Sentry from '@sentry/vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Initialize Sentry for Vue
        if (import.meta.env.VITE_SENTRY_DSN_PUBLIC) {
            Sentry.init({
                app,
                dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
                integrations: [
                    Sentry.browserTracingIntegration(),
                    Sentry.replayIntegration({
                        maskAllText: false, // Session replay available on free tier!
                        blockAllMedia: false,
                    }),
                ],
                tracesSampleRate: 0.1, // 10% of transactions for distributed tracing
                replaysSessionSampleRate: 0, // Don't record all sessions (save quota)
                replaysOnErrorSampleRate: 1.0, // Record session when error occurs
                environment: import.meta.env.VITE_APP_ENV || 'production',
            });
        }

        return app.use(plugin).use(ZiggyVue).mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
```

#### Step 3: Add Environment Variable

Add to `.env`:

```
VITE_SENTRY_DSN_PUBLIC=https://your-dsn@sentry.io/project-id
VITE_APP_ENV=production
```

Add to `.env.example`:

```
VITE_SENTRY_DSN_PUBLIC=
VITE_APP_ENV=production
```

**Benefits**:

- Track Vue component errors, JavaScript errors, unhandled promise rejections
- **Session replay** - See exactly what user did before error (free tier includes this!)
- Distributed tracing - Connect frontend errors to backend errors
- Breadcrumbs - User navigation history before error

---

### Phase 5: Configure Alerting Rules

**Goal**: Set up Slack/email alerts for critical errors

#### Sentry Dashboard Configuration

**Navigate to**: Sentry Dashboard → Settings → Alerts

**Recommended Alert Rules**:

1. **Circuit Breaker Opened**
    - Condition: Log message contains "N8n circuit breaker opened"
    - Channel: Slack + Email
    - Severity: Critical
    - Throttle: Once per hour

2. **Rate Limit Hit**
    - Condition: Log message contains "Anthropic rate limit"
    - Channel: Slack
    - Severity: Warning
    - Throttle: Once per 15 minutes

3. **Database Error**
    - Condition: Exception type is `QueryException`
    - Channel: Slack + Email
    - Severity: Critical
    - Throttle: Immediate

4. **Webhook Validation Failed**
    - Condition: Log message contains "Invalid N8n webhook"
    - Channel: Slack
    - Severity: Warning
    - Throttle: Once per 10 minutes

5. **Queue Job Failed**
    - Condition: Exception in queue context
    - Channel: Email
    - Severity: High
    - Throttle: Daily digest

**Configuration Required**:

- Slack webhook URL (already configured in `config/logging.php`)
- Email addresses for critical alerts

---

### Phase 6: Integrate with Existing Slack Infrastructure

**Goal**: Use existing Slack webhook for Sentry alerts

**File**: `config/logging.php`

**Current Slack Configuration** (lines 85-92):

```php
'slack' => [
    'driver' => 'slack',
    'url' => env('LOG_SLACK_WEBHOOK_URL'),
    'username' => 'Laravel Log',
    'emoji' => ':boom:',
    'level' => env('LOG_LEVEL', 'critical'),
],
```

**Steps**:

1. Copy `LOG_SLACK_WEBHOOK_URL` from `.env`
2. Add to Sentry dashboard: Settings → Integrations → Slack
3. Paste webhook URL (or OAuth connection for richer notifications)
4. Configure alert rules to use Slack channel
5. Test integration

**Benefits**: Reuse existing Slack infrastructure, centralised notifications, richer Slack cards with Sentry

---

## Critical Files to Modify

### Backend (Required)

- `composer.json` - Add Sentry Laravel SDK
- `config/sentry.php` - Created by `php artisan sentry:publish`
- `.env` - Add `SENTRY_LARAVEL_DSN` and `SENTRY_TRACES_SAMPLE_RATE`
- `.env.example` - Add Sentry environment variables
- `app/Services/N8nClient.php` (lines 56-67, 130-150) - Add Sentry context to circuit breaker and rate limiting
- `routes/api.php` (lines 157-178) - Add Sentry context to webhook errors
- `bootstrap/app.php` (lines 50-78) - Add global exception handler with Sentry context

### Frontend (Essential for Free Tier Value)

- `package.json` - Add @sentry/vue dependency
- `resources/js/app.ts` - Initialize Sentry for Vue 3 with session replay
- `.env` - Add `VITE_SENTRY_DSN_PUBLIC` and `VITE_APP_ENV`
- `.env.example` - Add Sentry frontend environment variables

### Configuration (Sentry Dashboard)

- Set up alert rules for critical errors
- Configure Slack integration
- Add team members
- Configure release tracking (optional)

---

## Implementation Order

### Week 1: Backend Setup (Essential)

1. Sign up for free Sentry account at https://sentry.io/signup/
2. Create new Laravel project in Sentry dashboard
3. Install Sentry Laravel SDK (`composer require sentry/sentry-laravel`)
4. Publish configuration (`php artisan sentry:publish --dsn`)
5. Add `SENTRY_LARAVEL_DSN` to `.env` and `.env.example`
6. Run `php artisan sentry:test` to verify setup

**Time Estimate**: 30-45 minutes

### Week 2: Enhanced Context (High Value)

7. Add Sentry context to N8nClient circuit breaker (lines 56-67)
8. Add Sentry context to N8nClient rate limiting (lines 130-150)
9. Add Sentry context to webhook receiver (lines 157-178)
10. Add global exception handler in `bootstrap/app.php`
11. Test error reporting with intentional exceptions

**Time Estimate**: 1-2 hours

### Week 2-3: Frontend Tracking (Essential - Maximise Free Tier Value!)

12. Install `@sentry/vue` package
13. Initialize Sentry in `resources/js/app.ts` with session replay
14. Add `VITE_SENTRY_DSN_PUBLIC` to environment
15. Build and test frontend error reporting
16. Trigger test error to verify session replay works

**Time Estimate**: 1 hour

### Week 3: Alerting Setup (Critical)

17. Configure Sentry dashboard alert rules
18. Connect Slack webhook (reuse existing `LOG_SLACK_WEBHOOK_URL`)
19. Set up email alerts for critical errors
20. Test alerts with sample errors
21. Configure alert throttling to avoid spam

**Time Estimate**: 1 hour

---

## Testing Strategy

### Backend Testing

#### Test 1: Circuit Breaker Error

```php
// Trigger circuit breaker manually
Cache::put('n8n_circuit_breaker_failures', 5);
app(\App\Services\N8nClient::class)->triggerWebhook('/test-webhook', []);

// Expected: Error logged to Flare with circuit breaker context
```

#### Test 2: Rate Limit Error

```php
// Mock 429 response from n8n
// Expected: Rate limit logged to Flare, circuit breaker extended to 15 minutes
```

#### Test 3: Database Error

```php
// Trigger database constraint violation
// Expected: QueryException logged to Flare with full context
```

#### Test 4: Webhook Validation Error

```bash
# Send invalid webhook payload
curl -X POST http://localhost/api/n8n/webhook \
  -H "X-N8N-SECRET: wrong-secret" \
  -H "Content-Type: application/json" \
  -d '{"invalid": "data"}'

# Expected: Validation error logged to Flare
```

### Frontend Testing (If Implemented)

#### Test 1: Vue Component Error

```typescript
// Add to a Vue component
throw new Error('Test Flare frontend error');

// Expected: Error appears in Flare dashboard
```

#### Test 2: Unhandled Promise Rejection

```typescript
// Add to a component
Promise.reject('Test async error');

// Expected: Promise rejection logged to Flare
```

### Alert Testing

#### Test 1: Slack Notification

```php
// Trigger critical error
\Log::critical('Test Flare Slack integration', [
    'test' => true,
    'timestamp' => now(),
]);

// Expected: Slack message received in configured channel
```

#### Test 2: Email Alert

```php
// Trigger email alert
throw new \RuntimeException('Test Flare email alert');

// Expected: Email received at configured address
```

---

## Success Metrics

After implementation, you should be able to:

### Visibility (Week 1-2)

✅ See all production errors in Flare dashboard

✅ View full stack traces with code context

✅ Group similar errors together

✅ Track error frequency and trends

✅ Identify error patterns by user, route, or time

### Context (Week 2-3)

✅ See circuit breaker status for every error

✅ Track which users trigger rate limits

✅ Identify problematic n8n workflow nodes

✅ View database query context for SQL errors

✅ See request headers, session data, and user details

### Alerting (Week 3-4)

✅ Receive Slack notifications for critical errors

✅ Get email alerts for circuit breaker events

✅ Track rate limit hits in real-time

✅ Monitor database errors proactively

✅ Set up escalation rules for recurring errors

### Performance (Week 4+)

✅ Use Flare performance monitoring (free beta) to track:

- n8n workflow execution times
- Database query performance
- Queue job processing times
- HTTP request/response times

---

## Cost Estimation

### Sentry Free Tier (Recommended Start)

- **Year 1-2**: $0/month (Free tier, 5,000 errors/month)
- **Spike protection**: Won't be cut off if temporarily over quota
- **Total Cost**: $0

### If You Exceed Free Tier

#### Sentry Team Plan (Most Likely Upgrade Path)

- **Year 1** (if needed): $26/month × 12 = $312
- **Year 2** (steady state): $26/month × 12 = $312
- **Features added**: 50K errors/month, session replay, performance monitoring

#### Alternative: Laravel Flare (If Budget Constrained)

- **Year 1**: $9/month × 6 + $29/month × 6 = $228
- **Year 2**: $29/month × 12 = $348
- **Trade-off**: Cheaper but no session replay, smaller ecosystem

### Realistic Projection

**Months 1-12**: Stay on free tier ($0/month)

**Month 13+**: Evaluate based on error volume:

- If under 5K/month: Stay free forever
- If 5K-50K/month: Upgrade to Sentry Team ($26/month)
- If need Laravel-specific features: Switch to Flare ($9-29/month)

**Most Likely Year 1 Cost**: $0 (free tier)

**Most Likely Year 2 Cost**: $0-312 (depends on growth)

---

## Integration with Existing Infrastructure

### What to Keep (Already Implemented)

✅ **N8nClient circuit breaker** - Sentry enhances with visibility and tags

✅ **Structured error logging** - Sentry automatically captures log context

✅ **Enhanced webhook validation** - Sentry groups validation errors

✅ **Rate limit detection** - Sentry tracks rate limit patterns with tags

✅ **Laravel Horizon** - Sentry integrates with queue monitoring

### What to Add (New Capabilities)

🆕 **Error aggregation** - Group similar errors automatically

🆕 **Visual dashboards** - See error trends over time

🆕 **Proactive alerts** - Get notified before users complain

🆕 **Code context** - See exact line of code that failed

🆕 **Session replay** - Watch exactly what user did before error (free tier!)

🆕 **Distributed tracing** - Connect frontend errors to backend failures

🆕 **Performance insights** - Track slow queries, API calls (with Team plan)

### What NOT to Remove

❌ **Don't remove Log::error() calls** - Sentry enhances them, doesn't replace them

❌ **Don't remove try-catch blocks** - Keep defensive programming

❌ **Don't remove circuit breaker** - Sentry monitors it, doesn't replace it

❌ **Don't remove webhook validation** - Sentry tracks failures, doesn't prevent them

---

## Rollback Plan

If Sentry doesn't meet expectations or causes issues:

### Quick Rollback (Within 1 Hour)

1. Remove `SENTRY_LARAVEL_DSN` from `.env`
2. Errors still logged to Laravel log files
3. No functionality lost (Sentry is purely additive)
4. Frontend will continue working (Sentry init is conditional)

### Full Removal (Within 1 Day)

1. Remove Sentry packages: `composer remove sentry/sentry-laravel` and `npm uninstall @sentry/vue`
2. Remove Sentry context from N8nClient, webhook receiver, bootstrap/app.php
3. Remove Sentry initialization from `resources/js/app.ts`
4. Delete `config/sentry.php`
5. Revert to Laravel's default error handling
6. Re-evaluate alternative solutions (Flare, Bugsnag, etc.)

**Risk**: Minimal - Sentry is non-intrusive and doesn't change application behavior

---

## Next Steps After Implementation

### Month 2-3: Optimise Alerting

- Review alert frequency (too many? too few?)
- Adjust throttling rules
- Add custom error grouping rules (fingerprinting)
- Create error resolution runbooks
- Set up issue auto-assignment based on error tags

### Month 4-6: Maximise Free Tier Value

- Review session replays to identify UX issues
- Use distributed tracing to debug frontend → backend errors
- Set up release tracking for deployment correlation
- Create custom Sentry dashboards
- Integrate with GitHub for commit tracking

### Month 7+: Evaluate Usage

- Check if still under 5K errors/month (stay free)
- If exceeding quota, evaluate:
    - Upgrade to Sentry Team ($26/month) for 50K errors + performance monitoring
    - Switch to Flare ($9/month) if budget-constrained and don't need session replay
- Integrate with Jira/Linear for automatic issue creation
- Set up error dashboards for stakeholders

---

## Summary

**Recommended Solution**: Sentry Free Tier

**Why**:

- Completely free for small projects (5,000 errors/month)
- Industry-standard tool (used by Fortune 500 companies)
- Official Laravel partnership (2025)
- Full-stack monitoring (Laravel + Vue 3)
- Session replay included on free tier
- Distributed tracing to connect frontend → backend errors
- Upgrade path available when needed

**Key Advantages Over Paid Solutions**:

- $0/month vs $9-26/month for alternatives
- Session replay (normally premium feature)
- Proven at scale
- Larger ecosystem and community
- Better documentation

**When to Upgrade to Sentry Team ($26/month)**:

- Consistently exceed 5,000 errors/month
- Need performance monitoring (APM)
- Want multiple projects (staging + production)
- Need priority support

**Alternative If Budget Becomes Issue**:

- Laravel Flare ($9/month) - Cheaper than Sentry Team, Laravel-specific, AI debugging

**Implementation Priority**:

1. **Week 1**: Install Sentry backend (30-45 mins) ✅ Essential
2. **Week 2**: Add context to errors (1-2 hours) ✅ High value
3. **Week 2-3**: Frontend tracking with session replay (1 hour) ✅ Essential (maximise free tier!)
4. **Week 3**: Configure alerts (1 hour) ✅ Critical

**Total Time Investment**: 3.5-5 hours
**Total Cost (Year 1)**: $0 (free tier)
**Total Cost (Year 2)**: $0-312 (depends on growth)
**Value**: Proactive error detection, visual debugging with session replay, faster troubleshooting, better user
experience - all at zero cost
