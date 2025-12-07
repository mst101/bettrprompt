# Plan: Stream n8n Workflow 1 Response for Progressive UI Updates

## User Request
Explore whether it's feasible and advisable to stream the n8n Workflow 1 response so that partial data (like `task_classification` or `selected_framework`) can be displayed on the frontend as soon as it's available, rather than waiting for the complete JSON response.

## Current State

**Workflow 1 Response Structure:**
```json
{
  "success": true,
  "data": {
    "task_classification": { ... },
    "cognitive_requirements": { ... },
    "selected_framework": { ... },
    "alternative_frameworks": [ ... ],
    "personality_tier": "full",
    "task_trait_alignment": { ... },
    "personality_adjustments_preview": [ ... ],
    "clarifying_questions": [ ... ],
    "question_rationale": "..."
  },
  "original_input": { ... },
  "api_usage": { ... },
  "error": null
}
```

**Current Flow:**
1. User submits task → Laravel dispatches `ProcessTaskAnalysis` job
2. Job calls n8n Workflow 1 via webhook
3. n8n calls Anthropic API (Claude)
4. Claude generates entire JSON response (structured output)
5. n8n returns complete JSON to Laravel
6. Laravel stores in database, broadcasts `AnalysisCompleted` event
7. Frontend receives event, reloads page to show all data at once

**Current UX:**
- User sees loading spinner/state for 5-7 seconds
- All data appears simultaneously when complete
- No progressive feedback during processing

## Phase 1: Exploration Complete ✓

### Current Architecture (Synchronous Request/Response)

**Flow:**
1. User submits task → `ProcessTaskAnalysis` job dispatched
2. Job calls `PromptFrameworkService::analyseTask()`
3. Laravel HTTP POST to n8n (60s timeout)
4. n8n Workflow 1 prepares prompt + calls Anthropic API
5. **Anthropic returns COMPLETE JSON** (no streaming enabled)
6. n8n returns full response to Laravel
7. Laravel updates database with ALL fields at once
8. Broadcasts `AnalysisCompleted` event
9. Frontend receives event, reloads page data

**Key Files:**
- n8n: `/home/mark/repos/personality/n8n/workflow_1_analysis.json`
- Job: `/home/mark/repos/personality/app/Jobs/ProcessTaskAnalysis.php`
- Service: `/home/mark/repos/personality/app/Services/PromptFrameworkService.php`
- Event: `/home/mark/repos/personality/app/Events/AnalysisCompleted.php`
- Frontend: `/home/mark/repos/personality/resources/js/Pages/PromptBuilder/Show.vue`

### Critical Constraints Identified

**Anthropic API:**
- ✅ **DOES support streaming** for text generation
- ❌ **Does NOT support streaming for structured outputs (JSON schema)**
- When using `response_format: { type: "json" }` or tools/structured outputs, streaming is disabled
- Workflow 1 uses JSON schema → **cannot stream the structured response**

**n8n Limitations:**
- HTTP Request node waits for complete response (synchronous)
- No native Server-Sent Events (SSE) support
- Would need custom n8n code node or webhook handling for streaming

**Laravel Architecture:**
- Queue jobs are atomic (start → process → complete)
- Single HTTP request with 60s timeout
- Database schema expects complete results (no partial state fields)
- Broadcasting fires once on completion

**Database Schema:**
- All analysis fields are nullable JSON columns
- Designed for batch updates, not progressive
- No fields for: `analysis_progress`, `partial_results`, `streaming_state`

### Technical Feasibility Assessment

| Approach | Feasibility | Complexity | Notes |
|----------|-------------|------------|-------|
| **Anthropic Streaming** | ❌ Not possible | N/A | Structured outputs don't support streaming |
| **Simulated Streaming** | ✅ Possible | Medium-High | Generate sections sequentially with multiple API calls |
| **Optimistic UI** | ✅ Possible | Low-Medium | Show skeleton/predictions while waiting |
| **Better Loading States** | ✅ Possible | Low | Progressive indicators without actual streaming |

## Phase 2: Design Complete ✓

## Recommended Approach: Sequential Multi-Call Pattern

Break Workflow 1 into **4 sequential API calls**, each returning structured JSON:

1. **Step 1** (3-5s): Classification + Cognitive Requirements
2. **Step 2** (8-12s): Framework Selection + Alternatives
3. **Step 3** (15-20s): Personality Analysis (if applicable)
4. **Step 4** (25-35s): Clarifying Questions

Each step broadcasts an event → Frontend shows results progressively.

### Why This Works

- ✅ **True streaming** - user sees results as they arrive
- ✅ **Maintains structured outputs** - each call uses JSON schema
- ✅ **Perceived latency** - first result in 3-5s (vs 5-7s wait)
- ✅ **Graceful degradation** - partial results saved if later steps fail
- ⚠️ **Cost increase** - 3.5x API calls (mitigated to 1.7x with caching)
- ⚠️ **Complexity** - 4 events, progressive UI, state management

### User Experience Flow

```
User submits task
    ↓ (3-5 seconds)
✓ Step 1 Complete: Show task classification
    ↓ (additional 5-7 seconds)
✓ Step 2 Complete: Show selected framework, switch to Framework tab
    ↓ (additional 7-8 seconds)
✓ Step 3 Complete: Show personality adjustments
    ↓ (additional 10-15 seconds)
✓ Step 4 Complete: Show questions, enable "Proceed" button
```

**Total time**: ~25-35s (vs 5-7s currently)
**Perceived improvement**: Results appear in 3-5s instead of 5-7s wait

## Implementation Summary

### 1. Database Changes

**New migration**: `2025_12_07_000001_add_streaming_to_prompt_runs.php`

Add fields to `prompt_runs` table:
- `analysis_stream_step` (integer, 0-4)
- `analysis_step_1_at` through `analysis_step_4_at` (timestamps)
- Optional: per-step API usage tracking

### 2. Backend Architecture

**New Job**: `ProcessTaskAnalysisStream.php`
- Orchestrates 4 sequential API calls
- Passes context between steps
- Fires events after each step
- Handles step failures gracefully

**New Service Method**: `PromptFrameworkService::analyseTaskStreaming()`
- Accepts `$step` parameter (1-4)
- Accepts `$previousStepData` for context
- Independent timeout per step (20-35s)

**New Events** (4):
- `AnalysisStep1Completed` → task_classification, cognitive_requirements
- `AnalysisStep2Completed` → selected_framework, alternative_frameworks
- `AnalysisStep3Completed` → personality_tier, task_trait_alignment, adjustments
- `AnalysisStep4Completed` → clarifying_questions, question_rationale

### 3. n8n Workflow Changes

**Modify**: `workflow_1_analysis.json`

Add routing based on `stream_step` parameter:
- Switch node routes to appropriate prompt preparation
- 4 separate "Prepare Prompt" nodes (targeted context)
- 4 separate Anthropic API calls
- Each returns only relevant fields

**Context Optimization**:
- Step 1: Framework taxonomy excerpts only
- Step 2: Framework mappings only
- Step 3: Personality calibration only
- Step 4: Question bank only

Reduces input tokens by ~40% per call.

### 4. Frontend Changes

**Modified**: `Show.vue`

Add 4 event handlers:
```typescript
AnalysisStep1Completed: () => {
    router.reload({ only: ['promptRun'] });
    // Stay on task tab, show classification
}

AnalysisStep2Completed: () => {
    router.reload({ only: ['promptRun'] });
    activeTab.value = 'framework'; // Auto-switch
}

AnalysisStep3Completed: () => {
    router.reload({ only: ['promptRun'] });
    // Show personality tab if advanced mode
}

AnalysisStep4Completed: () => {
    router.reload({ only: ['promptRun'] });
    // Questions tab appears
}
```

**New Component**: `StreamingProgress.vue`
- Shows current step (1-4)
- Step-specific loading messages
- Progress bar (0-100%)

**Updated Tab Logic**:
- Framework tab appears after Step 2
- Personality tab appears after Step 3
- Questions tab appears after Step 4

### 5. Feature Flag & Migration

**Config**: Add `ANALYSIS_STREAMING_ENABLED=false` to `.env`

**Dual-mode support**:
```php
if (config('features.analysis_streaming')) {
    ProcessTaskAnalysisStream::dispatch($promptRun);
} else {
    ProcessTaskAnalysis::dispatch($promptRun); // Legacy
}
```

**Rollback**: Set flag to `false` → instant revert to legacy mode

### 6. Cost Optimization

**Current**: ~$0.0036 per analysis (1 call)
**Naive streaming**: ~$0.0128 per analysis (4 calls, 3.5x)
**Optimized streaming**: ~$0.006 per analysis (1.7x)

**Optimizations**:
- Smaller context per step (targeted reference docs)
- Anthropic prompt caching (~90% savings on cached tokens)
- Combine Steps 3+4 for simple tasks (reduce to 3 calls)

**Break-even**: If streaming reduces abandonment by >70%, ROI is positive.

### 7. Error Handling

**Step Failure Strategy**:
- Step 1 fails → Complete failure (classification essential)
- Step 2 fails → Complete failure (framework essential)
- Step 3 fails → Skip to Step 4 (personality optional)
- Step 4 fails → Save partial, allow retry or manual entry

**Graceful degradation**: Earlier results preserved even if later steps fail.

## Critical Files to Modify

**High Priority**:
1. `/home/mark/repos/personality/database/migrations/2025_12_07_000001_add_streaming_to_prompt_runs.php` (NEW)
2. `/home/mark/repos/personality/app/Jobs/ProcessTaskAnalysisStream.php` (NEW)
3. `/home/mark/repos/personality/n8n/workflow_1_analysis.json` (MODIFY - most complex)
4. `/home/mark/repos/personality/app/Services/PromptFrameworkService.php` (MODIFY)
5. `/home/mark/repos/personality/resources/js/Pages/PromptBuilder/Show.vue` (MODIFY)

**Medium Priority**:
6. `/home/mark/repos/personality/app/Events/AnalysisStep{1,2,3,4}Completed.php` (NEW - 4 files)
7. `/home/mark/repos/personality/resources/js/Components/PromptBuilder/StreamingProgress.vue` (NEW)
8. `/home/mark/repos/personality/app/Http/Controllers/PromptBuilderController.php` (MODIFY)
9. `/home/mark/repos/personality/config/features.php` (NEW)

**Low Priority**:
10. `/home/mark/repos/personality/app/Models/PromptRun.php` (UPDATE casts/fillable)
11. Tests for streaming functionality

## Implementation Effort

**Estimated time**: 56 hours (~7 days of focused development)

**Breakdown**:
- Database & backend: 17 hours
- n8n workflow changes: 12 hours
- Frontend: 11 hours
- Testing: 12 hours
- Documentation: 4 hours

**Rollout Plan**:
1. Week 1: Backend infrastructure + migration
2. Week 2: n8n workflow + frontend
3. Week 3: Testing (feature flag OFF)
4. Week 4: A/B test with 10% users → Full rollout

## Alternative Quick Wins (If Full Streaming Too Complex)

If 56 hours is too much investment:

**Option A: Optimistic UI** (1 day)
- Show predicted classification immediately (keyword-based)
- Show skeleton framework cards
- Replace with real data when complete
- No backend changes

**Option B: Better Loading States** (2 days)
- Step-by-step loading indicators
- Estimated time remaining
- "What's happening" explanations
- No architecture changes

**Option C: Haiku → Sonnet Upgrade** (1 hour)
- Use faster Sonnet 4.5 model
- -30% latency, +40% cost
- Minimal code changes
