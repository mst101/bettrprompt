# Token Optimisation Implementation Guide

**Created:** 2025-11-28
**Purpose:** Step-by-step instructions for implementing each token optimisation
**Reference:** See `token-optimization-opportunities.md` for analysis and savings estimates

---

## Implementation Checklist

### Phase 1: Quick Wins

- [ ] **1.1** Condense System Prompts (Both Workflows)
- [ ] **1.2** Create Compressed Framework Taxonomy
- [ ] **1.3** Verify alternative_frameworks removal is complete

### Phase 2: API-Based Optimisations

- [ ] **2.1** Create Framework Template API Endpoints
- [ ] **2.2** Update Workflow 2 to Fetch Selected Template Only
- [ ] **2.3** Implement Smart Document Loading (Conditional Fetching)

### Phase 3: Advanced Optimisations

- [ ] **3.1** Split Personality Calibration (Type-Specific Documents)
- [ ] **3.2** Create Personality Calibration API Endpoints
- [ ] **3.3** Implement Multi-Personality Type Detection

---

## 1.1 Condense System Prompts

**Estimated Savings:** 6,000-10,000 tokens per request pair
**Time:** 1-2 hours
**Difficulty:** Low

### Workflow 1: Analysis & Questions

**File:** `/home/mark/repos/personality/n8n/workflow_1_analysis.json`
**Node:** "Prepare Prompt" (around line 53)

#### Current Issues:
1. Repetitive "CRITICAL INSTRUCTION" and "FORBIDDEN BEHAVIOR" sections
2. Verbose JSON schema examples
3. Redundant task-trait alignment instructions
4. Multiple restatements of the same concepts

#### Changes to Make:

**Before (lines with redundancy):**
```javascript
personalityInfo += `\n\n=== CRITICAL INSTRUCTION ===`;
personalityInfo += `\nThe ${traitDescriptions.length} traits listed above (${traitDescriptions.join(', ')}) are the user's ACTUAL personality.`;
personalityInfo += `\n\nYou MUST analyze these EXACT traits - NOT hypothetical ideal traits.`;
personalityInfo += `\n\nFORBIDDEN BEHAVIOR:`;
personalityInfo += `\n- DO NOT analyze traits the user doesn't have`;
personalityInfo += `\n- DO NOT infer opposite traits (e.g., if user has T, do NOT analyze F)`;
personalityInfo += `\n- DO NOT suggest what traits would be good for the task`;
personalityInfo += `\n- DO NOT use traits besides: ${traitDescriptions.join(', ')}`;
personalityInfo += `\n\nREQUIRED: Analyze ALL ${traitDescriptions.length} of the user's actual traits:`;
personalityInfo += `\n${traitDescriptions.map((t, i) => `${i + 1}. ${t}`).join('\n')}`;
personalityInfo += `\n\nFor EACH trait above, determine: AMPLIFIED, COUNTERBALANCED, or NEUTRAL.`;
```

**After (condensed):**
```javascript
personalityInfo += `\n\nAnalyze these ${traitDescriptions.length} user traits: ${traitDescriptions.join(', ')}`;
personalityInfo += `\nFor each, determine: AMPLIFIED (helps task), COUNTERBALANCED (opposes task), or NEUTRAL.`;
personalityInfo += `\nDo not analyze traits not listed above.`;
```

**Savings:** ~150-200 tokens

#### System Prompt Condensing:

**Section to condense:** Task-Trait Alignment Rules

**Before:**
```javascript
const systemPrompt = `You are an API that returns JSON...

## TASK-TRAIT ALIGNMENT RULES

CRITICAL: You MUST use the user's ACTUAL personality traits (provided in the personality data section). DO NOT make up or infer traits based on what would be ideal for the task.

Process:
1. Take each of the user's ACTUAL traits (e.g., "I (65%)", "N (64%)", "T (84%)", "P (57%)", "A (84%)")
2. For each trait, compare it against the task's cognitive requirements
3. Classify each of the user's traits as:
   - **AMPLIFIED** if the trait aligns with and supports the task requirements
   - **COUNTERBALANCED** if the trait opposes or creates blind spots for the task requirements
   - **NEUTRAL** if the trait is unrelated to the task requirements

DO NOT analyze hypothetical traits or what traits would be good for the task. ONLY analyze the specific traits the user actually has.

- **AMPLIFY** traits that are assets: The prompt will leverage the user's natural strengths
- **COUNTERBALANCE** traits that create blind spots: The prompt will inject explicit requirements the user might skip
- **NEUTRAL** for traits unrelated to the task: No adjustment needed

When a trait is marked for counterbalancing, specify the INJECTION - the explicit requirement that will be added to the prompt to cover the user's blind spot.
`;
```

**After:**
```javascript
const systemPrompt = `You are an API that returns JSON...

## TASK-TRAIT ALIGNMENT RULES

Analyze ONLY the user's actual personality traits provided (do not infer ideal traits):
- **AMPLIFY** aligned traits that help the task (leverage strengths)
- **COUNTERBALANCE** opposing traits that create blind spots (inject requirements)
- **NEUTRAL** for unrelated traits
`;
```

**Savings:** ~200-250 tokens

#### JSON Schema Condensing:

**Remove verbose examples from output format:**

**Before:**
```javascript
Return this exact JSON structure:

{
  "task_classification": {
    "primary_category": "CATEGORY_CODE",
    "secondary_category": null,
    "complexity": "simple | moderate | complex",
    "classification_reasoning": "Brief explanation",
    "content_type": "For CREATION_CONTENT only, e.g. customer_email"
  },
  "cognitive_requirements": {
    "primary": ["REQUIREMENT_CODE", "REQUIREMENT_CODE"],
    "secondary": ["REQUIREMENT_CODE"],
    "reasoning": "Why these requirements apply to this task"
  },
  // ... (many more lines of schema)
}
```

**After:**
```javascript
Return JSON with fields: task_classification, cognitive_requirements, selected_framework, alternative_frameworks, personality_tier, task_trait_alignment, personality_adjustments_preview, clarifying_questions, question_rationale.
```

**Savings:** ~400-500 tokens

**Total Workflow 1 Savings:** ~750-950 tokens

---

### Workflow 2: Generation

**File:** `/home/mark/repos/personality/n8n/workflow_2_generation.json`
**Node:** "Prepare Prompt" (around line 176)

#### Similar condensing as Workflow 1:

1. Reduce verbose system prompt instructions
2. Remove redundant schema examples
3. Condense user message formatting

**Estimated Savings:** ~800-1000 tokens

---

## 1.2 Create Compressed Framework Taxonomy

**Estimated Savings:** ~10,000 tokens per request
**Time:** 2-3 hours
**Difficulty:** Medium

### Steps:

1. **Create new file:** `/home/mark/repos/personality/resources/reference_documents/framework_taxonomy_compact.md`

2. **Extract essential information only:**
   - Framework name, code, components
   - Best-for description
   - When to use

3. **Remove from compact version:**
   - Weaknesses (not used in selection)
   - Time investment fields
   - Redundant mapping tables
   - Verbose examples

4. **Add new API endpoint:**

**File:** `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php`

```php
public function frameworkTaxonomyCompact(): JsonResponse
{
    $path = resource_path('reference_documents/framework_taxonomy_compact.md');

    if (!File::exists($path)) {
        return response()->json(['error' => 'Framework taxonomy not found'], 404);
    }

    return response()->json([
        'content' => File::get($path),
    ]);
}
```

**File:** `/home/mark/repos/personality/routes/api.php`

```php
Route::get('/reference/framework-taxonomy-compact', [ReferenceController::class, 'frameworkTaxonomyCompact']);
```

5. **Update Workflow 1:**

Change the fetch URL from:
```
/api/reference/framework-taxonomy
```

To:
```
/api/reference/framework-taxonomy-compact
```

### Compact Format Example:

**Original (verbose):**
```markdown
### COAST Framework

**Full Name:** Challenge, Objective, Actions, Strategy, Tactics

**Components:**
1. Challenge - Define the problem or goal
2. Objective - Specify what success looks like
3. Actions - List concrete steps
4. Strategy - Explain the overall approach
5. Tactics - Detail specific techniques

**Best For:**
- Complex planning tasks
- Strategic decision making
- Multi-step projects

**Strengths:**
- Highly structured
- Forces clear objective definition
- Separates strategy from tactics

**Weaknesses:**
- Can feel over-engineered for simple tasks
- Requires upfront thinking time
- May slow down quick iterations

**Time Investment:** Moderate (5-10 minutes to structure)
```

**Compact:**
```markdown
### COAST Framework

**Code:** COAST
**Components:** Challenge, Objective, Actions, Strategy, Tactics
**Best For:** Complex planning, strategic decisions, multi-step projects
```

---

## 2.1 Create Framework Template API Endpoints

**Estimated Savings:** ~17,000 tokens per request
**Time:** 3-4 hours
**Difficulty:** Medium-High

### Steps:

1. **Split prompt_templates.md into individual files:**

Create directory:
```bash
mkdir -p resources/reference_documents/prompt_templates
```

Split into files like:
```
resources/reference_documents/prompt_templates/COAST.md
resources/reference_documents/prompt_templates/BAB.md
resources/reference_documents/prompt_templates/PAS.md
... (one file per framework)
```

2. **Create API endpoint:**

**File:** `/home/mark/repos/personality/app/Http/Controllers/ReferenceController.php`

```php
public function promptTemplate(string $frameworkCode): JsonResponse
{
    $path = resource_path("reference_documents/prompt_templates/{$frameworkCode}.md");

    if (!File::exists($path)) {
        return response()->json(['error' => 'Template not found'], 404);
    }

    return response()->json([
        'framework_code' => $frameworkCode,
        'content' => File::get($path),
    ]);
}
```

**File:** `/home/mark/repos/personality/routes/api.php`

```php
Route::get('/reference/prompt-template/{frameworkCode}', [ReferenceController::class, 'promptTemplate']);
```

3. **Update Workflow 2:**

**File:** `/home/mark/repos/personality/n8n/workflow_2_generation.json`

Change "Fetch Prompt Templates" node to be dynamic:

**Current:**
```json
{
  "parameters": {
    "url": "={{ $env.LARAVEL_BASE_URL }}/api/reference/prompt-templates",
    "options": {}
  },
  "name": "Fetch Prompt Templates"
}
```

**New:**
```json
{
  "parameters": {
    "url": "={{ $env.LARAVEL_BASE_URL }}/api/reference/prompt-template/{{ $('Prepare Prompt').first().json.selectedFramework }}",
    "options": {}
  },
  "name": "Fetch Selected Template"
}
```

4. **Update Prepare Prompt node to expose selected framework:**

Add this line in the Prepare Prompt node:
```javascript
return [{
    json: {
        system: systemPrompt,
        messages: [...],
        originalInput: {...},
        selectedFramework: analysisData.selected_framework?.code || 'COAST'  // Add this
    },
}];
```

---

## 2.3 Implement Smart Document Loading

**Estimated Savings:** Variable (up to 23,000 tokens when no personality)
**Time:** 2-3 hours
**Difficulty:** Medium

### Steps:

1. **Update Workflow 1 - Conditional Personality Calibration:**

**File:** `/home/mark/repos/personality/n8n/workflow_1_analysis.json`

Add a new node "Check Personality Data" before fetching:

```javascript
{
  "parameters": {
    "jsCode": "const webhookData = $('Webhook Trigger').first().json.body || {};\nconst hasPersonality = !!webhookData.personality_type;\n\nreturn [{\n  json: {\n    hasPersonality: hasPersonality,\n    webhookData: webhookData\n  }\n}];"
  },
  "name": "Check Personality Data",
  "type": "n8n-nodes-base.code"
}
```

Then use an IF node to conditionally fetch Personality Calibration.

2. **Alternative: Use route filter in Prepare Prompt:**

Modify the fetch to include a check:

```javascript
const webhookData = $('Webhook Trigger').first().json.body || {};
const hasPersonality = !!webhookData.personality_type;

// Only fetch personality doc if needed
const personalityDoc = hasPersonality
  ? $('Fetch Personality Calibration').first().json
  : { content: 'Not needed - no personality data provided' };
```

---

## 3.1 Split Personality Calibration (Advanced)

**Estimated Savings:** ~18,000 tokens per request
**Time:** 5-6 hours
**Difficulty:** High

### Challenge:

The Personality Calibration document contains:
1. **Universal calibration rules** (applies to all types) - ~200 lines
2. **Task-Trait Alignment rules** (applies to all types) - ~300 lines
3. **Type-specific information** (minimal in current doc)

### Analysis:

The current document is mostly **universal rules**, not type-specific content. True type-specific splitting would require:

1. Restructuring the document to separate:
   - Core calibration framework (shared)
   - Type-specific trait tendencies
   - Type-specific counterbalance strategies

2. Creating 16 separate files with:
   - Shared core rules (repeated or referenced)
   - Specific trait manifestation for that type

### Recommended Approach:

**Instead of full splitting, create a hybrid approach:**

1. **Keep core rules in a shared file:** `personality_calibration_core.md` (~5K tokens)
2. **Create type-specific addendums:** `personality_calibration/{TYPE}.md` (~2K tokens each)
3. **Fetch both:** Core + Type-specific (total ~7K tokens vs current 23K)

### Implementation:

**File:** `resources/reference_documents/personality_calibration_core.md`
- Contains: Framework overview, Task-Trait Alignment rules, Cognitive requirements mapping

**Files:** `resources/reference_documents/personality_calibration/INTP-A.md`, etc.
- Contains: Type-specific trait tendencies, common blind spots, recommended counterbalances

**API Endpoint:**
```php
public function personalityCalibration(?string $type = null): JsonResponse
{
    $corePath = resource_path('reference_documents/personality_calibration_core.md');
    $core = File::exists($corePath) ? File::get($corePath) : '';

    $typeSpecific = '';
    if ($type) {
        $typePath = resource_path("reference_documents/personality_calibration/{$type}.md");
        $typeSpecific = File::exists($typePath) ? File::get($typePath) : '';
    }

    return response()->json([
        'content' => $core . "\n\n" . $typeSpecific,
    ]);
}
```

---

## 3.3 Multi-Personality Type Detection

**Purpose:** Extract mentioned personality types from task description and answers
**Difficulty:** High
**Time:** 4-5 hours

### Implementation:

**In Workflow 2 Prepare Prompt node:**

```javascript
// Extract mentioned personality types from task and answers
function extractPersonalityTypes(text) {
    const types = [];
    const typePattern = /\b([IE][NS][TF][JP]-[AT])\b/g;
    let match;
    while ((match = typePattern.exec(text)) !== null) {
        if (!types.includes(match[1])) {
            types.push(match[1]);
        }
    }
    return types;
}

const taskText = webhookData.original_task_description || '';
const answerText = questionAnswers.map(qa => qa.answer).join(' ');
const mentionedTypes = extractPersonalityTypes(taskText + ' ' + answerText);

// Include user's own type
if (webhookData.personality_type && !mentionedTypes.includes(webhookData.personality_type)) {
    mentionedTypes.unshift(webhookData.personality_type);
}

// Fetch calibration for all mentioned types
// (This would require modifying the API endpoint to accept multiple types)
```

---

## Testing Checklist

After each optimisation:

- [ ] Test with personality data provided
- [ ] Test without personality data
- [ ] Test with minimal vs full trait percentages
- [ ] Compare generated prompts before/after for quality
- [ ] Verify token usage in API response
- [ ] Check for any errors in workflow execution
- [ ] Validate JSON parsing still works

---

## Rollback Plan

For each change:

1. **Keep original files** with `.backup` or `.original` suffix
2. **Test in development first** before production
3. **Monitor error rates** after deployment
4. **Have quick rollback** by reverting file changes

---

## Monitoring

After implementation, track:

1. **Token usage per workflow** (before/after comparison)
2. **Cost per request** (daily/monthly averages)
3. **Prompt quality** (sample review)
4. **Error rates** (any increases)
5. **Response times** (check for performance impact)

---

**Document maintained by:** AI Buddy Development Team
**Last updated:** 2025-11-28
