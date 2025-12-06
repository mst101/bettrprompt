# Personality Calibration Reference Document

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

#### Requirement: Empathy & Stakeholder Awareness

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

#### Requirement: Big-Picture Strategic Vision

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

#### Requirement: Detailed Execution Planning

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

#### Requirement: Decisive Recommendations

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

#### Requirement: Exploring Multiple Options

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

#### Requirement: Objective Analysis

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

#### Requirement: Risk Awareness

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

#### Requirement: Creative Innovation

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

#### Requirement: Structured Communication

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

#### Requirement: Warm/Relational Tone

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

---

### Task Category to Cognitive Requirements Mapping

| Task Category | Primary Requirements | Secondary Requirements |
|---------------|---------------------|----------------------|
| DECISION | Objective Analysis, Risk Awareness | Decisive Recommendations |
| STRATEGY | Big-Picture Vision, Detailed Planning | Risk Awareness, Decisive Recommendations |
| ANALYSIS | Objective Analysis, Detailed Planning | Risk Awareness |
| CREATION_CONTENT | Varies by content type | Structured Communication |
| CREATION_TECHNICAL | Detailed Planning, Objective Analysis | Structured Communication |
| IDEATION | Creative Innovation, Exploring Options | Big-Picture Vision |
| PROBLEM_SOLVING | Objective Analysis, Detailed Planning | Risk Awareness, Exploring Options |
| LEARNING | Structured Communication | Varies by learner |
| PERSUASION | Empathy & Stakeholder Awareness, Warm Tone | Structured Communication |
| FEEDBACK | Empathy & Stakeholder Awareness, Objective Analysis | Warm Tone |
| RESEARCH | Objective Analysis, Exploring Options | Detailed Planning |
| GOAL_SETTING | Detailed Planning, Decisive Recommendations | Big-Picture Vision |

### Content-Type Specific Requirements

For CREATION_CONTENT tasks, requirements vary by content type:

| Content Type | Primary Requirements |
|--------------|---------------------|
| Customer email | Empathy, Warm Tone, Structured Communication |
| Marketing copy | Empathy, Creative Innovation, Decisive Recommendations |
| Technical blog | Objective Analysis, Structured Communication, Detailed Planning |
| Executive summary | Decisive Recommendations, Structured Communication |
| Apology/bad news | Empathy, Warm Tone, Risk Awareness |
| Sales pitch | Empathy, Decisive Recommendations, Creative Innovation |
| Internal memo | Structured Communication, Objective Analysis |
| Social media | Creative Innovation, Warm Tone |

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

## Trait Influence Matrices

### Mind Dimension: Introversion (I) ↔ Extraversion (E)

#### High Introversion (I ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | Fewer questions; allow depth over breadth |
| Question phrasing | "What is your thinking on...", "How do you see..." |
| Prompt framing | Emphasise independent analysis, solo deep-work |
| Output preference | Written depth, comprehensive analysis |

**When to Counterbalance (task requires external/social focus):**
- Inject: "Consider how stakeholders will perceive this"
- Add: Requirements for socialising ideas, getting buy-in
- Include: External communication components

#### High Extraversion (E ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | Conversational flow; can handle more questions |
| Question phrasing | "Tell me about...", "What's the situation with..." |
| Prompt framing | Collaborative context, stakeholder involvement |
| Output preference | Shareable outputs, presentation-ready |

**When to Counterbalance (task requires deep solo analysis):**
- Inject: "Take time to think through this independently"
- Add: Requirements for depth over breadth
- Include: Reflection and analysis components before action

---

### Energy Dimension: Intuitive (N) ↔ Observant/Sensing (S)

#### High Intuitive (N ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | "What possibilities...", "What patterns do you see..." |
| Prompt framing | Big-picture thinking, vision, future state |
| Output preference | Frameworks, models, strategic implications |
| Level of detail | Higher-level; comfortable with abstraction |

**When to Counterbalance (task requires concrete detail):**
- Inject: "Provide specific, actionable steps with dates and owners"
- Add: Requirements for measurable milestones
- Include: "Ground each concept in a concrete example"
- Force: Step-by-step implementation details

#### High Observant/Sensing (S ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | "What specifically...", "What exactly happened..." |
| Prompt framing | Current reality, tangible outcomes, practical steps |
| Output preference | Step-by-step instructions, concrete deliverables |
| Level of detail | Granular; needs grounding in specifics |

**When to Counterbalance (task requires big-picture vision):**
- Inject: "Step back and consider the broader strategic implications"
- Add: Requirements for future-state visioning
- Include: "Identify patterns across these examples"
- Force: Connection to larger goals and vision

---

### Nature Dimension: Thinking (T) ↔ Feeling (F)

#### High Thinking (T ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | "What are the criteria...", "What data supports..." |
| Prompt framing | Data-driven, logical structure, objective analysis |
| Output preference | Evidence, metrics, logical reasoning |
| Tone | Direct, efficient, analytical |

**When to Counterbalance (task requires empathy/warmth):**
- Inject: "Acknowledge the emotional impact on [stakeholder]"
- Add: Requirements for relationship-affirming language
- Include: "Consider how this will make the recipient feel"
- Force: Personal acknowledgment before business content
- Add: "Express appreciation for [relationship/loyalty/effort]"

#### High Feeling (F ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | "Who is affected...", "What matters most to..." |
| Prompt framing | Human impact, meaning, stakeholder considerations |
| Output preference | Narrative context, relationship implications |
| Tone | Warmer, acknowledging, contextual |

**When to Counterbalance (task requires objective analysis):**
- Inject: "Base your recommendation on data and evidence"
- Add: Requirements for separating facts from feelings
- Include: "Set aside personal preferences and evaluate objectively"
- Force: Quantitative criteria before qualitative considerations

---

### Tactics Dimension: Judging (J) ↔ Prospecting/Perceiving (P)

#### High Judging (J ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | Seeks definitive answers, closure-oriented |
| Prompt framing | Clear structure, defined outcomes, deadlines |
| Output preference | Single recommendation, action plan, definitive |
| Structure | Highly organised, clear sections |

**When to Counterbalance (task requires exploration):**
- Inject: "Generate at least 5 options before evaluating any"
- Add: Requirements for exploring alternatives
- Include: "What other approaches haven't been considered?"
- Force: Delay recommendation until options fully explored
- Add: "Resist the urge to conclude prematurely"

#### High Perceiving (P ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | Comfortable with "it depends", exploratory |
| Prompt framing | Exploratory, adaptive, flexible outcomes |
| Output preference | Multiple options, flexibility built in |
| Structure | Allow for adaptation, conditional paths |

**When to Counterbalance (task requires decisiveness):**
- Inject: "Provide ONE clear recommendation with confidence"
- Add: Requirements for commitment to specific action
- Include: "If you had to choose today, which option and why?"
- Force: Prioritised ranking, not just options list
- Add: "Limit caveats to only the most critical"

---

### Identity Dimension: Assertive (A) ↔ Turbulent (T)

#### High Assertive (A ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | Direct, efficient, no hand-holding needed |
| Prompt framing | Confident language, decisive direction |
| Output preference | Bold recommendations, minimal hedging |
| Risk framing | Acknowledge but don't overemphasise |

**When to Counterbalance (task requires risk awareness):**
- Inject: "Explicitly identify what could go wrong"
- Add: Requirements for downside analysis
- Include: "What would cause this to fail?"
- Force: Risk mitigation section
- Add: "Consider the perspective of a skeptic"

#### High Turbulent (T-identity ≥ 60%)

| Aspect | Standard Adjustment |
|--------|---------------------|
| Question style | May benefit from validation, thoroughness |
| Prompt framing | Acknowledge risks, build in checkpoints |
| Output preference | Hedged recommendations, confidence levels |
| Risk framing | Explicitly address potential pitfalls |

**When to Counterbalance (task requires confidence):**
- Inject: "State your recommendation with confidence"
- Add: Requirements for decisive language
- Include: "Use 'recommend' not 'might consider'"
- Force: Limit caveats to one sentence maximum
- Add: "Present as an expert giving clear guidance"

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
    "Empathy & Stakeholder Awareness",
    "Structured Communication",
    "Warm/Relational Tone"
  ],
  "trait_alignment": {
    "amplified": [
      {
        "trait": "High N (64%)",
        "requirement": "Big-Picture Vision",
        "reason": "Can frame the change in context of larger relationship/future"
      }
    ],
    "counterbalanced": [
      {
        "trait": "High T (84%)",
        "requirement": "Empathy & Stakeholder Awareness",
        "reason": "Task requires emotional acknowledgment that High-T may skip",
        "injection": "Prompt includes explicit requirements for expressing appreciation and acknowledging emotional impact"
      },
      {
        "trait": "High A (84%)",
        "requirement": "Warm/Relational Tone",
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
