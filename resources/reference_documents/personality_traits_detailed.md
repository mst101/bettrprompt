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

