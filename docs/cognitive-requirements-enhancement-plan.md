# Cognitive Requirements Enhancement Plan

## Overview

This document outlines the plan to better leverage the `cognitive_requirements` system in BettrPrompt. Currently, cognitive requirements are generated but primarily used only for personality trait alignment. This plan expands their use to improve framework selection, question selection, and overall system intelligence.

## Current State

### What Works
- Workflow 1 successfully identifies cognitive requirements based on task category
- Personality calibration system has comprehensive Task-Trait Alignment mappings
- Framework taxonomy documents which traits align/oppose each cognitive requirement

### Limitations
- **Framework selection** is purely category-based, not requirement-based
- **Question selection** doesn't consider which cognitive requirements need clarification
- **Naming inconsistency** between framework_taxonomy.md (codes) and personality_calibration.md (descriptive names)
- Cognitive requirements are generated but underutilised in decision-making

## Proposed Changes

### Priority 1: Cognitive Requirement-Based Framework Selection

**Objective:** Select frameworks based on how well they support the task's identified cognitive requirements, not just task category.

**Current Behaviour:**
```
Task: STRATEGY
→ Look up frameworks tagged for STRATEGY category
→ Select COAST/GOPA/SMART based on category match only
```

**Proposed Behaviour:**
```
Task: STRATEGY
Cognitive Requirements: VISION (primary), DETAIL (primary), SYNTHESIS (secondary)

→ Score each STRATEGY framework by requirement coverage:
   - COAST: Supports VISION ✓, DETAIL ✓, STRUCTURE ✓ → Score: High
   - GOPA: Supports DETAIL ✓, STRUCTURE ✓, DECISIVE ✓ → Score: Medium
   - SMART: Supports DETAIL ✓, DECISIVE ✓ → Score: Medium
→ Select COAST (highest score)
```

**Implementation:**

1. **Add framework-to-requirement mappings** in `framework_taxonomy.md`:
   ```markdown
   ### Framework Cognitive Support

   | Framework | Supported Requirements | Strength |
   |-----------|----------------------|----------|
   | COAST | VISION, DETAIL, STRUCTURE, DECISIVE | High |
   | GOPA | DETAIL, STRUCTURE, DECISIVE, OBJECTIVE | High |
   | CO-STAR | EMPATHY, WARM, STRUCTURE, PERSUASION | High |
   | ...
   ```

2. **Update Workflow 1 system prompt** to include framework scoring logic:
   - Single-pass: Update the framework selection step to score frameworks
   - Two-pass: Update Pass 1 (Prepare Prompt 1) to score frameworks

3. **Scoring algorithm** (to be included in system prompt):
   ```
   For each candidate framework:
     score = 0
     for each primary requirement:
       if framework supports this requirement with high strength: +3
       if framework supports this requirement with medium strength: +1.5
     for each secondary requirement:
       if framework supports this requirement: +1

   Select framework with highest score
   If tie, prefer lower complexity framework for simple tasks
   ```

### Priority 2: Fix Naming Consistency

**Objective:** Align terminology between framework_taxonomy.md and personality_calibration.md

**Current Inconsistency:**
- `framework_taxonomy.md`: Uses codes like `VISION`, `DETAIL`, `EMPATHY`
- `personality_calibration.md`: Uses "Big-Picture Strategic Vision", "Detailed Execution Planning", "Empathy & Stakeholder Awareness"

**Proposed Solution:**

**Option A (Recommended):** Use codes everywhere, add descriptive names as references
```markdown
## Cognitive Requirements

| Code | Name | Description | Aligned Traits | Opposed Traits |
|------|------|-------------|----------------|----------------|
| `VISION` | Big-Picture Strategic Vision | Future thinking, patterns, concepts | High N, High P | High S |
| `DETAIL` | Detailed Execution Planning | Step-by-step specificity, concrete actions | High S, High J | High N, High P |
```

**Option B:** Use full descriptive names everywhere
- More verbose but clearer for LLM comprehension
- Requires updating all references in both documents

**Recommendation:** Option A - codes are easier to work with programmatically, and we can keep descriptive names for human readability.

**Implementation:**
1. Update `personality_calibration.md` section headers to include codes
2. Update all references to use codes consistently
3. Keep descriptive names as subtitles for clarity

### Priority 3: Use Cognitive Requirements in Question Selection

**Objective:** Prioritise questions that help clarify the identified cognitive requirements

**Current Behaviour:**
```
Task: STRATEGY
→ Include all STRATEGY category questions
→ No prioritisation based on what needs clarification
```

**Proposed Behaviour:**
```
Task: STRATEGY
Cognitive Requirements: VISION, DETAIL, SYNTHESIS

→ Include universal questions (always)
→ Include STRATEGY category questions
→ PRIORITISE questions that clarify:
   - VISION needs (future state, big picture)
   - DETAIL needs (concrete steps, timelines)
   - SYNTHESIS needs (integrating multiple sources)
```

**Implementation:**

1. **Tag questions** in `question_bank.md` with cognitive requirements they address:
   ```markdown
   | ID | Question | Purpose | Cognitive Req | Priority |
   |----|----------|---------|---------------|----------|
   | S1 | What's your long-term vision for this? | Clarify future state | VISION | High |
   | S2 | What specific milestones do you need? | Get concrete details | DETAIL | High |
   | S3 | What information sources do you have? | Identify synthesis needs | SYNTHESIS | Medium |
   ```

2. **Update system prompt** (single-pass and two-pass) to:
   - Note which cognitive requirements are identified
   - Instruct Claude to prioritise questions that address those requirements
   - Still respect question quantity limits

3. **Two-pass specific**: Update Pass 2 (Prepare Prompt 2) to receive cognitive requirements and use them for question prioritisation

### Priority 4: Testing and Validation

**Objective:** Ensure the changes actually improve output quality

**Test Plan:**

1. **Create test suite** with 20 diverse task descriptions
2. **Classify each** using both old and new approaches
3. **Compare outputs**:
   - Is the selected framework more appropriate?
   - Are the questions more relevant?
   - Does personality alignment work correctly?

4. **Test cases should include**:
   - Tasks with clear requirement matches (e.g., RESEARCH → SYNTHESIS)
   - Tasks with requirement conflicts (e.g., needs both CREATIVE and STRUCTURE)
   - Tasks with personality data vs without
   - Edge cases (simple tasks, complex multi-category tasks)

## Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [ ] Create framework-to-requirement mapping table
- [ ] Fix naming consistency across documents
- [ ] Document current behaviour vs proposed behaviour
- [ ] Create test suite with baseline outputs

### Phase 2: Framework Selection (Week 2)
- [ ] Update `framework_taxonomy.md` with scoring mappings
- [ ] Update single-pass workflow_1 system prompt
- [ ] Update two-pass workflow_1 Pass 1 system prompt
- [ ] Test framework selection with test suite
- [ ] Validate improvements

### Phase 3: Question Selection (Week 3)
- [ ] Tag questions in `question_bank.md` with cognitive requirements
- [ ] Update single-pass workflow_1 system prompt for question prioritisation
- [ ] Update two-pass workflow_1 Pass 2 system prompt for question prioritisation
- [ ] Test question selection with test suite
- [ ] Validate improvements

### Phase 4: Integration & Testing (Week 4)
- [ ] End-to-end testing with real user scenarios
- [ ] Performance validation
- [ ] Documentation updates
- [ ] Deploy to production

## Workflow Changes Detail

### Single-Pass Workflow (workflow_1.json)

**Current Node: "Prepare Prompt"**

**Changes Required:**

1. **System Prompt Updates:**
   - Add framework scoring algorithm explanation
   - Add framework-to-requirement mapping reference
   - Update framework selection step to use scoring
   - Add question prioritisation based on cognitive requirements

2. **Example Changes:**
   ```javascript
   // Current
   const systemPrompt = `
   3. Select the most appropriate framework from the taxonomy
   `;

   // Proposed
   const systemPrompt = `
   3. Select the most appropriate framework:
      a. Identify candidate frameworks for task category
      b. Score each framework based on cognitive requirement coverage
      c. Select highest-scoring framework
      d. Explain scoring in rationale
   `;
   ```

### Two-Pass Workflow (workflow_1_two_pass.json)

**Changes Required in Two Nodes:**

#### Node 1: "Prepare Prompt 1" (Classification & Framework Selection)

**Changes:**
1. Add framework scoring algorithm to system prompt
2. Update framework selection step to use cognitive requirements
3. Ensure cognitive requirements are passed to Pass 2

**Example:**
```javascript
// Current: Simple category lookup
// Proposed: Requirement-based scoring

const systemPrompt = `
## YOUR TASK

1. Classify task (primary/secondary category)
2. Identify cognitive requirements
3. Select framework using requirement-based scoring:
   - List candidate frameworks for category
   - Score each by requirement coverage
   - Select highest score
   - Explain why this framework best supports identified requirements
`;
```

#### Node 2: "Prepare Prompt 2" (Question Generation)

**Changes:**
1. Receive cognitive requirements from Pass 1
2. Update question selection to prioritise based on requirements
3. Explain which requirements each question addresses

**Example:**
```javascript
// Current: Category-based filtering only
// Proposed: Requirement-aware prioritisation

const systemPrompt = `
## YOUR TASK

Select clarifying questions that:
1. Address universal needs (always)
2. Are relevant to task category
3. PRIORITISE questions that clarify the identified cognitive requirements:
   ${cognitive_requirements.primary.map(req =>
     `   - Questions addressing ${req}`
   ).join('\n')}
4. Explain which requirement each question helps clarify
`;
```

## Framework-to-Requirement Mapping (Initial Draft)

This will be added to `framework_taxonomy.md`:

### Structured Clarity Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| CRISPE | DETAIL, STRUCTURE, OBJECTIVE | ITERATIVE | High |
| RELIC | STRUCTURE, DETAIL, CREATIVE | EMPATHY | Medium |
| RTF | DETAIL, STRUCTURE | OBJECTIVE | Medium |
| CO-STAR | EMPATHY, WARM, STRUCTURE | PERSUASION | High |
| ICIO | DETAIL, OBJECTIVE, STRUCTURE | - | Medium |
| CRAFT | STRUCTURE, DETAIL, ITERATIVE | OBJECTIVE | High |

### Iterative Refinement Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| RACEF | ITERATIVE, CREATIVE, EXPLORE | STRUCTURE | High |
| Chain of Destiny | ITERATIVE, DETAIL, OBJECTIVE | RISK | High |

### Decision-Making Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| RICE | OBJECTIVE, DECISIVE, RISK | DETAIL | High |
| SMART | DETAIL, DECISIVE, STRUCTURE | OBJECTIVE | High |
| COAST | VISION, DETAIL, STRUCTURE, DECISIVE | RISK | High |
| Pros and Cons | OBJECTIVE, RISK, EXPLORE | DECISIVE | Medium |

### Analytical Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| Chain of Thought | OBJECTIVE, ABSTRACTION, DETAIL | STRUCTURE | High |
| Tree of Thought | OBJECTIVE, EXPLORE, ABSTRACTION | VISION | High |
| FOCUS | OBJECTIVE, DETAIL, DECISIVE | VISION | Medium |
| Six Thinking Hats | EXPLORE, OBJECTIVE, EMPATHY | CREATIVE, RISK | High |

### Creative & Innovation Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| SCAMPER | CREATIVE, EXPLORE | VISION | High |
| HMW | CREATIVE, EXPLORE, EMPATHY | - | Medium |
| Imagine | CREATIVE, VISION | - | Medium |
| What If | CREATIVE, EXPLORE | VISION | Medium |

### Educational Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| Bloom's Taxonomy | PEDAGOGY, STRUCTURE, ABSTRACTION | OBJECTIVE | High |
| ELI5 | PEDAGOGY, EMPATHY | STRUCTURE | High |
| Help Me Understand | PEDAGOGY, EMPATHY, STRUCTURE | - | Medium |
| TQA | PEDAGOGY, STRUCTURE | - | Medium |
| Socratic Method | PEDAGOGY, OBJECTIVE, EXPLORE | ABSTRACTION | High |

### Persuasion & Communication Frameworks

| Framework | Primary Requirements | Secondary Requirements | Strength |
|-----------|---------------------|----------------------|----------|
| BAB | PERSUASION, EMPATHY, STRUCTURE | WARM | High |
| CAR | PERSUASION, STRUCTURE, OBJECTIVE | - | Medium |
| TRACE | PERSUASION, OBJECTIVE, RISK | EMPATHY | High |

*(Full mapping to be completed for all 70+ frameworks)*

## Success Metrics

### Quantitative
- Framework selection accuracy: % of times selected framework matches task requirements
- Question relevance: % of questions that address identified cognitive requirements
- User satisfaction: Rating of final prompt quality

### Qualitative
- Does the selected framework feel more appropriate than category-only selection?
- Do the questions help clarify the right aspects of the task?
- Is personality alignment working correctly with new requirements?

## Risks & Mitigation

### Risk 1: Increased Prompt Complexity
**Mitigation:** Keep scoring algorithm simple, test with various model temperatures

### Risk 2: Framework Mapping Incompleteness
**Mitigation:** Start with most common 20 frameworks, expand iteratively

### Risk 3: LLM May Ignore Scoring Instructions
**Mitigation:** Test extensively, consider providing explicit examples in system prompt

### Risk 4: Performance Regression
**Mitigation:** Maintain test suite, compare outputs before/after, have rollback plan

## Open Questions

1. Should we weight primary requirements higher than secondary in scoring? (Proposal: Yes, 3x vs 1x)
2. Should framework complexity be a tiebreaker? (Proposal: Yes, simpler for simple tasks)
3. How many questions should address each cognitive requirement? (Proposal: At least 1 per primary requirement)
4. Should we auto-generate framework mappings from descriptions? (Proposal: No, manual curation is better)

## Next Steps

1. **Review this plan** with team
2. **Validate approach** with small pilot test
3. **Begin Phase 1** - foundation work
4. **Iterate** based on learnings

---

**Document Status:** Draft
**Last Updated:** 2025-12-29
**Owner:** Development Team
**Review Date:** TBD
