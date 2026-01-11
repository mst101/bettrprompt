# A/B Testing System Implementation Plan

## Executive Summary

This document outlines an in-house A/B testing system for BettrPrompt that provides:
- Full flexibility to test UI/copy, features, and pricing
- Visitor segmentation by country, personality, subscription, UTM source, etc.
- Built-in statistical significance calculations with automated recommendations

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
     * Record experiment event
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

```php
// routes/api.php
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

    $experimentService = app(ExperimentService::class);
    $visitorId = $request->cookie('visitor_id');
    $visitor = $visitorId ? Visitor::find($visitorId) : null;

    $experimentService->recordEvent(
        $request->input('event_type'),
        $request->input('event_name'),
        $visitor,
        $request->user(),
        $request->input('metadata', []),
        $request->input('value')
    );

    return response()->json(['success' => true]);
})->middleware('throttle:60,1');
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

**Solution:** Separate tables, future listener

- `experiment_events` optimised for A/B statistics
- When CDP implemented, add Laravel listener to also write to `analytics_events`
- Keeps statistical queries fast

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

### Phase 1: Core Infrastructure (Week 1-2)
- [ ] Database migrations (4 tables)
- [ ] Eloquent models with relationships
- [ ] ExperimentService (assignment, targeting, events)
- [ ] HandleInertiaRequests integration
- [ ] useExperiment composable
- [ ] ABTest component

### Phase 2: Statistical Analysis (Week 2-3)
- [ ] StatisticsService implementation
- [ ] Chi-squared and Z-test calculations
- [ ] Wilson confidence intervals
- [ ] Sample size calculator
- [ ] Recommendation engine

### Phase 3: Admin Dashboard (Week 3-4)
- [ ] ExperimentController with CRUD
- [ ] Index page with filters
- [ ] Create wizard with targeting builder
- [ ] Results dashboard with visualisations
- [ ] Start/pause/stop controls
- [ ] Declare winner workflow

### Phase 4: Advanced Features (Week 4-5)
- [ ] Auto-stop rules based on significance
- [ ] Exclusion group management UI
- [ ] Experiment scheduling
- [ ] Historical archive
- [ ] Export to CSV/JSON

### Phase 5: Testing & Documentation (Week 5-6)
- [ ] Unit tests for services
- [ ] Feature tests for admin CRUD
- [ ] Statistical calculation verification
- [ ] E2E tests for key workflows
- [ ] User documentation

---

## Files to Create

```
database/migrations/
├── 2026_01_XX_000001_create_experiments_table.php
├── 2026_01_XX_000002_create_experiment_variants_table.php
├── 2026_01_XX_000003_create_experiment_assignments_table.php
└── 2026_01_XX_000004_create_experiment_events_table.php

app/Models/
├── Experiment.php
├── ExperimentVariant.php
├── ExperimentAssignment.php
└── ExperimentEvent.php

app/Services/
├── ExperimentService.php
└── StatisticsService.php

app/Http/Controllers/Admin/
└── ExperimentController.php

app/Http/Requests/Admin/
├── StoreExperimentRequest.php
└── UpdateExperimentRequest.php

resources/js/Composables/features/
└── useExperiment.ts

resources/js/Components/Common/
└── ABTest.vue

resources/js/Types/
└── experiments.ts

resources/js/Pages/Admin/Experiments/
├── Index.vue
├── Create.vue
├── Edit.vue
└── Show.vue

tests/Unit/Services/
├── ExperimentServiceTest.php
└── StatisticsServiceTest.php

tests/Feature/Admin/
└── ExperimentControllerTest.php
```

## Files to Modify

- `app/Http/Middleware/HandleInertiaRequests.php` - Share experiments
- `routes/web.php` - Admin experiment routes
- `routes/api.php` - Tracking endpoint
- `resources/js/Types/index.ts` - Export types

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

---

*Document created: January 2025*
