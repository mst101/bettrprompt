# Cognitive Requirements Enhancement Test Suite

## Purpose

This test suite validates the effectiveness of the cognitive requirements enhancement system by comparing framework selection and question prioritisation before and after the implementation.

## Test Methodology

For each test case:
1. **Input**: Task description
2. **Expected Cognitive Requirements**: What the system should identify
3. **Expected Framework**: Which framework should score highest and why
4. **Expected Question Focus**: Which requirements should be prioritised in questions
5. **Success Criteria**: How to evaluate if the enhancement worked

## Test Cases

### Category 1: Clear Requirement Matches

#### Test 1.1: Strategy Task with VISION + DETAIL

**Task Description:**
```
I need to develop a 3-year growth strategy for my SaaS startup. We're currently at 500 customers and want to reach 10,000. I need to identify key milestones, resource requirements, and potential obstacles.
```

**Expected Cognitive Requirements:**
- Primary: `VISION`, `DETAIL`, `DECISIVE`
- Secondary: `RISK`, `STRUCTURE`

**Expected Framework Selection:**
- **COAST** should score highest:
  - Supports VISION (+3), DETAIL (+3), DECISIVE (+3) = 9 points
- **GOPA** would score lower:
  - Supports DETAIL (+3), DECISIVE (+3) = 6 points
- **SMART** would score lower:
  - Supports DETAIL (+3), DECISIVE (+3) = 6 points

**Expected Question Focus:**
- Questions addressing `VISION`: "What's the time horizon?", "What does success look like at the end?"
- Questions addressing `DETAIL`: "What resources are available?", "What specific milestones?"
- Questions addressing `RISK`: "What obstacles or risks do you foresee?"

**Success Criteria:**
- ✓ COAST selected as framework
- ✓ Rationale mentions VISION + DETAIL coverage
- ✓ At least 1 question addresses each primary requirement
- ✓ Question purposes mention which requirements they address

---

#### Test 1.2: Research Task with SYNTHESIS + OBJECTIVE

**Task Description:**
```
Help me research the current state of AI coding assistants. I need to understand the different approaches (fine-tuned models, RAG, prompt engineering), compare their strengths/weaknesses, and synthesise findings into a coherent overview.
```

**Expected Cognitive Requirements:**
- Primary: `SYNTHESIS`, `OBJECTIVE`, `EXPLORE`
- Secondary: `STRUCTURE`, `DETAIL`

**Expected Framework Selection:**
- **Tree of Thought** should score highest:
  - Supports OBJECTIVE (+3), EXPLORE (+3), ABSTRACTION (+3) = 9 points
  - Note: ABSTRACTION often pairs well with SYNTHESIS
- **Chain of Thought** alternative:
  - Supports OBJECTIVE (+3), ABSTRACTION (+3), DETAIL (+3) = 9 points

**Expected Question Focus:**
- Questions addressing `SYNTHESIS`: "What do you already know?", "Are there competing perspectives?"
- Questions addressing `OBJECTIVE`: "What specific questions need answering?", "What sources are acceptable?"
- Questions addressing `EXPLORE`: "What would be surprising or change your approach?"

**Success Criteria:**
- ✓ Tree of Thought or Chain of Thought selected
- ✓ Rationale mentions SYNTHESIS + OBJECTIVE coverage
- ✓ Questions prioritise information gathering and integration
- ✓ Question purposes explain requirement coverage

---

#### Test 1.3: Learning Task with PEDAGOGY + STRUCTURE

**Task Description:**
```
I'm trying to understand how database indexing works. I know the basics of SQL but I'm confused about when to use clustered vs non-clustered indexes, and how they actually improve query performance. Explain it to me like I'm an intermediate developer.
```

**Expected Cognitive Requirements:**
- Primary: `PEDAGOGY`, `STRUCTURE`, `DETAIL`
- Secondary: `ABSTRACTION`

**Expected Framework Selection:**
- **ELI5** should score highest:
  - Supports PEDAGOGY (+3), EMPATHY (+3) = 6 points
- **Bloom's Taxonomy** alternative:
  - Supports PEDAGOGY (+3), STRUCTURE (+3), ABSTRACTION (+3) = 9 points
- **Bloom's should win** due to better coverage

**Expected Question Focus:**
- Questions addressing `PEDAGOGY`: "What's your current understanding level?", "What specifically is confusing?"
- Questions addressing `STRUCTURE`: "What format works well for you?"
- Questions addressing `DETAIL`: "How deep do you need to go?"

**Success Criteria:**
- ✓ Bloom's Taxonomy or ELI5 selected
- ✓ Rationale mentions PEDAGOGY + STRUCTURE
- ✓ Questions focus on understanding level and learning style
- ✓ Question purposes mention pedagogical requirements

---

### Category 2: Requirement Conflicts

#### Test 2.1: Creative Exploration vs Structured Execution

**Task Description:**
```
I need to brainstorm innovative features for our project management tool, but I also need a concrete roadmap with specific deliverables and timelines for the next quarter.
```

**Expected Cognitive Requirements:**
- Primary: `CREATIVE`, `EXPLORE` (for brainstorming)
- Primary: `DETAIL`, `STRUCTURE` (for roadmap)
- Conflict: Creative exploration opposes structured execution

**Expected Framework Selection:**
- **Challenge**: No single framework perfectly handles both
- **Likely**: System might suggest **SCAMPER** for ideation phase + **SMART** for planning phase
- Or: **RACEF** (Iterative Refinement) as compromise

**Expected Question Focus:**
- Separate questions for each phase
- Clarify whether user wants both done simultaneously or sequentially
- "How wild can ideas be?" (CREATIVE/EXPLORE)
- "What specific deliverables and dates?" (DETAIL/STRUCTURE)

**Success Criteria:**
- ✓ System recognises the dual nature
- ✓ Either suggests two frameworks or a hybrid approach
- ✓ Questions address both creative and structured aspects
- ✓ Rationale explains the conflict and resolution

---

#### Test 2.2: Empathy vs Objectivity Balance

**Task Description:**
```
I need to give performance feedback to a struggling team member. The feedback needs to be honest and data-driven, but also supportive and motivating. They're sensitive to criticism.
```

**Expected Cognitive Requirements:**
- Primary: `EMPATHY`, `WARM` (for supportive delivery)
- Primary: `OBJECTIVE`, `DETAIL` (for honest, data-driven content)
- Balancing act required

**Expected Framework Selection:**
- **BAB (Before-After-Bridge)** should score well:
  - Supports PERSUASION (+3), EMPATHY (+3), STRUCTURE (+3), WARM (+1) = 10 points
- **CO-STAR** alternative:
  - Supports EMPATHY (+3), WARM (+3), STRUCTURE (+3) = 9 points

**Expected Question Focus:**
- "How candid can the feedback be?" (EMPATHY/WARM)
- "What specific criteria or standards?" (OBJECTIVE)
- "What aspects need feedback?" (DETAIL)
- "What will you do with the feedback?" (DECISIVE)

**Success Criteria:**
- ✓ BAB or similar persuasion framework selected
- ✓ Rationale mentions balancing empathy with objectivity
- ✓ Questions address both emotional and factual dimensions
- ✓ Framework supports constructive delivery

---

### Category 3: Personality Data Integration

#### Test 3.1: High N + High P (Creative Explorer) - Ideation Task

**Task Description:**
```
Generate innovative product ideas for sustainable transportation in urban environments.
```

**User Personality:**
```
Type: ENTP-A
Traits: E:65%, N:78%, T:62%, P:71%, A:68%
```

**Expected Cognitive Requirements:**
- Primary: `CREATIVE`, `EXPLORE`, `VISION`
- Secondary: `SYNTHESIS`

**Expected Framework Selection:**
- **SCAMPER** should score highest:
  - Supports CREATIVE (+3), EXPLORE (+3), VISION (+1) = 7 points

**Expected Task-Trait Alignment:**
- **Amplified**:
  - High N (78%) aligned with CREATIVE, VISION → Amplify natural ideation
  - High P (71%) aligned with EXPLORE → Amplify openness to possibilities
- **Neutral**:
  - High T, E, A → Not directly relevant to ideation task
- **Counterbalanced**: None needed (traits align well)

**Expected Question Adjustments:**
- Question quantity: +1 (High P comfortable with exploration)
- Phrasing: Encourage wild ideas, don't constrain prematurely

**Success Criteria:**
- ✓ SCAMPER or creative framework selected
- ✓ Amplifies N and P traits for ideation
- ✓ No counterbalancing needed (good trait-task fit)
- ✓ Questions encourage unconstrained creativity

---

#### Test 3.2: High S + High J (Practical Organiser) - Strategy Task

**Task Description:**
```
Develop a 3-year growth strategy for my SaaS startup.
```

**User Personality:**
```
Type: ISTJ-T
Traits: I:62%, S:73%, T:65%, J:76%, T:71%
```

**Expected Cognitive Requirements:**
- Primary: `VISION`, `DETAIL`, `DECISIVE`
- Secondary: `RISK`, `STRUCTURE`

**Expected Framework Selection:**
- **COAST** should still win (requirement-based scoring)

**Expected Task-Trait Alignment:**
- **Amplified**:
  - High S (73%) aligned with DETAIL → Amplify concrete planning strength
  - High J (76%) aligned with STRUCTURE, DECISIVE → Amplify organisation
  - High T-identity (71%) aligned with RISK → Amplify risk awareness
- **Counterbalanced**:
  - High S (73%) **opposed** to VISION → Inject big-picture requirements
    - Injection: "Step back and consider the broader implications"
    - Injection: "What's your 3-5 year vision beyond immediate goals?"

**Expected Question Adjustments:**
- Question quantity: -1 (High J wants to proceed)
- Question quantity: +1 (High T-identity wants thoroughness)
- Net: Baseline quantity
- Phrasing: Push for strategic vision despite preference for concrete detail

**Success Criteria:**
- ✓ COAST selected (supports requirements)
- ✓ Amplifies S, J, T-identity where aligned
- ✓ **Counterbalances S for VISION requirement** (critical test)
- ✓ Injections visible in prompt adjustments
- ✓ Questions include vision-pushing prompts despite S preference

---

#### Test 3.3: High F + High A (Confident Feeler) - Persuasion Task

**Task Description:**
```
Write a proposal to convince investors to fund our social impact startup.
```

**User Personality:**
```
Type: ENFP-A
Traits: E:71%, N:68%, F:79%, P:64%, A:77%
```

**Expected Cognitive Requirements:**
- Primary: `PERSUASION`, `EMPATHY`, `VISION`
- Secondary: `STRUCTURE`, `DECISIVE`

**Expected Framework Selection:**
- **BAB** should score highest:
  - Supports PERSUASION (+3), EMPATHY (+3), STRUCTURE (+3), WARM (+1) = 10 points

**Expected Task-Trait Alignment:**
- **Amplified**:
  - High F (79%) aligned with EMPATHY, PERSUASION → Amplify emotional appeal strength
  - High N (68%) aligned with VISION → Amplify big-picture framing
- **Counterbalanced**:
  - High F (79%) **opposed** to OBJECTIVE (implicit in persuasion) → Balance emotion with data
    - Injection: "Support emotional appeals with data and evidence"
  - High A (77%) might skip RISK awareness → Ensure balanced perspective
    - Injection: "Address potential concerns and risks proactively"

**Expected Question Adjustments:**
- Question quantity: -1 (High A confident)
- Phrasing: Encourage data alongside emotion

**Success Criteria:**
- ✓ BAB or persuasion framework selected
- ✓ Amplifies F and N for emotional and visionary appeal
- ✓ Counterbalances F with objectivity requirements
- ✓ Counterbalances A with risk acknowledgment
- ✓ Questions prompt both emotional resonance and factual support

---

### Category 4: Edge Cases

#### Test 4.1: Simple Task - Should Avoid Over-Engineering

**Task Description:**
```
Explain what a REST API is in simple terms.
```

**Expected Cognitive Requirements:**
- Primary: `PEDAGOGY`, `STRUCTURE`
- Secondary: None (simple task)

**Expected Framework Selection:**
- **ELI5** should win:
  - Supports PEDAGOGY (+3), EMPATHY (+3) = 6 points
  - Simpler framework appropriate for simple task

**Expected Question Focus:**
- Minimal questions (2-3 only for simple task)
- "What's your current understanding?"
- "What's the context for learning this?"

**Success Criteria:**
- ✓ Simple framework selected (ELI5, not complex frameworks)
- ✓ Minimal questions (complexity-appropriate)
- ✓ No over-engineering with unnecessary requirements
- ✓ Question quantity reflects task simplicity

---

#### Test 4.2: Multi-Category Task

**Task Description:**
```
I need to research the current market landscape for AI tools, develop a positioning strategy for our product, and create a go-to-market plan with specific tactics and timelines.
```

**Expected Cognitive Requirements:**
- Primary: `SYNTHESIS` (research), `VISION` (strategy), `DETAIL` (planning), `OBJECTIVE`
- Secondary: `STRUCTURE`, `DECISIVE`, `RISK`

**Expected Framework Selection:**
- **Complex**: Multiple categories (RESEARCH + STRATEGY + PLANNING)
- **Likely**: System might suggest breaking into phases
- Or: **GOPA** (comprehensive strategy framework)
  - Supports DETAIL (+3), STRUCTURE (+3), DECISIVE (+3), OBJECTIVE (+3) = 12 points

**Expected Question Focus:**
- Questions span all three task phases
- Clarify whether sequential or parallel execution
- Address research, strategy, and execution requirements

**Success Criteria:**
- ✓ System recognises multi-category nature
- ✓ Framework selection addresses multiple requirements
- ✓ Questions cover research, strategy, and planning aspects
- ✓ Complexity level marked as "complex"

---

#### Test 4.3: Vague/Ambiguous Task

**Task Description:**
```
Help me with my business.
```

**Expected Cognitive Requirements:**
- **Cannot determine** - task too vague
- System should recognise ambiguity

**Expected Framework Selection:**
- Likely defaults to general clarification approach
- Or: Selects framework for PROBLEM_SOLVING category

**Expected Question Focus:**
- **Heavy on universal/clarification questions**:
  - "What specific aspect of your business?"
  - "What's the goal or outcome you're trying to achieve?"
  - "What's the specific problem or opportunity?"

**Success Criteria:**
- ✓ System recognises vagueness in classification reasoning
- ✓ High proportion of clarification questions
- ✓ Questions aim to narrow scope before proceeding
- ✓ Doesn't make unfounded assumptions about requirements

---

### Category 5: Framework-Specific Validation

#### Test 5.1: ReAct Framework - Agentic Task

**Task Description:**
```
Research the top 5 companies in the electric vehicle space, compare their market share, and analyse their competitive advantages. Use reliable sources and cite your findings.
```

**Expected Cognitive Requirements:**
- Primary: `AGENTIC`, `SYNTHESIS`, `OBJECTIVE`
- Secondary: `DETAIL`, `STRUCTURE`

**Expected Framework Selection:**
- **ReAct** should score highest:
  - Supports AGENTIC (+3), OBJECTIVE (+3) = 6 points (but perfect fit for tool use)
- Or: **Chain of Thought** for structured analysis

**Expected Question Focus:**
- "What tools/sources are available?" (AGENTIC)
- "How will you know when complete?" (AGENTIC termination)
- "What sources are acceptable?" (OBJECTIVE)
- "What format should findings take?" (STRUCTURE)

**Success Criteria:**
- ✓ ReAct or similar agentic framework selected
- ✓ Questions address tool usage and termination criteria
- ✓ AGENTIC requirement properly identified
- ✓ Framework rationale explains tool-using nature

---

#### Test 5.2: Self-Refine Framework - Quality-Critical Task

**Task Description:**
```
Write a critical analysis of climate change policies. This needs to be publication-quality - rigorous argumentation, well-researched, and bulletproof against criticism. Multiple drafts expected.
```

**Expected Cognitive Requirements:**
- Primary: `ITERATIVE`, `OBJECTIVE`, `RISK`
- Secondary: `STRUCTURE`, `DETAIL`

**Expected Framework Selection:**
- **Self-Refine** should score highest:
  - Supports ITERATIVE (+3), OBJECTIVE (+3) = 6 points (but perfect for quality iteration)

**Expected Question Focus:**
- "What are specific quality criteria?" (ITERATIVE/OBJECTIVE)
- "What aspects are most important?" (DECISIVE)
- "How many refinement iterations?" (ITERATIVE)
- "What common mistakes to avoid?" (RISK)

**Success Criteria:**
- ✓ Self-Refine or Reflexion selected
- ✓ ITERATIVE requirement identified as primary
- ✓ Questions focus on quality standards and iteration
- ✓ Framework rationale mentions multi-pass refinement

---

#### Test 5.3: Step-Back Framework - Principle-Based Task

**Task Description:**
```
I'm trying to solve this calculus problem about optimisation. Before diving into the specific problem, help me understand the general principles of optimisation in calculus.
```

**Expected Cognitive Requirements:**
- Primary: `ABSTRACTION`, `PEDAGOGY`, `OBJECTIVE`
- Secondary: `STRUCTURE`

**Expected Framework Selection:**
- **Step-Back Prompting** should score highest:
  - Explicitly requests principle-first approach
  - Supports ABSTRACTION (+3), VISION (+3) (vision relates to principles) = 6 points

**Expected Question Focus:**
- "What domain does this belong to?" (ABSTRACTION)
- "What general principles might apply?" (ABSTRACTION)
- "What's your current understanding?" (PEDAGOGY)

**Success Criteria:**
- ✓ Step-Back Prompting selected
- ✓ ABSTRACTION identified as primary requirement
- ✓ Questions prompt principle identification before specifics
- ✓ Framework rationale mentions abstraction-first approach

---

## Summary of Test Categories

1. **Clear Requirement Matches (Tests 1.1-1.3)**: Validate basic scoring works
2. **Requirement Conflicts (Tests 2.1-2.2)**: Validate handling of competing needs
3. **Personality Integration (Tests 3.1-3.3)**: Validate Task-Trait Alignment with counterbalancing
4. **Edge Cases (Tests 4.1-4.3)**: Validate appropriate responses to unusual inputs
5. **Framework-Specific (Tests 5.1-5.3)**: Validate each framework type works correctly

## Success Metrics

### Quantitative Metrics

For each test:
- [ ] Cognitive requirements correctly identified (±1 requirement acceptable)
- [ ] Framework selection matches expected (or justifiable alternative with explanation)
- [ ] Framework scoring explained in rationale
- [ ] At least 1 question per PRIMARY requirement
- [ ] Question purposes mention relevant requirements

### Overall Success Criteria

- **90%+** of clear-match tests select expected framework
- **80%+** of conflict tests provide reasonable resolution
- **100%** of personality tests include counterbalancing where specified
- **100%** of edge cases avoid over-engineering
- **90%+** of framework-specific tests select correct framework type

### Qualitative Assessment

For each test, evaluate:
1. Does the framework selection make sense for the task?
2. Are the questions more relevant than generic category-based selection?
3. Does personality alignment work appropriately (amplify/counterbalance)?
4. Is the rationale clear and transparent?

## Running the Tests

### Manual Testing Process

1. **Prepare**: Set up test environment with both old and new workflows
2. **Execute**: Run each test task through the system
3. **Record**: Capture framework selected, questions generated, and rationale
4. **Compare**: Check against expected outcomes
5. **Document**: Note any deviations and whether they're acceptable

### Test Results Template

```markdown
## Test X.X: [Test Name]

**Framework Selected**: [Actual]
**Expected**: [Expected]
**Match**: ✓ / ✗

**Cognitive Requirements Identified**:
- Primary: [Actual]
- Secondary: [Actual]
- Expected: [Expected primary/secondary]
- Match: ✓ / ✗

**Framework Scoring Rationale**: [Copy from output]
**Scoring Explained**: ✓ / ✗

**Questions Generated**: [Count]
**Questions Addressing Each Primary Requirement**:
- [REQ1]: [Question IDs]
- [REQ2]: [Question IDs]
- Coverage: ✓ / ✗

**Personality Alignment** (if applicable):
- Amplified: [Actual]
- Counterbalanced: [Actual]
- Expected: [Expected]
- Match: ✓ / ✗

**Overall Assessment**: PASS / FAIL
**Notes**: [Any observations, improvements, or issues]
```

## Next Steps

1. Run all 15 test cases through the enhanced system
2. Document results using the template above
3. Calculate success metrics
4. Identify any patterns in failures
5. Refine system based on test results
6. Re-test failed cases
7. Document final validation results

---

**Document Status:** Test Suite Ready for Execution
**Last Updated:** 2025-12-29
**Total Test Cases:** 15
**Estimated Test Duration:** 2-3 hours
