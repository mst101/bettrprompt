# Customer Data Platform (CDP) Implementation Plan

## Executive Summary

BettrPrompt already has excellent data collection foundations. This plan extends these into a full in-house CDP capable of:
- Unified customer profiles (visitor → user → subscriber journey)
- Event-based behavioural tracking
- Attribution and conversion analytics
- Cohort analysis and segmentation
- Real-time dashboards

---

## Current State Assessment

### What We Have (Strong Foundation)

| Component | Status | Quality |
|-----------|--------|---------|
| **Visitor Tracking** | ✅ Complete | Excellent - UUID, UTM, referrer, geolocation |
| **User Profiles** | ✅ Complete | Excellent - 40+ fields, personality, professional |
| **Attribution** | ✅ Complete | Good - UTM params, referral codes |
| **Activity Tracking** | ⚠️ Partial | Only prompt_runs tracked |
| **Email Engagement** | ✅ Complete | Full Mailgun webhook integration |
| **Payment Events** | ✅ Complete | Stripe webhooks |
| **Session Recording** | ✅ External | FullStory (production only) |

### What's Missing (CDP Gaps)

| Component | Status | Impact |
|-----------|--------|--------|
| **Page View Tracking** | ❌ Missing | Can't analyse user journeys |
| **Event System** | ❌ Missing | No granular interaction tracking |
| **Session Analytics** | ❌ Missing | No time-on-site, bounce rate |
| **Funnel Analysis** | ❌ Missing | Can't identify drop-off points |
| **Cohort Engine** | ❌ Missing | Can't segment users effectively |
| **Analytics Dashboard** | ❌ Missing | No internal reporting |
| **Data Aggregations** | ❌ Missing | Real-time metrics unavailable |

---

## Recommended Architecture

### Data Model Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        CDP Data Layer                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐  │
│  │ visitors │───▶│  users   │───▶│subscribers│───▶│ churned  │  │
│  └──────────┘    └──────────┘    └──────────┘    └──────────┘  │
│       │               │               │               │          │
│       ▼               ▼               ▼               ▼          │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    analytics_events                       │   │
│  │  (page_views, clicks, form_submits, feature_usage, etc)  │   │
│  └──────────────────────────────────────────────────────────┘   │
│       │                                                          │
│       ▼                                                          │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                    analytics_sessions                     │   │
│  │  (session grouping, duration, page count, entry/exit)    │   │
│  └──────────────────────────────────────────────────────────┘   │
│       │                                                          │
│       ▼                                                          │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │                 analytics_aggregations                    │   │
│  │  (hourly/daily rollups, cohort metrics, funnel stats)    │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Event Tracking Foundation

### 1.1 Create Events Table

**Migration: `create_analytics_events_table`**

```php
Schema::create('analytics_events', function (Blueprint $table) {
    $table->id();
    $table->uuid('session_id')->index();
    $table->uuid('visitor_id')->nullable()->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Event identification
    $table->string('event_type', 50)->index(); // page_view, click, form_submit, feature_use
    $table->string('event_name', 100)->index(); // specific event: pricing_page_view, cta_click

    // Context
    $table->string('page_url', 500)->nullable();
    $table->string('page_path', 255)->nullable()->index();
    $table->string('page_title', 255)->nullable();
    $table->string('referrer_url', 500)->nullable();

    // Element tracking (for clicks)
    $table->string('element_id', 100)->nullable();
    $table->string('element_class', 255)->nullable();
    $table->string('element_text', 255)->nullable();

    // Custom properties (flexible JSON for any event-specific data)
    $table->json('properties')->nullable();

    // Device/browser (denormalised for query performance)
    $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();

    // Geolocation (denormalised)
    $table->string('country_code', 2)->nullable();
    $table->string('region', 100)->nullable();

    // Timestamps
    $table->timestamp('occurred_at')->index();
    $table->timestamps();

    // Composite indexes for common queries
    $table->index(['visitor_id', 'occurred_at']);
    $table->index(['user_id', 'occurred_at']);
    $table->index(['event_type', 'occurred_at']);
    $table->index(['page_path', 'occurred_at']);
});
```

### 1.2 Create Sessions Table

**Migration: `create_analytics_sessions_table`**

```php
Schema::create('analytics_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Session metrics
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->unsignedInteger('duration_seconds')->nullable();
    $table->unsignedSmallInteger('page_count')->default(0);
    $table->unsignedSmallInteger('event_count')->default(0);

    // Entry/exit pages
    $table->string('entry_page', 255)->nullable();
    $table->string('exit_page', 255)->nullable();

    // Attribution (snapshot at session start)
    $table->string('utm_source', 100)->nullable();
    $table->string('utm_medium', 100)->nullable();
    $table->string('utm_campaign', 100)->nullable();
    $table->string('referrer_domain', 255)->nullable();

    // Device info
    $table->string('device_type', 20)->nullable();
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();
    $table->string('country_code', 2)->nullable();

    // Conversion tracking
    $table->boolean('converted')->default(false);
    $table->string('conversion_type', 50)->nullable(); // registered, subscribed_pro, subscribed_private

    // Bounce detection
    $table->boolean('is_bounce')->default(true); // Set false after 2nd page view

    $table->timestamps();

    // Indexes
    $table->index(['visitor_id', 'started_at']);
    $table->index(['started_at']);
    $table->index(['converted', 'started_at']);
});
```

### 1.3 Event Types to Track

| Event Type | Event Name | Properties | Trigger |
|------------|------------|------------|---------|
| `page_view` | `{page_name}_view` | title, load_time_ms | Every page load |
| `click` | `{element}_click` | element_id, element_text, href | Key CTAs |
| `form_submit` | `{form}_submit` | form_name, success | Form submissions |
| `feature_use` | `{feature}_use` | feature-specific data | Feature interactions |
| `error` | `{error_type}_error` | message, stack | Client errors |
| `conversion` | `{type}_conversion` | tier, value | Registration, subscription |
| `scroll` | `scroll_depth` | depth_percent (25/50/75/100) | Scroll milestones |

### 1.4 Key Events to Implement

**Critical Conversion Events:**
```typescript
// Registration funnel
'landing_page_view'
'pricing_page_view'
'register_modal_open'
'register_form_submit'
'registration_complete'

// Subscription funnel
'pricing_tier_view' // { tier: 'pro' | 'private' }
'billing_toggle_click' // { selected: 'monthly' | 'yearly' }
'currency_switch' // { from: 'GBP', to: 'EUR' }
'subscribe_button_click' // { tier, interval }
'checkout_redirect' // { tier, interval, price }
'subscription_success' // { tier, interval }

// Prompt generation funnel
'prompt_builder_start'
'personality_selected'
'task_submitted'
'pre_analysis_complete'
'clarifying_questions_answered'
'prompt_generated'
'prompt_copied'
'prompt_saved'

// Engagement events
'faq_item_expand' // { question }
'help_tooltip_view'
'error_displayed' // { error_type, message }
```

---

## Phase 2: Frontend Tracking Implementation

### 2.1 Analytics Composable

**File: `resources/js/Composables/useAnalytics.ts`**

```typescript
import { usePage, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

interface EventProperties {
    [key: string]: string | number | boolean | null;
}

interface AnalyticsContext {
    sessionId: string;
    visitorId: string | null;
    userId: number | null;
}

const SESSION_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes
const SESSION_KEY = 'analytics_session_id';
const LAST_ACTIVITY_KEY = 'analytics_last_activity';

export function useAnalytics() {
    const page = usePage();
    const context = ref<AnalyticsContext | null>(null);
    const eventQueue: Array<{ event: string; properties: EventProperties; timestamp: number }> = [];
    let flushTimer: ReturnType<typeof setTimeout> | null = null;

    function getOrCreateSession(): string {
        const now = Date.now();
        const lastActivity = parseInt(localStorage.getItem(LAST_ACTIVITY_KEY) || '0', 10);
        let sessionId = localStorage.getItem(SESSION_KEY);

        // Create new session if expired or doesn't exist
        if (!sessionId || now - lastActivity > SESSION_TIMEOUT_MS) {
            sessionId = crypto.randomUUID();
            localStorage.setItem(SESSION_KEY, sessionId);
        }

        localStorage.setItem(LAST_ACTIVITY_KEY, now.toString());
        return sessionId;
    }

    function initContext(): AnalyticsContext {
        return {
            sessionId: getOrCreateSession(),
            visitorId: (page.props.visitor as { id?: string })?.id || null,
            userId: (page.props.auth as { user?: { id: number } })?.user?.id || null,
        };
    }

    function track(eventName: string, properties: EventProperties = {}) {
        if (!context.value) {
            context.value = initContext();
        }

        const event = {
            event: eventName,
            properties: {
                ...properties,
                page_path: window.location.pathname,
                page_url: window.location.href,
            },
            timestamp: Date.now(),
        };

        eventQueue.push(event);

        // Debounce flush
        if (flushTimer) clearTimeout(flushTimer);
        flushTimer = setTimeout(() => flush(), 1000);
    }

    function flush() {
        if (eventQueue.length === 0) return;

        const events = eventQueue.splice(0, eventQueue.length);

        // Use sendBeacon for reliability (works during page unload)
        const payload = JSON.stringify({
            session_id: context.value?.sessionId,
            visitor_id: context.value?.visitorId,
            user_id: context.value?.userId,
            events,
        });

        if (navigator.sendBeacon) {
            navigator.sendBeacon('/api/analytics/events', payload);
        } else {
            fetch('/api/analytics/events', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true,
            });
        }
    }

    // Auto-track page views on Inertia navigation
    function setupPageTracking() {
        // Track initial page view
        track('page_view', {
            title: document.title,
        });

        // Track subsequent Inertia navigations
        router.on('navigate', (event) => {
            track('page_view', {
                title: document.title,
                referrer: document.referrer,
            });
        });
    }

    // Track scroll depth
    function setupScrollTracking() {
        const milestones = [25, 50, 75, 100];
        const tracked = new Set<number>();

        function checkScroll() {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const scrollPercent = Math.round((scrollTop / docHeight) * 100);

            for (const milestone of milestones) {
                if (scrollPercent >= milestone && !tracked.has(milestone)) {
                    tracked.add(milestone);
                    track('scroll_depth', { depth_percent: milestone });
                }
            }
        }

        window.addEventListener('scroll', checkScroll, { passive: true });
        return () => window.removeEventListener('scroll', checkScroll);
    }

    // Track visibility (time on page)
    function setupVisibilityTracking() {
        let hiddenAt: number | null = null;

        function handleVisibility() {
            if (document.hidden) {
                hiddenAt = Date.now();
            } else if (hiddenAt) {
                const hiddenDuration = Date.now() - hiddenAt;
                if (hiddenDuration > 1000) {
                    track('tab_return', { hidden_duration_ms: hiddenDuration });
                }
                hiddenAt = null;
            }
        }

        document.addEventListener('visibilitychange', handleVisibility);
        return () => document.removeEventListener('visibilitychange', handleVisibility);
    }

    // Flush on page unload
    function setupUnloadTracking() {
        function handleUnload() {
            flush();
        }

        window.addEventListener('beforeunload', handleUnload);
        window.addEventListener('pagehide', handleUnload);

        return () => {
            window.removeEventListener('beforeunload', handleUnload);
            window.removeEventListener('pagehide', handleUnload);
        };
    }

    // Click tracking for elements with data-track attribute
    function setupClickTracking() {
        function handleClick(e: MouseEvent) {
            const target = e.target as HTMLElement;
            const trackable = target.closest('[data-track]') as HTMLElement | null;

            if (trackable) {
                const eventName = trackable.dataset.track || 'element_click';
                const properties: EventProperties = {};

                // Collect all data-track-* attributes
                for (const [key, value] of Object.entries(trackable.dataset)) {
                    if (key.startsWith('track') && key !== 'track') {
                        const propName = key.replace('track', '').toLowerCase();
                        properties[propName] = value;
                    }
                }

                properties.element_id = trackable.id || null;
                properties.element_text = trackable.textContent?.slice(0, 100) || null;

                track(eventName, properties);
            }
        }

        document.addEventListener('click', handleClick);
        return () => document.removeEventListener('click', handleClick);
    }

    return {
        track,
        flush,
        setupPageTracking,
        setupScrollTracking,
        setupVisibilityTracking,
        setupUnloadTracking,
        setupClickTracking,
    };
}
```

### 2.2 Analytics Provider Component

**File: `resources/js/Components/AnalyticsProvider.vue`**

```vue
<script setup lang="ts">
import { onMounted, onUnmounted, provide } from 'vue';
import { useAnalytics } from '@/Composables/useAnalytics';
import { useCookieConsent } from '@/Composables/useCookieConsent';

const analytics = useAnalytics();
const { hasConsent } = useCookieConsent();

const cleanupFns: Array<() => void> = [];

onMounted(() => {
    // Only track if user has consented to analytics cookies
    if (!hasConsent('analytics')) return;

    analytics.setupPageTracking();
    cleanupFns.push(analytics.setupScrollTracking());
    cleanupFns.push(analytics.setupVisibilityTracking());
    cleanupFns.push(analytics.setupUnloadTracking());
    cleanupFns.push(analytics.setupClickTracking());
});

onUnmounted(() => {
    cleanupFns.forEach(fn => fn());
});

// Provide track function to child components
provide('analytics', {
    track: analytics.track,
});
</script>

<template>
    <slot />
</template>
```

### 2.3 Declarative Tracking in Templates

```vue
<!-- Automatic click tracking via data attributes -->
<button
    data-track="subscribe_click"
    data-track-tier="pro"
    data-track-interval="yearly"
    @click="subscribe('pro')"
>
    Subscribe to Pro
</button>

<!-- Or use the composable directly -->
<script setup>
import { inject } from 'vue';
const { track } = inject('analytics');

function handleFormSubmit() {
    track('contact_form_submit', {
        subject: form.subject,
    });
}
</script>
```

---

## Phase 3: Backend Event Collection

### 3.1 Analytics Event Controller

**File: `app/Http/Controllers/Api/AnalyticsController.php`**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAnalyticsEvents;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class AnalyticsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_id' => ['required', 'uuid'],
            'visitor_id' => ['nullable', 'uuid'],
            'user_id' => ['nullable', 'integer'],
            'events' => ['required', 'array', 'max:50'],
            'events.*.event' => ['required', 'string', 'max:100'],
            'events.*.properties' => ['nullable', 'array'],
            'events.*.timestamp' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        // Parse user agent
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $deviceInfo = [
            'device_type' => $agent->isDesktop() ? 'desktop' : ($agent->isMobile() ? 'mobile' : 'tablet'),
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
        ];

        // Get country from request (set by middleware or geolocation)
        $countryCode = $request->header('CF-IPCountry') // Cloudflare
            ?? session('country_code')
            ?? null;

        // Dispatch job for async processing
        ProcessAnalyticsEvents::dispatch(
            $request->input('session_id'),
            $request->input('visitor_id'),
            $request->input('user_id'),
            $request->input('events'),
            $deviceInfo,
            $countryCode,
            $request->ip()
        );

        return response()->json(['success' => true]);
    }
}
```

### 3.2 Async Event Processing Job

**File: `app/Jobs/ProcessAnalyticsEvents.php`**

```php
<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessAnalyticsEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $sessionId,
        private ?string $visitorId,
        private ?int $userId,
        private array $events,
        private array $deviceInfo,
        private ?string $countryCode,
        private string $ipAddress,
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            // Ensure session exists
            $session = $this->ensureSession();

            // Insert events
            $eventRecords = [];
            $pageViewCount = 0;

            foreach ($this->events as $event) {
                $occurredAt = Carbon::createFromTimestampMs($event['timestamp']);
                $properties = $event['properties'] ?? [];

                $eventRecords[] = [
                    'session_id' => $this->sessionId,
                    'visitor_id' => $this->visitorId,
                    'user_id' => $this->userId,
                    'event_type' => $this->getEventType($event['event']),
                    'event_name' => $event['event'],
                    'page_url' => $properties['page_url'] ?? null,
                    'page_path' => $properties['page_path'] ?? null,
                    'page_title' => $properties['title'] ?? null,
                    'properties' => json_encode($properties),
                    'device_type' => $this->deviceInfo['device_type'],
                    'browser' => $this->deviceInfo['browser'],
                    'os' => $this->deviceInfo['os'],
                    'country_code' => $this->countryCode,
                    'occurred_at' => $occurredAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if ($event['event'] === 'page_view') {
                    $pageViewCount++;
                }

                // Update session exit page
                if (isset($properties['page_path'])) {
                    $session->exit_page = $properties['page_path'];
                }
            }

            // Bulk insert events
            AnalyticsEvent::insert($eventRecords);

            // Update session metrics
            $session->page_count += $pageViewCount;
            $session->event_count += count($eventRecords);
            $session->ended_at = now();
            $session->duration_seconds = $session->started_at->diffInSeconds(now());

            // No longer a bounce if more than 1 page view
            if ($session->page_count > 1) {
                $session->is_bounce = false;
            }

            $session->save();
        });
    }

    private function ensureSession(): AnalyticsSession
    {
        return AnalyticsSession::firstOrCreate(
            ['id' => $this->sessionId],
            [
                'visitor_id' => $this->visitorId,
                'user_id' => $this->userId,
                'started_at' => now(),
                'entry_page' => $this->events[0]['properties']['page_path'] ?? null,
                'device_type' => $this->deviceInfo['device_type'],
                'browser' => $this->deviceInfo['browser'],
                'os' => $this->deviceInfo['os'],
                'country_code' => $this->countryCode,
            ]
        );
    }

    private function getEventType(string $eventName): string
    {
        if (str_ends_with($eventName, '_view') || $eventName === 'page_view') {
            return 'page_view';
        }
        if (str_ends_with($eventName, '_click')) {
            return 'click';
        }
        if (str_ends_with($eventName, '_submit')) {
            return 'form_submit';
        }
        if (str_ends_with($eventName, '_conversion')) {
            return 'conversion';
        }
        return 'custom';
    }
}
```

---

## Phase 4: Aggregations and Metrics

### 4.1 Daily Aggregations Table

**Migration: `create_analytics_daily_stats_table`**

```php
Schema::create('analytics_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();

    // Traffic metrics
    $table->unsignedInteger('visitors')->default(0);
    $table->unsignedInteger('sessions')->default(0);
    $table->unsignedInteger('page_views')->default(0);
    $table->unsignedInteger('unique_page_views')->default(0);

    // Engagement metrics
    $table->unsignedInteger('avg_session_duration_seconds')->default(0);
    $table->unsignedInteger('avg_pages_per_session')->default(0);
    $table->decimal('bounce_rate', 5, 2)->default(0);

    // Conversion metrics
    $table->unsignedInteger('registrations')->default(0);
    $table->unsignedInteger('pro_subscriptions')->default(0);
    $table->unsignedInteger('private_subscriptions')->default(0);
    $table->unsignedInteger('prompts_created')->default(0);
    $table->unsignedInteger('prompts_completed')->default(0);

    // Revenue (from Stripe, in pence/cents)
    $table->unsignedInteger('revenue_gbp')->default(0);
    $table->unsignedInteger('revenue_eur')->default(0);
    $table->unsignedInteger('revenue_usd')->default(0);

    // Traffic sources (top 5 stored as JSON)
    $table->json('top_sources')->nullable();
    $table->json('top_landing_pages')->nullable();
    $table->json('top_countries')->nullable();

    $table->timestamps();

    $table->unique('date');
});
```

### 4.2 Aggregation Command

**File: `app/Console/Commands/AggregateAnalytics.php`**

```php
<?php

namespace App\Console\Commands;

use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsEvent;
use App\Models\AnalyticsSession;
use App\Models\PromptRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateAnalytics extends Command
{
    protected $signature = 'analytics:aggregate {date? : Date to aggregate (Y-m-d), defaults to yesterday}';
    protected $description = 'Aggregate analytics data for a specific date';

    public function handle(): int
    {
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))
            : Carbon::yesterday();

        $this->info("Aggregating analytics for {$date->format('Y-m-d')}");

        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        // Session metrics
        $sessions = AnalyticsSession::whereBetween('started_at', [$startOfDay, $endOfDay]);
        $sessionStats = $sessions->selectRaw('
            COUNT(*) as total_sessions,
            COUNT(DISTINCT visitor_id) as unique_visitors,
            AVG(duration_seconds) as avg_duration,
            AVG(page_count) as avg_pages,
            SUM(CASE WHEN is_bounce THEN 1 ELSE 0 END) as bounces,
            SUM(CASE WHEN converted THEN 1 ELSE 0 END) as conversions
        ')->first();

        // Page views
        $pageViews = AnalyticsEvent::whereBetween('occurred_at', [$startOfDay, $endOfDay])
            ->where('event_type', 'page_view')
            ->count();

        // Registrations
        $registrations = User::whereBetween('created_at', [$startOfDay, $endOfDay])->count();

        // Subscriptions
        $subscriptions = DB::table('subscriptions')
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();

        // Prompt metrics
        $promptsCreated = PromptRun::whereBetween('created_at', [$startOfDay, $endOfDay])->count();
        $promptsCompleted = PromptRun::whereBetween('completed_at', [$startOfDay, $endOfDay])->count();

        // Top sources
        $topSources = AnalyticsSession::whereBetween('started_at', [$startOfDay, $endOfDay])
            ->whereNotNull('utm_source')
            ->groupBy('utm_source')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->pluck(DB::raw('COUNT(*) as count'), 'utm_source');

        // Top landing pages
        $topLandingPages = AnalyticsSession::whereBetween('started_at', [$startOfDay, $endOfDay])
            ->whereNotNull('entry_page')
            ->groupBy('entry_page')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->pluck(DB::raw('COUNT(*) as count'), 'entry_page');

        // Top countries
        $topCountries = AnalyticsSession::whereBetween('started_at', [$startOfDay, $endOfDay])
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->limit(5)
            ->pluck(DB::raw('COUNT(*) as count'), 'country_code');

        // Calculate bounce rate
        $bounceRate = $sessionStats->total_sessions > 0
            ? ($sessionStats->bounces / $sessionStats->total_sessions) * 100
            : 0;

        // Upsert daily stats
        AnalyticsDailyStat::updateOrCreate(
            ['date' => $date->format('Y-m-d')],
            [
                'visitors' => $sessionStats->unique_visitors ?? 0,
                'sessions' => $sessionStats->total_sessions ?? 0,
                'page_views' => $pageViews,
                'avg_session_duration_seconds' => round($sessionStats->avg_duration ?? 0),
                'avg_pages_per_session' => round($sessionStats->avg_pages ?? 0),
                'bounce_rate' => round($bounceRate, 2),
                'registrations' => $registrations,
                'prompts_created' => $promptsCreated,
                'prompts_completed' => $promptsCompleted,
                'top_sources' => $topSources->toJson(),
                'top_landing_pages' => $topLandingPages->toJson(),
                'top_countries' => $topCountries->toJson(),
            ]
        );

        $this->info('Aggregation complete.');

        return Command::SUCCESS;
    }
}
```

### 4.3 Schedule Aggregation

**File: `routes/console.php`**

```php
Schedule::command('analytics:aggregate')->dailyAt('02:00');
```

---

## Phase 5: Analytics Dashboard

### 5.1 Dashboard Controller

**File: `app/Http/Controllers/Admin/AnalyticsDashboardController.php`**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsDailyStat;
use App\Models\AnalyticsSession;
use App\Models\PromptRun;
use App\Models\User;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $request->input('period', '7d');
        $startDate = match ($period) {
            '24h' => Carbon::now()->subDay(),
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            default => Carbon::now()->subDays(7),
        };

        // Real-time metrics
        $realtime = [
            'active_sessions' => AnalyticsSession::where('ended_at', '>', now()->subMinutes(5))->count(),
            'today_visitors' => AnalyticsSession::whereDate('started_at', today())
                ->distinct('visitor_id')
                ->count('visitor_id'),
            'today_page_views' => AnalyticsEvent::whereDate('occurred_at', today())
                ->where('event_type', 'page_view')
                ->count(),
        ];

        // Historical data from aggregations
        $dailyStats = AnalyticsDailyStat::where('date', '>=', $startDate->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        // Summary metrics
        $summary = [
            'total_visitors' => $dailyStats->sum('visitors'),
            'total_sessions' => $dailyStats->sum('sessions'),
            'total_page_views' => $dailyStats->sum('page_views'),
            'avg_bounce_rate' => round($dailyStats->avg('bounce_rate'), 1),
            'total_registrations' => $dailyStats->sum('registrations'),
            'total_prompts' => $dailyStats->sum('prompts_created'),
        ];

        // Funnel data
        $funnel = $this->calculateFunnel($startDate);

        // Top content
        $topPages = $this->getTopPages($startDate);

        return Inertia::render('Admin/Analytics/Dashboard', [
            'period' => $period,
            'realtime' => $realtime,
            'summary' => $summary,
            'dailyStats' => $dailyStats,
            'funnel' => $funnel,
            'topPages' => $topPages,
        ]);
    }

    private function calculateFunnel(Carbon $startDate): array
    {
        $visitors = Visitor::where('created_at', '>=', $startDate)->count();
        $registered = User::where('created_at', '>=', $startDate)->count();
        $promptStarted = PromptRun::where('created_at', '>=', $startDate)->distinct('user_id')->count('user_id');
        $promptCompleted = PromptRun::where('completed_at', '>=', $startDate)->distinct('user_id')->count('user_id');
        $subscribed = User::where('created_at', '>=', $startDate)
            ->whereIn('subscription_tier', ['pro', 'private'])
            ->count();

        return [
            ['stage' => 'Visitors', 'count' => $visitors, 'rate' => 100],
            ['stage' => 'Registered', 'count' => $registered, 'rate' => $visitors > 0 ? round(($registered / $visitors) * 100, 1) : 0],
            ['stage' => 'Started Prompt', 'count' => $promptStarted, 'rate' => $registered > 0 ? round(($promptStarted / $registered) * 100, 1) : 0],
            ['stage' => 'Completed Prompt', 'count' => $promptCompleted, 'rate' => $promptStarted > 0 ? round(($promptCompleted / $promptStarted) * 100, 1) : 0],
            ['stage' => 'Subscribed', 'count' => $subscribed, 'rate' => $promptCompleted > 0 ? round(($subscribed / $promptCompleted) * 100, 1) : 0],
        ];
    }

    private function getTopPages(Carbon $startDate): array
    {
        return AnalyticsEvent::where('occurred_at', '>=', $startDate)
            ->where('event_type', 'page_view')
            ->whereNotNull('page_path')
            ->groupBy('page_path')
            ->orderByDesc(\DB::raw('COUNT(*)'))
            ->limit(10)
            ->get(['page_path', \DB::raw('COUNT(*) as views')])
            ->toArray();
    }
}
```

---

## Phase 6: Cohort Analysis

### 6.1 Cohort Table

**Migration: `create_analytics_cohorts_table`**

```php
Schema::create('analytics_cohorts', function (Blueprint $table) {
    $table->id();
    $table->date('cohort_date')->index(); // Week/month the cohort was created
    $table->string('cohort_type', 20); // 'weekly', 'monthly'
    $table->unsignedInteger('cohort_size')->default(0);

    // Retention by period (0 = same week/month, 1 = next week/month, etc.)
    $table->json('retention'); // {"0": 100, "1": 45, "2": 32, ...}

    // Revenue by period
    $table->json('revenue'); // {"0": 0, "1": 500, "2": 200, ...}

    // Conversion by period
    $table->json('conversions'); // {"0": 0, "1": 5, "2": 2, ...}

    $table->timestamps();

    $table->unique(['cohort_date', 'cohort_type']);
});
```

### 6.2 Cohort Analysis Command

```php
// app/Console/Commands/CalculateCohorts.php
// Calculates weekly/monthly cohort retention based on:
// - Users who returned (had sessions)
// - Users who created prompts
// - Users who converted to paid
```

---

## Phase 7: Segmentation Engine

### 7.1 Segments Table

```php
Schema::create('analytics_segments', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('description')->nullable();
    $table->json('conditions'); // Filter conditions
    $table->boolean('is_dynamic')->default(true);
    $table->unsignedInteger('cached_count')->nullable();
    $table->timestamp('cached_at')->nullable();
    $table->timestamps();
});

Schema::create('analytics_segment_users', function (Blueprint $table) {
    $table->foreignId('segment_id')->constrained('analytics_segments')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamp('added_at');
    $table->primary(['segment_id', 'user_id']);
});
```

### 7.2 Predefined Segments

| Segment | Conditions |
|---------|------------|
| **Power Users** | prompts_created >= 10 AND last_active_at > 7 days ago |
| **At-Risk Paid** | subscription_tier IN ('pro', 'private') AND last_active_at < 14 days ago |
| **Free Tier Engaged** | subscription_tier = 'free' AND prompts_created >= 5 |
| **High Intent Visitors** | visited_pricing_page = true AND NOT registered |
| **Churned** | subscription_ends_at < now() AND subscription_tier = 'free' |
| **Referral Champions** | referred_users_count >= 3 |

---

## Implementation Timeline

### Phase 1 (Week 1-2): Foundation
- [ ] Create migrations for analytics_events and analytics_sessions
- [ ] Implement AnalyticsEvent and AnalyticsSession models
- [ ] Create API endpoint for event collection
- [ ] Implement ProcessAnalyticsEvents job

### Phase 2 (Week 2-3): Frontend Tracking
- [ ] Create useAnalytics composable
- [ ] Implement AnalyticsProvider component
- [ ] Add tracking to key pages and CTAs
- [ ] Integrate with cookie consent system

### Phase 3 (Week 3-4): Aggregations
- [ ] Create analytics_daily_stats table
- [ ] Implement AggregateAnalytics command
- [ ] Schedule daily aggregation
- [ ] Backfill historical data from existing tables

### Phase 4 (Week 4-5): Dashboard
- [ ] Create admin analytics routes
- [ ] Implement AnalyticsDashboardController
- [ ] Build Vue dashboard components
- [ ] Add real-time metrics display

### Phase 5 (Week 5-6): Advanced Features
- [ ] Implement cohort analysis
- [ ] Build segmentation engine
- [ ] Add funnel visualisation
- [ ] Create export functionality

---

## Data Retention Policy

| Data Type | Retention | Reason |
|-----------|-----------|--------|
| Raw events | 90 days | Query performance |
| Sessions | 1 year | Journey analysis |
| Daily aggregations | Indefinite | Trend analysis |
| Cohort data | Indefinite | LTV calculations |
| User profiles | Until deletion request | GDPR compliance |

### Cleanup Command

```php
// app/Console/Commands/CleanupAnalytics.php
// Runs weekly to purge old raw event data
// Keeps aggregated data forever
```

---

## Estimated Storage Requirements

| Table | Rows/Day (Est.) | Row Size | Daily Storage |
|-------|-----------------|----------|---------------|
| analytics_events | 10,000-50,000 | ~500 bytes | 5-25 MB |
| analytics_sessions | 500-2,000 | ~300 bytes | 150-600 KB |
| analytics_daily_stats | 1 | ~1 KB | 1 KB |

**Monthly estimate:** 150-750 MB raw events (before 90-day cleanup)

---

## Key Advantages of In-House CDP

1. **Full Data Ownership** - No third-party data sharing
2. **Custom Events** - Track exactly what matters for your product
3. **Privacy Compliant** - Full control over data retention and deletion
4. **Cost Effective** - No per-event pricing (Mixpanel, Amplitude charge by volume)
5. **Integration** - Direct access to user/visitor data for personalisation
6. **Offline Analysis** - Export to any BI tool
7. **Real-time** - WebSocket integration for live dashboards

---

## Comparison: Build vs Buy

| Factor | In-House CDP | Third-Party (Mixpanel/Amplitude) |
|--------|--------------|----------------------------------|
| Setup time | 4-6 weeks | 1-2 days |
| Monthly cost | Server costs only (~£20-50) | £100-1000+ based on volume |
| Data ownership | 100% yours | Shared with vendor |
| Customisation | Unlimited | Limited by platform |
| Privacy control | Full | Dependent on vendor |
| Maintenance | Required | Minimal |
| Learning curve | Moderate | Low |

**Recommendation:** Build in-house given your privacy-focused positioning and long-term cost savings.

---

*Document created: January 2025*
