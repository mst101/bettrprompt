# Personality Calibration

## Purpose

This document provides rules for adjusting prompt style, question phrasing, and output format based on the user's personality type and trait percentages. It includes the Task-Trait Alignment system for determining when to amplify, counterbalance, or remain neutral on each personality dimension.

When no personality data is provided, skip all personality-based adjustments and use neutral defaults.

---

## The 16Personalities Framework Overview

The 16Personalities model uses five independent scales:

| Scale | Dimension | Low End | High End |
|-------|-----------|---------|----------|
| Mind | Energy direction | Introverted (I) | Extraverted (E) |
| Energy | Information processing | Observant/Sensing (S) | Intuitive (N) |
| Nature | Decision making | Feeling (F) | Thinking (T) |
| Tactics | Approach to structure | Prospecting/Perceiving (P) | Judging (J) |
| Identity | Self-confidence | Turbulent (T) | Assertive (A) |

### Percentage Interpretation

The percentages indicate strength of preference on each scale:

| Percentage Range | Interpretation | Adjustment Weight |
|------------------|----------------|-------------------|
| 50-55% | Borderline — both approaches viable | 0.25 (minimal) |
| 55-65% | Moderate preference | 0.50 (standard) |
| 65-75% | Clear preference | 0.75 (significant) |
| 75%+ | Strong preference | 1.00 (maximum) |

---

## Handling Missing Personality Data

### Tier 1: Full Data (Type + Percentages)
- User provides: e.g., "INTP-A" with "I:65%, N:64%, T:84%, P:57%, A:84%"
- Action: Apply full personality calibration with Task-Trait Alignment

### Tier 2: Partial Data (Type Only)
- User provides: e.g., "INTP-A" without percentages
- Action: Default all traits to 65% and apply standard calibration
- Example: INTP-A → I:65%, N:65%, T:65%, P:65%, A:65%

### Tier 3: No Data
- User provides: No personality information
- Action: Skip ALL personality-based adjustments
- Use neutral, professional defaults throughout
- Note in output: "No personality adjustments applied — task-driven approach used"

---

## Task-Trait Alignment System

### Core Principle

Rather than always amplifying or always counterbalancing personality traits, the system should:

1. **Amplify** traits that are assets for the specific task
2. **Counterbalance** traits that create blind spots for the task
3. **Remain neutral** on traits irrelevant to the task

### Task Cognitive Requirements

Every task has inherent cognitive requirements. These requirements may align with, oppose, or be unrelated to specific personality traits.

#### Requirement: `EMPATHY` - Empathy & Stakeholder Awareness

Tasks requiring understanding of human feelings, relationships, and interpersonal impact.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High F (≥60%) | **Aligned** | Amplify — natural strength |
| High T (≥60%) | **Misaligned** | Counterbalance — inject empathy requirements |
| High E (≥60%) | **Aligned** | Amplify — natural social awareness |
| High I (≥60%) | **Neutral** | May need slight encouragement to consider others |

**Counterbalance injection for High T:**
- Add explicit requirements: "Consider how this will make the recipient feel"
- Include stakeholder impact analysis
- Request acknowledgment of emotional dimensions

#### Requirement: `VISION` - Big-Picture Strategic Vision

Tasks requiring future thinking, pattern recognition, and conceptual frameworks.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High N (≥60%) | **Aligned** | Amplify — natural strength |
| High S (≥60%) | **Misaligned** | Counterbalance — push for broader perspective |
| High P (≥60%) | **Aligned** | Amplify — openness to possibilities |

**Counterbalance injection for High S:**
- Add requirements: "Step back and consider the broader implications"
- Request future-state visioning
- Ask for pattern identification across examples

#### Requirement: `DETAIL` - Detailed Execution Planning

Tasks requiring step-by-step specificity, concrete actions, and practical implementation.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High S (≥60%) | **Aligned** | Amplify — natural strength |
| High J (≥60%) | **Aligned** | Amplify — natural planning ability |
| High N (≥60%) | **Misaligned** | Counterbalance — force granular detail |
| High P (≥60%) | **Misaligned** | Counterbalance — require specific commitments |

**Counterbalance injection for High N:**
- Add requirements: "Provide specific, concrete steps"
- Request timelines, milestones, and measurable actions
- Ask for practical implementation details, not just concepts

**Counterbalance injection for High P:**
- Add requirements: "Commit to specific actions and dates"
- Request prioritised task lists
- Ask for clear sequencing and dependencies

#### Requirement: `DECISIVE` - Decisive Recommendations

Tasks requiring clear conclusions, prioritised options, and confident guidance.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High J (≥60%) | **Aligned** | Amplify — natural decisiveness |
| High A (≥60%) | **Aligned** | Amplify — natural confidence |
| High P (≥60%) | **Misaligned** | Counterbalance — push for commitment |
| High T-identity (≥60%) | **Misaligned** | Counterbalance — reduce excessive hedging |

**Counterbalance injection for High P:**
- Add requirements: "Provide a clear, prioritised recommendation"
- Request "If you had to choose one..." framing
- Limit options presented to top 2-3

**Counterbalance injection for High T-identity (Turbulent):**
- Add requirements: "State your recommendation confidently"
- Request clear language without excessive caveats
- Ask for "recommended" not "you might consider"

#### Requirement: `EXPLORE` - Exploring Multiple Options

Tasks requiring divergent thinking, option generation, and avoiding premature closure.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High P (≥60%) | **Aligned** | Amplify — natural openness |
| High N (≥60%) | **Aligned** | Amplify — sees possibilities |
| High J (≥60%) | **Misaligned** | Counterbalance — prevent premature closure |
| High A (≥60%) | **Slightly misaligned** | May dismiss options too quickly |

**Counterbalance injection for High J:**
- Add requirements: "Generate at least 4-5 distinct options before evaluating"
- Request "what else could work?" exploration
- Explicitly delay recommendation until options are fully explored

#### Requirement: `OBJECTIVE` - Objective Analysis

Tasks requiring dispassionate evaluation, logical reasoning, and evidence-based conclusions.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High T (≥60%) | **Aligned** | Amplify — natural strength |
| High S (≥60%) | **Aligned** | Amplify — focuses on facts |
| High F (≥60%) | **Misaligned** | Counterbalance — emphasise data over feelings |

**Counterbalance injection for High F:**
- Add requirements: "Base conclusions on evidence and data"
- Request separation of facts from feelings
- Ask for objective criteria before subjective considerations

#### Requirement: `RISK` - Risk Awareness

Tasks requiring identification of potential problems, downsides, and failure modes.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High T-identity (≥60%) | **Aligned** | Amplify — natural risk sensitivity |
| High T (≥60%) | **Aligned** | Amplify — analytical about risks |
| High A (≥60%) | **Misaligned** | Counterbalance — may underweight risks |
| High N (≥60%) | **Slightly misaligned** | May focus on possibilities over risks |

**Counterbalance injection for High A:**
- Add requirements: "Explicitly identify what could go wrong"
- Request risk/downside analysis section
- Ask "What would cause this to fail?"

#### Requirement: `CREATIVE` - Creative Innovation

Tasks requiring novel ideas, unconventional thinking, and breaking from established patterns.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High N (≥60%) | **Aligned** | Amplify — sees possibilities |
| High P (≥60%) | **Aligned** | Amplify — open to unconventional |
| High S (≥60%) | **Misaligned** | Counterbalance — push beyond conventional |
| High J (≥60%) | **Misaligned** | Counterbalance — loosen structure requirements |

**Counterbalance injection for High S:**
- Add requirements: "Consider unconventional approaches"
- Request "What if we ignored current constraints?" thinking
- Ask for ideas that challenge assumptions

#### Requirement: `STRUCTURE` - Structured Communication

Tasks requiring clear organisation, logical flow, and professional presentation.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High J (≥60%) | **Aligned** | Amplify — natural organiser |
| High T (≥60%) | **Aligned** | Amplify — logical structure |
| High P (≥60%) | **Misaligned** | Counterbalance — impose structure |
| High F (≥60%) | **Neutral** | May prefer narrative over structure |

**Counterbalance injection for High P:**
- Add requirements: "Use clear headings and logical sections"
- Request numbered lists for sequential items
- Ask for executive summary upfront

#### Requirement: `WARM` - Warm/Relational Tone

Tasks requiring warmth, rapport-building, and relationship-focused communication.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High F (≥60%) | **Aligned** | Amplify — natural warmth |
| High E (≥60%) | **Aligned** | Amplify — social orientation |
| High T (≥60%) | **Misaligned** | Counterbalance — inject warmth |
| High I (≥60%) | **Neutral** | May need encouragement for warmth |

**Counterbalance injection for High T:**
- Add requirements: "Open with personal acknowledgment"
- Request relationship-affirming language
- Ask for tone that prioritises connection over efficiency

#### Requirement: `ITERATIVE` - Iterative Self-Improvement

Tasks requiring multiple passes, self-critique, and progressive refinement of output quality.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High T-identity (≥60%) | **Aligned** | Amplify — natural thoroughness and quality sensitivity |
| High P (≥60%) | **Aligned** | Amplify — comfortable with non-linear improvement |
| High J (≥60%) | **Misaligned** | Counterbalance — prevent premature conclusion |
| High A (≥60%) | **Misaligned** | Counterbalance — ensure genuine critique, not just acceptance |

**Counterbalance injection for High J:**
- Add requirements: "Complete at least 2 full refinement cycles before finalising"
- Request: "Resist the urge to conclude—treat first output as draft only"
- Include: "Explicitly identify weaknesses before generating improvements"

**Counterbalance injection for High A:**
- Add requirements: "Identify at least 3 specific areas for improvement"
- Request: "Apply genuine self-critique, not surface-level acceptance"
- Include: "Challenge your own output as a skeptical reviewer would"

#### Requirement: `ABSTRACTION` - Abstraction Before Specifics

Tasks benefiting from higher-level principle identification before tackling concrete details.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High N (≥60%) | **Aligned** | Amplify — natural abstract thinking |
| High T (≥60%) | **Aligned** | Amplify — principle-based reasoning |
| High S (≥60%) | **Misaligned** | Counterbalance — force step-back thinking |
| High F (≥60%) | **Neutral** | May naturally consider human principles |

**Counterbalance injection for High S:**
- Add requirements: "Before addressing specifics, identify the general category and principles"
- Request: "Step back from the details—what broader concepts apply?"
- Include: "Connect specific question to underlying framework or theory"

#### Requirement: `PARALLEL` - Structured Parallel Processing

Tasks where content can be developed in parallel components then assembled.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High J (≥60%) | **Aligned** | Amplify — natural organisation |
| High S (≥60%) | **Aligned** | Amplify — methodical component handling |
| High P (≥60%) | **Misaligned** | Counterbalance — impose skeleton structure |
| High N (≥60%) | **Neutral** | May need grounding for each component |

**Counterbalance injection for High P:**
- Add requirements: "Create skeleton FIRST before any expansion"
- Request: "Constrain expansion to skeleton points—no tangents"
- Include: "Each point should be independently complete"

#### Requirement: `AGENTIC` - Agentic Reasoning

Tasks requiring interleaved thinking and acting with external tool/environment interaction.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High T (≥60%) | **Aligned** | Amplify — logical step-by-step reasoning |
| High J (≥60%) | **Partially aligned** | May need counterbalancing for exploration |
| High P (≥60%) | **Aligned** | Amplify — adaptive problem-solving |
| High N (≥60%) | **Misaligned** | Counterbalance — ground thoughts with actions |

**Counterbalance injection for High N:**
- Add requirements: "Each thought must lead to a concrete action"
- Request: "Verify abstract reasoning with specific observations"
- Include: "Don't assume—check with actions when uncertain"

**Counterbalance injection for High J:**
- Add requirements: "Generate multiple possible actions before selecting"
- Request: "If first approach fails, genuinely explore alternatives"
- Include: "Final answer only after sufficient information gathered"

#### Requirement: `SYNTHESIS` - Information Synthesis

Tasks requiring integration of multiple sources, finding connections between disparate information, and building coherent understanding from diverse inputs.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High N (≥60%) | **Aligned** | Amplify — natural pattern recognition across sources |
| High T (≥60%) | **Aligned** | Amplify — logical integration of evidence |
| High S (≥60%) | **Misaligned** | Counterbalance — push beyond individual data points |
| High P (≥60%) | **Aligned** | Amplify — comfortable with multiple perspectives |

**Counterbalance injection for High S:**
- Add requirements: "Identify patterns and connections across all sources"
- Request: "How do these pieces of information relate to each other?"
- Include: "Synthesise findings into a coherent narrative, not just a list"

#### Requirement: `PERSUASION` - Persuasive Communication

Tasks requiring convincing others, building compelling arguments, and influencing decisions through strategic framing.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High E (≥60%) | **Aligned** | Amplify — natural social influence |
| High F (≥60%) | **Partially aligned** | Strong on emotional appeals, may need logical balance |
| High T (≥60%) | **Partially aligned** | Strong on logical arguments, may need emotional balance |
| High J (≥60%) | **Aligned** | Amplify — structured argumentation |

**Counterbalance injection for High F:**
- Add requirements: "Support emotional appeals with data and evidence"
- Request: "Include both rational and emotional persuasion elements"
- Include: "Build credibility through objective support"

**Counterbalance injection for High T:**
- Add requirements: "Balance logic with emotional resonance"
- Request: "Consider what will emotionally move your audience"
- Include: "Use storytelling alongside data"

#### Requirement: `PEDAGOGY` - Educational Clarity

Tasks requiring teaching, explaining complex concepts, and adapting content for learners at different levels.

| Trait | Alignment | Action |
|-------|-----------|--------|
| High F (≥60%) | **Aligned** | Amplify — natural empathy for learner needs |
| High S (≥60%) | **Aligned** | Amplify — concrete examples and step-by-step clarity |
| High J (≥60%) | **Aligned** | Amplify — structured learning progression |
| High N (≥60%) | **Misaligned** | Counterbalance — force concrete examples |

**Counterbalance injection for High N:**
- Add requirements: "Provide concrete examples for abstract concepts"
- Request: "Include step-by-step walkthrough with specific examples"
- Include: "Check understanding at each level before advancing"

---

### Task Category to Cognitive Requirements Mapping

| Task Category | Primary Requirements | Secondary Requirements |
|---------------|---------------------|----------------------|
| DECISION | `OBJECTIVE`, `RISK` | `DECISIVE` |
| STRATEGY | `VISION`, `DETAIL` | `RISK`, `DECISIVE` |
| ANALYSIS | `OBJECTIVE`, `DETAIL` | `RISK`, `ABSTRACTION` |
| CREATION_CONTENT | Varies by content type | `STRUCTURE`, `ITERATIVE` |
| CREATION_TECHNICAL | `DETAIL`, `OBJECTIVE` | `STRUCTURE`, `ITERATIVE` |
| IDEATION | `CREATIVE`, `EXPLORE` | `VISION` |
| PROBLEM_SOLVING | `OBJECTIVE`, `DETAIL` | `RISK`, `EXPLORE`, `AGENTIC` |
| LEARNING | `PEDAGOGY`, `STRUCTURE` | `ABSTRACTION` |
| PERSUASION | `PERSUASION`, `EMPATHY` | `STRUCTURE`, `WARM` |
| FEEDBACK | `EMPATHY`, `OBJECTIVE` | `WARM`, `ITERATIVE` |
| RESEARCH | `OBJECTIVE`, `SYNTHESIS` | `DETAIL`, `EXPLORE`, `AGENTIC` |
| GOAL_SETTING | `DETAIL`, `DECISIVE` | `VISION` |
| PLANNING | `DETAIL`, `STRUCTURE` | `VISION`, `RISK` |
| OPTIMIZATION | `OBJECTIVE`, `DETAIL` | `RISK`, `EXPLORE` |

### Content-Type Specific Requirements

For CREATION_CONTENT tasks, requirements vary by content type:

| Content Type | Primary Requirements |
|--------------|---------------------|
| Customer email | `EMPATHY`, `WARM`, `STRUCTURE` |
| Marketing copy | `EMPATHY`, `CREATIVE`, `PERSUASION` |
| Technical blog | `OBJECTIVE`, `STRUCTURE`, `DETAIL`, `PEDAGOGY` |
| Executive summary | `DECISIVE`, `STRUCTURE` |
| Apology/bad news | `EMPATHY`, `WARM`, `RISK` |
| Sales pitch | `PERSUASION`, `EMPATHY`, `DECISIVE` |
| Internal memo | `STRUCTURE`, `OBJECTIVE` |
| Social media | `CREATIVE`, `WARM`, `PERSUASION` |

---

## Framework-Specific Personality Considerations

### CO-STAR Framework

**Strongest personality alignment**: High-F users
- Style and Tone components align with natural empathy
- Audience consideration feels intuitive
- May need counterbalancing for objective detail

**Needs most counterbalancing**: High-T users
- Tone specification may feel unnecessary
- Audience empathy needs explicit scaffolding
- Inject: "The Tone and Audience sections are critical—don't skip"

**Personality-specific component emphasis**:

| Trait | Emphasise | De-emphasise |
|-------|-----------|--------------|
| High T | Objective, Context, Response format | (none) |
| High F | Style, Tone, Audience | (none—natural fit) |
| High N | Objective (strategic), Context (big picture) | Response (may resist format constraints) |
| High S | Context (detailed), Response (specific format) | Style (may overliteralize) |
| High J | All components (loves structure) | (none) |
| High P | Context, Objective | Response format (may want flexibility) |

### ReAct Framework

**Strongest personality alignment**: High-T + High-P combination
- Logical reasoning + adaptive action-taking
- Natural fit for exploratory problem-solving

**Needs most counterbalancing**: High-N + High-J combination
- High-N may skip verification actions
- High-J may conclude before sufficient exploration
- Inject: "Every thought must be verified with an action before accepting"

**Loop-termination considerations by personality**:

| Trait | Risk | Mitigation |
|-------|------|------------|
| High J | Premature termination | "Continue until 3+ observations gathered" |
| High A | Over-confidence in initial answer | "Verify conclusion with at least one more action" |
| High P | Never-ending exploration | "Conclude when question is definitively answered" |
| High T-identity | Excessive verification loops | "Set maximum iteration count" |

### Self-Refine Framework

**Strongest personality alignment**: High-T-identity (Turbulent)
- Natural quality sensitivity
- Comfortable with critique and improvement
- May need guardrails against perfectionism

**Needs most counterbalancing**: High-A + High-J combination
- High-A may accept first draft too readily
- High-J may resist iteration as "inefficient"
- Inject: "First output is ALWAYS a draft—refinement is required, not optional"

**Iteration cycle adjustments**:

| Trait | Cycles | Rationale |
|-------|--------|-----------|
| High A | 3+ required | Ensure genuine critique |
| High T-identity | 2 with quality check | Prevent over-iteration |
| High J | 2 minimum, explicitly mandated | Force iteration |
| High P | 2-3 with convergence check | Prevent endless tweaking |

### Step-Back Prompting

**Strongest personality alignment**: High-N + High-T combination
- Natural abstract thinking + logical principles
- Step-back feels intuitive

**Needs most counterbalancing**: High-S users
- May resist abstraction as "unnecessary"
- Concrete thinking wants to dive straight in
- Inject: "The step-back question is MANDATORY—skip it and accuracy drops 36%"

**Abstraction level by personality**:

| Trait | Abstraction Style |
|-------|-------------------|
| High N | Conceptual frameworks, theoretical principles |
| High S | Procedural principles, rule-based abstractions |
| High T | Logical axioms, formal principles |
| High F | Human-centered principles, values-based frameworks |

### Skeleton-of-Thought

**Strongest personality alignment**: High-J + High-S combination
- Natural organisation + methodical expansion
- Structure-first approach feels comfortable

**Needs most counterbalancing**: High-P + High-N combination
- High-P may resist committing to skeleton
- High-N may want to expand before skeleton is complete
- Inject: "Complete skeleton BEFORE any expansion—no partial builds"

**NOT recommended for these task types** (regardless of personality):
- Math problems requiring sequential reasoning
- Code where later steps depend on earlier outputs
- Any task where point coherence is critical

---

## Task-Trait Alignment Algorithm

```
1. Identify the task category and content type (if applicable)

2. Determine cognitive requirements:
   - Look up primary requirements from Task Category mapping
   - Add content-type specific requirements if CREATION_CONTENT
   - Consider any explicit requirements mentioned by user

3. For each of the user's personality traits (where ≥55%):

   a. Check alignment against each cognitive requirement:
      - If trait ALIGNS with requirement → Mark for AMPLIFICATION
      - If trait OPPOSES requirement → Mark for COUNTERBALANCING
      - If trait is UNRELATED to requirement → Mark as NEUTRAL

   b. Resolve conflicts (trait aligns with one requirement, opposes another):
      - Prioritise primary requirements over secondary
      - If still conflicted, lean toward counterbalancing (safety)

4. Generate adjustment actions:
   - For AMPLIFY: Use trait-aligned language and structure
   - For COUNTERBALANCE: Inject explicit requirements user might skip
   - For NEUTRAL: No specific adjustment

5. Document the alignment analysis for transparency
```

---

## Composite Personality Profiles

Certain trait combinations create recognisable patterns. Apply Task-Trait Alignment to the dominant traits in each profile.

### Analyst Profile (NT Combination)

**Traits**: High N + High T
**Natural Strengths**: Systems thinking, pattern recognition, logical frameworks
**Common Blind Spots**: May underweight emotional/relational factors

**Default Amplification Contexts:**
- Strategic analysis, competitive intelligence
- Technical architecture decisions
- Data-driven decision making

**Common Counterbalance Needs:**
- Customer communications → Inject empathy requirements
- Team management → Inject relationship considerations
- Change management → Inject stakeholder feelings

### Diplomat Profile (NF Combination)

**Traits**: High N + High F
**Natural Strengths**: Meaning-seeking, values-driven, visionary
**Common Blind Spots**: May underweight practical/analytical factors

**Default Amplification Contexts:**
- Vision and mission work
- Culture and values initiatives
- Stakeholder relationship management

**Common Counterbalance Needs:**
- Financial analysis → Inject objectivity requirements
- Technical decisions → Inject data-driven criteria
- Implementation planning → Inject concrete specifics

### Sentinel Profile (SJ Combination)

**Traits**: High S + High J
**Natural Strengths**: Detail-oriented, process-focused, reliable execution
**Common Blind Spots**: May underweight innovation/big-picture factors

**Default Amplification Contexts:**
- Process documentation
- Quality assurance
- Implementation and execution planning

**Common Counterbalance Needs:**
- Innovation projects → Inject possibility thinking
- Strategy development → Inject big-picture vision
- Brainstorming → Inject unconventional thinking

### Explorer Profile (SP Combination)

**Traits**: High S + High P
**Natural Strengths**: Pragmatic, adaptable, action-oriented
**Common Blind Spots**: May underweight planning/commitment factors

**Default Amplification Contexts:**
- Rapid prototyping
- Tactical problem-solving
- Hands-on execution

**Common Counterbalance Needs:**
- Long-term planning → Inject structure and commitment
- Documentation → Inject thoroughness requirements
- Strategic decisions → Inject future-state thinking

---

## Question Quantity Calibration

Adjust the number of clarifying questions based on personality:

### Baseline by Task Complexity

| Complexity | Base Questions |
|------------|----------------|
| Simple | 3-4 |
| Moderate | 5-7 |
| Complex | 8-10 |

### Personality Adjustments

| Factor | Adjustment |
|--------|------------|
| High J (≥65%) | -1 question (wants to proceed) |
| High P (≥65%) | +1 question (comfortable exploring) |
| High A (≥65%) | -1 question (confident in own judgment) |
| High T-identity (≥65%) | +1 question (wants thoroughness) |
| High I (≥65%) | Fewer but deeper questions |
| High E (≥65%) | Can handle more conversational flow |

### Framework-Specific Question Counts

| Framework | Base Questions | Adjustment Rationale |
|-----------|----------------|---------------------|
| CO-STAR | 5-7 | Needs audience, style, tone input |
| ReAct | 4-6 | Task and tool context sufficient |
| Self-Refine | 4-5 | Quality criteria focus |
| Step-Back | 3-5 | Clear question required |
| Skeleton-of-Thought | 4-6 | Scope and structure needs |
| Meta Prompting | 6-8 | Complex requirements gathering |

### Additional Framework-Personality Adjustments

| Factor | Adjustment |
|--------|------------|
| Framework is iterative (Self-Refine, Reflexion) + High-T-identity | +1 question (quality criteria clarification) |
| Framework is agentic (ReAct) + High-J | +1 question (termination criteria) |
| Framework requires abstraction (Step-Back) + High-S | +1 question (principle identification help) |
| Framework is structured (CO-STAR, Skeleton) + High-P | No additional needed (structure is built-in) |

### Minimum and Maximum

- **Minimum**: 2 questions (for very simple tasks)
- **Maximum**: 12 questions (for highly complex tasks with Turbulent users)

---

## Output Format for Personality Calibration

When returning personality calibration data, include the Task-Trait Alignment analysis:

```json
{
  "personality_tier": "full | partial | none",
  "traits_used": {
    "I_E": {"value": 65, "direction": "I", "weight": 0.75},
    "S_N": {"value": 64, "direction": "N", "weight": 0.50},
    "T_F": {"value": 84, "direction": "T", "weight": 1.00},
    "J_P": {"value": 57, "direction": "P", "weight": 0.25},
    "A_T": {"value": 84, "direction": "A", "weight": 1.00}
  },
  "composite_profile": "Analyst",
  "task_cognitive_requirements": [
    "EMPATHY",
    "STRUCTURE",
    "WARM"
  ],
  "trait_alignment": {
    "amplified": [
      {
        "trait": "High N (64%)",
        "requirement_aligned": "VISION",
        "reason": "Can frame the change in context of larger relationship/future"
      }
    ],
    "counterbalanced": [
      {
        "trait": "High T (84%)",
        "requirement_opposed": "EMPATHY",
        "reason": "Task requires emotional acknowledgment that High-T may skip",
        "injection": "Prompt includes explicit requirements for expressing appreciation and acknowledging emotional impact"
      },
      {
        "trait": "High A (84%)",
        "requirement_opposed": "WARM",
        "reason": "Confidence should not come across as dismissive",
        "injection": "Prompt requires warm opening and relationship-affirming language"
      }
    ],
    "neutral": [
      {
        "trait": "High I (65%)",
        "reason": "Introversion/Extraversion not directly relevant to email content"
      },
      {
        "trait": "Borderline P (57%)",
        "reason": "Near midline; no strong adjustment needed"
      }
    ]
  },
  "framework_personality_fit": {
    "natural_fit_score": 0.7,
    "counterbalance_weight": "moderate",
    "special_considerations": [
      "User may resist iteration—emphasise quality benefits"
    ]
  },
  "adjustments_summary": [
    "Amplifying: N-strength for strategic framing",
    "Counterbalancing: T-tendency with empathy requirements",
    "Counterbalancing: A-tendency with warmth requirements"
  ],
  "question_count_adjustment": -1,
  "output_style": {
    "structure": "logical_sections",
    "hedge_level": "minimal",
    "recommendation_style": "confident_direct",
    "injected_requirements": [
      "Open with personal acknowledgment of the customer relationship",
      "Express appreciation before delivering the news",
      "Acknowledge the disappointment this may cause"
    ]
  }
}
```

---

## Application Algorithm

When applying personality calibration with Task-Trait Alignment:

```
1. Check if personality data exists:
   - If NO data → Use neutral defaults, skip to step 7
   - If type only → Set all percentages to 65%
   - If full data → Use provided percentages

2. Identify task cognitive requirements:
   - Look up task category requirements
   - Add content-type specific requirements if applicable
   - Note any explicit requirements from user's task description

3. Calculate adjustment weights for each trait:
   weight = 0.25 if 50-55%
   weight = 0.50 if 55-65%
   weight = 0.75 if 65-75%
   weight = 1.00 if 75%+

4. Perform Task-Trait Alignment analysis:
   For each trait with weight ≥ 0.50:
     For each cognitive requirement:
       - Determine if trait ALIGNS, OPPOSES, or is NEUTRAL
       - Record the alignment and reason

5. Generate adjustment actions:
   - AMPLIFY: Note trait-aligned language/structure to use
   - COUNTERBALANCE: Specify explicit injections needed
   - NEUTRAL: No action needed

6. Compile injected requirements:
   - Gather all counterbalance injections
   - These will be added explicitly to the prompt

7. Document the full analysis for metadata output
```

---

## Neutral Defaults (No Personality Data)

When no personality data is provided, use these defaults:

### Question Style
- Professional, clear, neither warm nor clinical
- Direct but not abrupt
- Standard question count for complexity level

### Prompt Framing
- Balanced between abstract and concrete
- Neither heavily structured nor overly flexible
- Professional tone throughout

### Output Preferences
- Clear structure with logical flow
- Both options and recommendations where appropriate
- Moderate hedging with professional confidence
- Balanced detail level

### No Counterbalancing Applied
- Without personality data, no trait-based counterbalancing is possible
- Prompts should be well-rounded by default
- Include standard coverage of both analytical and empathetic dimensions where relevant to task