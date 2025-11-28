# Token Optimisation Opportunities

**Analysis Date:** 2025-11-28
**Current Status:** Identified opportunities, not yet implemented
**Current Model:** Claude Haiku 4.5 for both workflows
**Potential Savings:** 40-65% token reduction (~$40-66/month at 100 requests/day)

---

## Executive Summary

Analysis of the n8n workflows revealed significant token optimisation opportunities through:
- Eliminating duplicate reference document fetches (23K tokens/request)
- Sending only required framework templates instead of all templates (17K tokens/request)
- Condensing verbose system prompts (6K tokens/request)
- Removing unused data from payloads (0.5-1K tokens/request)

**Total potential savings: 46,500-79,500 tokens per request pair (40-65% reduction)**

---

## Current Usage Summary

### Token Usage (100 requests/day)

| Workflow | Model | Input Tokens/Req | Output Tokens/Req | Daily Input | Daily Output |
|----------|-------|------------------|-------------------|-------------|--------------|
| Workflow 1 (Analysis) | Haiku 4.5 | 90,000 | 1,200 | 9,000,000 | 120,000 |
| Workflow 2 (Generation) | Haiku 4.5 | 75,000 | 1,500 | 7,500,000 | 150,000 |
| **Total** | | **165,000** | **2,700** | **16,500,000** | **270,000** |

### Cost Summary (100 requests/day)

**Pricing (Haiku 4.5):**
- Input: $0.80 per 1M tokens
- Output: $4.00 per 1M tokens

| Period | Input Cost | Output Cost | Total Cost |
|--------|------------|-------------|------------|
| **Daily** | $13.20 | $1.08 | **$14.28** |
| **Monthly** | $396.00 | $32.40 | **$428.40** |

---

## Optimised Usage Projections

### After Phase 1 (Quick Wins)

| Workflow | Input Tokens/Req | Output Tokens/Req | Daily Input | Daily Output |
|----------|------------------|-------------------|-------------|--------------|
| Workflow 1 | 67,000 | 1,200 | 6,700,000 | 120,000 |
| Workflow 2 | 70,000 | 1,500 | 7,000,000 | 150,000 |
| **Total** | **137,000** | **2,700** | **13,700,000** | **270,000** |

**Cost:** $11.08/day | **$332.40/month** | **Savings: $96/month (22%)**

### After Phase 2 (API Changes)

| Workflow | Input Tokens/Req | Output Tokens/Req | Daily Input | Daily Output |
|----------|------------------|-------------------|-------------|--------------|
| Workflow 1 | 62,000 | 1,200 | 6,200,000 | 120,000 |
| Workflow 2 | 53,000 | 1,500 | 5,300,000 | 150,000 |
| **Total** | **115,000** | **2,700** | **11,500,000** | **270,000** |

**Cost:** $9.28/day | **$278.40/month** | **Savings: $150/month (35%)**

### After Phase 3 (Full Optimisation)

| Workflow | Input Tokens/Req | Output Tokens/Req | Daily Input | Daily Output |
|----------|------------------|-------------------|-------------|--------------|
| Workflow 1 | 45,000 | 1,200 | 4,500,000 | 120,000 |
| Workflow 2 | 40,000 | 1,500 | 4,000,000 | 150,000 |
| **Total** | **85,000** | **2,700** | **8,500,000** | **270,000** |

**Cost:** $6.88/day | **$206.40/month** | **Savings: $222/month (52%)**

---

## Current Token Usage Analysis

### Workflow 1: Analysis & Questions (workflow_1_analysis.json)

**Model:** Claude Haiku 4.5 ($0.80 per 1M input tokens, $4.00 per 1M output tokens)

**Reference Documents Fetched:**
- Framework Taxonomy: ~25,000 tokens
- Personality Calibration: ~23,000 tokens
- Question Bank: ~13,000 tokens
- **Total: ~61,000 tokens**

**System Prompt:** ~5,000 tokens (verbose with repetitive instructions)

**Estimated Total Input:** ~90,000 tokens per request

**Issues Identified:**
1. All three reference documents fetched every time, even when personality data not provided
2. Excessive repetition in system prompt about trait analysis rules
3. Verbose JSON schema examples included when model already knows structure
4. "CRITICAL INSTRUCTION" and "FORBIDDEN BEHAVIOR" sections repeat same concepts multiple times

---

### Workflow 2: Generation (workflow_2_generation.json)

**Model:** Claude Haiku 4.5 ($0.80 per 1M input tokens, $4.00 per 1M output tokens)

**Reference Documents Fetched:**
- Prompt Templates: ~20,000 tokens (all 50+ framework templates)
- Personality Calibration: ~23,000 tokens (duplicate from Workflow 1)
- **Total: ~43,000 tokens**

**Analysis Payload from Workflow 1:** ~2,000-3,000 tokens
- Includes: task_classification, cognitive_requirements, selected_framework, alternative_frameworks, personality_tier, task_trait_alignment

**System Prompt:** ~5,000 tokens (verbose with repetitive instructions)

**Estimated Total Input:** ~75,000 tokens per request

**Issues Identified:**
1. **Personality Calibration sent twice** (once in each workflow) - pure duplication
2. All framework templates sent when only 1 is actually used
3. `alternative_frameworks` included in payload but never used in generation
4. Verbose user message construction with JSON.stringify formatting adds extra tokens
5. Always uses expensive Sonnet model even for simple cases

---

## Reference Documents Analysis

### Framework Taxonomy (~25,000 tokens)
- Contains exhaustive list of 50+ frameworks with descriptions
- Many frameworks rarely used (e.g., specialised image generation frameworks)
- Includes verbose "weaknesses" and "time investment" fields not used in selection
- Redundant mapping tables that can be inferred

**Optimisation opportunity:** Create compact version with only essential fields

---

### Personality Calibration (~23,000 tokens)
- Extremely detailed trait-by-trait breakdown for all 16 personality types
- Contains many redundant examples
- Same information represented multiple ways (trait influence matrices, composite profiles)
- **Most users only need their specific personality type, not all 16 types**

**Optimisation opportunity:** Create type-specific endpoints or only send when needed

---

### Question Bank (~13,000 tokens)
- Comprehensive question pool organised by category
- Only 3-6 questions typically selected from each category
- Contains questions for all task types

**Optimisation opportunity:** Send only relevant category questions based on task classification

---

### Prompt Templates (~20,000 tokens)
- Contains templates for all 50+ frameworks
- Only 1 template actually used per request
- **Sending 19,000 tokens of unused templates every time**

**Optimisation opportunity:** Create framework-specific endpoints to fetch only selected template

---

## Optimisation Recommendations

### Phase 1: Quick Wins (Week 1)

**Estimated Savings: ~25,000 tokens per request**

#### 1. Remove Personality Calibration from Workflow 1 ✓ HIGH PRIORITY
- **Current:** 23,000 tokens sent in both workflows (46,000 total)
- **Fix:** Only send in Workflow 2 where it's actually used for prompt construction
- **Savings:** 23,000 tokens per request in Workflow 1
- **Effort:** Low - remove HTTP request node from workflow_1_analysis.json (lines 34-46)
- **Files to modify:**
  - `/home/mark/repos/personality/n8n/workflow_1_analysis.json`

#### 2. Remove `alternative_frameworks` from Workflow 2 Payload ✓
- **Current:** Sent but never used in generation
- **Savings:** 500-1,000 tokens per request
- **Effort:** Very low - just delete from payload construction
- **Files to modify:**
  - `/home/mark/repos/personality/app/Services/PromptFrameworkService.php` (line 80)

#### 3. Condense System Prompts - Remove Redundancy ✓
- **Current:** Excessive "CRITICAL", "FORBIDDEN", "MUST" repetition
- **Example:** Lines 162-184 in workflow_1_analysis.json repeat trait analysis rules
- **Savings:** ~3,000-5,000 tokens per workflow (6,000-10,000 total)
- **Effort:** Low - edit JavaScript code blocks in Prepare Prompt nodes
- **Files to modify:**
  - `/home/mark/repos/personality/n8n/workflow_1_analysis.json` (lines 52-53, Prepare Prompt node)
  - `/home/mark/repos/personality/n8n/workflow_2_generation.json` (lines 176-256, Prepare Prompt node)

**Specific changes:**
- Remove duplicate "Perform full Task-Trait Alignment analysis" sections
- Condense "CRITICAL INSTRUCTION" and "FORBIDDEN BEHAVIOR" to single concise statement
- Remove JSON schema examples (model already knows structure)
- Simplify verbose output format descriptions

---

### Phase 2: API Changes (Week 2-3)

**Estimated Additional Savings: ~20,000 tokens per request**

#### 4. Send Only Selected Framework Template ✓ HIGH IMPACT
- **Current:** All framework templates sent (~20,000 tokens)
- **Fix:** After Workflow 1 selects framework, only fetch that specific template
- **Savings:** ~17,000 tokens per request (reduce from 20K to ~3K)
- **Effort:** Medium - requires API endpoint changes

**Implementation approach:**
1. Create separate API endpoints:
   - `/api/reference/prompt-template/{framework_code}`
   - Example: `/api/reference/prompt-template/COAST`, `/api/reference/prompt-template/BAB`
2. Update Workflow 2 to:
   - Extract selected framework code from analysis_data
   - Fetch only that framework's template
3. Split `prompt_templates.md` into individual framework files

**Files to modify:**
- `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php` - Add new endpoint
- `/home/mark/repos/personality/routes/api.php` - Add route
- `/home/mark/repos/personality/n8n/workflow_2_generation.json` - Update fetch logic
- `/home/mark/repos/personality/resources/reference_documents/prompt_templates.md` - Split into individual files

#### 5. Smart Document Loading (Conditional Fetching) ✓
- **Current:** All documents fetched regardless of need
- **Fix:**
  - If no personality data provided, don't fetch Personality Calibration
  - Only fetch question categories relevant to task classification
- **Savings:** Variable, but up to 23,000 tokens when no personality provided
- **Effort:** Medium - requires conditional fetching logic

**Implementation approach:**
1. In Workflow 1, check if `personality_type` exists in webhook data
2. If not, skip Personality Calibration fetch
3. For Question Bank, pass task classification to API and filter server-side

**Files to modify:**
- `/home/mark/repos/personality/n8n/workflow_1_analysis.json` - Add conditional logic
- `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php` - Add filtering

---

### Phase 3: Document Optimisation (Week 4+)

**Estimated Additional Savings: ~15,000 tokens per request**

#### 6. Compress Framework Taxonomy ✓
- **Current:** Verbose descriptions for 50+ frameworks
- **Fix:** Create "compact" version with:
  - Only framework names, codes, best-for descriptions
  - Remove "weaknesses" and "time investment" fields (not used in selection)
  - Remove redundant mapping tables (can be inferred)
- **Savings:** ~10,000 tokens
- **Effort:** Medium - create new reference document version

**Files to create/modify:**
- `/home/mark/repos/personality/resources/reference_documents/framework_taxonomy_compact.md`
- `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php` - Add endpoint

#### 7. Personality Calibration - Send Only Relevant Type ✓ HIGH IMPACT
- **Current:** All 16 personality types' calibration rules sent (23,000 tokens)
- **Fix:** If user has personality data, send only their type's calibration
- **Savings:** ~18,000 tokens (from 23K to ~5K)
- **Effort:** High - significant API and reference doc restructuring

**Implementation approach:**
1. Create API endpoint: `/api/reference/personality-calibration/{type}`
   - Example: `/api/reference/personality-calibration/INTP-A`
2. Split personality_calibration.md into 16 separate files
3. Update Workflow 2 to fetch only relevant type

**Files to modify:**
- `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php`
- `/home/mark/repos/personality/routes/api.php`
- `/home/mark/repos/personality/resources/reference_documents/personality_calibration.md` - Split into 16 files
- `/home/mark/repos/personality/n8n/workflow_2_generation.json`

#### 8. Use Haiku for Workflow 2 Simple Cases ✓ COST OPTIMISATION
- **Current:** Always uses Claude Haiku 4.5 (same pricing as Workflow 1))
- **Fix:** Use Haiku ($0.25 per 1M tokens) when:
  - No personality data provided (no counterbalancing needed)
  - Simple task complexity
  - No heavy counterbalancing (0-1 traits)
- **Savings:** ~80% cost reduction on those requests (not token reduction, but cost reduction)
- **Effort:** Medium - add conditional model selection logic

**Implementation approach:**
1. Add model selection logic in Workflow 2 Prepare Prompt node
2. Check `personality_tier` and `task_trait_alignment.counterbalanced` length
3. Use Haiku if tier is "none" or counterbalanced array is empty/small

**Files to modify:**
- `/home/mark/repos/personality/n8n/workflow_2_generation.json` - Update model selection in API call

---

## Estimated Total Savings

### Per Request (Both Workflows)

**Conservative Optimisations (Phase 1 only):**
- Remove Personality Cal duplication: 23,000 tokens
- Condense system prompts: 6,000 tokens
- Remove alternative frameworks: 500 tokens
- **Total: 29,500 tokens saved (~40% reduction)**

**Phase 1 + Phase 2:**
- All Phase 1 optimisations: 29,500 tokens
- Selected framework template only: 17,000 tokens
- Smart document loading (average): 5,000 tokens
- **Total: 51,500 tokens saved (~55% reduction)**

**All Phases (Aggressive Optimisations):**
- All Phase 1 + 2 optimisations: 51,500 tokens
- Compress Framework Taxonomy: 10,000 tokens
- Personality Cal - relevant type only: 18,000 tokens
- **Total: 79,500 tokens saved (~65% reduction)**

---

## Cost Impact Analysis

### Current Costs (Detailed Breakdown)

See **Current Usage Summary** section above for current token usage and costs.

**Current:** $14.28/day | $428.40/month

---

## Implementation Priority Summary

### Priority 1 (Immediate) - Phase 1
**Time estimate:** 2-4 hours
**Savings:** $96/month (22% reduction)
**New cost:** $332.40/month (was $428.40)

1. Remove Personality Calibration from Workflow 1 ✓
2. Remove alternative_frameworks from Workflow 2 payload ✓
3. Condense system prompts ✓

**Risk:** Very low - simple removals and text edits

---

### Priority 2 (Near-term) - Phase 2
**Time estimate:** 1-2 days
**Savings:** Additional $54/month (13% more, 35% total)
**New cost:** $278.40/month (was $428.40)

4. Create framework template endpoints ✓
5. Modify Workflow 2 to fetch only selected template ✓
6. Add smart document loading ✓

**Risk:** Low-medium - requires API changes and testing

---

### Priority 3 (Future) - Phase 3
**Time estimate:** 3-5 days
**Savings:** Additional $72/month (17% more, 52% total)
**New cost:** $206.40/month (was $428.40)

7. Create compressed Framework Taxonomy ✓
8. Create personality-specific calibration endpoints ✓

**Risk:** Medium - significant restructuring of reference documents

---

## Technical Notes

### System Prompt Condensation Examples

**Current (verbose):**
```javascript
personalityInfo += `\n\n=== CRITICAL INSTRUCTION ===`;
personalityInfo += `\nThe ${traitDescriptions.length} traits listed above are the user's ACTUAL personality.`;
personalityInfo += `\n\nYou MUST analyse these EXACT traits - NOT hypothetical ideal traits.`;
personalityInfo += `\n\nFORBIDDEN BEHAVIOR:`;
personalityInfo += `\n- DO NOT analyse traits the user doesn't have`;
personalityInfo += `\n- DO NOT infer opposite traits`;
```

**Optimised (concise):**
```javascript
personalityInfo += `\n\nIMPORTANT: Analyse ONLY these ${traitDescriptions.length} user traits: ${traitDescriptions.join(', ')}`;
personalityInfo += `\nDo not infer or analyse traits not listed.`;
```

**Tokens saved:** ~100 tokens per instruction block

---

### Framework Template Endpoint Structure

**Proposed API structure:**
```
GET /api/reference/prompt-template/{framework_code}

Examples:
- /api/reference/prompt-template/COAST
- /api/reference/prompt-template/BAB
- /api/reference/prompt-template/PAS

Response:
{
  "framework": {
    "name": "COAST Framework",
    "code": "COAST",
    "template": "..."
  }
}
```

---

### Personality Calibration Split Structure

**Current:** Single 23,000 token file with all 16 types

**Proposed:** 16 separate files:
```
/resources/reference_documents/personality_calibration/
  - INTJ-A.md
  - INTJ-T.md
  - INTP-A.md
  - INTP-T.md
  - ... (32 files total for all variants)
```

**API endpoint:**
```
GET /api/reference/personality-calibration/{type}

Example: /api/reference/personality-calibration/INTP-A
```

---

## Testing Considerations

### Phase 1 Testing
- Verify Workflow 1 still completes successfully without Personality Calibration fetch
- Confirm Workflow 2 still receives all needed data despite payload changes
- Check that condensed prompts still produce quality results

### Phase 2 Testing
- Test framework template fetching for all supported frameworks
- Verify smart document loading works with and without personality data
- Ensure no regressions in prompt quality

### Phase 3 Testing
- Compare compressed taxonomy selection accuracy vs original
- Test personality-specific calibration for all 16 types
- Validate Haiku model selection logic and quality threshold

---

## Monitoring Recommendations

After implementing optimisations, monitor:

1. **Token usage metrics**
   - Track input/output tokens per workflow
   - Compare before/after averages
   - Identify any unexpected increases

2. **Prompt quality**
   - Sample generated prompts for quality assessment
   - User satisfaction with optimised prompts
   - Framework selection accuracy

3. **Error rates**
   - Any increase in workflow failures
   - API endpoint availability
   - Model timeout issues

4. **Cost metrics**
   - Daily/monthly API costs
   - Cost per request
   - Return on optimisation investment

---

## Future Opportunities (Beyond Current Analysis)

### 1. Prompt Caching
Claude API supports prompt caching for frequently used content. Reference documents could be cached to reduce costs further.

### 2. Framework Selection Cache
For repeat task types, cache framework selections to potentially skip Workflow 1 entirely.

### 3. Progressive Enhancement
Start with minimal context, only fetch additional reference materials if initial analysis is uncertain.

### 4. User-Specific Templates
For frequent users, create personalised prompt templates that require less context.

---

## Conclusion

The analysis identified significant token optimisation opportunities across three implementation phases:

- **Phase 1:** Quick wins with minimal risk (8% cost reduction)
- **Phase 2:** API improvements with moderate effort (29% total reduction)
- **Phase 3:** Document restructuring with larger effort (47-60% total reduction)

**Recommended approach:** Implement Phase 1 immediately, assess results, then proceed with Phase 2 and 3 based on ROI and available development time.

**Total potential savings:** Up to $446/month at current usage levels (60% reduction from $741/month to $295/month)

---

**Document maintained by:** AI Buddy Development Team
**Last updated:** 2025-11-28
**Status:** Analysis complete, implementation pending
