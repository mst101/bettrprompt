# Unified Analytics + Experimentation Architecture (CDP + A/B Testing)

## Executive Summary

This document replaces the separate CDP and A/B testing implementation plans with a single cohesive architecture.

Core idea:

> **Capture events once, store them once, then run processors** (A/B attribution + stats, funnels, question analytics, workflow performance, dashboards) **off the same event stream**.

The most important corrections vs the older docs:
- **Identity is server-owned** (no trusting client `visitor_id` / `user_id`), and **experiments bucket by `visitor_id` even when authenticated** to preserve stickiness.
- **Events can attribute to multiple experiments** (overlapping experiments), so experiment attribution cannot be a single `experiment_id` column on the event row.
- **Consent and session propagation are explicit**: analytics tracking is gated by consent and session IDs are propagated to the server via a header (not only localStorage).

---

## Goals

- A single, secure, non-blocking, first-party analytics ingestion pipeline.
- A/B testing built as a **processor** of the analytics pipeline (not a parallel system).
- Deterministic, sticky experiment assignment that survives visitor→user conversion.
- Clear semantics for **assignment vs exposure vs conversion**.
- Support overlapping experiments and correctly attribute conversions to *all* relevant experiments.
- GDPR-aligned consent model that applies consistently to:
  - internal CDP analytics
  - A/B test measurement
  - FullStory (or other external analytics)

## Non-Goals (v1)

- Multi-armed bandits / Bayesian sequential stopping (we default to fixed-horizon to avoid peeking bias).
- Cross-device identity resolution beyond “this device’s `visitor_id` ↔ logged-in `user_id`”.
- A full “Segment builder” UI before core data quality is proven.

---

## Glossary / Event Semantics (Must-Agree Definitions)

- **Visitor**: Anonymous device/browser identity. Key: `visitor_id` (UUID).
- **User**: Authenticated account identity. Key: `user_id` (int).
- **Session**: Analytics grouping of activity. Key: `analytics_session_id` (UUID).
- **Assignment**: A user/visitor is *bucketed* into an experiment variant. Happens once per experiment (per identity).
- **Exposure**: The assigned variant is actually *shown* (rendered) to the user.
- **Conversion**: The target outcome event (registration completed, subscription success, prompt completed, etc.).
- **Attribution**: Linking conversions to exposures (and therefore variants) for statistical analysis.

Key rule:

> **Conversions must be attributed to exposures, not merely assignments.**

---

## Identity Model (Canonical)

### 1) Canonical bucketing key for experiments: `visitor_id` (always)

To prevent variant flipping mid-funnel:
- Experiments are bucketed by `visitor_id` **even when a user is authenticated**.
- `user_id` is stored alongside for convenience/joins, but the deterministic key remains `visitor_id`.

### 2) Where `visitor_id` comes from: server middleware

- `visitor_id` is set/maintained server-side (via middleware).
- **Clients must not send or control `visitor_id`** for analytics or experiment attribution.

### 3) Visitor context availability to the frontend

Because `visitor_id` should be `httpOnly` (recommended), the frontend cannot read it from `document.cookie`.

Therefore:
- The server should **share a safe `visitor` prop** in Inertia page props (e.g. `{ id: string }`) once the visitor is established.
- Middleware ordering must ensure the visitor exists **before** Inertia props are composed.

### 4) Linking visitor→user

When a user registers/logs in:
- Persist the mapping (`visitors.user_id`, `visitors.converted_at` already exist).
- Do not change experiment bucketing keys.

---

## Consent Model (GDPR-Aligned and Practical)

This architecture assumes the existing cookie preference model:
- `essential`
- `functional`
- `analytics`

### 1) What is allowed without analytics consent

**Essential** (always on):
- `visitor_id` (supports guest flows + fraud/security rate-limiting + linking a guest journey on registration).
- Auth/session cookies, CSRF, localization/country routing.

**Functional** (user-controlled):
- UI preferences (e.g. `ui_complexity`), workflow variant preferences, etc.
- (Optional) Experiment assignment cookies for consistent UX *if* you decide “experiment UX is functional”.

### 2) What requires analytics consent

**Analytics**:
- Sending behavioral events to the analytics ingestion endpoint.
- Recording experiment exposures/conversions for analysis.
- FullStory identification/events (or any third-party analytics tooling).

### 3) Experiments: assignment vs measurement

Experiments have two concerns:
- **Rendering**: show variant A vs B (can be treated as “functional” if it affects UX).
- **Measurement**: record exposure/conversion events (this is “analytics”).

Default policy (recommended):
- Variant rendering may occur regardless of analytics consent (consistent UX).
- **No analytics events are captured without analytics consent**, including exposures and conversions.
- Analyses are explicitly “analytics-consented population only”.

If you need unbiased experiment readouts across all users, that is a legal/policy decision: adjust the cookie banner categories (e.g. a dedicated “optimization” category) and update policy text accordingly.

---

## Decisions (Default Policy)

This section removes ambiguity and sets recommended defaults for v1.

### 1) Experiment assignment vs measurement

- **Assignment/rendering:** treated as **functional**.
  - If `functional=false`, default to **control** (no UX-altering experimentation/personalisation).
  - If `functional=true`, variants may render for consistent UX.
- **Measurement (exposures, conversions, behavioral events):** treated as **analytics**.
  - If `analytics=false`, do **not** record exposures/conversions/events.
  - Analysis is over the **analytics-consented population** (make that explicit in dashboards).

Rationale: consistent UX and avoiding variant flipping is “functional”; recording behavior is “analytics”.

### 2) Revenue and subscription conversions (source of truth)

Use a **hybrid** approach:
- **Source of truth:** operational tables (Stripe/Cashier subscriptions, invoices, webhook payloads).
- **Analytics pipeline integration:** emit a **server-side, idempotent** conversion event derived from the operational trigger (e.g. Stripe webhook event ID as `event_id`) so the experiment processor can attribute revenue using the same unified machinery.

Rationale: client-side analytics is lossy (ad blockers, retries, tab closes). Operational events are complete and auditable while still feeding a unified attribution pipeline.

### 3) Session start before consent (SSR pages, first load)

- Do **not** start analytics sessions before analytics consent.
- After consent:
  - Generate `analytics_session_id`.
  - Send a `consent_granted` event including `initial_page_path` (and optionally a `page_view` immediately after).
  - Treat that moment as session start.

Rationale: avoids creating identifiers pre-consent and keeps compliance story clean and enforceable.

---

## Analytics Session Model (Propagated End-to-End)

### Problem to solve

If `analytics_session_id` exists only in localStorage, the server cannot connect:
- prompt run creation
- server-rendered page views (SSR)
- subscription success callbacks
- other server-side events

### Decision

- `analytics_session_id` is a **client-generated UUID** created only after analytics consent.
- The client must send it on every request via an HTTP header:

`X-Analytics-Session-Id: <uuid>`

Notes:
- The backend treats this as advisory (used for grouping), not as identity.
- If absent, server-side “analytics events” should not be written (or are written with `session_id = null` and excluded from session-based reporting).

---

## Unified Event Contract (Client + Server)

### Event naming

- Use `snake_case` event names.
- Prefer domain-scoped names (e.g. `subscription_success`, `prompt_completed`, `framework_switched`).
- Avoid page/component-specific noise until you need it.

### Event envelope (ingestion API payload)

```json
{
  "events": [
    {
      "event_id": "a4b3c6c9-7b14-4a8a-9f2e-8c2c3a1f3b2e",
      "name": "subscription_success",
      "occurred_at_ms": 1736680000000,
      "properties": {
        "tier": "pro",
        "interval": "yearly",
        "value": 99.00,
        "page_path": "/pricing"
      }
    }
  ]
}
```

### Security constraints

- `visitor_id` and `user_id` are **never accepted from the client**.
- Identity is derived from request context:
  - server-side visitor middleware
  - `$request->user()`

### Idempotency / deduplication

- `event_id` is required and **unique**.
- Insertion uses “insert ignore/upsert” semantics to prevent double-counting from retries.

---

## Storage Model (Recommended Tables)

This section focuses on tables that are fundamental for cohesion and correctness.

### 1) `analytics_events` (raw, append-only)

Purpose: immutable event log. Everything else derives from this.

Key fields:
- `event_id` (UUID, unique): idempotency key
- `name`, `type` (derived), `properties` (JSON)
- `occurred_at`, `received_at`
- `source` (`client` | `server`)
- `session_id` (UUID, nullable)
- `visitor_id` (UUID, nullable but should be present when analytics is consented)
- `user_id` (nullable)
- `page_path`, `referrer`, basic device info (optional denormalization)
- `prompt_run_id` (nullable)

### 2) `analytics_sessions` (derived/maintained)

Purpose: session metrics and entry/exit attribution, for dashboards and funnels.

Key fields:
- `id` (UUID): `analytics_session_id`
- `visitor_id`, `user_id`
- `started_at`, `ended_at`, `duration_seconds`
- `entry_page`, `exit_page`
- counters: `page_count`, `event_count`

### 3) Experiments (definitions and assignment)

Core tables:
- `experiments`
- `experiment_variants`
- `experiment_assignments`

Key decisions:
- `experiment_assignments.visitor_id` is the primary identity key.
- Store `assigned_at` and a `segment_snapshot` for debugging.

### 4) Experiment exposures (first-class)

`experiment_exposures`

One row per *actual exposure*:
- `experiment_id`, `variant_id`
- `visitor_id`, `user_id` (optional)
- `session_id` (optional)
- `occurred_at`
- `page_path` / render context metadata

### 5) Many-to-many attribution: events ↔ experiments

To support overlapping experiments:

`analytics_event_experiments`
- `analytics_event_id` (or `event_id`)
- `experiment_id`
- `variant_id`
- (optional) `exposure_id` (for “conversion attributed to which exposure?” clarity)

Attribution rule (v1):
- A conversion event is linked to **all experiments where the visitor had an exposure** before the conversion time and within the experiment’s run window.

---

## Processing Pipeline (One Ingestion Path, Many Processors)

### Step 1: Ingest (fast, non-blocking)

- Controller validates payload shape, throttles, and dispatches a job.
- The request returns immediately (`200 OK`).

### Step 2: Persist raw events (idempotent)

- Insert raw events into `analytics_events` using `event_id` as the unique key.

### Step 3: Run processors (async)

Processors read from the raw event log and update derived tables:

1) **Session processor**
   - Create/update `analytics_sessions`
   - Maintain entry/exit pages, bounce, duration, counters

2) **Experiment attribution processor**
   - On exposure-type events: write `experiment_exposures`
   - On conversion-type events: create rows in `analytics_event_experiments` for all eligible experiments/variants
   - Update aggregated counts for reporting (see next)

3) **Domain processors** (later phases)
   - framework selections
   - question analytics
   - workflow performance
   - prompt quality

### Aggregations (for fast dashboards)

Keep heavy queries off raw events by maintaining small aggregate tables:
- experiment conversion aggregates (`experiment_conversions`-style)
- daily stats (`analytics_daily_stats`)
- domain-specific daily stats

---

## A/B Testing Model (Correctness-First)

### 1) Assignment is deterministic and sticky

- Deterministic hash-based assignment by `visitor_id`.
- Repeated visits always map to the same variant while the experiment is running.

### 2) Exposure is explicit

- You only count an exposure when the variant content actually rendered.
- For SSR/Inertia pages, the server can emit an exposure event once it knows the page rendered the variant (implementation detail; the contract is what matters).

### 3) Conversions attribute to exposures

Default v1 attribution:
- For each conversion event:
  - find experiments where the visitor had at least one exposure before conversion
  - attribute the conversion to that experiment’s exposed variant
  - support multiple simultaneous attributions (overlapping experiments)

### 4) Fixed-horizon as default

To avoid “peeking” bias:
- Define minimum sample size and minimum run time per experiment.
- Do not auto-stop based on p-values in v1 unless you implement a sequential method.

---

## Implementation Phasing (Aligned and Pragmatic)

### Phase 0 — Foundation: Identity + consent + middleware ordering

- Ensure visitor context exists before Inertia props are created.
- Make `visitor_id` available to frontend via server props (not `document.cookie`).
- Gate analytics + FullStory behind analytics consent.
- Add `X-Analytics-Session-Id` propagation from the frontend after consent.

### Phase 1 — Minimal ingestion + raw event storage (idempotent)

- `/api/analytics/events` that accepts only event batches.
- Server-derived identity; `event_id` idempotency.
- Store to `analytics_events`.

### Phase 2 — Experiments core (assignment + exposure)

- Experiment definition tables + admin CRUD basics.
- Deterministic assignment by `visitor_id`.
- Exposure capture and storage.

### Phase 3 — Attribution + aggregates + dashboards

- Many-to-many attribution (`analytics_event_experiments`).
- Experiment conversion aggregates and minimal results dashboard.
- Session + funnel dashboards driven by aggregates.

### Phase 4+ — Expand event catalog + domain analytics

- Question effectiveness tracking.
- Framework selection analytics.
- Workflow performance and cost monitoring.
- Prompt quality metrics.

---

## Cookies & Client Storage (Recommended Strategy)

This section captures practical guidance for cookies/localStorage so implementation matches the security and consent model.

### 1) `visitor_id` cookie (essential, server-owned)

- Keep `visitor_id` as **essential** because it supports core product behavior:
  - guest journeys (guest prompt runs, linking on registration)
  - abuse prevention / rate limiting
  - consistent UX within a device
- Keep it **`httpOnly`** (recommended) so JavaScript cannot read it.

Implication:
- The frontend must not rely on `document.cookie` to read `visitor_id`.
- If the frontend needs a stable identifier for client-side logic, the server should share a safe `visitor: { id }` prop via Inertia after the visitor is established.

### 2) Analytics consent cookie

- Persist consent in a dedicated cookie.
- Prefer a **namespaced** cookie name (e.g. `bp_cookie_consent`) to avoid collisions and to align with the frontend namespacing utilities.
- Consent must gate:
  - internal analytics ingestion
  - experiment measurement (exposure/conversion events)
  - FullStory identification/events (and any third-party analytics)

### 3) Analytics session identifier (analytics, post-consent)

- `analytics_session_id` should exist **only after analytics consent**.
- Prefer sending it via header (`X-Analytics-Session-Id`) rather than making it a cookie:
  - keeps identifiers explicit and consent-created
  - ensures server-side events can be correlated when relevant

### 4) Avoid copying `visitor_id` into localStorage

- Do not store a “backup” visitor id in localStorage if `visitor_id` is intended to be `httpOnly`.
- Copying it into localStorage defeats the security model (XSS exposure) and can make identity harder to reason about.

### 5) Cookie categories: keep simple unless you add marketing/ads

- `essential` / `functional` / `analytics` is sufficient for v1.
- Add a separate `marketing` category only when you introduce marketing/advertising tooling.
- Avoid adding an “optimization” category unless legal/policy explicitly requires experiments without analytics consent.

---

## Privacy, Retention, and Data Minimization

Recommended defaults (tune to your policy):
- Raw events: short retention (e.g. 90 days) + strict access controls.
- Aggregates: long retention (trend analysis, no per-event payloads).
- Store IP addresses only if necessary; prefer hashing/truncation.

Critical rule:

> Avoid storing new sensitive payloads in event properties (free-text, full prompt contents, emails). Keep properties small and structured.

---

## Relationship to Existing Tables

BettrPrompt already has tables that this architecture must integrate with.

### `visitors` table (existing)

Already contains:
- `id` (UUID) — the `visitor_id` we reference throughout
- `user_id` (nullable) — set when visitor registers/logs in
- `converted_at` — when visitor became a user
- Attribution fields (`utm_source`, `utm_medium`, etc.)
- Personality and context fields

**No changes required.** The new analytics tables reference `visitor_id` as a foreign key.

### `users` table (existing)

Already contains identity, subscription, personality data.

**Additions needed** (Phase 4):
- `prompts_lifetime` — running count for value attribution
- `estimated_hours_saved` — calculated value metric

### `prompt_runs` table (existing)

Already tracks the 3-stage workflow with rich metadata.

**Additions needed** (Phase 4):
- `analytics_session_id` (UUID, nullable) — links prompt to analytics session
- `user_rating` (1-5) — prompt quality rating
- `prompt_copied`, `copy_count` — engagement tracking
- `prompt_edited`, `edit_percentage` — edit tracking

### Integration approach

- Analytics events reference `prompt_run_id` when relevant
- Domain processors read from both `analytics_events` and `prompt_runs`
- Aggregations join across both data sources

---

## Detailed Schema Definitions

All migrations use Laravel conventions. Tables are listed in dependency order.

### Core Analytics Tables

#### `analytics_events` (raw event log)

```php
Schema::create('analytics_events', function (Blueprint $table) {
    $table->uuid('event_id')->primary(); // Client-generated, idempotency key

    // Event identification
    $table->string('name', 100)->index(); // e.g., 'subscription_success', 'prompt_completed'
    $table->string('type', 50)->index(); // Derived category: 'conversion', 'engagement', 'exposure'
    $table->json('properties')->nullable(); // Event-specific data

    // Identity (server-derived, never from client)
    $table->uuid('visitor_id')->nullable()->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->uuid('session_id')->nullable()->index(); // analytics_session_id from header

    // Source
    $table->enum('source', ['client', 'server'])->default('client');

    // Context (denormalised for query performance)
    $table->string('page_path', 255)->nullable();
    $table->string('referrer', 500)->nullable();
    $table->string('device_type', 20)->nullable(); // desktop, mobile, tablet
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();
    $table->string('country_code', 2)->nullable();

    // Prompt context (when applicable)
    $table->foreignId('prompt_run_id')->nullable()->index();

    // Timestamps
    $table->timestamp('occurred_at')->index(); // When event happened (client time)
    $table->timestamp('received_at')->useCurrent(); // When server received it
    $table->timestamps();

    // Query indexes
    $table->index(['visitor_id', 'occurred_at']);
    $table->index(['name', 'occurred_at']);
    $table->index(['type', 'occurred_at']);
    $table->index(['session_id', 'occurred_at']);
});
```

#### `analytics_sessions` (derived from events)

```php
Schema::create('analytics_sessions', function (Blueprint $table) {
    $table->uuid('id')->primary(); // The analytics_session_id

    // Identity
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

    // Timing
    $table->timestamp('started_at')->index();
    $table->timestamp('ended_at')->nullable();
    $table->unsignedInteger('duration_seconds')->nullable();

    // Navigation
    $table->string('entry_page', 255)->nullable();
    $table->string('exit_page', 255)->nullable();
    $table->unsignedSmallInteger('page_count')->default(0);
    $table->unsignedSmallInteger('event_count')->default(0);

    // Attribution (captured at session start)
    $table->string('utm_source', 100)->nullable();
    $table->string('utm_medium', 100)->nullable();
    $table->string('utm_campaign', 100)->nullable();
    $table->string('referrer', 500)->nullable();

    // Device (captured at session start)
    $table->string('device_type', 20)->nullable();
    $table->string('browser', 50)->nullable();
    $table->string('os', 50)->nullable();
    $table->string('country_code', 2)->nullable();

    // Outcomes
    $table->boolean('is_bounce')->default(true); // False after 2nd page view
    $table->boolean('converted')->default(false);
    $table->string('conversion_type', 50)->nullable(); // registered, subscribed_pro, etc.

    // Prompt activity
    $table->unsignedSmallInteger('prompts_started')->default(0);
    $table->unsignedSmallInteger('prompts_completed')->default(0);

    $table->timestamps();

    // Indexes
    $table->index(['visitor_id', 'started_at']);
    $table->index(['started_at']);
    $table->index(['converted', 'started_at']);
});
```

### Experiment Tables

#### `experiments`

```php
Schema::create('experiments', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 100)->unique(); // URL-safe identifier
    $table->string('name', 200);
    $table->text('description')->nullable();
    $table->text('hypothesis')->nullable();

    // Status
    $table->enum('status', ['draft', 'running', 'paused', 'completed', 'archived'])
          ->default('draft')->index();

    // Timing
    $table->timestamp('started_at')->nullable();
    $table->timestamp('ended_at')->nullable();
    $table->unsignedInteger('minimum_runtime_hours')->default(168); // 1 week

    // Goal
    $table->string('goal_event', 100); // e.g., 'subscription_success'
    $table->string('goal_type', 50)->default('conversion'); // conversion, revenue, engagement

    // Targeting (JSON rules, null = all visitors)
    $table->json('targeting_rules')->nullable();

    // Traffic allocation
    $table->unsignedTinyInteger('traffic_percentage')->default(100); // 0-100

    // Statistical settings
    $table->unsignedInteger('minimum_sample_size')->nullable();
    $table->decimal('minimum_detectable_effect', 5, 2)->nullable(); // e.g., 0.05 = 5%

    // Winner
    $table->foreignId('winner_variant_id')->nullable();
    $table->timestamp('winner_declared_at')->nullable();

    // Metadata
    $table->boolean('is_personality_research')->default(false);
    $table->string('personality_hypothesis', 500)->nullable();

    $table->timestamps();

    $table->index(['status', 'started_at']);
});
```

#### `experiment_variants`

```php
Schema::create('experiment_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();

    $table->string('slug', 100); // e.g., 'control', 'variant_a'
    $table->string('name', 200);
    $table->text('description')->nullable();

    $table->boolean('is_control')->default(false);
    $table->unsignedTinyInteger('weight')->default(50); // Relative weight for allocation

    // Variant-specific configuration (JSON)
    $table->json('config')->nullable();

    $table->timestamps();

    $table->unique(['experiment_id', 'slug']);
    $table->index(['experiment_id', 'is_control']);
});
```

#### `experiment_assignments`

```php
Schema::create('experiment_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();

    // Identity (visitor_id is the canonical bucketing key)
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable()->index(); // For convenience, not bucketing

    // Assignment metadata
    $table->timestamp('assigned_at');
    $table->json('segment_snapshot')->nullable(); // Targeting context at assignment time

    $table->timestamps();

    // One assignment per visitor per experiment
    $table->unique(['experiment_id', 'visitor_id']);
    $table->index(['visitor_id', 'assigned_at']);
});
```

#### `experiment_exposures`

```php
Schema::create('experiment_exposures', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
    $table->foreignId('assignment_id')->constrained('experiment_assignments')->cascadeOnDelete();

    // Identity
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable();
    $table->uuid('session_id')->nullable();

    // Context
    $table->string('page_path', 255)->nullable();
    $table->string('component', 100)->nullable(); // Which component rendered the variant

    $table->timestamp('occurred_at')->index();
    $table->timestamps();

    // Indexes for attribution queries
    $table->index(['experiment_id', 'visitor_id', 'occurred_at']);
    $table->index(['visitor_id', 'occurred_at']);
});
```

#### `analytics_event_experiments` (many-to-many attribution)

```php
Schema::create('analytics_event_experiments', function (Blueprint $table) {
    $table->id();
    $table->uuid('event_id'); // References analytics_events.event_id
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
    $table->foreignId('exposure_id')->nullable()
          ->constrained('experiment_exposures')->nullOnDelete();

    $table->timestamps();

    // Prevent duplicate attribution
    $table->unique(['event_id', 'experiment_id']);

    // Query indexes
    $table->index(['experiment_id', 'variant_id']);

    // Foreign key to events (not constrained for performance)
    $table->foreign('event_id')->references('event_id')->on('analytics_events')
          ->cascadeOnDelete();
});
```

#### `experiment_conversions` (aggregation for fast stats)

```php
Schema::create('experiment_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();

    // Counts (updated by processor)
    $table->unsignedInteger('exposures')->default(0);
    $table->unsignedInteger('conversions')->default(0);
    $table->unsignedInteger('unique_visitors_exposed')->default(0);
    $table->unsignedInteger('unique_visitors_converted')->default(0);

    // Revenue (if goal_type = revenue)
    $table->decimal('total_revenue', 12, 2)->default(0);

    // Derived (updated by processor)
    $table->decimal('conversion_rate', 8, 6)->nullable();
    $table->decimal('revenue_per_visitor', 10, 4)->nullable();

    $table->timestamps();

    $table->unique(['experiment_id', 'variant_id']);
});
```

#### `experiment_exclusion_groups`

```php
Schema::create('experiment_exclusion_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('experiment_exclusion_group_members', function (Blueprint $table) {
    $table->foreignId('exclusion_group_id')
          ->constrained('experiment_exclusion_groups')->cascadeOnDelete();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->primary(['exclusion_group_id', 'experiment_id']);
});
```

### Domain Analytics Tables

These tables support BettrPrompt-specific analytics: framework selection, question effectiveness, and workflow performance.

#### `framework_selections` (tracks recommended vs chosen)

```php
Schema::create('framework_selections', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

    // Identity
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable();

    // Framework recommendation
    $table->string('recommended_framework', 50); // What system recommended
    $table->string('chosen_framework', 50);      // What user selected (may be same)
    $table->boolean('accepted_recommendation');   // Derived: recommended == chosen

    // Recommendation context
    $table->string('task_category', 50)->nullable(); // DECISION, STRATEGY, etc.
    $table->string('personality_type', 10)->nullable(); // INTJ, ENFP, etc.
    $table->json('recommendation_scores')->nullable(); // All frameworks and scores

    // Outcome (updated after prompt completion)
    $table->unsignedTinyInteger('prompt_rating')->nullable(); // 1-5 user rating
    $table->boolean('prompt_copied')->nullable();
    $table->boolean('prompt_edited')->nullable();
    $table->decimal('edit_percentage', 5, 2)->nullable();

    $table->timestamp('selected_at');
    $table->timestamps();

    $table->index(['recommended_framework', 'chosen_framework']);
    $table->index(['personality_type', 'task_category']);
    $table->index(['accepted_recommendation', 'selected_at']);
});
```

#### `question_analytics` (tracks question bank usage)

```php
Schema::create('question_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

    // Identity
    $table->uuid('visitor_id')->index();
    $table->foreignId('user_id')->nullable();

    // Question identification (from question_bank.md)
    $table->string('question_id', 10)->index(); // U1, U2, D1, S1, etc.
    $table->string('question_category', 20);    // universal, decision, strategy, etc.

    // Presentation context
    $table->string('personality_variant', 50)->nullable(); // Which phrasing used
    $table->unsignedTinyInteger('display_order');          // Order shown to user
    $table->boolean('was_required');

    // User response
    $table->enum('response_status', ['answered', 'skipped', 'not_shown'])->index();
    $table->unsignedSmallInteger('response_length')->nullable(); // Character count
    $table->unsignedInteger('time_to_answer_ms')->nullable();    // Time spent

    // Outcome correlation (updated after prompt completion)
    $table->unsignedTinyInteger('prompt_rating')->nullable();
    $table->boolean('prompt_copied')->nullable();

    $table->timestamp('presented_at');
    $table->timestamps();

    $table->index(['question_id', 'response_status']);
    $table->index(['question_category', 'response_status']);
    $table->index(['personality_variant', 'response_status']);
});
```

#### `workflow_analytics` (tracks n8n workflow performance)

```php
Schema::create('workflow_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->constrained()->cascadeOnDelete();

    // Workflow identification
    $table->unsignedTinyInteger('workflow_stage'); // 0, 1, 2
    $table->string('workflow_version', 20)->nullable(); // Track n8n workflow versions

    // Timing
    $table->timestamp('started_at');
    $table->timestamp('completed_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();

    // Status
    $table->enum('status', ['processing', 'completed', 'failed', 'timeout'])->index();
    $table->string('error_code', 50)->nullable();
    $table->text('error_message')->nullable();

    // Cost tracking
    $table->unsignedInteger('input_tokens')->nullable();
    $table->unsignedInteger('output_tokens')->nullable();
    $table->decimal('estimated_cost_usd', 8, 6)->nullable();
    $table->string('model_used', 50)->nullable();

    // Retry tracking
    $table->unsignedTinyInteger('attempt_number')->default(1);
    $table->boolean('was_retry')->default(false);

    $table->timestamps();

    $table->index(['workflow_stage', 'status']);
    $table->index(['status', 'started_at']);
    $table->index(['workflow_version', 'status']);
});
```

#### `prompt_quality_metrics` (aggregated prompt outcomes)

```php
Schema::create('prompt_quality_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('prompt_run_id')->unique()->constrained()->cascadeOnDelete();

    // User engagement
    $table->unsignedTinyInteger('user_rating')->nullable(); // 1-5
    $table->boolean('was_copied')->default(false);
    $table->unsignedSmallInteger('copy_count')->default(0);
    $table->boolean('was_edited')->default(false);
    $table->decimal('edit_percentage', 5, 2)->nullable(); // % of prompt changed

    // Quality signals
    $table->unsignedSmallInteger('prompt_length')->nullable(); // Final prompt length
    $table->unsignedTinyInteger('questions_answered')->default(0);
    $table->unsignedTinyInteger('questions_skipped')->default(0);
    $table->unsignedInteger('time_to_complete_ms')->nullable();

    // Context
    $table->string('task_category', 50)->nullable();
    $table->string('framework_used', 50)->nullable();
    $table->string('personality_type', 10)->nullable();

    // Composite scores (calculated by processor)
    $table->decimal('engagement_score', 5, 2)->nullable(); // 0-100
    $table->decimal('quality_score', 5, 2)->nullable();    // 0-100

    $table->timestamps();

    $table->index(['user_rating']);
    $table->index(['task_category', 'framework_used']);
    $table->index(['personality_type']);
});
```

### Aggregation Tables

#### `analytics_daily_stats`

```php
Schema::create('analytics_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date')->index();

    // Traffic
    $table->unsignedInteger('unique_visitors')->default(0);
    $table->unsignedInteger('total_sessions')->default(0);
    $table->unsignedInteger('total_page_views')->default(0);
    $table->decimal('avg_session_duration_seconds', 10, 2)->nullable();
    $table->decimal('bounce_rate', 5, 4)->nullable();

    // Conversions
    $table->unsignedInteger('registrations')->default(0);
    $table->unsignedInteger('subscriptions_free')->default(0);
    $table->unsignedInteger('subscriptions_pro')->default(0);
    $table->unsignedInteger('subscriptions_business')->default(0);
    $table->decimal('total_revenue_usd', 12, 2)->default(0);

    // Prompts
    $table->unsignedInteger('prompts_started')->default(0);
    $table->unsignedInteger('prompts_completed')->default(0);
    $table->decimal('prompt_completion_rate', 5, 4)->nullable();
    $table->decimal('avg_prompt_rating', 3, 2)->nullable();

    // By source (JSON for flexibility)
    $table->json('by_utm_source')->nullable();
    $table->json('by_country')->nullable();
    $table->json('by_device_type')->nullable();

    $table->timestamps();

    $table->unique('date');
});
```

#### `framework_daily_stats`

```php
Schema::create('framework_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->string('framework', 50);

    // Usage
    $table->unsignedInteger('times_recommended')->default(0);
    $table->unsignedInteger('times_chosen')->default(0);
    $table->unsignedInteger('times_accepted')->default(0); // Recommended and chosen
    $table->decimal('acceptance_rate', 5, 4)->nullable();

    // Quality
    $table->decimal('avg_rating', 3, 2)->nullable();
    $table->unsignedInteger('prompts_copied')->default(0);
    $table->unsignedInteger('prompts_edited')->default(0);
    $table->decimal('copy_rate', 5, 4)->nullable();

    // By personality (JSON for top patterns)
    $table->json('by_personality_type')->nullable();
    $table->json('by_task_category')->nullable();

    $table->timestamps();

    $table->unique(['date', 'framework']);
    $table->index(['framework', 'date']);
});
```

#### `question_daily_stats`

```php
Schema::create('question_daily_stats', function (Blueprint $table) {
    $table->id();
    $table->date('date');
    $table->string('question_id', 10); // U1, D1, S1, etc.

    // Presentation
    $table->unsignedInteger('times_shown')->default(0);
    $table->unsignedInteger('times_answered')->default(0);
    $table->unsignedInteger('times_skipped')->default(0);
    $table->decimal('answer_rate', 5, 4)->nullable();
    $table->decimal('skip_rate', 5, 4)->nullable();

    // Response quality
    $table->decimal('avg_response_length', 8, 2)->nullable();
    $table->decimal('avg_time_to_answer_ms', 10, 2)->nullable();

    // Outcome correlation
    $table->decimal('avg_prompt_rating_when_answered', 3, 2)->nullable();
    $table->decimal('avg_prompt_rating_when_skipped', 3, 2)->nullable();
    $table->decimal('copy_rate_when_answered', 5, 4)->nullable();
    $table->decimal('copy_rate_when_skipped', 5, 4)->nullable();

    // Personality variants (JSON)
    $table->json('by_personality_variant')->nullable();

    $table->timestamps();

    $table->unique(['date', 'question_id']);
    $table->index(['question_id', 'date']);
});
```

---

## Event Catalog (v1)

This catalog defines all events to be tracked in v1. Events are grouped by type and include their properties schema.

### Lifecycle Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `consent_granted` | system | User grants analytics consent | `categories: string[]`, `initial_page_path: string` |
| `consent_revoked` | system | User revokes analytics consent | `categories: string[]` |
| `session_start` | system | Analytics session begins (post-consent) | `entry_page: string`, `referrer?: string` |
| `page_view` | engagement | User views a page | `path: string`, `title?: string`, `referrer?: string` |

### Registration & Auth Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `registration_started` | funnel | User begins registration flow | `source: string` (modal, page, etc.) |
| `registration_completed` | conversion | User completes registration | `method: string` (email, google, etc.) |
| `login_completed` | engagement | User logs in | `method: string` |
| `password_reset_requested` | engagement | User requests password reset | — |

### Subscription Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `pricing_page_viewed` | funnel | User views pricing page | — |
| `subscription_initiated` | funnel | User clicks subscribe button | `tier: string`, `interval: string` |
| `subscription_success` | conversion | Subscription completed | `tier: string`, `interval: string`, `value: number`, `currency: string` |
| `subscription_cancelled` | engagement | User cancels subscription | `tier: string`, `reason?: string` |

### Prompt Builder Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `prompt_started` | funnel | User begins prompt builder | `source: string`, `prompt_run_id: uuid` |
| `task_entered` | engagement | User enters task description | `prompt_run_id: uuid`, `task_length: int` |
| `personality_applied` | engagement | Personality assessment applied | `prompt_run_id: uuid`, `personality_type: string` |
| `questions_presented` | engagement | Clarifying questions shown | `prompt_run_id: uuid`, `question_ids: string[]`, `question_count: int` |
| `question_answered` | engagement | User answers a question | `prompt_run_id: uuid`, `question_id: string`, `response_length: int`, `time_ms: int` |
| `question_skipped` | engagement | User skips a question | `prompt_run_id: uuid`, `question_id: string` |
| `framework_recommended` | engagement | Framework recommendation made | `prompt_run_id: uuid`, `recommended: string`, `scores: object` |
| `framework_switched` | engagement | User changes framework | `prompt_run_id: uuid`, `from: string`, `to: string` |
| `prompt_generated` | engagement | Final prompt generated | `prompt_run_id: uuid`, `framework: string`, `prompt_length: int` |
| `prompt_completed` | conversion | User accepts final prompt | `prompt_run_id: uuid`, `framework: string`, `questions_answered: int`, `questions_skipped: int` |
| `prompt_copied` | engagement | User copies prompt | `prompt_run_id: uuid`, `copy_count: int` |
| `prompt_edited` | engagement | User edits generated prompt | `prompt_run_id: uuid`, `edit_percentage: float` |
| `prompt_rated` | engagement | User rates prompt | `prompt_run_id: uuid`, `rating: int` (1-5) |
| `prompt_abandoned` | engagement | User leaves without completing | `prompt_run_id: uuid`, `stage: string`, `time_spent_ms: int` |

### Experiment Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `experiment_exposure` | exposure | Variant rendered to user | `experiment_slug: string`, `variant_slug: string`, `component?: string` |

### Error Events

| Event Name | Type | Description | Properties |
|------------|------|-------------|------------|
| `workflow_failed` | system | n8n workflow failed | `prompt_run_id: uuid`, `workflow_stage: int`, `error_code: string` |
| `client_error` | system | Client-side error | `error_type: string`, `message: string`, `stack?: string` |

---

## A/B Test Priority Roadmap

Tests are organised into tiers based on potential impact, implementation complexity, and learning value. Start with Tier 1 and progress sequentially.

### Tier 1: High-Impact, Low-Risk (Start Here)

These tests target high-traffic, high-value touchpoints with minimal risk.

#### 1.1 Registration CTA Copy

**Location:** Homepage hero, pricing page
**Hypothesis:** More specific value propositions will increase registration rate
**Variants:**
- Control: "Get Started Free"
- Variant A: "Create Your First Prompt"
- Variant B: "Try Free — No Credit Card"

**Goal:** `registration_completed`
**Sample Size:** ~500 visitors per variant
**Expected MDE:** 15% relative improvement

#### 1.2 Pricing Page Layout

**Location:** `/[country]/pricing`
**Hypothesis:** Emphasising annual savings will increase Pro subscription rate
**Variants:**
- Control: Current monthly-first view
- Variant A: Annual-first with monthly toggle
- Variant B: Side-by-side comparison with savings highlighted

**Goal:** `subscription_success` (tier: pro)
**Sample Size:** ~300 visitors per variant
**Expected MDE:** 20% relative improvement

#### 1.3 Social Proof Placement

**Location:** Homepage, pricing page
**Hypothesis:** Visible testimonials increase trust and conversion
**Variants:**
- Control: Testimonials below fold
- Variant A: Testimonial carousel in hero
- Variant B: "Join X users" counter + mini testimonials

**Goal:** `registration_completed`
**Sample Size:** ~400 visitors per variant

### Tier 2: Prompt Builder Optimisation

These tests optimise the core product experience.

#### 2.1 Question Quantity

**Hypothesis:** Fewer questions reduce abandonment without hurting prompt quality
**Variants:**
- Control: Full question set (6-8 questions)
- Variant A: Reduced set (4-5 questions)
- Variant B: Progressive disclosure (3 required + "want more?")

**Goals:**
- Primary: `prompt_completed`
- Secondary: `prompt_rated` (maintain quality)
**Sample Size:** ~250 completed prompts per variant

#### 2.2 Framework Selection UI

**Hypothesis:** Explaining framework benefits increases acceptance rate
**Variants:**
- Control: Framework name + one-line description
- Variant A: Framework name + benefits bullets + "Why this works for you"
- Variant B: Interactive framework preview

**Goal:** `framework_switched` rate (lower is better for recommendation quality)

#### 2.3 Progress Indicators

**Hypothesis:** Clear progress reduces abandonment
**Variants:**
- Control: No progress indicator
- Variant A: Step counter (Step 2 of 4)
- Variant B: Progress bar with stage names

**Goal:** `prompt_completed`

### Tier 3: Personality-Segmented Tests

These tests validate personality-driven optimisations.

#### 3.1 Question Phrasing by Personality

**Hypothesis:** Personality-matched question phrasing increases answer rate
**Variants:**
- Control: Neutral phrasing for all
- Variant: Personality-specific phrasing (from question_bank.md variants)

**Segmentation:** Run separately for each major personality dimension (T/F, S/N, J/P)
**Goal:** `question_answered` rate per question

#### 3.2 UI Complexity by Personality

**Hypothesis:** Matching UI complexity to personality preference improves completion
**Variants:**
- Control: Standard UI for all
- Variant: Auto-adjusted UI based on personality (more detail for high-J, simpler for high-P)

**Goal:** `prompt_completed`, `prompt_rated`

#### 3.3 Prompt Length Preferences

**Hypothesis:** Personality types have different optimal prompt lengths
**Variants:**
- Control: Standard length prompts
- Variant A: Concise prompts (high-P, high-N optimised)
- Variant B: Detailed prompts (high-J, high-S optimised)

**Segmentation:** By personality type
**Goal:** `prompt_rated`, `prompt_copied`

### Tier 4: Monetisation Tests

These tests optimise revenue per visitor.

#### 4.1 Free Trial Length

**Hypothesis:** Different trial lengths affect conversion to paid
**Variants:**
- Control: 7-day trial
- Variant A: 14-day trial
- Variant B: 3 prompts (usage-based trial)

**Goal:** `subscription_success` (tier: pro or business)
**Attribution window:** 30 days

#### 4.2 Upgrade Prompts

**Hypothesis:** Contextual upgrade prompts convert better than generic ones
**Variants:**
- Control: Generic "Upgrade to Pro" modal
- Variant A: "You've used X prompts this month — unlock unlimited"
- Variant B: "Pro users see Y% better prompt ratings"

**Goal:** `subscription_success`

#### 4.3 Annual vs Monthly Default

**Hypothesis:** Defaulting to annual increases LTV
**Variants:**
- Control: Monthly selected by default
- Variant A: Annual selected by default
- Variant B: No default (force explicit selection)

**Goal:** `subscription_success`, `total_revenue` per conversion

### Tier 5: Advanced Tests

These require more infrastructure and longer run times.

#### 5.1 Onboarding Flow Variations

**Hypothesis:** Guided onboarding improves activation
**Variants:**
- Control: Self-service (current)
- Variant A: Guided tour (tooltips)
- Variant B: Video walkthrough + CTA

**Goal:** First `prompt_completed` within 7 days of registration

#### 5.2 Email Nurture Sequences

**Hypothesis:** Segmented email content improves re-engagement
**Variants:**
- Control: Generic nurture emails
- Variant: Personality-segmented content

**Goal:** Return visit within 14 days, `prompt_completed`

### Statistical Considerations

For all tests:
- **Minimum runtime:** 7 days (captures weekly patterns)
- **Minimum sample:** Calculate based on expected baseline rate and MDE
- **Fixed-horizon analysis:** No peeking until minimum runtime + sample reached
- **Exclusion groups:** Tests on same page/flow should be mutually exclusive

### Key Metrics by Funnel Stage

| Stage | Primary Metric | Secondary Metrics |
|-------|---------------|-------------------|
| Awareness | Unique visitors | Page views, bounce rate |
| Interest | `registration_started` | Time on site, pages per session |
| Registration | `registration_completed` | Registration rate, drop-off points |
| Activation | First `prompt_completed` | Time to first prompt, questions answered |
| Revenue | `subscription_success` | ARPU, conversion rate by tier |
| Retention | Monthly active prompts | Return visit rate, prompts per user |

---

## Advanced Opportunities

These opportunities extend beyond v1 but inform architectural decisions.

### Collaborative Intelligence

**Concept:** Analyse successful prompts across users to improve recommendations.

**Implementation:**
- Track `prompt_rated` with high ratings (4-5)
- Identify common patterns: framework × task category × personality
- Use patterns to improve default recommendations

**Privacy consideration:** Only analyse aggregate patterns, never individual prompt content.

### Adaptive UX Personalisation

**Concept:** Automatically adjust UI based on observed behaviour, not just stated personality.

**Signals:**
- Time spent on questions → Adjust question count
- Scrolling patterns → Adjust content density
- Click patterns → Adjust information hierarchy

**Implementation:** Store behavioural signals in `analytics_events`, build ML model to predict optimal UX settings.

### Predictive Analytics

**Concept:** Predict user outcomes to enable proactive intervention.

**Examples:**
- Churn prediction: Users likely to cancel → trigger retention campaign
- Conversion prediction: Visitors likely to subscribe → adjust pricing page
- Quality prediction: Prompts likely to rate poorly → offer additional questions

**Implementation:** Requires significant event history. Start with simple heuristics (e.g., "no login in 14 days"), graduate to ML.

### Framework Evolution Intelligence

**Concept:** Use analytics to evolve frameworks themselves.

**Approach:**
1. Track framework effectiveness by task category and personality
2. Identify gaps where no framework performs well
3. A/B test framework modifications
4. Graduate winning modifications to defaults

### Question Bank Intelligence

**Concept:** Dynamically optimise question selection based on effectiveness.

**Approach:**
1. Track `question_answered` → `prompt_rated` correlation by question
2. Identify questions that improve outcomes vs. questions that don't help
3. Automatically adjust question priority based on learned value
4. Eventually: Generate new questions for underserved task categories

### Economic Value Attribution

**Concept:** Calculate the £/$ value of each user and attribute to acquisition source.

**Metrics:**
- Lifetime value (LTV) per user
- Customer acquisition cost (CAC) by source
- Payback period
- Prompt value: (subscription revenue / prompts generated)

**Implementation:** Requires subscription revenue events attributed to users, then joined with acquisition source from `visitors`.

### Privacy as Competitive Moat

**Concept:** Position first-party analytics and privacy-respecting experimentation as a feature.

**Marketing angles:**
- "Your prompts stay private — we don't train on your data"
- "GDPR-compliant analytics with no third-party tracking"
- Transparency report on data usage

### Cross-Platform Intelligence (Future)

**Concept:** If BettrPrompt expands to browser extension, mobile app, or API, unify analytics across platforms.

**Preparation:**
- Keep event schema platform-agnostic
- Include `platform` field in events
- Design identity resolution for cross-platform users

---

## Detailed Implementation Phases

Each phase includes specific tasks, dependencies, and acceptance criteria.

### Phase 0: Foundation (Identity + Consent + Middleware)

**Goal:** Establish the identity and consent infrastructure that everything else depends on.

#### Task 0.1: Visitor Context Middleware Ordering

**Description:** Ensure `visitor_id` is available before Inertia props are composed.

**Steps:**
1. Audit current middleware order in `app/Http/Kernel.php`
2. Ensure `SetVisitor` middleware runs before `HandleInertiaRequests`
3. Add test: visitor context is available in Inertia shared props

**Files:**
- `app/Http/Kernel.php`
- `app/Http/Middleware/SetVisitor.php`
- `tests/Feature/Middleware/SetVisitorTest.php`

**Acceptance Criteria:**
- [ ] Inertia pages receive `visitor.id` in props
- [ ] `visitor_id` is not readable via `document.cookie`
- [ ] Test confirms middleware ordering

#### Task 0.2: Share Visitor Context to Frontend

**Description:** Make `visitor_id` available to Vue without exposing in cookies.

**Steps:**
1. Update `HandleInertiaRequests::share()` to include visitor object
2. Create `useVisitor()` composable for frontend access
3. Add TypeScript types for visitor prop

**Files:**
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/Composables/useVisitor.ts`
- `resources/js/types/global.d.ts`

**Acceptance Criteria:**
- [ ] `useVisitor()` returns `{ id: string }`
- [ ] Visitor ID available on first render (SSR)
- [ ] TypeScript types complete

#### Task 0.3: Analytics Consent Integration

**Description:** Ensure analytics tracking is gated by consent state.

**Steps:**
1. Audit current cookie consent implementation
2. Create `useAnalyticsConsent()` composable
3. Gate FullStory initialisation behind consent
4. Add consent state to analytics event payloads

**Files:**
- `resources/js/Composables/useAnalyticsConsent.ts`
- `resources/js/plugins/analytics.ts` (or equivalent)

**Acceptance Criteria:**
- [ ] No analytics events sent before consent
- [ ] FullStory only initialised after consent
- [ ] Consent revocation stops event collection

#### Task 0.4: Analytics Session ID Generation

**Description:** Generate and propagate analytics session ID post-consent.

**Steps:**
1. Create session ID generation logic (UUID v4)
2. Store in memory (not cookie) after consent
3. Add `X-Analytics-Session-Id` header to all requests
4. Configure Inertia to include header automatically

**Files:**
- `resources/js/services/analyticsSession.ts`
- `resources/js/plugins/axios.ts` (or equivalent)

**Acceptance Criteria:**
- [ ] Session ID generated only after analytics consent
- [ ] Header present on all subsequent requests
- [ ] Session ID persists across page navigations
- [ ] Session ID clears on consent revocation

### Phase 1: Event Ingestion + Storage

**Goal:** Create the non-blocking ingestion pipeline and raw event storage.

#### Task 1.1: Create Analytics Events Migration

**Description:** Create the `analytics_events` table with full schema.

**Steps:**
1. Create migration from schema in this document
2. Run migration
3. Verify indexes created correctly

**Files:**
- `database/migrations/xxxx_create_analytics_events_table.php`

**Acceptance Criteria:**
- [ ] Table created with all columns and indexes
- [ ] `event_id` is unique primary key
- [ ] Foreign key to `prompt_runs` if applicable

#### Task 1.2: Create Analytics Sessions Migration

**Description:** Create the `analytics_sessions` table.

**Steps:**
1. Create migration from schema in this document
2. Run migration

**Files:**
- `database/migrations/xxxx_create_analytics_sessions_table.php`

**Acceptance Criteria:**
- [ ] Table created with all columns and indexes

#### Task 1.3: Create Analytics Event Model

**Description:** Create Eloquent model for analytics events.

**Steps:**
1. Create model with UUID primary key
2. Define casts for JSON and timestamp fields
3. Add relationships (visitor, user, promptRun)
4. Add scopes for common queries

**Files:**
- `app/Models/AnalyticsEvent.php`

**Acceptance Criteria:**
- [ ] Model uses UUID primary key
- [ ] JSON properties cast correctly
- [ ] Relationships defined

#### Task 1.4: Create Event Ingestion Controller

**Description:** Create API endpoint for event ingestion.

**Steps:**
1. Create controller with `store()` method
2. Validate event batch structure
3. Derive identity from request (never trust client)
4. Dispatch job and return immediately
5. Add rate limiting

**Files:**
- `app/Http/Controllers/Api/AnalyticsEventController.php`
- `app/Http/Requests/StoreAnalyticsEventsRequest.php`
- `routes/api.php`

**Acceptance Criteria:**
- [ ] Endpoint accepts event batches
- [ ] Returns 200 immediately (non-blocking)
- [ ] Rejects events without valid structure
- [ ] Does not accept `visitor_id` or `user_id` from client

#### Task 1.5: Create Event Processing Job

**Description:** Create async job to persist events.

**Steps:**
1. Create job class with retry logic
2. Implement idempotent insertion (upsert by `event_id`)
3. Enrich events with server-derived identity
4. Handle batch insertions efficiently

**Files:**
- `app/Jobs/ProcessAnalyticsEvents.php`

**Acceptance Criteria:**
- [ ] Job processes event batches
- [ ] Duplicate `event_id` handled gracefully (no error)
- [ ] Events have `visitor_id` and `user_id` from request context
- [ ] Job is retryable

#### Task 1.6: Create Frontend Analytics Service

**Description:** Create TypeScript service for tracking events.

**Steps:**
1. Create `AnalyticsService` class
2. Implement event queuing and batching
3. Gate all tracking behind consent check
4. Include session ID header automatically
5. Handle offline/retry scenarios

**Files:**
- `resources/js/services/analytics.ts`
- `resources/js/types/analytics.d.ts`

**Acceptance Criteria:**
- [ ] `track(name, properties)` method available
- [ ] Events batched (configurable interval)
- [ ] No events sent without consent
- [ ] Session ID included in all requests

### Phase 2: Experiment Infrastructure

**Goal:** Build experiment definition, assignment, and exposure tracking.

#### Task 2.1: Create Experiment Tables Migration

**Description:** Create all experiment-related tables.

**Steps:**
1. Create migration with all experiment tables from schema
2. Run migration
3. Verify foreign keys and indexes

**Files:**
- `database/migrations/xxxx_create_experiment_tables.php`

**Acceptance Criteria:**
- [ ] All experiment tables created
- [ ] Foreign keys correctly reference parent tables
- [ ] Exclusion group many-to-many relationship works

#### Task 2.2: Create Experiment Models

**Description:** Create Eloquent models for experiment system.

**Steps:**
1. Create `Experiment` model with status scopes
2. Create `ExperimentVariant` model with weight logic
3. Create `ExperimentAssignment` model
4. Create `ExperimentExposure` model
5. Define all relationships

**Files:**
- `app/Models/Experiment.php`
- `app/Models/ExperimentVariant.php`
- `app/Models/ExperimentAssignment.php`
- `app/Models/ExperimentExposure.php`

**Acceptance Criteria:**
- [ ] All models created with relationships
- [ ] Status enum casts correctly
- [ ] Scopes for active experiments work

#### Task 2.3: Create Experiment Assignment Service

**Description:** Create service for deterministic variant assignment.

**Steps:**
1. Create `ExperimentService` class
2. Implement deterministic hash-based bucketing by `visitor_id`
3. Check targeting rules before assignment
4. Handle exclusion groups
5. Cache assignments for request lifetime

**Files:**
- `app/Services/ExperimentService.php`

**Acceptance Criteria:**
- [ ] Same `visitor_id` always gets same variant
- [ ] Targeting rules respected
- [ ] Exclusion groups prevent overlapping assignment
- [ ] Traffic percentage allocation works

#### Task 2.4: Create Experiment Middleware

**Description:** Create middleware to assign experiments and share to frontend.

**Steps:**
1. Create middleware that checks active experiments
2. Get/create assignments for current visitor
3. Share assignments to Inertia props
4. Include config for each assigned variant

**Files:**
- `app/Http/Middleware/AssignExperiments.php`
- `app/Http/Kernel.php` (register middleware)

**Acceptance Criteria:**
- [ ] Experiments assigned on each request
- [ ] Assignments available in Vue via props
- [ ] Assignment respects functional consent setting

#### Task 2.5: Create useExperiment Composable

**Description:** Create Vue composable for accessing experiment variants.

**Steps:**
1. Create `useExperiment(experimentSlug)` composable
2. Return variant slug and config
3. Track exposure automatically when variant accessed
4. Gate exposure tracking behind analytics consent

**Files:**
- `resources/js/Composables/useExperiment.ts`

**Acceptance Criteria:**
- [ ] Returns variant for given experiment
- [ ] Exposure event fired on first access (if analytics consent)
- [ ] Returns control if experiment not running

#### Task 2.6: Create Exposure Tracking

**Description:** Implement exposure event capture and storage.

**Steps:**
1. Frontend: Fire `experiment_exposure` event when variant rendered
2. Backend: Process exposure events specially
3. Create `experiment_exposures` rows from exposure events
4. Deduplicate exposures per session

**Files:**
- `app/Jobs/ProcessAnalyticsEvents.php` (extend)
- `resources/js/Composables/useExperiment.ts` (extend)

**Acceptance Criteria:**
- [ ] Exposures recorded in `experiment_exposures` table
- [ ] One exposure per session per experiment (deduplicated)
- [ ] Exposure links to assignment

### Phase 3: Attribution + Aggregates + Dashboards

**Goal:** Connect conversions to experiments and build reporting.

#### Task 3.1: Create Attribution Processor

**Description:** Create processor to attribute conversions to experiments.

**Steps:**
1. Identify conversion events (by `type = 'conversion'`)
2. Find all experiments where visitor had exposure before conversion
3. Create `analytics_event_experiments` rows for each
4. Handle overlapping experiments correctly

**Files:**
- `app/Services/ConversionAttributionService.php`
- `app/Jobs/ProcessAnalyticsEvents.php` (integrate)

**Acceptance Criteria:**
- [ ] Conversions attributed to all eligible experiments
- [ ] Only experiments with prior exposure attributed
- [ ] Attribution respects experiment run window

#### Task 3.2: Create Experiment Conversion Aggregates

**Description:** Maintain aggregate stats for fast dashboard queries.

**Steps:**
1. Create job to update `experiment_conversions` table
2. Run after conversion attribution
3. Calculate rates and derived metrics

**Files:**
- `app/Jobs/UpdateExperimentAggregates.php`

**Acceptance Criteria:**
- [ ] Aggregates updated after each conversion batch
- [ ] Conversion rate calculated correctly
- [ ] Revenue aggregated for revenue-goal experiments

#### Task 3.3: Create Session Processor

**Description:** Create processor to build session metrics.

**Steps:**
1. Process page_view events to build sessions
2. Update entry/exit pages, bounce status
3. Calculate duration and event counts
4. Mark conversions on sessions

**Files:**
- `app/Services/SessionProcessorService.php`
- `app/Jobs/ProcessAnalyticsEvents.php` (integrate)

**Acceptance Criteria:**
- [ ] Sessions created from page_view events
- [ ] Bounce correctly detected (single page)
- [ ] Duration calculated from first to last event
- [ ] Session marked as converted when appropriate

#### Task 3.4: Create Experiment Results API

**Description:** Create API endpoints for experiment results.

**Steps:**
1. Create controller for experiment results
2. Return variant stats (exposures, conversions, rates)
3. Calculate statistical significance (chi-squared or Z-test)
4. Include confidence intervals

**Files:**
- `app/Http/Controllers/Admin/ExperimentResultsController.php`
- `app/Services/StatisticalSignificanceService.php`

**Acceptance Criteria:**
- [ ] Results endpoint returns per-variant stats
- [ ] Statistical significance calculated
- [ ] Minimum sample warning if not met

#### Task 3.5: Create Experiment Admin UI

**Description:** Build admin interface for managing experiments.

**Steps:**
1. Create experiment list page
2. Create experiment detail/edit page
3. Create results dashboard with visualisations
4. Add ability to create/pause/end experiments

**Files:**
- `resources/js/Pages/Admin/Experiments/Index.vue`
- `resources/js/Pages/Admin/Experiments/Show.vue`
- `resources/js/Pages/Admin/Experiments/Create.vue`

**Acceptance Criteria:**
- [ ] Admin can create new experiments
- [ ] Admin can view real-time results
- [ ] Admin can pause/end experiments
- [ ] Results show confidence levels

### Phase 4: Domain Analytics

**Goal:** Add BettrPrompt-specific analytics for frameworks, questions, and workflows.

#### Task 4.1: Create Domain Analytics Migrations

**Description:** Create tables for domain-specific analytics.

**Steps:**
1. Create `framework_selections` table
2. Create `question_analytics` table
3. Create `workflow_analytics` table
4. Create `prompt_quality_metrics` table
5. Create daily aggregation tables

**Files:**
- `database/migrations/xxxx_create_domain_analytics_tables.php`

**Acceptance Criteria:**
- [ ] All domain tables created with indexes
- [ ] Foreign keys to `prompt_runs` work

#### Task 4.2: Create Framework Selection Tracking

**Description:** Track recommended vs chosen frameworks.

**Steps:**
1. Fire `framework_recommended` event when recommendation made
2. Fire `framework_switched` event when user changes
3. Create processor to populate `framework_selections` table
4. Update with outcomes after prompt completion

**Files:**
- `resources/js/Components/PromptBuilder/FrameworkSelector.vue` (extend)
- `app/Services/FrameworkAnalyticsService.php`

**Acceptance Criteria:**
- [ ] All framework recommendations tracked
- [ ] User switches tracked
- [ ] Acceptance rate calculable
- [ ] Outcome correlation tracked

#### Task 4.3: Create Question Analytics Tracking

**Description:** Track question presentation and responses.

**Steps:**
1. Fire `questions_presented` with all question IDs
2. Fire `question_answered` for each answer (with timing)
3. Fire `question_skipped` for each skip
4. Create processor to populate `question_analytics` table

**Files:**
- `resources/js/Components/PromptBuilder/ClarifyingQuestions.vue` (extend)
- `app/Services/QuestionAnalyticsService.php`

**Acceptance Criteria:**
- [ ] All question presentations tracked by ID
- [ ] Answer/skip status tracked
- [ ] Response time captured
- [ ] Personality variant noted

#### Task 4.4: Create Workflow Analytics Tracking

**Description:** Track n8n workflow performance.

**Steps:**
1. Capture workflow start/complete/fail events server-side
2. Track timing, token usage, model used
3. Populate `workflow_analytics` table
4. Track retries separately

**Files:**
- `app/Services/N8nClient.php` (extend)
- `app/Services/WorkflowAnalyticsService.php`

**Acceptance Criteria:**
- [ ] All workflow executions tracked
- [ ] Duration accurate
- [ ] Token usage captured (if available from n8n)
- [ ] Error codes categorised

#### Task 4.5: Create Daily Aggregation Jobs

**Description:** Create scheduled jobs to build daily stats.

**Steps:**
1. Create job for `analytics_daily_stats`
2. Create job for `framework_daily_stats`
3. Create job for `question_daily_stats`
4. Schedule to run at midnight UTC

**Files:**
- `app/Jobs/BuildDailyAnalyticsStats.php`
- `app/Console/Kernel.php` (schedule)

**Acceptance Criteria:**
- [ ] Daily stats populated correctly
- [ ] Jobs are idempotent (re-runnable)
- [ ] Historical backfill possible

#### Task 4.6: Create Domain Analytics Dashboard

**Description:** Build admin dashboard for domain analytics.

**Steps:**
1. Framework effectiveness page (acceptance rates, quality by framework)
2. Question effectiveness page (answer rates, impact on quality)
3. Workflow performance page (success rates, timing, costs)
4. Add filtering by date range, personality, task category

**Files:**
- `resources/js/Pages/Admin/Analytics/Frameworks.vue`
- `resources/js/Pages/Admin/Analytics/Questions.vue`
- `resources/js/Pages/Admin/Analytics/Workflows.vue`

**Acceptance Criteria:**
- [ ] Framework stats viewable with filters
- [ ] Question effectiveness visualised
- [ ] Workflow health dashboard complete
- [ ] Export to CSV available

### Phase 5: Advanced Features

**Goal:** Add sophisticated analysis and automation capabilities.

#### Task 5.1: Funnel Analysis

**Description:** Build configurable funnel reports.

**Steps:**
1. Define standard funnels (visitor → registration → prompt → subscription)
2. Create funnel query builder
3. Build funnel visualisation component
4. Add conversion rate between stages

#### Task 5.2: Cohort Analysis

**Description:** Track metrics by registration cohort.

**Steps:**
1. Create cohort definition (by registration date/week)
2. Track retention by cohort
3. Track revenue by cohort
4. Build cohort comparison visualisation

#### Task 5.3: Segment Builder

**Description:** Allow defining user segments for analysis and targeting.

**Steps:**
1. Create segment definition model
2. Build segment criteria editor
3. Integrate segments with experiment targeting
4. Show segment performance in dashboards

#### Task 5.4: Automated Alerts

**Description:** Alert on significant metric changes.

**Steps:**
1. Define alertable metrics (conversion rate, error rate, etc.)
2. Create threshold-based alert rules
3. Implement notification delivery (email, Slack)
4. Build alert management UI

---

## Migration Strategy

### For teams with existing analytics (GA4, Mixpanel, etc.)

1. **Run in parallel** for 4-6 weeks
2. Compare metrics between systems
3. Identify and fix discrepancies
4. Gradually reduce reliance on external system
5. Keep external system for cross-validation

### For teams starting fresh

1. Implement Phase 0-1 first
2. Validate event capture is working
3. Add experiments (Phase 2) when ready for first test
4. Add domain analytics (Phase 4) as product matures

---

## Testing Strategy

### Unit Tests

- Hash bucketing produces deterministic results
- Targeting rules evaluate correctly
- Statistical calculations are accurate
- Event validation works correctly

### Integration Tests

- Events flow from frontend to database
- Assignments persist across requests
- Exposures deduplicate per session
- Conversions attribute to correct experiments

### End-to-End Tests

- Complete experiment lifecycle (create → run → end → analyse)
- Consent flow properly gates analytics
- Session tracking works across page navigations
- Dashboard shows accurate data

---

## Monitoring and Alerting

### Key Metrics to Monitor

1. **Event ingestion rate** — events/minute, latency percentiles
2. **Job queue depth** — backlog of unprocessed events
3. **Error rate** — failed event processing
4. **Storage growth** — events table size
5. **Experiment exposure rate** — exposures/day by experiment

### Alerts

- Event processing latency > 30 seconds
- Job queue backlog > 10,000 events
- Event error rate > 1%
- Experiment with zero exposures for 24 hours (when running)
