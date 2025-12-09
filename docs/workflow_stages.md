# PromptRun Field Refactoring Plan

## Overview

Refactor the `prompt_runs` table migration and all related code to:

1. **Remove `status` field** - Redundant with granular `workflow_stage`
2. Add workflow prefixes to `workflow_stage` enum values (`0_`, `1_`, `2_`)
3. Reorder migration fields into logical groups
4. Fix missing `pre_analysis_context` in Model `$casts`
5. Add helper methods/scopes to Model for common queries

## Rationale for Removing `status`

With prefixed `workflow_stage` values, `status` becomes redundant:

- ❌ Old: `status: 'processing'` + `workflow_stage: 'submitted'`
- ✅ New: `workflow_stage: '1_processing'` (implies processing)

Helper methods will provide same functionality:

- `isProcessing()` - Check if actively running
- `isCompleted()` - Check if finished
- `isFailed()` - Check if failed
- `scopeProcessing()` - Query scope for processing runs

## Enum Value Mappings

### workflow_stage Changes

| Old Value                 | New Value                            | State Type | Description                            |
|---------------------------|--------------------------------------|------------|----------------------------------------|
| `generating_pre_analysis` | `0_processing`                       | Processing | Workflow 0: Running pre-analysis       |
| `pre_analysis_questions`  | `0_completed`                        | Completed  | Workflow 0: Completed, questions ready |
| `submitted`               | `1_processing`                       | Processing | Workflow 1: Running main analysis      |
| `analysis_complete`       | `1_completed`                        | Completed  | Workflow 1: Completed, questions ready |
| `answering_questions`     | ❌ REMOVED                            | -          | (Redundant - use `1_completed`)        |
| `generating_prompt`       | `2_processing`                       | Processing | Workflow 2: Generating final prompt    |
| `completed`               | `2_completed`                        | Completed  | Workflow 2: Completed - SUCCESS!       |
| `failed`                  | `0_failed` / `1_failed` / `2_failed` | Failed     | Failed at specific workflow            |

**Complete 9-stage workflow:**

**Workflow 0 (Pre-analysis):**

1. `0_processing` - Running pre-analysis
2. `0_completed` - Pre-analysis complete (optional questions available)
3. `0_failed` - Pre-analysis failed

**Workflow 1 (Main analysis):**

4. `1_processing` - Running main analysis
5. `1_completed` - Analysis complete, clarifying questions ready
6. `1_failed` - Analysis failed

**Workflow 2 (Prompt generation):**

7. `2_processing` - Generating final prompt
8. `2_completed` - **Successfully finished!** (the only true success state)
9. `2_failed` - Prompt generation failed

**State Types:**

- **Processing** (`X_processing`) - Background job running
- **Completed** (`X_completed`) - Workflow completed successfully
- **Failed** (`X_failed`) - Workflow failed at this specific stage

**Key Improvements:**

- ✅ **Granular failure tracking**: Know exactly which workflow failed
- ✅ **Consistent pattern**: Every workflow has `X_processing`, `X_completed`, `X_failed`
- ✅ **Better retry logic**: Can restart from exact failure point
- ✅ **Clear success state**: Only `2_completed` means fully successful
- ✅ **No ambiguity**: `2_completed` follows the same pattern as other workflows

## Migration Field Ordering (New Structure)

```php
// 1. Core Identity & Relationships
$table->id();
$table->foreignUuid('visitor_id');
$table->foreignId('user_id')->nullable();
$table->foreignId('parent_id')->nullable();

// 2. User Input
$table->text('task_description');
$table->enum('personality_type', [...])->nullable();
$table->json('trait_percentages')->nullable();

// 3. Workflow Status (REMOVED: status field - now redundant)
$table->enum('workflow_stage', [
    '0_processing', '0_completed', '0_failed',
    '1_processing', '1_completed', '1_failed',
    '2_processing', '2_completed', '2_failed'
])->default('0_processing');
$table->text('error_message')->nullable();

// 4. Pre-Analysis / Workflow 0
$table->json('pre_analysis_questions')->nullable();
$table->json('pre_analysis_answers')->nullable();
$table->json('pre_analysis_context')->nullable();
$table->text('pre_analysis_reasoning')->nullable();
$table->boolean('pre_analysis_skipped')->default(false);
$table->json('pre_analysis_api_usage')->nullable();

// 5. Task Analysis / Workflow 1
$table->json('task_classification')->nullable();
$table->json('cognitive_requirements')->nullable();
$table->json('selected_framework')->nullable();
$table->json('alternative_frameworks')->nullable();
$table->enum('personality_tier', ['full', 'partial', 'none'])->nullable();
$table->json('task_trait_alignment')->nullable();
$table->json('personality_adjustments_preview')->nullable();
$table->text('question_rationale')->nullable();
$table->json('analysis_api_usage')->nullable();

// 6. Framework Q&A
$table->json('framework_questions')->nullable();
$table->json('clarifying_answers')->nullable();
$table->integer('current_question_index')->default(0);

// 7. Prompt Generation / Workflow 2
$table->text('optimized_prompt')->nullable();
$table->json('framework_used')->nullable();
$table->json('personality_adjustments_summary')->nullable();
$table->json('model_recommendations')->nullable();
$table->json('iteration_suggestions')->nullable();
$table->json('generation_api_usage')->nullable();

// 8. Timestamps
$table->timestamps();
$table->timestamp('completed_at')->nullable();
```

## Model Helper Methods

Add these methods to `app/Models/PromptRun.php`:

```php
/**
 * Check if the prompt run is actively processing (background job running)
 */
public function isProcessing(): bool
{
    return in_array($this->workflow_stage, [
        '0_processing',
        '1_processing',
        '2_processing',
    ]);
}

/**
 * Check if the prompt run is pending (waiting for user input)
 */
public function isPending(): bool
{
    return in_array($this->workflow_stage, [
        '0_completed',
        '1_completed',
    ]);
}

/**
 * Check if the prompt run is completed successfully
 */
public function isCompleted(): bool
{
    return $this->workflow_stage === '2_completed';
}

/**
 * Check if the prompt run has failed at any stage
 */
public function isFailed(): bool
{
    return in_array($this->workflow_stage, [
        '0_failed',
        '1_failed',
        '2_failed',
    ]);
}

/**
 * Get which workflow failed (0, 1, or 2), or null if not failed
 */
public function getFailedWorkflow(): ?int
{
    return match($this->workflow_stage) {
        '0_failed' => 0,
        '1_failed' => 1,
        '2_failed' => 2,
        default => null,
    };
}

/**
 * Scope: Query only processing runs
 */
public function scopeProcessing($query)
{
    return $query->whereIn('workflow_stage', [
        '0_processing',
        '1_processing',
        '2_processing',
    ]);
}

/**
 * Scope: Query only completed runs
 */
public function scopeCompleted($query)
{
    return $query->where('workflow_stage', '2_completed');
}

/**
 * Scope: Query only failed runs (any workflow)
 */
public function scopeFailed($query)
{
    return $query->whereIn('workflow_stage', [
        '0_failed',
        '1_failed',
        '2_failed',
    ]);
}
```

## Implementation Steps

### Step 1: Update Migration File

**File:** `database/migrations/2025_11_02_000002_create_prompt_runs_table.php`

- **REMOVE** `status` field entirely (line 34)
- Update `workflow_stage` enum with prefixed values
- Update default value: `workflow_stage` → `'0_processing'`
- Reorder all fields according to new structure above

### Step 2: Update Model

**File:** `app/Models/PromptRun.php`

- **REMOVE** `'status'` from `$fillable` array (line 31)
- Add `'pre_analysis_context' => 'array'` to `$casts` (currently missing)
- Reorder `$fillable` array to match new migration field order (optional but recommended)
- **ADD** all helper methods listed above (isProcessing, isPending, isCompleted, isFailed, and scopes)

### Step 3: Update Jobs (3 files)

**File:** `app/Jobs/ProcessPreAnalysis.php`

- Line 57: **REMOVE** `'status' =>`, change to `'workflow_stage' => '0_completed'`
- Line 99: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`
- Line 149: **REMOVE** `'status' =>`, change to `'workflow_stage' => '0_failed'`

**File:** `app/Jobs/ProcessAnalysis.php`

- Line 71: **REMOVE** `'status' =>`, keep `'workflow_stage' => '1_completed'`
- Line 135: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_failed'`

**File:** `app/Jobs/ProcessPromptGeneration.php`

- Line 92: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_completed'`
- Line 146: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_failed'`

### Step 4: Update Controller

**File:** `app/Http/Controllers/PromptBuilderController.php` (20+ changes)

**Remove all `'status' =>` assignments, update workflow_stage values:**

- Line 145: **REMOVE** `'status' =>`, change to `'workflow_stage' => '0_processing'`
- Line 174: Update comparison → `!== '0_completed'`
- Line 195: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`
- Line 247: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`
- Line 413: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_processing'`
- Line 481: Update comparison → `!== '1_completed'` (no more `2_answering`)
- Line 505: Update stage → `'1_completed'` (remove status, no `2_answering`)
- Line 594: **REMOVE** `'status' =>`, change to `'workflow_stage' => '0_processing'`
- Line 677: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_processing'`
- Line 708: Update retry check → `$promptRun->isFailed()` (use helper method)
- Line 724: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`
- Line 746: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_processing'`
- Line 886: **REMOVE** `'status' =>`, keep `'workflow_stage' => '1_completed'` (no change needed - already at this stage
  when answering)
- Line 908: **REMOVE** `'status'` from `$allowedSortColumns` array (or replace with `'workflow_stage'`)
- Line 959: Update edit check → `$promptRun->isCompleted()` (use helper method)
- Line 1047: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`

### Step 5: Update Middleware

**File:** `app/Http/Middleware/HandleInertiaRequests.php`

- Line 41: Change `->where('status', 'completed')` to `->completed()` (use scope)

### Step 6: Update API Routes

**File:** `routes/api.php`

- Line 30: Update validation rule:
  ```php
  'workflow_stage' => 'nullable|string|in:0_processing,0_completed,0_failed,1_processing,1_completed,1_failed,2_processing,2_completed,2_failed'
  ```
- Line 31: **REMOVE** `'status'` validation rule entirely
- Line 60, 84: **REMOVE** any `'status'` field reads/updates

### Step 7: Update Resource

**File:** `app/Http/Resources/PromptRunResource.php`

- Line 88: **REMOVE** `'status' => $this->status`

### Step 8: Update Factory

**File:** `database/factories/PromptRunFactory.php`

- Line 41: **REMOVE** `'status' =>`, keep only `'workflow_stage' => '0_processing'`
- Line 58: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_processing'`
- Line 69: **REMOVE** `'status' =>`, keep `'workflow_stage' => '1_completed'`
- Line 86: **REMOVE** method `answeringQuestions()` entirely (no longer needed - use `analysisCompleted()`)
- Line 103: **REMOVE** `'status' =>`, keep `'workflow_stage' => '2_processing'`
- Line 114: Change to `'workflow_stage' => '2_completed'`
- Line 127: Change to `'workflow_stage' => '0_failed'` (default failed state - could be any `X_failed`)

### Step 9: Update Seeders (2 files)

**File:** `database/seeders/TestPromptRunsSeeder.php`

- **REMOVE** all `'status' =>` assignments
- Update all workflow_stage values with new prefixed values

**File:** `database/seeders/RealtimeBroadcastTestSeeder.php`

- **REMOVE** all `'status' =>` assignments
- Update all workflow_stage values with new prefixed values

### Step 10: Update Test Builder

**File:** `tests/Builders/PromptRunBuilder.php`

- Line 73: Change to `->workflow('1_processing')`
- Line 93: **REMOVE** `'status' =>`, change to `'workflow_stage' => '2_completed'`
- Line 105: **REMOVE** `'status' =>`, change to `'workflow_stage' => '1_failed'` (or add variations for `0_failed`,
  `2_failed`)
- Line 122: Keep `->workflow('1_completed')` (no change)
- Line 139: **REMOVE** `answeringQuestions()` method entirely (use `analysisCompleted()` instead)
- Line 150: Keep `->workflow('2_processing')` (no change)

### Step 11: Update Feature Tests (6+ files)

Search and replace all hardcoded status/workflow_stage strings:

- **REMOVE** all `'status' =>` assignments and comparisons
- Update workflow_stage enum values with new prefixes in:
    - `tests/Feature/N8nWebhookTest.php`
    - `tests/Feature/N8nWorkflowIntegrationTest.php`
    - `tests/Feature/PromptBuilderChildRunsTest.php`
    - `tests/Feature/PromptBuilderErrorHandlingTest.php`
    - `tests/Feature/PromptBuilderTest.php`
    - `tests/Feature/PromptBuilderUpdateTest.php`

### Step 12: Update Frontend Constants

**File:** `resources/js/constants/workflow.ts`

Update constants and remove status-related code:

```typescript
export const WORKFLOW_STAGES = {
    PRE_ANALYSIS_PROCESSING: '0_processing',
    PRE_ANALYSIS_COMPLETED: '0_completed',
    PRE_ANALYSIS_FAILED: '0_failed',
    ANALYSIS_PROCESSING: '1_processing',
    ANALYSIS_COMPLETED: '1_completed',
    ANALYSIS_FAILED: '1_failed',
    PROMPT_PROCESSING: '2_processing',
    PROMPT_COMPLETED: '2_completed',
    PROMPT_FAILED: '2_failed',
} as const;

// REMOVE STATUSES constant entirely
// REMOVE SUBMITTED, PRE_ANALYSIS_QUESTIONS, ANSWERING_QUESTIONS, COMPLETED, FAILED (old names)
```

Update helper functions:

- `getWorkflowStageLabel()` - Update all cases with new 9 values
- **REMOVE** `getStatusLabel()` function if it exists
- Add new helper: `isProcessingStage(stage)` - returns true for `0_processing`, `1_processing`, `2_processing`
- Add new helper: `isFailedStage(stage)` - returns true for `0_failed`, `1_failed`, `2_failed`

### Step 13: Update Frontend Components

**File:** `resources/js/Pages/PromptBuilder/Show.vue`

- Line 148: Change `props.promptRun.status !== 'processing'` to check workflow_stage instead
- Line 191: Change `props.promptRun.status === 'processing'` to `promptRun.isProcessing()` or check stage
- Lines 236-264: Update all workflow_stage comparisons with new prefixed values
- Lines 376-387: Update progress component stage checks

**File:** `resources/js/Composables/useStatusBadge.ts`

- **REFACTOR** to work with workflow_stage instead of status
- Map workflow stages to badge colours:
    - Processing stages (`0_processing`, `1_processing`, `2_processing`) → Yellow
    - Completed stages (`0_completed`, `1_completed`) → Grey
    - Success (`2_completed`) → Green
    - Failed stages (`0_failed`, `1_failed`, `2_failed`) → Red

**File:** `resources/js/Components/StatusBadge.vue`

- Update to accept `workflowStage` prop instead of `status`
- Use `useStatusBadge` composable with workflow_stage

**Other Vue components** - Update hardcoded strings:

- `PreAnalysisProgress.vue` - Check for workflow_stage references
- `AnalysisProgress.vue` - Check for workflow_stage references
- `GenerationProgress.vue` - Check for workflow_stage references

### Step 14: Update TypeScript Types

**File:** `resources/js/types/resources/PromptRunResource.ts`

- **REMOVE** `readonly status: string;` (line 49)
- Add strict WorkflowStage type:

```typescript
export type WorkflowStage =
    | '0_processing' | '0_completed' | '0_failed'
    | '1_processing' | '1_completed' | '1_failed'
    | '2_processing' | '2_completed' | '2_failed';

readonly
workflowStage: WorkflowStage;
```

**File:** `resources/js/types/models/PromptRun.ts`

- **REMOVE** `readonly status: string | null;` (line 26)
- Update workflowStage type to use strict union type (same as above)

### Step 15: Update Frontend Test Helpers

**File:** `tests-frontend/helpers/mount.ts`

- Line 96: **REMOVE** `workflow_stage: string` type definition for status
- Line 115: **REMOVE** `status: 'processing'`, keep only `workflow_stage: '0_processing'`

**File:** `tests-frontend/e2e/mocks/n8n-mock-service.ts`

- Line 140: Change comparison to `=== '2_processing'`
- **REMOVE** any status checks

**File:** `tests-frontend/e2e/mocks/n8n-responses.ts`

- **REMOVE** all status fields from mocks
- Update all workflow_stage values to new prefixed values
- Lines 24, 43, 50, 386, 442, 454, 470, 487, 500 - Update workflow_stage values

## Testing Checklist

### Backend Tests

```bash
php artisan test
php artisan test tests/Feature/PromptBuilderTest.php
php artisan test tests/Feature/N8nWebhookTest.php
php artisan test tests/Feature/N8nWorkflowIntegrationTest.php
```

### Frontend Tests

```bash
npm run type-check
npm run lint
npm run build
```

### Manual Testing

1. Create new prompt run → verify `workflow_stage: '0_processing'`
2. Pre-analysis completes → verify transition to `0_completed`
3. User proceeds → verify transition to `1_processing`
4. Analysis completes → verify `1_completed`
5. User answers questions → stays at `1_completed` (no separate answering stage)
6. Generate prompt → verify `2_processing`
7. Success → verify `2_completed`
8. Test failure scenarios:
    - Force failure at workflow 0 → verify `0_failed`
    - Force failure at workflow 1 → verify `1_failed`
    - Force failure at workflow 2 → verify `2_failed`
9. Test retry functionality with each failed state
10. Test child run creation
11. Verify n8n webhook updates
12. Check StatusBadge displays correctly for all 9 stages
13. Verify polling works (check `isProcessing()` logic with 3 generating stages)
14. Verify failed runs show correct error messages and stage information

## Critical Files Summary

**Most Critical (Must be perfect):**

1. `database/migrations/2025_11_02_000002_create_prompt_runs_table.php` - **REMOVE status field**, update enum, reorder
   fields
2. `app/Models/PromptRun.php` - Add helper methods, remove status from fillable/casts
3. `app/Http/Controllers/PromptBuilderController.php` - Most complex (20+ changes)
4. `resources/js/constants/workflow.ts` - Frontend source of truth
5. `resources/js/Composables/useStatusBadge.ts` - Refactor to use workflow_stage

**High Priority:**

6. `app/Jobs/ProcessPreAnalysis.php` - Workflow 0 transitions
7. `app/Jobs/ProcessAnalysis.php` - Workflow 1 transitions
8. `app/Jobs/ProcessPromptGeneration.php` - Workflow 2 transitions
9. `database/factories/PromptRunFactory.php` - Used by all tests
10. `tests/Builders/PromptRunBuilder.php` - Test helper
11. `resources/js/Pages/PromptBuilder/Show.vue` - Main UI
12. `resources/js/types/resources/PromptRunResource.ts` - TypeScript types

## Deployment Strategy

Single-step migration (not yet in production):

1. **Update migration** - Modify original create table migration
2. **Update all code** - Backend PHP and Frontend TypeScript/Vue
3. **Run migration** - `php artisan migrate:fresh` (dev environment)
4. **Run tests** - All backend and frontend tests must pass
5. **Manual testing** - Complete workflow end-to-end
6. **Build frontend** - Verify no TypeScript errors

## Notes

- **Breaking Change**: Removes `status` field entirely, replaces with 9-stage workflow_stage system
- **Granular Failure Tracking**: `0_failed`, `1_failed`, `2_failed` instead of generic `failed`
- **Consistent Pattern**: Every workflow follows `X_processing` → `X_completed` / `X_failed`
- **Success State**: Only `2_completed` represents successful completion
- **Helper Methods**: New model methods (`isProcessing()`, `isFailed()`, `getFailedWorkflow()`)
- **Scopes**: Query scopes for filtering by state (processing, completed, failed)
- **Badge Logic**: StatusBadge derives colour from workflow_stage patterns
- **Backwards Compatibility**: Not required (not in production)
- **Database**: Single migration that removes status column and updates enum to 9 values
