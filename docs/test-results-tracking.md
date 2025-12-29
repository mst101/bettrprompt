# Cognitive Requirements Enhancement - Test Results Tracking

**Test Date:** _____________________
**Tested By:** _____________________
**System Version:** Single-Pass ☐ / Two-Pass ☐
**Workflow Variant:** Old ☐ / New ☐

---

## Summary Dashboard

| Category | Tests | Passed | Failed | Pass Rate |
|----------|-------|--------|--------|-----------|
| Clear Requirement Matches | 3 | | | |
| Requirement Conflicts | 2 | | | |
| Personality Integration | 3 | | | |
| Edge Cases | 3 | | | |
| Framework-Specific | 4 | | | |
| **TOTAL** | **15** | | | |

---

## Detailed Test Results

### Category 1: Clear Requirement Matches

---

#### Test 1.1: Strategy Task with VISION + DETAIL

**Task Description:**
```
I need to develop a 3-year growth strategy for my SaaS startup. We're currently at 500 customers and want to reach 10,000. I need to identify key milestones, resource requirements, and potential obstacles.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `VISION`, `DETAIL`, `DECISIVE` | Secondary: `RISK`, `STRUCTURE`
- Framework: `COAST` (score: 9 points)
- Questions: Cover VISION, DETAIL, RISK

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | VISION, DETAIL, DECISIVE | | ☐ |
| Secondary Cognitive Reqs | RISK, STRUCTURE | | ☐ |
| Framework Selected | COAST | | ☐ |
| Framework Score Mentioned | Yes, with breakdown | | ☐ |
| Questions Cover VISION | ≥1 question | | ☐ |
| Questions Cover DETAIL | ≥1 question | | ☐ |
| Questions Cover RISK | ≥1 question | | ☐ |
| Purposes Mention Reqs | Yes | | ☐ |

**Framework Scoring Rationale (from output):**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions with their purposes here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
[Any observations, deviations, or insights]
```

---

#### Test 1.2: Research Task with SYNTHESIS + OBJECTIVE

**Task Description:**
```
Help me research the current state of AI coding assistants. I need to understand the different approaches (fine-tuned models, RAG, prompt engineering), compare their strengths/weaknesses, and synthesise findings into a coherent overview.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `SYNTHESIS`, `OBJECTIVE`, `EXPLORE` | Secondary: `STRUCTURE`, `DETAIL`
- Framework: `Tree of Thought` or `Chain of Thought`
- Questions: Cover SYNTHESIS, OBJECTIVE, EXPLORE

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | SYNTHESIS, OBJECTIVE, EXPLORE | | ☐ |
| Secondary Cognitive Reqs | STRUCTURE, DETAIL | | ☐ |
| Framework Selected | Tree of Thought / Chain of Thought | | ☐ |
| Framework Score Mentioned | Yes, with breakdown | | ☐ |
| Questions Cover SYNTHESIS | ≥1 question | | ☐ |
| Questions Cover OBJECTIVE | ≥1 question | | ☐ |
| Questions Cover EXPLORE | ≥1 question | | ☐ |
| Purposes Mention Reqs | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 1.3: Learning Task with PEDAGOGY + STRUCTURE

**Task Description:**
```
I'm trying to understand how database indexing works. I know the basics of SQL but I'm confused about when to use clustered vs non-clustered indexes, and how they actually improve query performance. Explain it to me like I'm an intermediate developer.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `PEDAGOGY`, `STRUCTURE`, `DETAIL` | Secondary: `ABSTRACTION`
- Framework: `Bloom's Taxonomy` (should beat ELI5 due to STRUCTURE coverage)
- Questions: Cover PEDAGOGY, STRUCTURE, DETAIL

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | PEDAGOGY, STRUCTURE, DETAIL | | ☐ |
| Secondary Cognitive Reqs | ABSTRACTION | | ☐ |
| Framework Selected | Bloom's Taxonomy / ELI5 | | ☐ |
| Framework Score Mentioned | Yes, with breakdown | | ☐ |
| Questions Cover PEDAGOGY | ≥1 question | | ☐ |
| Questions Cover STRUCTURE | ≥1 question | | ☐ |
| Questions Cover DETAIL | ≥1 question | | ☐ |
| Purposes Mention Reqs | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

### Category 2: Requirement Conflicts

---

#### Test 2.1: Creative Exploration vs Structured Execution

**Task Description:**
```
I need to brainstorm innovative features for our project management tool, but I also need a concrete roadmap with specific deliverables and timelines for the next quarter.
```

**Expected Outcomes:**
- Cognitive Requirements: `CREATIVE`, `EXPLORE` + `DETAIL`, `STRUCTURE` (conflicting)
- Framework: Either dual framework suggestion or hybrid like `RACEF`
- Questions: Address both creative and structured aspects

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Recognises Conflict | Yes (in reasoning) | | ☐ |
| Primary Cognitive Reqs | CREATIVE, EXPLORE, DETAIL, STRUCTURE | | ☐ |
| Framework Approach | Dual/Hybrid | | ☐ |
| Questions Cover CREATIVE | ≥1 question | | ☐ |
| Questions Cover STRUCTURE | ≥1 question | | ☐ |
| Rationale Explains Conflict | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Resolution Approach:**
```
[How did system handle the conflict?]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 2.2: Empathy vs Objectivity Balance

**Task Description:**
```
I need to give performance feedback to a struggling team member. The feedback needs to be honest and data-driven, but also supportive and motivating. They're sensitive to criticism.
```

**Expected Outcomes:**
- Cognitive Requirements: `EMPATHY`, `WARM` + `OBJECTIVE`, `DETAIL` (balancing act)
- Framework: `BAB` or `CO-STAR`
- Questions: Address both emotional and factual dimensions

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | EMPATHY, WARM, OBJECTIVE, DETAIL | | ☐ |
| Framework Selected | BAB / CO-STAR | | ☐ |
| Questions Cover EMPATHY | ≥1 question | | ☐ |
| Questions Cover OBJECTIVE | ≥1 question | | ☐ |
| Rationale Mentions Balance | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

### Category 3: Personality Integration

---

#### Test 3.1: High N + High P (Creative Explorer) - Ideation Task

**Task Description:**
```
Generate innovative product ideas for sustainable transportation in urban environments.
```

**Personality:**
```
Type: ENTP-A
Traits: E:65%, N:78%, T:62%, P:71%, A:68%
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `CREATIVE`, `EXPLORE`, `VISION`
- Framework: `SCAMPER`
- Amplified: High N (78%) for CREATIVE/VISION, High P (71%) for EXPLORE
- Counterbalanced: None
- Question Adjustments: +1 question (High P)

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | CREATIVE, EXPLORE, VISION | | ☐ |
| Framework Selected | SCAMPER | | ☐ |
| Personality Tier | full | | ☐ |
| Amplified Traits | N (CREATIVE/VISION), P (EXPLORE) | | ☐ |
| Counterbalanced Traits | None | | ☐ |
| Question Count | Baseline +1 | | ☐ |

**Task-Trait Alignment:**
```
Amplified:
[Paste from output]

Counterbalanced:
[Paste from output]

Neutral:
[Paste from output]
```

**Personality Adjustments Preview:**
```
[Paste from output]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 3.2: High S + High J (Practical Organiser) - Strategy Task ⚠️ CRITICAL TEST

**Task Description:**
```
Develop a 3-year growth strategy for my SaaS startup.
```

**Personality:**
```
Type: ISTJ-T
Traits: I:62%, S:73%, T:65%, J:76%, T:71%
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `VISION`, `DETAIL`, `DECISIVE`
- Framework: `COAST`
- Amplified: High S (73%) for DETAIL, High J (76%) for STRUCTURE/DECISIVE, High T-identity (71%) for RISK
- **Counterbalanced**: High S (73%) **opposed to VISION** ← Critical test
- Expected Injection: "Step back and consider broader implications", "What's your 3-5 year vision?"
- Question Adjustments: -1 (High J) +1 (High T-identity) = Baseline

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | VISION, DETAIL, DECISIVE | | ☐ |
| Framework Selected | COAST | | ☐ |
| Personality Tier | full | | ☐ |
| Amplified: High S for DETAIL | Yes | | ☐ |
| Amplified: High J for STRUCTURE/DECISIVE | Yes | | ☐ |
| **Counterbalanced: High S for VISION** | **YES (Critical)** | | ☐ |
| Injection Mentions Vision/Big-Picture | Yes | | ☐ |
| Question Count | Baseline | | ☐ |

**Task-Trait Alignment:**
```
Amplified:
[Paste from output]

Counterbalanced: (Should include S opposed to VISION)
[Paste from output]

Neutral:
[Paste from output]
```

**Personality Adjustments Preview:**
```
[Should show counterbalancing for VISION requirement]
```

**Result:** PASS ☐ / FAIL ☐

**Critical Test Status:** ☐ Counterbalancing works correctly / ☐ FAILED - no counterbalancing

**Notes:**
```
```

---

#### Test 3.3: High F + High A (Confident Feeler) - Persuasion Task

**Task Description:**
```
Write a proposal to convince investors to fund our social impact startup.
```

**Personality:**
```
Type: ENFP-A
Traits: E:71%, N:68%, F:79%, P:64%, A:77%
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `PERSUASION`, `EMPATHY`, `VISION`
- Framework: `BAB`
- Amplified: High F (79%) for EMPATHY/PERSUASION, High N (68%) for VISION
- Counterbalanced: High F (79%) opposed to OBJECTIVE (balance emotion with data), High A (77%) for RISK awareness
- Expected Injections: "Support emotional appeals with data", "Address potential concerns and risks"
- Question Adjustments: -1 (High A)

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | PERSUASION, EMPATHY, VISION | | ☐ |
| Framework Selected | BAB | | ☐ |
| Personality Tier | full | | ☐ |
| Amplified: High F for EMPATHY/PERSUASION | Yes | | ☐ |
| Amplified: High N for VISION | Yes | | ☐ |
| Counterbalanced: High F (add objectivity) | Yes | | ☐ |
| Counterbalanced: High A (add risk awareness) | Yes | | ☐ |
| Question Count | Baseline -1 | | ☐ |

**Task-Trait Alignment:**
```
Amplified:
[Paste from output]

Counterbalanced: (Should include F for objectivity, A for risk)
[Paste from output]

Neutral:
[Paste from output]
```

**Personality Adjustments Preview:**
```
[Should show counterbalancing for objectivity and risk]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

### Category 4: Edge Cases

---

#### Test 4.1: Simple Task - Should Avoid Over-Engineering

**Task Description:**
```
Explain what a REST API is in simple terms.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `PEDAGOGY`, `STRUCTURE` only (no complex requirements)
- Framework: `ELI5` (simple framework for simple task)
- Questions: 2-3 only (minimal for simple task)
- Complexity: "simple"

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Primary Cognitive Reqs | PEDAGOGY, STRUCTURE | | ☐ |
| No Over-Complex Reqs | No SYNTHESIS, ABSTRACTION, etc. | | ☐ |
| Framework Selected | ELI5 or similar simple framework | | ☐ |
| Complexity Level | simple | | ☐ |
| Question Count | 2-4 questions max | | ☐ |
| No Over-Engineering | Simple, appropriate approach | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated (should be minimal):**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 4.2: Multi-Category Task

**Task Description:**
```
I need to research the current market landscape for AI tools, develop a positioning strategy for our product, and create a go-to-market plan with specific tactics and timelines.
```

**Expected Outcomes:**
- Cognitive Requirements: Complex - spans multiple categories (RESEARCH + STRATEGY + PLANNING)
- Framework: Comprehensive framework like `GOPA` or phased approach
- Questions: Cover research, strategy, and planning aspects
- Complexity: "complex"

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Recognises Multi-Category | Yes (RESEARCH + STRATEGY + PLANNING) | | ☐ |
| Complexity Level | complex | | ☐ |
| Framework Approach | Comprehensive or phased | | ☐ |
| Questions Cover Research | ≥1 question | | ☐ |
| Questions Cover Strategy | ≥1 question | | ☐ |
| Questions Cover Planning | ≥1 question | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 4.3: Vague/Ambiguous Task

**Task Description:**
```
Help me with my business.
```

**Expected Outcomes:**
- System recognises vagueness
- Heavy on clarification questions
- Questions aim to narrow scope
- Doesn't make unfounded assumptions

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| Acknowledges Vagueness | Yes (in classification reasoning) | | ☐ |
| High Clarification Question Ratio | >60% universal/clarification | | ☐ |
| Questions Narrow Scope | "What specific aspect?", "What goal?" | | ☐ |
| No Unfounded Assumptions | Doesn't assume specific category | | ☐ |

**Classification Reasoning:**
```
[Should mention ambiguity/vagueness]
```

**Questions Generated:**
```
[Should be heavily clarification-focused]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

### Category 5: Framework-Specific Validation

---

#### Test 5.1: ReAct Framework - Agentic Task

**Task Description:**
```
Research the top 5 companies in the electric vehicle space, compare their market share, and analyse their competitive advantages. Use reliable sources and cite your findings.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `AGENTIC`, `SYNTHESIS`, `OBJECTIVE`
- Framework: `ReAct` or `Chain of Thought`
- Questions: Address tool usage, termination criteria

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| AGENTIC Requirement Identified | Yes | | ☐ |
| Framework Selected | ReAct / Chain of Thought | | ☐ |
| Questions Cover Tool/Source Usage | ≥1 question | | ☐ |
| Questions Cover Termination | "How will you know when complete?" | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 5.2: Self-Refine Framework - Quality-Critical Task

**Task Description:**
```
Write a critical analysis of climate change policies. This needs to be publication-quality - rigorous argumentation, well-researched, and bulletproof against criticism. Multiple drafts expected.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `ITERATIVE`, `OBJECTIVE`, `RISK`
- Framework: `Self-Refine` or `Reflexion`
- Questions: Focus on quality standards, iteration

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| ITERATIVE Requirement Identified | Yes (primary) | | ☐ |
| Framework Selected | Self-Refine / Reflexion | | ☐ |
| Questions Cover Quality Criteria | ≥1 question | | ☐ |
| Questions Cover Iteration Count | "How many refinement iterations?" | | ☐ |
| Rationale Mentions Multi-Pass | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

#### Test 5.3: Step-Back Framework - Principle-Based Task

**Task Description:**
```
I'm trying to solve this calculus problem about optimisation. Before diving into the specific problem, help me understand the general principles of optimisation in calculus.
```

**Expected Outcomes:**
- Cognitive Requirements: Primary: `ABSTRACTION`, `PEDAGOGY`, `OBJECTIVE`
- Framework: `Step-Back Prompting`
- Questions: Prompt principle identification before specifics

**Actual Results:**

| Metric | Expected | Actual | Match |
|--------|----------|--------|-------|
| ABSTRACTION Requirement Identified | Yes (primary) | | ☐ |
| Framework Selected | Step-Back Prompting | | ☐ |
| Questions Cover Domain/Principles | "What domain?", "What principles?" | | ☐ |
| Rationale Mentions Abstraction-First | Yes | | ☐ |

**Framework Scoring Rationale:**
```
[Paste actual rationale here]
```

**Questions Generated:**
```
[Paste questions here]
```

**Result:** PASS ☐ / FAIL ☐

**Notes:**
```
```

---

## Overall Analysis

### Success Metrics Summary

| Metric | Target | Actual | Status |
|--------|--------|--------|--------|
| Clear-Match Tests (Framework Selection) | 90%+ (≥2.7/3) | /3 | ☐ |
| Conflict Tests (Reasonable Resolution) | 80%+ (≥1.6/2) | /2 | ☐ |
| Personality Tests (Counterbalancing) | 100% (3/3) | /3 | ☐ |
| Edge Cases (Appropriate Response) | 100% (3/3) | /3 | ☐ |
| Framework-Specific (Correct Type) | 90%+ (≥2.7/3) | /3 | ☐ |
| **OVERALL PASS RATE** | **85%+ (≥12.75/15)** | **/15** | ☐ |

### Key Findings

**What Worked Well:**
```
[List successes and positive observations]
```

**What Needs Improvement:**
```
[List failures and areas for enhancement]
```

**Unexpected Behaviors:**
```
[List any surprising or unexpected results]
```

### Patterns in Failures

```
[Identify any common patterns in failed tests]
```

### Recommendations

**Priority 1 (Critical Issues):**
```
[Issues that must be fixed before deployment]
```

**Priority 2 (Enhancement Opportunities):**
```
[Nice-to-have improvements]
```

**Priority 3 (Future Considerations):**
```
[Ideas for future iterations]
```

---

## Validation Status

**Test Execution Complete:** ☐ Yes / ☐ No
**All Tests Passed:** ☐ Yes / ☐ No
**System Ready for Production:** ☐ Yes / ☐ No / ☐ Needs Refinement

**Final Sign-Off:**
- Tested By: ___________________ Date: ___________
- Reviewed By: _________________ Date: ___________
- Approved By: _________________ Date: ___________

---

**Document Status:** Ready for Test Execution
**Last Updated:** 2025-12-29
**Version:** 1.0
