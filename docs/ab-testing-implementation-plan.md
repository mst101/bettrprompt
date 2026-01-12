# A/B Testing System Implementation Plan (Superseded)

> **Status:** Superseded by `docs/unified-analytics-experimentation-architecture.md`.
>
> This document is retained for historical context and test idea inventory, but should not be implemented directly.

## Executive Summary

This document outlines an in-house A/B testing system for BettrPrompt that provides:
- Full flexibility to test UI/copy, features, and pricing
- Visitor segmentation by country, personality, subscription, UTM source, etc.
- Built-in statistical significance calculations with automated recommendations
- **Non-blocking event tracking** via queued jobs
- **Unified event architecture** shared with the CDP system

> **Integration Note:** This A/B testing system shares a unified event architecture with the Customer Data Platform (see `cdp-implementation-plan.md`). All events flow through a single `AnalyticsEvent` dispatcher that writes to the shared `analytics_events` table, enabling cross-system analysis (e.g., "which experiment variant had the best question completion rate?").

---

## A/B Testing Priority Roadmap

### Why These Priorities?

BettrPrompt's unique value proposition is **personality-calibrated AI prompts**. Our A/B testing priorities should focus on:

1. **Revenue conversion** - Direct impact on business sustainability
2. **Core differentiator validation** - Does personality calibration actually improve outcomes?
3. **Funnel completion** - Reducing drop-off in the multi-stage workflow
4. **Question effectiveness** - The question bank is both our differentiator AND potential friction

### Priority Tiers

#### 🔴 Tier 1: Critical Revenue & Conversion Tests

These tests have direct, measurable revenue impact and should be prioritised first.

| Test | Hypothesis | Primary Metric | Why Critical |
|------|------------|----------------|--------------|
| **Pricing Modal Wall Removal** | Allowing unauth users to go directly to Stripe (with email capture) will increase subscription conversion by 15-25% | `subscription_success` conversion rate | Current flow requires registration → modal → Stripe. Each step loses ~30-40% of users. Direct-to-Stripe removes one friction point. |
| **Yearly Billing Default** | Defaulting to yearly billing (with monthly as option) will increase average revenue per subscriber by 20%+ | Revenue per conversion, billing period distribution | Yearly has 17% discount but 12× upfront commitment. Testing default bias. |
| **Free Tier CTA Differentiation** | Visually distinguishing "Get Started Free" from paid CTAs will increase free signups without cannibalising paid | Free signup rate, paid conversion rate | Currently all CTAs look similar. Free tier is the funnel entry point. |
| **Pricing Page Social Proof** | Adding customer count/testimonials will increase subscription conversion by 10-15% | `subscribe_button_click`, `subscription_success` | No social proof currently. Trust signals reduce purchase anxiety. |

**Expected Impact:** 20-40% improvement in visitor → paid conversion

#### 🟠 Tier 2: Core Product Experience Tests

These tests validate our core differentiator and improve the prompt generation experience.

| Test | Hypothesis | Primary Metric | Why Critical |
|------|------------|----------------|--------------|
| **Personality Collection Timing** | Collecting personality AFTER task description (when user is invested) will increase personality completion rate by 30%+ | Personality completion rate, prompt quality rating | Currently asked upfront when user hasn't committed. After task entry, sunk cost increases compliance. |
| **Personality Type Required vs Optional** | Making personality type required will improve prompt ratings despite slightly lower funnel completion | Prompt `user_rating`, funnel completion rate | Core differentiator—if users skip personality, they get generic prompts. Test if quality improvement justifies friction. |
| **Framework Explanation Prominence** | Showing "Why this framework?" explanation prominently will reduce framework switching and increase trust | Framework switch rate, prompt rating, time on results page | Users may not understand why CO-STAR was chosen over RICE. Explanation builds trust. |
| **Alternative Framework Presentation** | Showing alternatives as "Also good for your task" (positive) vs "Other options" (neutral) will affect switch rate | Framework switch rate, prompt completion rate | Framing affects whether users second-guess the AI's choice. |
| **Question Count Optimisation** | Reducing clarifying questions from average 6→4 will improve completion rate without significant quality loss | Question completion rate, prompt rating, prompt edit rate | Each question is friction. Find the minimum viable question count. |

**Expected Impact:** 15-30% improvement in prompt completion rate, validation of personality calibration value

#### 🟡 Tier 3: Funnel Optimisation Tests

These tests reduce drop-off at critical funnel stages.

| Test | Hypothesis | Primary Metric | Why Critical |
|------|------------|----------------|--------------|
| **Processing Time Feedback** | Showing estimated time ("Usually 2-3 minutes") will reduce abandonment during workflow processing by 20% | Workflow completion rate, time on processing page, bounce rate | Long processing with only spinner causes anxiety. Setting expectations reduces abandonment. |
| **Processing Progress Indicators** | Showing workflow stage progress (e.g., "Analysing task... Selecting framework... Generating prompt...") will reduce abandonment | Workflow completion rate, user engagement during processing | Visible progress feels faster than unknown waiting. |
| **Visitor Limit Early Warning** | Showing "1 free prompt remaining" before completion will increase registration conversion | Guest → registered conversion rate | Currently limit only shown after 1st prompt. Early warning creates urgency while user is engaged. |
| **Pre-Analysis Skip UX** | Making "Skip" less prominent (smaller, greyed out) will increase pre-analysis completion | Pre-analysis completion rate, prompt rating | Pre-analysis questions improve task clarity. Prominent skip encourages skipping. |
| **Question Batching vs Individual** | Showing all questions at once vs one-at-a-time will affect completion rate and answer quality | Question completion rate, avg answer length, prompt rating | Individual questions feel longer but may get better answers. Batch feels faster but may get rushed answers. |

**Expected Impact:** 10-20% improvement in workflow completion rate

#### 🟢 Tier 4: Engagement & Retention Tests

These tests improve long-term engagement and return usage.

| Test | Hypothesis | Primary Metric | Why Critical |
|------|------------|----------------|--------------|
| **Prompt Rating Request Timing** | Asking for rating immediately after copy (vs on page load) will increase rating submission rate | Rating submission rate, rating distribution | Currently rating prompt shown on results page. After copy = moment of highest satisfaction. |
| **Profile Completion Gamification** | Progress bar + milestone rewards will increase profile completion by 25%+ | Profile completion %, fields completed | More profile data = better prompts. Gamification motivates completion. |
| **"Save This Prompt" CTA** | Prominent save CTA for guests will increase registration conversion | Guest → registered conversion, prompt save rate | Saving requires account. Loss aversion ("Don't lose this prompt!") motivates signup. |
| **Post-Prompt Next Steps** | Showing "Refine this prompt" vs "Create new prompt" as primary CTA will affect refinement rate | Refinement rate, prompts per user per session | Refinement suggests the prompt isn't done. New prompt suggests completion. |
| **Email Re-engagement Subject Lines** | Testing subject line variations for prompt completion reminders | Email open rate, return visit rate | Users who abandon mid-workflow may return with right messaging. |

**Expected Impact:** 15-25% improvement in retention metrics

#### 🔵 Tier 5: Landing Page & Acquisition Tests

These tests improve initial visitor conversion but are lower priority than in-product tests.

| Test | Hypothesis | Primary Metric | Why Critical |
|------|------------|----------------|--------------|
| **Hero CTA Copy** | "Create Your First Prompt" will outperform "Get Started Free" by 10% | Hero CTA click rate | Specific action ("Create") vs generic ("Get Started"). |
| **Value Proposition Messaging** | Leading with "Prompts tailored to how you think" vs "Better AI prompts" will improve engagement | Time on page, scroll depth, CTA click rate | Personality angle is unique. Generic "better prompts" is commodity. |
| **Use Case Section Count** | 3 focused use cases will outperform 6 comprehensive use cases | CTA click rate, time on page | Too many choices = paralysis. Focused = clarity. |
| **Comparison Section Position** | Moving ChatGPT comparison higher will increase CTA clicks | CTA click rate, scroll depth | Comparison clarifies value but currently buried. |

**Expected Impact:** 5-15% improvement in landing page → prompt builder conversion

---

### Recommended Test Sequence

Based on expected impact, statistical power requirements, and implementation complexity:

**Quarter 1: Revenue & Core Validation**
1. Pricing Modal Wall Removal (high impact, low complexity)
2. Personality Collection Timing (validates core differentiator)
3. Processing Time Feedback (quick win, low risk)
4. Visitor Limit Early Warning (quick win)

**Quarter 2: Funnel Optimisation**
5. Question Count Optimisation (requires question analytics data from Q1)
6. Framework Explanation Prominence
7. Yearly Billing Default
8. Pre-Analysis Skip UX

**Quarter 3: Engagement & Retention**
9. Prompt Rating Request Timing
10. Profile Completion Gamification
11. Alternative Framework Presentation
12. Question Batching vs Individual

**Quarter 4: Acquisition & Polish**
13. Landing page tests (hero, value prop, use cases)
14. Email re-engagement tests
15. Advanced personalisation tests (by personality type)

---

### Personality-Segmented Testing

BettrPrompt's unique advantage is rich personality data. We should segment A/B test results by personality type to discover:

| Segment | Hypothesis | Why Test Separately |
|---------|------------|---------------------|
| **High J (Judging)** | Prefer structured, step-by-step flows | May respond better to progress indicators, numbered steps |
| **High P (Perceiving)** | Prefer flexible, exploratory flows | May respond better to "skip" options, fewer required fields |
| **High T (Thinking)** | Prefer logical explanations | May respond better to "Why this framework?" explanations |
| **High F (Feeling)** | Prefer empathetic messaging | May respond better to "How your prompt will help" messaging |
| **High A (Assertive)** | Confident, less need for reassurance | May not need social proof, processing time warnings |
| **High T-identity (Turbulent)** | Anxious, need reassurance | May benefit more from progress indicators, confirmations |

**Implementation:** All experiments should track `personality_type` in assignment snapshots. Post-hoc analysis can reveal if certain variants perform better for certain personality segments, enabling future personalisation.

---

### Key Metrics by Funnel Stage

| Stage | Primary Metric | Secondary Metrics |
|-------|----------------|-------------------|
| **Landing** | CTA click rate | Time on page, scroll depth, bounce rate |
| **Registration** | Registration completion rate | OAuth vs email ratio, time to complete |
| **Personality** | Personality completion rate | Skip rate, external link clicks |
| **Task Entry** | Task submission rate | Task length, voice input usage |
| **Pre-Analysis** | Question completion rate | Skip rate, time per question |
| **Framework** | Framework acceptance rate | Switch rate, time viewing framework |
| **Clarifying Questions** | Question completion rate | Skip rate, answer length, time per question |
| **Processing** | Workflow completion rate | Abandonment during processing, time on page |
| **Results** | Prompt satisfaction | Rating, copy rate, edit rate, refinement rate |
| **Conversion** | Subscription rate | Tier distribution, billing period, revenue |
| **Retention** | Return rate | Prompts per user, days between visits |

---

### Statistical Considerations

**Minimum Sample Sizes (95% confidence, 80% power):**

| Baseline Rate | MDE 5% | MDE 10% | MDE 20% |
|---------------|--------|---------|---------|
| 2% (subscription) | 31,000 | 7,800 | 2,000 |
| 10% (registration) | 6,200 | 1,600 | 400 |
| 30% (prompt completion) | 2,000 | 500 | 130 |
| 50% (question completion) | 1,500 | 380 | 100 |

**Implications:**
- Subscription conversion tests need large traffic or longer run times
- In-product tests (completion rates) can achieve significance faster
- Consider running high-traffic tests (landing page) concurrently with low-traffic tests (pricing)
- Personality-segmented analysis requires ~4× sample size for meaningful sub-group analysis

---

## Current State Assessment

### Existing Infrastructure (Leveraged)

| Component | Status | Details |
|-----------|--------|---------|
| **Visitor Tracking** | ✅ Complete | UUID-based, 2-year cookie, rich demographic data |
| **Inertia.js Data Sharing** | ✅ Complete | `HandleInertiaRequests` shares data to frontend |
| **Conversion Tracking** | ✅ Complete | `prompt_runs.workflow_stage`, `visitors.converted_at` |
| **WorkflowVariantService** | ✅ Exists | Pattern for n8n workflow variants (can extend) |

### Key Files Reference

- **Visitor Middleware:** `/app/Http/Middleware/TrackVisitor.php`
- **Inertia Sharing:** `/app/Http/Middleware/HandleInertiaRequests.php`
- **Visitor Model:** `/app/Models/Visitor.php` (30+ fields including UTM, geo, personality)
- **Composables Pattern:** `/resources/js/Composables/features/`

---

## Database Schema

### 1. Experiments Table

```php
Schema::create('experiments', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100)->unique();
    $table->string('slug', 100)->unique();
    $table->text('description')->nullable();
    $table->text('hypothesis')->nullable();

    // Experiment type
    $table->enum('type', ['ui', 'feature', 'pricing', 'copy', 'workflow'])->default('ui');

    // Status management
    $table->enum('status', ['draft', 'running', 'paused', 'completed', 'archived'])->default('draft');

    // Targeting rules (JSON for segment matching)
    $table->json('targeting_rules')->nullable();

    // Traffic allocation (0-100)
    $table->unsignedTinyInteger('traffic_percentage')->default(100);

    // Conversion goals
    $table->string('primary_goal', 100); // e.g., 'registration', 'subscription'
    $table->json('secondary_goals')->nullable();

    // Mutual exclusion groups
    $table->string('exclusion_group')->nullable();

    // Statistical configuration
    $table->decimal('minimum_detectable_effect', 5, 4)->default(0.05); // 5% MDE
    $table->decimal('statistical_power', 4, 3)->default(0.80); // 80% power
    $table->decimal('significance_level', 4, 3)->default(0.05); // 95% confidence

    // Auto-stop configuration
    $table->boolean('auto_stop_enabled')->default(false);
    $table->unsignedInteger('minimum_sample_size')->nullable();
    $table->unsignedInteger('maximum_duration_days')->nullable();

    // Scheduling
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamp('stopped_at')->nullable();

    // Winner tracking
    $table->foreignId('winning_variant_id')->nullable();
    $table->text('conclusion')->nullable();

    $table->timestamps();

    $table->index('status');
    $table->index('exclusion_group');
});
```

### 2. Experiment Variants Table

```php
Schema::create('experiment_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();

    $table->string('name', 100); // e.g., 'control', 'treatment_a'
    $table->string('slug', 100);
    $table->text('description')->nullable();

    // Traffic weight (relative, normalised at runtime)
    $table->unsignedSmallInteger('weight')->default(100);

    // Variant configuration (JSON payload)
    $table->json('config')->nullable();

    // Control flag
    $table->boolean('is_control')->default(false);

    $table->timestamps();

    $table->unique(['experiment_id', 'slug']);
});
```

### 3. Experiment Assignments Table

```php
Schema::create('experiment_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();

    // Link to visitor OR user
    $table->foreignUuid('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();

    // Assignment metadata
    $table->string('assignment_hash', 64); // SHA256 for verification
    $table->timestamp('assigned_at');
    $table->json('segment_snapshot')->nullable(); // Debug snapshot

    $table->timestamps();

    $table->unique(['experiment_id', 'visitor_id']);
    $table->unique(['experiment_id', 'user_id']);
});
```

### 4. Experiment Events Table

```php
Schema::create('experiment_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->constrained('experiment_variants')->cascadeOnDelete();
    $table->foreignId('assignment_id')->constrained('experiment_assignments')->cascadeOnDelete();

    // Event identification
    $table->string('event_type', 100); // 'exposure', 'conversion'
    $table->string('event_name', 100); // e.g., 'registration', 'checkout'

    // Event value (for revenue goals)
    $table->decimal('value', 10, 2)->nullable();
    $table->json('metadata')->nullable();

    // Related records
    $table->foreignId('prompt_run_id')->nullable();

    $table->timestamp('occurred_at');
    $table->timestamps();

    $table->index(['experiment_id', 'event_type']);
    $table->index('occurred_at');
});
```

---

## Backend Services

### ExperimentService

**Location:** `app/Services/ExperimentService.php`

```php
class ExperimentService
{
    /**
     * Get all active experiments (cached)
     */
    public function getActiveExperiments(): Collection;

    /**
     * Get variant assignment for visitor/user (deterministic)
     */
    public function getVariantAssignment(
        Experiment $experiment,
        ?Visitor $visitor,
        ?User $user
    ): ?ExperimentVariant;

    /**
     * Check if visitor/user matches targeting rules
     */
    public function matchesTargeting(
        Experiment $experiment,
        ?Visitor $visitor,
        ?User $user
    ): bool;

    /**
     * Record experiment event (NON-BLOCKING via unified event system)
     *
     * Events are dispatched to the unified AnalyticsEvent system which:
     * 1. Writes to analytics_events table (for CDP)
     * 2. Updates experiment_conversions aggregates (for A/B stats)
     */
    public function recordEvent(
        string $eventType,
        string $eventName,
        ?Visitor $visitor,
        ?User $user,
        array $metadata = [],
        ?float $value = null
    ): void;

    /**
     * Get all assignments for Inertia sharing
     */
    public function getAssignmentsForContext(
        ?Visitor $visitor,
        ?User $user
    ): array;

    /**
     * Get experiment context for a visitor/user (for attaching to analytics events)
     */
    public function getExperimentContext(
        ?Visitor $visitor,
        ?User $user
    ): array;
}
```

#### Deterministic Assignment Algorithm

```php
private function assignVariant(Experiment $experiment, ?Visitor $visitor, ?User $user): ExperimentVariant
{
    $identifier = $visitor?->id ?? $user?->id ?? '';
    $hash = hash('sha256', $experiment->id . ':' . $identifier);
    $hashValue = hexdec(substr($hash, 0, 8)) / 0xFFFFFFFF; // 0-1 range

    // Calculate cumulative weights
    $variants = $experiment->variants->sortBy('id');
    $totalWeight = $variants->sum('weight');
    $cumulative = 0;

    foreach ($variants as $variant) {
        $cumulative += $variant->weight / $totalWeight;
        if ($hashValue <= $cumulative) {
            return $variant;
        }
    }

    return $variants->last();
}
```

#### Targeting Rule Evaluation

```php
private function evaluateRule(array $rule, ?Visitor $visitor, ?User $user): bool
{
    $field = $rule['field'];
    $operator = $rule['operator'];
    $value = $rule['value'];

    $actualValue = match ($field) {
        'country_code' => $visitor?->country_code ?? $user?->country_code,
        'personality_type' => $visitor?->personality_type ?? $user?->personality_type,
        'visit_count' => $visitor?->visit_count ?? 1,
        'subscription_tier' => $user?->subscription_tier ?? 'free',
        'utm_source' => $visitor?->utm_source,
        'device_type' => $this->detectDeviceType($visitor?->user_agent),
        'is_authenticated' => $user !== null,
        'has_completed_prompt' => $visitor?->hasCompletedPrompts() ?? false,
        default => null,
    };

    return match ($operator) {
        'equals' => $actualValue === $value,
        'not_equals' => $actualValue !== $value,
        'in' => in_array($actualValue, (array) $value),
        'greater_than' => $actualValue > $value,
        'contains' => str_contains((string) $actualValue, (string) $value),
        default => true,
    };
}
```

### StatisticsService

**Location:** `app/Services/StatisticsService.php`

```php
class StatisticsService
{
    /**
     * Calculate full experiment results
     */
    public function calculateResults(Experiment $experiment): array;

    /**
     * Wilson score confidence interval
     */
    private function wilsonConfidenceInterval(int $successes, int $n, float $confidence): array;

    /**
     * Chi-squared test for 2x2 contingency table
     */
    private function chiSquaredTest(int $a, int $b, int $c, int $d): array;

    /**
     * Z-test for two proportions
     */
    private function zTestProportions(int $x1, int $n1, int $x2, int $n2): array;

    /**
     * Calculate required sample size
     */
    public function calculateRequiredSampleSize(
        float $baselineRate,
        float $minimumDetectableEffect,
        float $power = 0.80,
        float $significance = 0.05
    ): int;

    /**
     * Get recommendation (continue/winner/loser)
     */
    private function getRecommendation(Experiment $experiment, array $results): array;
}
```

#### Results Structure

```php
[
    'variants' => [
        'control' => [
            'sample_size' => 1250,
            'conversions' => 125,
            'conversion_rate' => 0.10,
            'conversion_rate_percentage' => 10.0,
            'confidence_interval' => ['lower' => 8.4, 'upper' => 11.8],
        ],
        'treatment_a' => [
            'sample_size' => 1280,
            'conversions' => 154,
            'conversion_rate' => 0.1203,
            'conversion_rate_percentage' => 12.03,
            'confidence_interval' => ['lower' => 10.3, 'upper' => 14.0],
            'comparison' => [
                'relative_lift' => 20.3,
                'absolute_lift' => 2.03,
                'chi_squared' => [
                    'statistic' => 4.21,
                    'p_value' => 0.04,
                    'is_significant' => true,
                ],
                'z_test' => [
                    'z_score' => 2.05,
                    'p_value' => 0.04,
                    'is_significant' => true,
                ],
            ],
        ],
    ],
    'control_slug' => 'control',
    'recommendation' => [
        'status' => 'winner_found',
        'message' => "Variant 'treatment_a' shows statistically significant improvement",
        'winner' => 'treatment_a',
        'required_sample_size' => 2000,
        'current_sample_size' => 2530,
        'percent_complete' => 126.5,
    ],
]
```

---

## Middleware Integration

### HandleInertiaRequests Modification

```php
// app/Http/Middleware/HandleInertiaRequests.php

public function share(Request $request): array
{
    $experimentService = app(ExperimentService::class);

    $visitor = null;
    $user = $request->user();

    if (!$user) {
        $visitorId = $request->cookie('visitor_id');
        if ($visitorId) {
            $visitor = Visitor::find($visitorId);
        }
    }

    $experiments = $experimentService->getAssignmentsForContext($visitor, $user);

    return [
        ...parent::share($request),
        // ... existing shares ...
        'experiments' => fn() => $experiments,
    ];
}
```

---

## Frontend Integration

### useExperiment Composable

**Location:** `resources/js/Composables/features/useExperiment.ts`

```typescript
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface ExperimentAssignment {
    experimentId: number;
    variantId: number;
    variantSlug: string;
    config: Record<string, unknown>;
}

export function useExperiment(experimentSlug: string) {
    const page = usePage();

    const experiments = computed(() =>
        (page.props.experiments as Record<string, ExperimentAssignment>) || {}
    );

    const assignment = computed(() =>
        experiments.value[experimentSlug] || null
    );

    const variant = computed(() =>
        assignment.value?.variantSlug || null
    );

    const variantConfig = computed(() =>
        assignment.value?.config || {}
    );

    const isVariant = (slug: string): boolean => variant.value === slug;

    const isControl = computed(() => variant.value === 'control');

    const isInExperiment = computed(() => assignment.value !== null);

    const trackConversion = async (
        goalName: string,
        value?: number,
        metadata?: Record<string, unknown>
    ): Promise<void> => {
        if (!assignment.value) return;

        await fetch('/api/experiments/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': (page.props.csrf_token as string) || '',
            },
            body: JSON.stringify({
                experiment_id: assignment.value.experimentId,
                event_type: 'conversion',
                event_name: goalName,
                value,
                metadata,
            }),
        });
    };

    return {
        assignment,
        variant,
        variantConfig,
        isVariant,
        isControl,
        isInExperiment,
        trackConversion,
    };
}
```

### ABTest Component

**Location:** `resources/js/Components/Common/ABTest.vue`

```vue
<script setup lang="ts">
import { useExperiment } from '@/Composables/features/useExperiment';
import { computed } from 'vue';

interface Props {
    experiment: string;
    defaultVariant?: string;
}

const props = withDefaults(defineProps<Props>(), {
    defaultVariant: 'control',
});

const { variant, variantConfig, isInExperiment, trackConversion } = useExperiment(
    props.experiment
);

const currentVariant = computed(() => variant.value || props.defaultVariant);
</script>

<template>
    <slot
        :variant="currentVariant"
        :config="variantConfig"
        :is-in-experiment="isInExperiment"
        :track-conversion="trackConversion"
    />
</template>
```

### Usage Examples

```vue
<!-- Simple variant switching -->
<ABTest experiment="homepage-hero" v-slot="{ variant }">
    <HeroVariantA v-if="variant === 'control'" />
    <HeroVariantB v-else-if="variant === 'large_cta'" />
</ABTest>

<!-- Using config object -->
<ABTest experiment="cta-color" v-slot="{ config }">
    <ButtonPrimary :class="config.buttonClass">
        {{ config.buttonText }}
    </ButtonPrimary>
</ABTest>

<!-- Tracking conversions -->
<script setup>
const { variant, trackConversion } = useExperiment('pricing-page');

async function handleSubscribe(tier: string) {
    await trackConversion('subscription_click', null, { tier });
    // ... proceed with subscription
}
</script>
```

---

## Admin Dashboard

### Routes

```php
// routes/web.php (admin group)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('experiments', Admin\ExperimentController::class);
    Route::post('/experiments/{experiment}/start', [Admin\ExperimentController::class, 'start'])
        ->name('experiments.start');
    Route::post('/experiments/{experiment}/pause', [Admin\ExperimentController::class, 'pause'])
        ->name('experiments.pause');
    Route::post('/experiments/{experiment}/stop', [Admin\ExperimentController::class, 'stop'])
        ->name('experiments.stop');
    Route::post('/experiments/{experiment}/declare-winner/{variant}',
        [Admin\ExperimentController::class, 'declareWinner'])
        ->name('experiments.declare-winner');
});
```

### Vue Pages Structure

```
resources/js/Pages/Admin/Experiments/
├── Index.vue       # List with status filters, search
├── Create.vue      # Wizard: name → variants → targeting → goals
├── Edit.vue        # Edit (only draft/paused)
└── Show.vue        # Results dashboard with charts
```

### Dashboard Features

1. **Experiment List**
   - Filter by status (draft, running, completed)
   - Search by name
   - Quick actions (start, pause, view)

2. **Create Wizard**
   - Step 1: Name, description, hypothesis, type
   - Step 2: Define variants with weights
   - Step 3: Targeting rules builder
   - Step 4: Goals and statistical settings

3. **Results Dashboard**
   - Conversion rate per variant with confidence intervals
   - Statistical significance indicators
   - Sample size progress
   - Recommendation (continue/stop/winner)
   - Daily/hourly trend charts

---

## API Endpoints

### Track Experiment Event

> **Note:** Experiment tracking uses the unified analytics endpoint. The experiment context is automatically attached by the `ExperimentEventListener` based on the visitor/user's assignments.

```php
// routes/api.php
// Unified analytics endpoint (handles both CDP and A/B events)
Route::post('/analytics/events', function (Request $request) {
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

    // NON-BLOCKING: Dispatch to queue for async processing
    ProcessAnalyticsEvents::dispatch(
        $request->input('session_id'),
        $request->input('visitor_id'),
        $request->input('user_id'),
        $request->input('events'),
        $request->userAgent(),
        $request->ip()
    )->onQueue('analytics');

    return response()->json(['success' => true]);
})->middleware('throttle:120,1');

// Legacy endpoint for direct experiment tracking (redirects to unified)
Route::post('/experiments/track', function (Request $request) {
    $validator = Validator::make($request->all(), [
        'experiment_id' => 'required|integer|exists:experiments,id',
        'event_type' => 'required|string|in:exposure,conversion',
        'event_name' => 'required|string|max:100',
        'value' => 'nullable|numeric',
        'metadata' => 'nullable|array',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Invalid payload'], 422);
    }

    $visitorId = $request->cookie('visitor_id');

    // NON-BLOCKING: Dispatch via unified event system
    AnalyticsEvent::dispatch(
        $request->input('event_type'),
        $request->input('event_name'),
        array_merge($request->input('metadata', []), [
            'value' => $request->input('value'),
            'experiment_id' => $request->input('experiment_id'),
        ]),
        $visitorId,
        $request->user()?->id
    );

    return response()->json(['success' => true]);
})->middleware('throttle:60,1');
```

### Non-Blocking Event Flow

```
Frontend                    API                         Queue                      Database
   │                         │                            │                           │
   │  POST /analytics/events │                            │                           │
   │────────────────────────>│                            │                           │
   │                         │  Dispatch job              │                           │
   │                         │───────────────────────────>│                           │
   │  200 OK (immediate)     │                            │                           │
   │<────────────────────────│                            │                           │
   │                         │                            │  ProcessAnalyticsEvents   │
   │                         │                            │─────────────────────────>│
   │                         │                            │  - Insert analytics_events│
   │                         │                            │  - Update experiment_conversions
   │                         │                            │  - Update framework_selections
   │                         │                            │  - Update question_analytics
```

---

## Key Design Decisions

### 1. Overlapping Experiments

**Solution:** Exclusion groups

- Experiments with same `exclusion_group` are mutually exclusive
- Visitor can only be in ONE experiment per group
- Experiments with `null` group can overlap freely

### 2. Sticky Assignment

**Solution:** SHA256 hash-based

```
hash = SHA256(experimentId + ":" + visitorId)
bucket = first 8 hex chars → decimal → divide by max → 0-1 range
```

- Same visitor always gets same result for same experiment
- Recorded in `experiment_assignments` for fast lookup
- Persists across sessions via `visitor_id` cookie

### 3. Server-side vs Client-side

**Solution:** Server-side via Inertia

- Variants calculated in middleware, shared as props
- No flash of incorrect content (FOUC)
- Works with SSR
- SEO-friendly

### 4. CDP Integration

**Solution:** Unified event architecture (shared with CDP)

All experiment events flow through the unified `AnalyticsEvent` dispatcher:

```php
// When recording an experiment event:
AnalyticsEvent::dispatch('conversion', 'subscription_success', [
    'tier' => 'pro',
    'value' => 29.99,
], $visitorId, $userId, $sessionId);

// The ExperimentEventListener automatically:
// 1. Looks up the user's experiment assignments
// 2. Attaches experiment_id and variant_id to the event
// 3. Updates the experiment_conversions aggregation table
```

**Benefits of unified approach:**
- Single source of truth in `analytics_events`
- Cross-system analysis (e.g., "conversion rate by experiment variant AND personality type")
- Non-blocking: all writes happen via queued jobs
- No duplicate events or data synchronisation issues

**Aggregation table for fast statistical queries:**

```php
Schema::create('experiment_conversions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained();
    $table->foreignId('variant_id')->constrained('experiment_variants');
    $table->string('goal_name', 100);

    // Aggregated counts (updated by ExperimentEventListener)
    $table->unsignedInteger('exposures')->default(0);
    $table->unsignedInteger('conversions')->default(0);
    $table->decimal('conversion_rate', 8, 6)->default(0);
    $table->decimal('revenue', 12, 2)->default(0);

    $table->timestamps();

    $table->unique(['experiment_id', 'variant_id', 'goal_name']);
});
```

---

## Targeting Fields

| Field | Source | Example Values |
|-------|--------|----------------|
| `country_code` | Visitor/User | GB, US, DE |
| `personality_type` | Visitor/User | INTJ-A, ENFP-T |
| `visit_count` | Visitor | 1, 5, 10+ |
| `subscription_tier` | User | free, pro, private |
| `utm_source` | Visitor | google, facebook, email |
| `utm_medium` | Visitor | cpc, organic, social |
| `utm_campaign` | Visitor | summer_sale, launch |
| `device_type` | Detected | desktop, mobile, tablet |
| `is_authenticated` | Request | true, false |
| `has_completed_prompt` | Visitor/User | true, false |
| `industry` | User | technology, finance |
| `experience_level` | User | beginner, intermediate |

---

## Implementation Phases

> **Note:** This timeline is synchronised with the CDP Implementation Plan. Phase 1 (Unified Event Foundation) is shared between both systems.

### Phase 1: Unified Event Foundation (Shared with CDP)
**See CDP Implementation Plan for full details**

- [ ] Create unified `analytics_events` table with A/B test context columns
- [ ] Implement `AnalyticsEvent` Laravel event class
- [ ] Create `ProcessAnalyticsEvents` job (non-blocking)
- [ ] Implement `ExperimentEventListener`:
  - [ ] Attach experiment context to events
  - [ ] Update `experiment_conversions` aggregation table
- [ ] Create unified `/api/analytics/events` endpoint

### Phase 2: A/B Testing Core Infrastructure
**Experiment Management**

- [ ] Create `experiments` table migration
- [ ] Create `experiment_variants` table migration
- [ ] Create `experiment_assignments` table migration
- [ ] Create `experiment_conversions` aggregation table
- [ ] Implement Eloquent models with relationships:
  - [ ] `Experiment` model
  - [ ] `ExperimentVariant` model
  - [ ] `ExperimentAssignment` model
- [ ] Implement `ExperimentService`:
  - [ ] `getActiveExperiments()` with caching
  - [ ] `getVariantAssignment()` with deterministic hashing
  - [ ] `matchesTargeting()` rule evaluation
  - [ ] `recordEvent()` via unified dispatcher
  - [ ] `getAssignmentsForContext()` for Inertia sharing
  - [ ] `getExperimentContext()` for attaching to events

### Phase 3: Frontend Integration
**Inertia & Vue Components**

- [ ] Update `HandleInertiaRequests` middleware to share experiments
- [ ] Create `useExperiment` composable:
  - [ ] Variant access
  - [ ] Config access
  - [ ] Conversion tracking
- [ ] Create `ABTest.vue` component for declarative variant switching
- [ ] Integrate with unified `useAnalytics` composable for event tracking

### Phase 4: Statistical Analysis
**Results & Recommendations**

- [ ] Implement `StatisticsService`:
  - [ ] Wilson confidence intervals
  - [ ] Chi-squared test
  - [ ] Z-test for two proportions
  - [ ] Required sample size calculation
  - [ ] Recommendation engine
- [ ] Create results calculation from `analytics_events` + `experiment_conversions`

### Phase 5: Admin Dashboard
**Experiment Management UI**

- [ ] Create `ExperimentController` with CRUD operations
- [ ] Build Vue pages:
  - [ ] `Index.vue` - List with status filters, search
  - [ ] `Create.vue` - Wizard for experiment creation
  - [ ] `Edit.vue` - Edit draft/paused experiments
  - [ ] `Show.vue` - Results dashboard with visualisations
- [ ] Implement targeting rules builder UI
- [ ] Add start/pause/stop/declare-winner controls
- [ ] Results dashboard with charts (conversion rates, confidence intervals)

### Phase 6: Advanced Features
**Automation & Export**

- [ ] Implement auto-stop rules based on statistical significance
- [ ] Create exclusion group management UI
- [ ] Add experiment scheduling (start/end dates)
- [ ] Build historical archive view
- [ ] Implement export to CSV/JSON

### Phase 7: Testing & Documentation
**Quality Assurance**

- [ ] Unit tests for `ExperimentService`
- [ ] Unit tests for `StatisticsService` (verify calculations against known results)
- [ ] Feature tests for admin CRUD operations
- [ ] Feature tests for assignment determinism
- [ ] E2E tests for experiment creation workflow
- [ ] E2E tests for variant rendering
- [ ] Documentation for creating experiments
- [ ] Documentation for analysing results

---

## Files to Create

```
# Unified Event System (Shared with CDP)
app/Events/
└── AnalyticsEvent.php                    # Unified event dispatcher

app/Jobs/
└── ProcessAnalyticsEvents.php            # Non-blocking event processor

app/Listeners/
├── CDPEventListener.php                  # Writes to analytics_events
├── ExperimentEventListener.php           # Updates experiment_conversions
├── FrameworkAnalyticsListener.php        # Writes to framework_selections
└── QuestionAnalyticsListener.php         # Writes to question_analytics

# A/B Testing Migrations
database/migrations/
├── 2026_XX_XX_000001_create_analytics_events_table.php      # Shared with CDP
├── 2026_XX_XX_000002_create_experiments_table.php
├── 2026_XX_XX_000003_create_experiment_variants_table.php
├── 2026_XX_XX_000004_create_experiment_assignments_table.php
└── 2026_XX_XX_000005_create_experiment_conversions_table.php # Aggregation

# A/B Testing Models
app/Models/
├── AnalyticsEvent.php                    # Shared with CDP
├── Experiment.php
├── ExperimentVariant.php
├── ExperimentAssignment.php
└── ExperimentConversion.php              # Aggregation model

# Services
app/Services/
├── ExperimentService.php
└── StatisticsService.php

# Controllers
app/Http/Controllers/Admin/
└── ExperimentController.php

# Requests
app/Http/Requests/Admin/
├── StoreExperimentRequest.php
└── UpdateExperimentRequest.php

# Frontend - Composables
resources/js/Composables/features/
└── useExperiment.ts

# Frontend - Components
resources/js/Components/Common/
└── ABTest.vue

# Frontend - Types
resources/js/Types/
└── experiments.ts

# Frontend - Admin Pages
resources/js/Pages/Admin/Experiments/
├── Index.vue
├── Create.vue
├── Edit.vue
└── Show.vue

# Tests
tests/Unit/Services/
├── ExperimentServiceTest.php
└── StatisticsServiceTest.php

tests/Feature/Admin/
└── ExperimentControllerTest.php

tests/Feature/Events/
└── ExperimentEventListenerTest.php
```

## Files to Modify

- `app/Http/Middleware/HandleInertiaRequests.php` - Share experiments
- `app/Providers/EventServiceProvider.php` - Register event listeners
- `routes/web.php` - Admin experiment routes
- `routes/api.php` - Unified analytics endpoint
- `resources/js/Types/index.ts` - Export types
- `resources/js/Composables/useAnalytics.ts` - Integrate experiment context

---

## Comparison: Build vs Buy

| Factor | In-House | Third-Party (Optimizely, LaunchDarkly) |
|--------|----------|----------------------------------------|
| Setup time | 5-6 weeks | 1-2 days |
| Monthly cost | Server only (~£20-50) | £150-2000+ based on MAU |
| Data ownership | 100% yours | Shared with vendor |
| Customisation | Unlimited | Limited by platform |
| Privacy control | Full | Dependent on vendor |
| Segmentation | Uses your existing data | Requires data sync |
| Statistical rigour | Implement your own | Usually excellent |

**Recommendation:** Build in-house given:
- Privacy-focused product positioning
- Existing rich visitor data (personality, geo, professional)
- Long-term cost savings
- Full control over experiment logic
- Unified architecture with CDP enables cross-system analysis

---

## Cross-System Analysis Examples

With the unified event architecture, you can answer questions that span both A/B testing and CDP:

```sql
-- Which experiment variant has the best prompt completion rate?
SELECT
    e.name as experiment,
    ev.name as variant,
    COUNT(DISTINCT ae.prompt_run_id) as prompts_completed,
    COUNT(DISTINCT ae.id) as total_events,
    ROUND(COUNT(DISTINCT ae.prompt_run_id)::numeric / NULLIF(COUNT(DISTINCT ae.session_id), 0) * 100, 2) as completion_rate
FROM analytics_events ae
JOIN experiments e ON ae.experiment_id = e.id
JOIN experiment_variants ev ON ae.variant_id = ev.id
WHERE ae.event_name = 'prompt_completed'
GROUP BY e.name, ev.name;

-- Which frameworks perform better in which experiment variants?
SELECT
    e.name as experiment,
    ev.name as variant,
    fs.framework_code,
    COUNT(*) as selections,
    AVG(pq.user_rating) as avg_rating
FROM framework_selections fs
JOIN analytics_events ae ON ae.prompt_run_id = fs.prompt_run_id
JOIN experiments e ON ae.experiment_id = e.id
JOIN experiment_variants ev ON ae.variant_id = ev.id
LEFT JOIN prompt_runs pq ON fs.prompt_run_id = pq.id
WHERE fs.selection_type = 'primary'
GROUP BY e.name, ev.name, fs.framework_code;

-- Question skip rate by experiment variant and personality type
SELECT
    e.name as experiment,
    ev.name as variant,
    qa.personality_type,
    qa.question_id,
    ROUND(SUM(CASE WHEN qa.status = 'skipped' THEN 1 ELSE 0 END)::numeric / COUNT(*) * 100, 2) as skip_rate
FROM question_analytics qa
JOIN analytics_events ae ON ae.prompt_run_id = qa.prompt_run_id
JOIN experiments e ON ae.experiment_id = e.id
JOIN experiment_variants ev ON ae.variant_id = ev.id
GROUP BY e.name, ev.name, qa.personality_type, qa.question_id
HAVING COUNT(*) > 10;
```

---

## Advanced Testing Strategies: Beyond Traditional A/B

### 1. Multi-Armed Bandit: Faster Learning, Less Regret

**The Problem:** Traditional A/B tests require waiting for statistical significance, leaving "losing" traffic on inferior variants.

**The Opportunity:** Multi-armed bandit (MAB) algorithms dynamically shift traffic toward winning variants while still exploring.

**When to Use MAB vs A/B:**

| Scenario | Recommendation |
|----------|----------------|
| High traffic, clear winner likely | MAB (Thompson Sampling) |
| Need precise measurement | Traditional A/B |
| Irreversible decisions (pricing) | Traditional A/B with significance |
| Reversible UX changes | MAB for faster iteration |
| Personalisation experiments | Contextual bandits |

**Implementation:**

```php
// app/Services/BanditService.php
class BanditService
{
    /**
     * Thompson Sampling for variant selection
     * Balances exploration vs exploitation
     */
    public function selectVariant(Experiment $experiment, array $variants): ExperimentVariant
    {
        $samples = [];

        foreach ($variants as $variant) {
            $stats = $this->getVariantStats($variant);

            // Beta distribution sampling
            // More conversions = higher probability of selection
            $alpha = $stats->conversions + 1;
            $beta = $stats->exposures - $stats->conversions + 1;

            $samples[$variant->id] = $this->sampleBeta($alpha, $beta);
        }

        // Select variant with highest sampled value
        $selectedId = array_keys($samples, max($samples))[0];
        return $variants->find($selectedId);
    }
}
```

**Dashboard Addition:**
- Show traffic allocation shifting over time
- "Regret" metric (value lost by not always choosing winner)
- Confidence intervals updating in real-time

---

### 2. Holdout Groups: Measuring Long-Term Impact

**The Problem:** A/B tests measure immediate conversion, but some changes have delayed effects (positive or negative).

**The Opportunity:** Maintain permanent holdout groups to measure long-term impact.

**Implementation:**

```php
Schema::create('experiment_holdouts', function (Blueprint $table) {
    $table->id();
    $table->string('holdout_name', 100); // 'global_holdout', 'pricing_holdout'
    $table->decimal('holdout_percentage', 5, 2)->default(5.00); // 5% of traffic
    $table->text('description')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
});

// In ExperimentService
public function isInHoldout(string $holdoutName, string $visitorId): bool
{
    $holdout = ExperimentHoldout::where('holdout_name', $holdoutName)->first();
    if (!$holdout || !$holdout->active) return false;

    // Deterministic assignment based on visitor ID
    $hash = crc32($visitorId . $holdoutName);
    return ($hash % 10000) < ($holdout->holdout_percentage * 100);
}
```

**Use Cases:**
- **Global 5% holdout:** Never sees any experiments. Baseline for "did experiments collectively help?"
- **Feature holdout:** 10% never sees new prompt builder UI. Compare 6-month retention.
- **Pricing holdout:** 5% sees original pricing forever. Measure long-term revenue impact.

**Reporting:**

```sql
-- Long-term holdout analysis
SELECT
    CASE WHEN eh.id IS NOT NULL THEN 'holdout' ELSE 'experiment' END as group_type,
    COUNT(DISTINCT u.id) as users,
    AVG(u.prompts_lifetime) as avg_prompts,
    AVG(CASE WHEN u.subscription_tier != 'free' THEN 1 ELSE 0 END) as paid_rate,
    AVG(EXTRACT(DAY FROM NOW() - u.created_at)) as avg_account_age_days
FROM users u
LEFT JOIN experiment_holdout_assignments eha ON u.id = eha.user_id
LEFT JOIN experiment_holdouts eh ON eha.holdout_id = eh.id
WHERE u.created_at > NOW() - INTERVAL '6 months'
GROUP BY group_type;
```

---

### 3. Qualitative + Quantitative: Mixed Methods Testing

**The Problem:** A/B tests tell you *what* works, not *why*.

**The Opportunity:** Integrate qualitative research with quantitative testing.

**Approach 1: Post-Experiment User Interviews**

```php
// After experiment reaches significance, flag users for interview
if ($experiment->hasReachedSignificance() && $experiment->winner_variant_id) {
    $interviewCandidates = ExperimentAssignment::query()
        ->where('experiment_id', $experiment->id)
        ->whereHas('user', fn($q) => $q
            ->where('subscription_tier', '!=', 'free')
            ->whereNotNull('email')
        )
        ->inRandomOrder()
        ->limit(20)
        ->get();

    // Notify product team for outreach
    Notification::send($productTeam, new InterviewCandidatesReady($interviewCandidates));
}
```

**Approach 2: In-Experiment Micro-Surveys**

```typescript
// After key action in experiment variant, show micro-survey
if (variant === 'new_framework_explanation' && userJustViewedFramework) {
    showMicroSurvey({
        question: 'Did the framework explanation help you understand the recommendation?',
        options: ['Yes, very helpful', 'Somewhat helpful', 'Not really', 'I skipped it'],
        experimentId: experiment.id,
        variantId: variant.id,
    });
}
```

**Approach 3: Session Recording Sampling**

```php
// Record 5% of sessions in each variant for qualitative review
if ($this->shouldRecordSession($variant)) {
    // Integrate with FullStory (already in production)
    FullStory::tagSession([
        'experiment' => $experiment->slug,
        'variant' => $variant->slug,
    ]);
}
```

---

### 4. Feature Flags Integration

**The Opportunity:** Unify A/B testing with feature flag management.

**Unified Model:**

```php
// Features can be:
// 1. Boolean flags (on/off)
// 2. Percentage rollouts (10% of users)
// 3. A/B experiments (with statistical tracking)
// 4. Targeted releases (by segment)

Schema::create('features', function (Blueprint $table) {
    $table->id();
    $table->string('key', 100)->unique(); // 'new_prompt_builder', 'voice_input'
    $table->string('name', 200);
    $table->text('description')->nullable();

    $table->enum('type', ['boolean', 'percentage', 'experiment', 'targeted']);
    $table->boolean('enabled')->default(false);
    $table->decimal('rollout_percentage', 5, 2)->nullable();
    $table->foreignId('experiment_id')->nullable(); // Links to experiment if type=experiment
    $table->json('targeting_rules')->nullable();

    $table->timestamps();
});

// Usage in code
class FeatureService
{
    public function isEnabled(string $key, User|Visitor|null $subject = null): bool
    {
        $feature = $this->getFeature($key);

        return match($feature->type) {
            'boolean' => $feature->enabled,
            'percentage' => $this->inPercentage($feature, $subject),
            'experiment' => $this->getExperimentVariant($feature, $subject)?->is_control === false,
            'targeted' => $this->matchesTargeting($feature, $subject),
        };
    }
}
```

**Benefits:**
- Gradual rollouts before full A/B test
- Kill switch for experiments gone wrong
- Same targeting rules for flags and experiments
- Unified dashboard for all feature states

---

### 5. Experiment Velocity & Learning Cadence

**The Problem:** Running experiments is only valuable if learnings are applied.

**The Opportunity:** Track experiment velocity and institutionalise learning.

**Metrics to Track:**

```php
Schema::create('experiment_learnings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('experiment_id')->constrained();

    // Learning documentation
    $table->text('hypothesis_validated')->nullable(); // Was hypothesis correct?
    $table->text('key_insights')->nullable(); // What did we learn?
    $table->text('unexpected_findings')->nullable(); // Surprises?
    $table->text('next_experiments')->nullable(); // Follow-up tests?

    // Impact tracking
    $table->enum('decision', ['implement_winner', 'iterate', 'abandon', 'inconclusive']);
    $table->boolean('changes_shipped')->default(false);
    $table->timestamp('shipped_at')->nullable();

    // Attribution
    $table->foreignId('documented_by')->nullable();
    $table->timestamps();
});
```

**Learning Dashboard:**

```
┌─────────────────────────────────────────────────────────────┐
│  Experimentation Health                                      │
├─────────────────────────────────────────────────────────────┤
│  Experiments this quarter:     12                            │
│  Reached significance:         8 (67%)                       │
│  Winners shipped:              6 (75% of significant)        │
│  Avg days to significance:     14.3                          │
│                                                              │
│  Cumulative Impact (est.):                                   │
│  - Conversion rate:            +12.4%                        │
│  - Prompt completion rate:     +8.2%                         │
│  - Revenue:                    +£4,200/month                 │
└─────────────────────────────────────────────────────────────┘
```

**Experiment Review Ritual:**
- Weekly: Review running experiments, check for significance
- Bi-weekly: Document learnings, decide on shipping
- Monthly: Retrospective on experiment velocity, prioritise next tests
- Quarterly: Calculate cumulative impact, update roadmap

---

### 6. Bayesian A/B Testing: Better for Small Samples

**The Problem:** Frequentist A/B testing (p-values) requires large samples and gives binary answers.

**The Opportunity:** Bayesian approach gives probability distributions, better for smaller traffic.

**Bayesian vs Frequentist:**

| Aspect | Frequentist | Bayesian |
|--------|-------------|----------|
| Question answered | "Is difference real?" | "How likely is B better than A?" |
| Output | p-value (reject/accept) | Probability distribution |
| Early stopping | Risky (inflates false positives) | Safe (posterior updates continuously) |
| Small samples | Unreliable | More informative |
| Interpretation | "95% confidence interval" (confusing) | "90% probability B is better" (intuitive) |

**Implementation:**

```php
class BayesianStatisticsService
{
    /**
     * Calculate probability that variant B beats A
     */
    public function probabilityBBeatsA(
        int $conversionsA, int $exposuresA,
        int $conversionsB, int $exposuresB,
        int $simulations = 100000
    ): float {
        $bWins = 0;

        for ($i = 0; $i < $simulations; $i++) {
            // Sample from Beta posteriors
            $sampleA = $this->sampleBeta($conversionsA + 1, $exposuresA - $conversionsA + 1);
            $sampleB = $this->sampleBeta($conversionsB + 1, $exposuresB - $conversionsB + 1);

            if ($sampleB > $sampleA) $bWins++;
        }

        return $bWins / $simulations;
    }

    /**
     * Expected loss if we choose wrong variant
     */
    public function expectedLoss(/* ... */): array
    {
        // Returns expected regret for choosing A vs B
        // Helps decide when to stop experiment
    }
}
```

**Dashboard Enhancement:**

```
┌─────────────────────────────────────────────────────────────┐
│  Experiment: Pricing Modal Wall Removal                      │
├─────────────────────────────────────────────────────────────┤
│  Probability B beats Control:  94.2%                         │
│  Expected lift:                +18.4% (+12.1% to +24.8%)     │
│  Expected loss if wrong:       0.3%                          │
│                                                              │
│  Recommendation: SHIP VARIANT B                              │
│  (94% confidence, minimal downside risk)                     │
└─────────────────────────────────────────────────────────────┘
```

---

### 7. Experiment Governance & Avoiding Conflicts

**The Problem:** Multiple experiments running simultaneously can interact in unexpected ways.

**The Solution:** Experiment governance framework.

**Conflict Detection:**

```php
class ExperimentGovernanceService
{
    /**
     * Check if new experiment conflicts with existing ones
     */
    public function detectConflicts(Experiment $newExperiment): array
    {
        $conflicts = [];
        $activeExperiments = Experiment::active()->get();

        foreach ($activeExperiments as $existing) {
            // Same page/component?
            if ($this->overlappingScope($newExperiment, $existing)) {
                $conflicts[] = [
                    'type' => 'scope_overlap',
                    'experiment' => $existing,
                    'message' => "Both experiments modify {$existing->scope}",
                ];
            }

            // Same target audience?
            if ($this->overlappingAudience($newExperiment, $existing)) {
                $conflicts[] = [
                    'type' => 'audience_overlap',
                    'experiment' => $existing,
                    'message' => "Both target same user segment",
                ];
            }

            // Same success metric?
            if ($this->sameGoal($newExperiment, $existing)) {
                $conflicts[] = [
                    'type' => 'metric_interference',
                    'experiment' => $existing,
                    'message' => "Both measure {$existing->goal_name}, may confound results",
                ];
            }
        }

        return $conflicts;
    }
}
```

**Mutual Exclusion Groups:**

```php
// Users in one experiment are excluded from conflicting experiments
Schema::create('experiment_exclusion_groups', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('experiment_exclusion_group_members', function (Blueprint $table) {
    $table->foreignId('exclusion_group_id')->constrained('experiment_exclusion_groups');
    $table->foreignId('experiment_id')->constrained();
    $table->primary(['exclusion_group_id', 'experiment_id']);
});

// Usage: "Pricing Experiments" exclusion group
// User in "Yearly Default" experiment won't be in "Price Point" experiment
```

---

### 8. Counterfactual Analysis: What Would Have Happened?

**The Opportunity:** After shipping a winning variant, estimate impact on users who never saw the experiment.

**Approach:**

```sql
-- Compare users who saw experiment vs similar users who didn't
-- (using propensity score matching or similar technique)

WITH experiment_users AS (
    SELECT
        u.id,
        u.created_at,
        u.personality_type,
        u.subscription_tier,
        COUNT(pr.id) as prompt_count,
        MAX(ea.variant_id) as variant_id
    FROM users u
    JOIN experiment_assignments ea ON u.id = ea.user_id
    LEFT JOIN prompt_runs pr ON u.id = pr.user_id
    WHERE ea.experiment_id = 123
    GROUP BY u.id
),
non_experiment_users AS (
    -- Similar users who weren't in experiment (same time period, similar profiles)
    SELECT
        u.id,
        u.created_at,
        u.personality_type,
        u.subscription_tier,
        COUNT(pr.id) as prompt_count
    FROM users u
    LEFT JOIN prompt_runs pr ON u.id = pr.user_id
    WHERE u.id NOT IN (SELECT id FROM experiment_users)
      AND u.created_at BETWEEN '2026-01-01' AND '2026-02-01'
    GROUP BY u.id
)
-- Compare outcomes between groups
SELECT
    'experiment_winner' as cohort,
    AVG(prompt_count) as avg_prompts,
    AVG(CASE WHEN subscription_tier != 'free' THEN 1 ELSE 0 END) as paid_rate
FROM experiment_users
WHERE variant_id = (SELECT winner_variant_id FROM experiments WHERE id = 123)
UNION ALL
SELECT
    'non_experiment' as cohort,
    AVG(prompt_count),
    AVG(CASE WHEN subscription_tier != 'free' THEN 1 ELSE 0 END)
FROM non_experiment_users;
```

**Use Case:** After shipping "Framework Explanation" winner:
- "If we'd shown this to all users from day 1, we estimate 2,400 additional completed prompts"
- Helps calculate true ROI of experimentation program

---

### 9. The "Personality Lab": Unique to BettrPrompt

**The Opportunity:** Use personality data to create a research advantage no competitor can replicate.

**Concept:** A dedicated experimentation track for personality-driven hypotheses.

**Example Experiments:**

| Hypothesis | Test | Expected Insight |
|------------|------|------------------|
| Judgers prefer structured flows | J-types: Linear wizard vs P-types: Flexible tabs | Validate personality UX theory |
| Thinkers need more explanation | T-types: Detailed rationale vs F-types: Emotional benefit | Framework explanation strategy |
| Turbulent types need reassurance | T-identity: Progress + ETA vs A-identity: Minimal chrome | Anxiety-reducing patterns |
| Introverts prefer async | I-types: "We'll email when done" vs E-types: "Watch it generate" | Processing UX preference |

**Research Value:**
- Publishable insights on personality-driven UX
- Content marketing: "How INTJs approach AI prompts differently"
- Product differentiation: "The only prompt tool designed for how you think"

**Implementation:**

```php
// Tag experiments as "Personality Lab" research
Schema::table('experiments', function (Blueprint $table) {
    $table->boolean('is_personality_research')->default(false);
    $table->string('personality_hypothesis', 500)->nullable();
    $table->json('personality_segments_tested')->nullable(); // ['INTJ', 'ENFP']
});
```

---

## Implementation Priority for Advanced Strategies

| Strategy | Complexity | Value | When to Implement |
|----------|------------|-------|-------------------|
| Feature Flags Integration | Medium | High | Phase 2 (before experiments) |
| Bayesian Statistics | Medium | High | Phase 4 (replace frequentist) |
| Experiment Governance | Low | Medium | Phase 3 (as experiments scale) |
| Learning Documentation | Low | High | Phase 1 (cultural, not technical) |
| Multi-Armed Bandit | High | Medium | Phase 5 (for specific use cases) |
| Holdout Groups | Low | High | Phase 3 (set up early) |
| Qualitative Integration | Medium | High | Phase 4 (after baseline established) |
| Personality Lab | Medium | Very High | Phase 4 (unique differentiator) |
| Counterfactual Analysis | High | Medium | Phase 6 (advanced) |

---

*Document created: January 2025*
*Last updated: January 2026 - Added unified event architecture, non-blocking event handling, CDP integration, A/B testing priority roadmap, and advanced testing strategies*
