# Customer Data Platform (CDP) Implementation Plan (Superseded)

> **Status:** Superseded by `docs/unified-analytics-experimentation-architecture.md`.
>
> This document is retained for historical context, but should not be implemented directly.

## Executive Summary

BettrPrompt already has excellent data collection foundations. This plan extends these into a full in-house CDP capable of:
- Unified customer profiles (visitor → user → subscriber journey)
- Event-based behavioural tracking
- Attribution and conversion analytics
- Cohort analysis and segmentation
- Real-time dashboards
- **Framework selection analytics** (which frameworks are recommended vs chosen)
- **Question bank effectiveness tracking** (usage, skip rates, correlation with outcomes)
- **Prompt quality metrics** (user ratings, edits, refinements)
- **Unified event architecture** (shared foundation with A/B testing system)

> **Integration Note:** This CDP shares a unified event architecture with the A/B Testing System (see `ab-testing-implementation-plan.md`). All events flow through a single `AnalyticsEvent` dispatcher that feeds both systems, ensuring consistent data and enabling cross-system analysis.

> **A/B Testing Priorities:** The framework and question analytics in this CDP directly support key A/B tests including personality collection timing, question count optimisation, and framework presentation tests. See the "A/B Testing Priority Roadmap" section in `ab-testing-implementation-plan.md` for the full prioritised test list.

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
| **Framework Analytics** | ❌ Missing | No insight into framework selection patterns |
| **Question Bank Analytics** | ❌ Missing | No tracking of question effectiveness |
| **Prompt Quality Metrics** | ❌ Missing | No user satisfaction or outcome tracking |
| **Workflow Performance** | ❌ Missing | No n8n execution metrics |

---

## Recommended Architecture

### Data Model Overview

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                         Unified Analytics Layer                               │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                               │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐               │
│  │ visitors │───▶│  users   │───▶│subscribers│───▶│ churned  │               │
│  └──────────┘    └──────────┘    └──────────┘    └──────────┘               │
│       │               │               │               │                       │
│       ▼               ▼               ▼               ▼                       │
│  ┌───────────────────────────────────────────────────────────────────────┐   │
│  │                         analytics_events                               │   │
│  │  (page_views, clicks, conversions, A/B exposures - SINGLE SOURCE)     │   │
│  │  Includes: experiment_id, variant_id for A/B test attribution         │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
│       │                                                                       │
│       ├───────────────────┬───────────────────┬───────────────────┐          │
│       ▼                   ▼                   ▼                   ▼          │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐   │
│  │  analytics  │    │  framework  │    │  question   │    │  workflow   │   │
│  │  sessions   │    │  selections │    │  analytics  │    │ performance │   │
│  └─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘   │
│       │                   │                   │                   │          │
│       └───────────────────┴───────────────────┴───────────────────┘          │
│                                   │                                           │
│                                   ▼                                           │
│  ┌───────────────────────────────────────────────────────────────────────┐   │
│  │                      analytics_daily_stats                             │   │
│  │  (unified aggregations: traffic, conversions, framework, questions)   │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
│  ┌───────────────────────────────────────────────────────────────────────┐   │
│  │                      A/B Testing Integration                           │   │
│  │  experiment_conversions (aggregated stats from analytics_events)      │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
│                                                                               │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Unified Event Architecture

All analytics events flow through a single dispatcher that feeds both CDP and A/B testing:

```php
// app/Events/AnalyticsEvent.php
class AnalyticsEvent implements ShouldBroadcast
{
    public function __construct(
        public string $eventType,
        public string $eventName,
        public array $properties = [],
        public ?string $visitorId = null,
        public ?int $userId = null,
        public ?string $sessionId = null,
    ) {}
}

// Usage throughout the application:
AnalyticsEvent::dispatch('conversion', 'registration_complete', [
    'personality_type' => 'INTJ-A',
    'framework_selected' => 'CO_STAR',
]);

// Listeners automatically handle:
// 1. CDPEventListener → analytics_events table
// 2. ExperimentEventListener → experiment_conversions (if user in experiment)
// 3. FrameworkSelectionListener → framework_selections (if framework event)
// 4. QuestionAnalyticsListener → question_analytics (if question event)
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
    $table->string('event_type', 50)->index(); // page_view, click, form_submit, feature_use, conversion, exposure
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

    // A/B Testing Context (nullable - only populated when user is in experiment)
    $table->foreignId('experiment_id')->nullable()->index();
    $table->foreignId('variant_id')->nullable();
    $table->string('experiment_slug', 100)->nullable()->index();
    $table->string('variant_slug', 100)->nullable();

    // Prompt Run Context (nullable - only populated for prompt-related events)
    $table->foreignId('prompt_run_id')->nullable()->index();

    // Timestamps
    $table->timestamp('occurred_at')->index();
    $table->timestamps();

    // Composite indexes for common queries
    $table->index(['visitor_id', 'occurred_at']);
    $table->index(['user_id', 'occurred_at']);
    $table->index(['event_type', 'occurred_at']);
    $table->index(['page_path', 'occurred_at']);

    // A/B testing indexes
    $table->index(['experiment_id', 'event_type', 'occurred_at']);
    $table->index(['experiment_id', 'variant_id', 'event_name']);
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

    // Prompt activity in this session
    $table->unsignedSmallInteger('prompts_started')->default(0);
    $table->unsignedSmallInteger('prompts_completed')->default(0);
    $table->foreignId('first_prompt_run_id')->nullable(); // Link to first prompt in session

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
| `exposure` | `experiment_exposure` | experiment_slug, variant_slug | A/B test exposure |
| `framework` | `framework_recommended` | framework_code, rank, alternatives | Workflow 1 completion |
| `framework` | `framework_switched` | from_framework, to_framework | User switches framework |
| `question` | `question_asked` | question_id, question_phase, framework_code | Question displayed |
| `question` | `question_answered` | question_id, answer_length, time_to_answer_ms | Question answered |
| `question` | `question_skipped` | question_id, skip_reason | Question skipped |
| `prompt` | `prompt_started` | personality_type, task_category | Workflow 0 starts |
| `prompt` | `prompt_completed` | framework_code, personality_type | Workflow 2 completes |
| `prompt` | `prompt_rated` | rating, framework_code | User rates prompt |
| `prompt` | `prompt_copied` | framework_code | User copies prompt |
| `prompt` | `prompt_edited` | edit_distance, framework_code | User edits prompt |

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

### 1.5 Framework Selection Analytics Table

Track which frameworks are recommended vs actively chosen by users.

**Migration: `create_framework_selections_table`**

```php
Schema::create('framework_selections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();
    $table->uuid('visitor_id')->nullable()->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Framework identification
    $table->string('framework_code', 50)->index(); // CO_STAR, RICE, CHAIN_OF_THOUGHT, etc.
    $table->string('framework_name', 100)->nullable();

    // Selection context
    $table->enum('selection_type', [
        'primary',      // Recommended as primary framework
        'alternative',  // Shown as alternative option
        'switched_to',  // User actively switched to this framework
        'switched_from' // User switched away from this framework
    ])->index();
    $table->unsignedTinyInteger('recommendation_rank')->nullable(); // 1 = primary, 2+ = alternative
    $table->boolean('user_initiated')->default(false); // True if user made active choice

    // Task classification context (for correlation analysis)
    $table->string('task_category', 50)->nullable()->index(); // DECISION, STRATEGY, etc.
    $table->string('secondary_category', 50)->nullable();
    $table->string('complexity', 20)->nullable(); // Low, Medium, High

    // Personality correlation
    $table->string('personality_type', 10)->nullable()->index(); // INTJ-A, ENFP-T, etc.
    $table->json('trait_percentages')->nullable();

    // A/B test context (if user in experiment)
    $table->foreignId('experiment_id')->nullable();
    $table->foreignId('variant_id')->nullable();

    $table->timestamp('selected_at');
    $table->timestamps();

    // Composite indexes for analysis
    $table->index(['framework_code', 'selection_type']);
    $table->index(['framework_code', 'personality_type']);
    $table->index(['task_category', 'framework_code']);
    $table->index(['framework_code', 'selected_at']);
});
```

**Framework Daily Stats Table:**

```php
Schema::create('framework_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();
    $table->string('framework_code', 50)->index();

    // Selection counts
    $table->unsignedInteger('times_recommended_primary')->default(0);
    $table->unsignedInteger('times_recommended_alternative')->default(0);
    $table->unsignedInteger('times_user_switched_to')->default(0);
    $table->unsignedInteger('times_user_switched_from')->default(0);

    // Derived metrics
    $table->decimal('switch_to_rate', 5, 2)->default(0); // % of alternatives that users switched to
    $table->decimal('switch_from_rate', 5, 2)->default(0); // % of primaries that users switched from
    $table->decimal('completion_rate', 5, 2)->default(0); // % that led to completed prompts

    // Top task categories for this framework (JSON: {category: count})
    $table->json('task_categories')->nullable();

    // Top personality types using this framework (JSON: {type: count})
    $table->json('personality_types')->nullable();

    $table->timestamps();

    $table->unique(['date', 'framework_code']);
});
```

### 1.6 Question Bank Analytics Table

Track usage, skip rates, and effectiveness of each question from the Question Bank.

> **Reference:** Question IDs follow the format defined in `resources/reference_documents/question_bank.md`:
> - Universal: U1-U6
> - Decision: D1-D10
> - Strategy: S1-S12
> - Analysis: A1-A10
> - Content Creation: C1-C11
> - Technical Creation: T1-T10
> - Ideation: I1-I10
> - Problem Solving: P1-P12
> - Learning: L1-L8
> - Persuasion: PE1-PE10
> - Feedback: F1-F8
> - Research: R1-R10
> - Goal Setting: G1-G8
> - Framework-specific: COS1-COS8, REA1-REA6, SRF1-SRF6, STB1-STB5, SOT1-SOT5, MET1-MET7

**Migration: `create_question_analytics_table`**

```php
Schema::create('question_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();
    $table->uuid('visitor_id')->nullable()->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Question identification (matches Question Bank IDs)
    $table->string('question_id', 20)->index(); // U1, D3, COS4, S7, etc.
    $table->text('question_text')->nullable(); // Actual question asked (may vary by personality)

    // Question context
    $table->enum('question_phase', ['pre_analysis', 'clarifying'])->index();
    $table->string('framework_code', 50)->nullable()->index(); // For clarifying questions
    $table->string('task_category', 50)->nullable(); // Task category when asked

    // User interaction
    $table->enum('status', ['asked', 'answered', 'skipped'])->index();
    $table->unsignedSmallInteger('display_order')->nullable(); // Position in question list
    $table->unsignedInteger('time_to_answer_ms')->nullable(); // Time from display to answer
    $table->unsignedSmallInteger('answer_length')->nullable(); // Characters in answer
    $table->string('answer_type', 20)->nullable(); // 'choice', 'text', 'yes_no'

    // Skip tracking
    $table->string('skip_reason', 50)->nullable(); // 'explicit', 'timeout', 'default_used', 'phase_skipped'

    // Personality correlation
    $table->string('personality_type', 10)->nullable()->index();
    $table->json('trait_percentages')->nullable();

    // Personality-adjusted phrasing used?
    $table->boolean('personality_adjusted')->default(false);

    // Timestamps
    $table->timestamp('asked_at');
    $table->timestamp('answered_at')->nullable();
    $table->timestamps();

    // Composite indexes for analysis
    $table->index(['question_id', 'status']);
    $table->index(['question_id', 'personality_type']);
    $table->index(['framework_code', 'question_id']);
    $table->index(['question_phase', 'status']);
    $table->index(['question_id', 'asked_at']);
});
```

**Question Daily Stats Table:**

```php
Schema::create('question_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();
    $table->string('question_id', 20)->index();

    // Usage counts
    $table->unsignedInteger('times_asked')->default(0);
    $table->unsignedInteger('times_answered')->default(0);
    $table->unsignedInteger('times_skipped')->default(0);

    // Derived metrics
    $table->decimal('answer_rate', 5, 2)->default(0); // % answered
    $table->decimal('skip_rate', 5, 2)->default(0); // % skipped
    $table->unsignedInteger('avg_answer_length')->default(0); // Avg chars
    $table->unsignedInteger('avg_time_to_answer_ms')->default(0);
    $table->unsignedInteger('median_time_to_answer_ms')->default(0);

    // Phase breakdown
    $table->unsignedInteger('pre_analysis_count')->default(0);
    $table->unsignedInteger('clarifying_count')->default(0);

    // Top personality types asking this question (JSON)
    $table->json('personality_distribution')->nullable();

    // Skip reason distribution (JSON: {reason: count})
    $table->json('skip_reasons')->nullable();

    $table->timestamps();

    $table->unique(['date', 'question_id']);
});
```

### 1.7 Workflow Performance Table

Track n8n workflow execution performance and costs.

**Migration: `create_workflow_performance_table`**

```php
Schema::create('workflow_performance', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

    // Workflow identification
    $table->enum('workflow_stage', ['0', '1', '2'])->index(); // Pre-analysis, Analysis, Generation

    // Timing
    $table->timestamp('started_at');
    $table->timestamp('completed_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->unsignedInteger('queue_wait_ms')->nullable(); // Time spent waiting in queue

    // API usage (from n8n response)
    $table->string('model_used', 50)->nullable(); // claude-3-5-sonnet, etc.
    $table->unsignedInteger('api_tokens_input')->nullable();
    $table->unsignedInteger('api_tokens_output')->nullable();
    $table->unsignedInteger('api_tokens_total')->nullable();
    $table->decimal('api_cost_usd', 10, 6)->nullable();

    // Outcome
    $table->boolean('success')->default(false);
    $table->string('error_code', 50)->nullable();
    $table->text('error_message')->nullable();
    $table->unsignedTinyInteger('retry_count')->default(0);

    // n8n execution metadata
    $table->string('n8n_execution_id', 100)->nullable();
    $table->string('n8n_workflow_id', 50)->nullable();

    $table->timestamps();

    // Indexes
    $table->index(['workflow_stage', 'started_at']);
    $table->index(['success', 'workflow_stage']);
    $table->index(['started_at']);
});
```

**Workflow Daily Stats Table:**

```php
Schema::create('workflow_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();
    $table->enum('workflow_stage', ['0', '1', '2'])->index();

    // Execution counts
    $table->unsignedInteger('total_executions')->default(0);
    $table->unsignedInteger('successful')->default(0);
    $table->unsignedInteger('failed')->default(0);
    $table->decimal('success_rate', 5, 2)->default(0);

    // Timing (in ms)
    $table->unsignedInteger('avg_duration_ms')->default(0);
    $table->unsignedInteger('p50_duration_ms')->default(0);
    $table->unsignedInteger('p95_duration_ms')->default(0);
    $table->unsignedInteger('max_duration_ms')->default(0);

    // API usage
    $table->unsignedBigInteger('total_tokens_input')->default(0);
    $table->unsignedBigInteger('total_tokens_output')->default(0);
    $table->decimal('total_cost_usd', 12, 4)->default(0);
    $table->decimal('avg_cost_per_execution_usd', 10, 6)->default(0);

    // Error distribution (JSON: {error_code: count})
    $table->json('error_distribution')->nullable();

    $table->timestamps();

    $table->unique(['date', 'workflow_stage']);
});
```

### 1.8 Prompt Quality Metrics

Track user satisfaction and prompt outcome quality.

**Migration: `add_quality_metrics_to_prompt_runs`**

```php
// Add to existing prompt_runs table
Schema::table('prompt_runs', function (Blueprint $table) {
    // User rating
    $table->unsignedTinyInteger('user_rating')->nullable(); // 1-5 stars
    $table->timestamp('rated_at')->nullable();

    // Prompt interaction tracking
    $table->boolean('prompt_copied')->default(false);
    $table->timestamp('first_copied_at')->nullable();
    $table->unsignedSmallInteger('copy_count')->default(0);

    // Edit tracking
    $table->boolean('prompt_edited')->default(false);
    $table->unsignedSmallInteger('edit_distance')->nullable(); // Levenshtein distance
    $table->decimal('edit_percentage', 5, 2)->nullable(); // % of prompt changed
    $table->timestamp('first_edited_at')->nullable();

    // Refinement tracking
    $table->boolean('returned_for_refinement')->default(false);
    $table->unsignedTinyInteger('refinement_count')->default(0);

    // Session attribution
    $table->uuid('analytics_session_id')->nullable()->index();

    // Indexes
    $table->index(['user_rating', 'completed_at']);
    $table->index(['prompt_copied', 'completed_at']);
});
```

**Prompt Quality Daily Stats:**

```php
Schema::create('prompt_quality_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();

    // Volume
    $table->unsignedInteger('prompts_completed')->default(0);
    $table->unsignedInteger('prompts_rated')->default(0);

    // Ratings
    $table->decimal('avg_rating', 3, 2)->nullable();
    $table->unsignedInteger('rating_1_count')->default(0);
    $table->unsignedInteger('rating_2_count')->default(0);
    $table->unsignedInteger('rating_3_count')->default(0);
    $table->unsignedInteger('rating_4_count')->default(0);
    $table->unsignedInteger('rating_5_count')->default(0);

    // Engagement
    $table->unsignedInteger('prompts_copied')->default(0);
    $table->decimal('copy_rate', 5, 2)->default(0); // % of completed prompts copied
    $table->unsignedInteger('prompts_edited')->default(0);
    $table->decimal('edit_rate', 5, 2)->default(0);
    $table->decimal('avg_edit_percentage', 5, 2)->nullable();

    // Refinement
    $table->unsignedInteger('prompts_refined')->default(0);
    $table->decimal('refinement_rate', 5, 2)->default(0);

    // By framework (JSON: {framework_code: {completed, avg_rating, copy_rate}})
    $table->json('framework_breakdown')->nullable();

    // By personality (JSON: {personality_type: {completed, avg_rating}})
    $table->json('personality_breakdown')->nullable();

    $table->timestamps();

    $table->unique('date');
});
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

> **Note:** This timeline is synchronised with the A/B Testing Implementation Plan. Both systems share the unified event architecture established in Phase 1.

### Phase 1: Unified Event Foundation
**Shared with A/B Testing System**

- [ ] Create `analytics_events` table with A/B test context columns
- [ ] Create `analytics_sessions` table with prompt tracking
- [ ] Implement `AnalyticsEvent` Laravel event class (unified dispatcher)
- [ ] Create `ProcessAnalyticsEvents` job (async, non-blocking)
- [ ] Implement event listeners:
  - [ ] `CDPEventListener` → writes to analytics_events
  - [ ] `ExperimentEventListener` → updates experiment_conversions
  - [ ] `FrameworkSelectionListener` → writes to framework_selections
  - [ ] `QuestionAnalyticsListener` → writes to question_analytics
- [ ] Create API endpoint for frontend event collection

### Phase 2: Framework & Question Analytics
**BettrPrompt-Specific Analytics**

- [ ] Create `framework_selections` table
- [ ] Create `framework_daily_stats` table
- [ ] Create `question_analytics` table
- [ ] Create `question_daily_stats` table
- [ ] Add analytics tracking to workflow jobs:
  - [ ] `ProcessPreAnalysis` → track pre-analysis questions
  - [ ] `ProcessAnalysis` → track framework selection + clarifying questions
  - [ ] `ProcessPromptGeneration` → track completion
- [ ] Implement `PromptBuilderController` tracking:
  - [ ] Question answered events
  - [ ] Question skipped events
  - [ ] Framework switch events

### Phase 3: Frontend Tracking
**General Event Collection**

- [ ] Create `useAnalytics` composable
- [ ] Implement `AnalyticsProvider` component
- [ ] Add page view tracking (Inertia navigation aware)
- [ ] Add scroll depth tracking
- [ ] Add click tracking (`data-track` attributes)
- [ ] Integrate with cookie consent system
- [ ] Add prompt builder-specific tracking:
  - [ ] Question timing (time to answer)
  - [ ] Prompt copy events
  - [ ] Prompt edit events

### Phase 4: Prompt Quality & Workflow Performance
**Outcome Tracking**

- [ ] Add quality metrics columns to `prompt_runs` table
- [ ] Create `workflow_performance` table
- [ ] Create `workflow_daily_stats` table
- [ ] Create `prompt_quality_daily_stats` table
- [ ] Implement prompt rating UI component
- [ ] Track prompt copy/edit actions
- [ ] Record workflow execution metrics from n8n responses

### Phase 5: Aggregations & Dashboard
**Reporting Infrastructure**

- [ ] Create `analytics_daily_stats` table
- [ ] Implement `AggregateAnalytics` command (includes framework/question stats)
- [ ] Implement `AggregateFrameworkStats` command
- [ ] Implement `AggregateQuestionStats` command
- [ ] Implement `AggregateWorkflowStats` command
- [ ] Implement `AggregatePromptQuality` command
- [ ] Schedule daily aggregation (02:00 UTC)
- [ ] Backfill historical data from existing `prompt_runs`

### Phase 6: Admin Dashboard
**Visualisation & Reporting**

- [ ] Create admin analytics routes
- [ ] Implement `AnalyticsDashboardController`
- [ ] Build Vue dashboard components:
  - [ ] Traffic overview (visitors, sessions, page views)
  - [ ] Conversion funnel visualisation
  - [ ] Framework selection analytics
  - [ ] Question effectiveness dashboard
  - [ ] Prompt quality metrics
  - [ ] Workflow performance monitoring
- [ ] Add real-time metrics display

### Phase 7: Advanced Features
**Cohorts, Segments & Export**

- [ ] Create `analytics_cohorts` table
- [ ] Implement cohort analysis (weekly/monthly retention)
- [ ] Create `analytics_segments` table
- [ ] Build segmentation engine with predefined segments
- [ ] Add export functionality (CSV/JSON)
- [ ] Implement data cleanup command (90-day retention for raw events)

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
| analytics_events | 10,000-50,000 | ~600 bytes | 6-30 MB |
| analytics_sessions | 500-2,000 | ~350 bytes | 175-700 KB |
| framework_selections | 200-1,000 | ~400 bytes | 80-400 KB |
| question_analytics | 1,000-5,000 | ~350 bytes | 350 KB-1.75 MB |
| workflow_performance | 200-1,000 | ~300 bytes | 60-300 KB |
| analytics_daily_stats | 1 | ~1 KB | 1 KB |
| framework_daily_stats | ~50 (per framework) | ~500 bytes | 25 KB |
| question_daily_stats | ~150 (per question) | ~400 bytes | 60 KB |
| workflow_daily_stats | 3 (per stage) | ~300 bytes | 900 bytes |
| prompt_quality_daily_stats | 1 | ~2 KB | 2 KB |

**Monthly estimate:** 200-1000 MB raw events (before 90-day cleanup)
**Aggregation tables:** ~3 MB/month (kept indefinitely)

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

**Recommendation:** Build in-house given your privacy-focused positioning, long-term cost savings, and the ability to unify with A/B testing for cross-system analysis.

---

## Cross-System Analysis Examples

With the unified event architecture, you can answer questions that span both CDP and A/B testing:

```sql
-- Framework effectiveness by personality type
SELECT
    fs.personality_type,
    fs.framework_code,
    COUNT(*) as times_selected,
    AVG(pr.user_rating) as avg_rating,
    ROUND(SUM(CASE WHEN pr.prompt_copied THEN 1 ELSE 0 END)::numeric / COUNT(*) * 100, 2) as copy_rate
FROM framework_selections fs
JOIN prompt_runs pr ON fs.prompt_run_id = pr.id
WHERE fs.selection_type = 'primary'
  AND pr.workflow_stage = '2_completed'
GROUP BY fs.personality_type, fs.framework_code
HAVING COUNT(*) > 10
ORDER BY avg_rating DESC NULLS LAST;

-- Question effectiveness: which questions correlate with higher prompt ratings?
SELECT
    qa.question_id,
    COUNT(*) as times_asked,
    AVG(pr.user_rating) as avg_prompt_rating,
    ROUND(AVG(qa.answer_length)::numeric, 0) as avg_answer_length,
    ROUND(SUM(CASE WHEN qa.status = 'skipped' THEN 1 ELSE 0 END)::numeric / COUNT(*) * 100, 2) as skip_rate
FROM question_analytics qa
JOIN prompt_runs pr ON qa.prompt_run_id = pr.id
WHERE pr.workflow_stage = '2_completed'
  AND pr.user_rating IS NOT NULL
GROUP BY qa.question_id
HAVING COUNT(*) > 20
ORDER BY avg_prompt_rating DESC NULLS LAST;

-- Workflow performance impact on user satisfaction
SELECT
    wp.workflow_stage,
    CASE
        WHEN wp.duration_ms < 5000 THEN 'fast (<5s)'
        WHEN wp.duration_ms < 15000 THEN 'medium (5-15s)'
        ELSE 'slow (>15s)'
    END as speed_bucket,
    COUNT(*) as executions,
    AVG(pr.user_rating) as avg_rating
FROM workflow_performance wp
JOIN prompt_runs pr ON wp.prompt_run_id = pr.id
WHERE pr.user_rating IS NOT NULL
GROUP BY wp.workflow_stage, speed_bucket
ORDER BY wp.workflow_stage, avg_rating DESC;

-- Session journey to conversion
SELECT
    DATE(s.started_at) as date,
    AVG(s.page_count) as avg_pages_before_conversion,
    AVG(s.duration_seconds) as avg_session_duration,
    AVG(s.prompts_started) as avg_prompts_started,
    COUNT(*) as conversions
FROM analytics_sessions s
WHERE s.converted = true
GROUP BY DATE(s.started_at)
ORDER BY date DESC;
```

---

## Advanced Opportunities: Beyond Basic Analytics

These ideas go beyond traditional CDP functionality to create competitive advantages unique to BettrPrompt.

### 1. Prompt Outcome Tracking (The Missing Feedback Loop)

**The Problem:** We track prompt generation, but we don't know if prompts actually *worked* for users.

**The Opportunity:** Close the feedback loop by tracking what happens *after* the prompt is copied.

```php
Schema::create('prompt_outcomes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

    // Self-reported outcome
    $table->enum('ai_response_quality', ['poor', 'okay', 'good', 'excellent'])->nullable();
    $table->boolean('achieved_goal')->nullable();
    $table->text('outcome_notes')->nullable();

    // Behavioural signals
    $table->boolean('returned_to_refine')->default(false); // Came back to same prompt
    $table->boolean('created_similar_task')->default(false); // Similar task within 24h
    $table->unsignedSmallInteger('refinement_iterations')->default(0);

    // Target AI model (if known)
    $table->string('target_model', 50)->nullable(); // claude-3-5-sonnet, gpt-4, etc.

    $table->timestamp('outcome_reported_at')->nullable();
    $table->timestamps();
});
```

**Implementation Ideas:**
- Follow-up email 24h after prompt generation: "Did your prompt work?"
- In-app prompt: "How did the AI respond?" when user returns
- Browser extension that detects AI tool usage and asks for feedback
- Infer outcomes from behaviour (returned to refine = prompt didn't work)

**Business Value:**
- Proves ROI to users and prospects ("87% of prompts achieve user goals")
- Identifies which frameworks/personalities produce best outcomes
- Creates training data for improving prompt generation

---

### 2. Collaborative Intelligence: Learning from the Collective

**The Problem:** Each user's prompts are generated in isolation. We're not learning from aggregate patterns.

**The Opportunity:** Use anonymised collective data to improve prompts for everyone.

**Data Points to Aggregate:**

| Pattern | Usage |
|---------|-------|
| Framework × Task Category × Outcome | "CO-STAR has 92% success rate for CREATION_CONTENT tasks" |
| Question × Answer Rate × Prompt Rating | "Question U4 correlates with 15% higher ratings when answered" |
| Personality × Framework × Satisfaction | "INTJs rate CHAIN_OF_THOUGHT prompts 0.8 stars higher than average" |
| Task Length × Question Count × Completion | "Tasks >200 chars need 2 fewer clarifying questions" |

**Features This Enables:**

```typescript
// Dynamic framework confidence
"CO-STAR is recommended for your task. Users with similar tasks rated
CO-STAR prompts 4.2/5 on average."

// Smart question selection
"Based on your task and personality, we're asking 4 questions instead
of the usual 6. Users like you find this optimal."

// Outcome prediction
"Based on similar prompts, this has an 89% likelihood of achieving
your goal on the first try."
```

**Privacy Consideration:** All aggregations are anonymised. Individual prompts are never shared. This is a competitive advantage over third-party tools.

---

### 3. Adaptive UX: Real-Time Personalisation Beyond A/B Testing

**The Problem:** A/B testing finds the best *average* experience. But users aren't average.

**The Opportunity:** Use behavioural signals to adapt the UX in real-time.

**Behavioural Signals → UX Adaptations:**

| Signal | Adaptation |
|--------|------------|
| User skipped last 2 pre-analysis questions | Show fewer questions, make skip more prominent |
| User switched frameworks on last 3 prompts | Show framework comparison by default, explain selection |
| User always uses voice input | Default to voice input, larger mic button |
| User consistently edits prompts >20% | Show "preview and edit" before final generation |
| User has High P personality | Reduce required fields, show more "skip" options |
| User has High J personality | Show progress indicators, step numbers |
| User abandoned during processing twice | Show estimated time, progress stages prominently |

**Implementation:**

```php
// app/Services/AdaptiveUXService.php
class AdaptiveUXService
{
    public function getUXPreferences(User|Visitor $subject): array
    {
        $history = $this->analyseHistory($subject);
        $personality = $subject->personality_type;

        return [
            'question_count_modifier' => $this->calculateQuestionReduction($history),
            'show_framework_comparison' => $history->framework_switch_rate > 0.3,
            'default_input_mode' => $history->voice_input_rate > 0.5 ? 'voice' : 'text',
            'show_prompt_preview' => $history->avg_edit_percentage > 15,
            'show_progress_indicators' => $this->needsReassurance($personality, $history),
            'skip_button_prominence' => $this->calculateSkipProminence($personality),
        ];
    }
}
```

**This goes beyond A/B testing:** Instead of "Variant A vs B for everyone", it's "the right variant for this specific user based on their behaviour."

---

### 4. Predictive Analytics: Anticipating User Needs

**The Opportunity:** Use historical patterns to predict and proactively address user needs.

**Churn Prediction Model:**

```sql
-- Features for churn prediction
SELECT
    u.id,
    u.subscription_tier,
    EXTRACT(DAY FROM NOW() - u.last_active_at) as days_since_active,
    COUNT(pr.id) as total_prompts,
    COUNT(pr.id) FILTER (WHERE pr.created_at > NOW() - INTERVAL '30 days') as recent_prompts,
    AVG(pr.user_rating) as avg_rating,
    SUM(CASE WHEN pr.workflow_stage LIKE '%failed%' THEN 1 ELSE 0 END)::float / COUNT(pr.id) as failure_rate,
    COUNT(DISTINCT DATE(pr.created_at)) as active_days_30d
FROM users u
LEFT JOIN prompt_runs pr ON u.id = pr.user_id
WHERE u.subscription_tier IN ('pro', 'private')
GROUP BY u.id;
```

**Churn Indicators:**
- Days since last prompt > 14 (for paid users)
- Recent prompts declining month-over-month
- High failure rate (>20%)
- Low ratings (<3.5 average)
- Decreasing session duration

**Proactive Interventions:**

| Risk Level | Intervention |
|------------|--------------|
| Low risk | In-app tip: "Did you know you can..." |
| Medium risk | Email: "We noticed you haven't created a prompt recently..." |
| High risk | Personal outreach: "Is there anything we can help with?" |
| Pre-renewal high risk | Discount offer or feature highlight |

**Conversion Likelihood Scoring:**

```php
// Score free users on conversion likelihood
$conversionScore = (
    ($user->prompts_created * 10) +
    ($user->profile_completion_percentage * 0.5) +
    ($user->visited_pricing_page ? 20 : 0) +
    ($user->personality_type ? 15 : 0) +
    (min($user->visit_count, 10) * 2)
) / 100;

// High-score users get targeted upgrade prompts
```

---

### 5. Framework Evolution Engine

**The Problem:** The framework taxonomy is static. We don't know if it's optimal.

**The Opportunity:** Use outcome data to evolve framework selection and even create new frameworks.

**Framework Performance Matrix:**

```sql
-- Which frameworks work best for which task types and personalities?
SELECT
    fs.framework_code,
    pr.task_classification->>'primary_category' as task_category,
    SUBSTRING(fs.personality_type, 1, 4) as personality_base, -- INTJ, ENFP, etc.
    COUNT(*) as usage_count,
    AVG(pr.user_rating) as avg_rating,
    AVG(CASE WHEN pr.prompt_copied THEN 1 ELSE 0 END) as copy_rate,
    AVG(CASE WHEN pr.prompt_edited THEN pr.edit_percentage ELSE 0 END) as avg_edit_pct
FROM framework_selections fs
JOIN prompt_runs pr ON fs.prompt_run_id = pr.id
WHERE fs.selection_type = 'primary'
  AND pr.workflow_stage = '2_completed'
  AND pr.user_rating IS NOT NULL
GROUP BY fs.framework_code, task_category, personality_base
HAVING COUNT(*) > 20;
```

**Insights This Enables:**
- "RICE performs poorly for IDEATION tasks—consider removing from recommendations"
- "INTJs prefer CHAIN_OF_THOUGHT over CO-STAR for ANALYSIS tasks"
- "Users who switch from primary framework have 15% lower ratings—improve initial selection"

**Framework A/B Testing:**
- Test new framework selection algorithms
- Test adding/removing frameworks from the taxonomy
- Test framework component variations (e.g., CO-STAR with vs without "Tone" component)

---

### 6. Question Bank Intelligence

**The Problem:** The question bank has 150+ questions, but we don't know which are valuable.

**The Opportunity:** Use analytics to continuously optimise the question bank.

**Question Effectiveness Score:**

```sql
-- Calculate effectiveness score for each question
WITH question_metrics AS (
    SELECT
        qa.question_id,
        COUNT(*) as times_asked,
        AVG(CASE WHEN qa.status = 'answered' THEN 1 ELSE 0 END) as answer_rate,
        AVG(CASE WHEN qa.status = 'skipped' THEN 1 ELSE 0 END) as skip_rate,
        AVG(qa.time_to_answer_ms) as avg_time_ms,
        AVG(qa.answer_length) as avg_answer_length
    FROM question_analytics qa
    GROUP BY qa.question_id
),
question_outcomes AS (
    SELECT
        qa.question_id,
        AVG(pr.user_rating) FILTER (WHERE qa.status = 'answered') as rating_when_answered,
        AVG(pr.user_rating) FILTER (WHERE qa.status = 'skipped') as rating_when_skipped
    FROM question_analytics qa
    JOIN prompt_runs pr ON qa.prompt_run_id = pr.id
    WHERE pr.user_rating IS NOT NULL
    GROUP BY qa.question_id
)
SELECT
    qm.question_id,
    qm.times_asked,
    qm.answer_rate,
    qm.skip_rate,
    qo.rating_when_answered,
    qo.rating_when_skipped,
    (qo.rating_when_answered - COALESCE(qo.rating_when_skipped, 3.0)) as rating_lift,
    -- Effectiveness = high answer rate + high rating lift
    (qm.answer_rate * 0.4) + ((qo.rating_when_answered - 3) / 2 * 0.6) as effectiveness_score
FROM question_metrics qm
JOIN question_outcomes qo ON qm.question_id = qo.question_id
ORDER BY effectiveness_score DESC;
```

**Actions Based on Data:**

| Effectiveness | Answer Rate | Action |
|---------------|-------------|--------|
| High | High | Keep and prioritise |
| High | Low | Improve phrasing, test variations |
| Low | High | Consider removing (effort without value) |
| Low | Low | Remove or replace |

**Personality-Specific Question Effectiveness:**
- Some questions may be high-value for Thinkers but low-value for Feelers
- Adjust question selection based on personality, not just task type

---

### 7. Privacy as Competitive Moat

**The Opportunity:** Turn our in-house analytics into a marketing advantage.

**Features to Build:**

1. **Data Transparency Dashboard**
   - Show users exactly what data we collect
   - "Your data is stored in [region] and never shared"
   - Real-time data export (GDPR Article 20)

2. **Privacy Comparison**
   - "Unlike [competitor], your prompts are never used to train AI models"
   - "Your personality data stays on our servers, not third-party analytics"

3. **Enterprise Privacy Features**
   - Data residency options (EU, UK, US)
   - SOC 2 compliance tracking
   - Audit logs for all data access

4. **"Forget Me" Instant Delete**
   - One-click complete data deletion
   - Verification and confirmation
   - Competitive differentiator vs tools that retain data

---

### 8. Economic Value Attribution

**The Problem:** Users don't know the ROI of BettrPrompt.

**The Opportunity:** Help users quantify the value they're getting.

**Value Metrics to Track:**

```php
Schema::table('users', function (Blueprint $table) {
    // Value tracking
    $table->unsignedInteger('prompts_lifetime')->default(0);
    $table->unsignedInteger('estimated_hours_saved')->default(0);
    $table->unsignedInteger('estimated_value_gbp')->default(0);
});

// Calculation logic
$hoursPerPrompt = 0.5; // Conservative estimate: 30 min saved per good prompt
$hourlyRate = match($user->experience_level) {
    'junior' => 25,
    'mid' => 50,
    'senior' => 75,
    'executive' => 150,
    default => 40,
};

$estimatedValue = $user->prompts_completed * $hoursPerPrompt * $hourlyRate;
```

**User-Facing Value Dashboard:**

```
┌─────────────────────────────────────────┐
│  Your BettrPrompt Impact                │
├─────────────────────────────────────────┤
│  Prompts Created:        47             │
│  Estimated Time Saved:   23.5 hours     │
│  Estimated Value:        £1,175         │
│                                         │
│  Your subscription cost: £29/month      │
│  ROI this month:         4,052%         │
└─────────────────────────────────────────┘
```

**Business Value:**
- Reduces churn by showing concrete value
- Justifies pricing for enterprise negotiations
- Creates shareable "value achieved" metrics for word-of-mouth

---

### 9. Prompt Lineage & Iteration Tracking

**The Problem:** We track individual prompts but not the evolution of a user's prompting over time.

**The Opportunity:** Track prompt lineage to understand refinement patterns.

```php
// Already have parent_id in prompt_runs, but enhance with:
Schema::table('prompt_runs', function (Blueprint $table) {
    $table->unsignedTinyInteger('generation')->default(1); // 1 = original, 2+ = iterations
    $table->string('iteration_reason', 50)->nullable(); // 'refine', 'rephrase', 'expand', 'simplify'
    $table->foreignId('root_prompt_id')->nullable(); // Points to original in chain
});
```

**Insights This Enables:**
- Average iterations before user is satisfied
- Which frameworks need more refinement?
- Which personality types iterate more?
- Common refinement patterns (expand → simplify → done)

**Features:**
- "Prompt history" showing evolution of a task
- "Templates" based on successful prompt lineages
- "Similar successful prompts" recommendations

---

### 10. Cross-Platform Intelligence

**Future Opportunity:** Track where prompts are used and how they perform across AI platforms.

**Options:**
1. **Browser Extension** - Detects AI tool usage, offers to track prompt performance
2. **API Integrations** - Direct integration with Claude API, OpenAI API to track actual responses
3. **Self-Reported** - Simple "Which AI did you use this with?" survey

**Data Model:**

```php
Schema::create('prompt_usages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained();
    $table->string('ai_platform', 50); // claude, chatgpt, gemini, copilot, etc.
    $table->string('model_version', 50)->nullable(); // gpt-4-turbo, claude-3-opus
    $table->enum('response_quality', ['poor', 'okay', 'good', 'excellent'])->nullable();
    $table->boolean('required_followup')->nullable();
    $table->timestamps();
});
```

**Value:**
- "This prompt performs best with Claude" recommendations
- Model-specific prompt optimisation
- Competitive intelligence on AI platform usage

---

## Implementation Priority for Advanced Features

| Feature | Complexity | Business Value | Recommended Phase |
|---------|------------|----------------|-------------------|
| Question Bank Intelligence | Low | High | Phase 2 (uses existing data) |
| Economic Value Attribution | Low | High | Phase 3 (quick win for retention) |
| Adaptive UX | Medium | High | Phase 4 (after baseline A/B data) |
| Framework Evolution Engine | Medium | High | Phase 4 (needs outcome data) |
| Prompt Outcome Tracking | Medium | Very High | Phase 5 (closes feedback loop) |
| Predictive Analytics | High | High | Phase 6 (needs historical data) |
| Collaborative Intelligence | High | Very High | Phase 7 (needs scale) |
| Privacy Competitive Moat | Low | Medium | Ongoing (marketing focus) |
| Cross-Platform Intelligence | High | Medium | Future (requires integrations) |

---

*Document created: January 2025*
*Last updated: January 2026 - Added unified event architecture, framework/question analytics, prompt quality metrics, workflow performance tracking, synchronised implementation phases with A/B testing plan, and advanced opportunity roadmap*
