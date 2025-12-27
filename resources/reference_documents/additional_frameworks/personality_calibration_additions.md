# Personality Calibration Additions

## New Cognitive Requirement: Iterative Refinement

### Requirement: Iterative Self-Improvement

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

---

### Requirement: Abstraction Before Specifics

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

---

### Requirement: Structured Parallel Processing

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

---

### Requirement: Agentic Reasoning

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

---

## Updated Task → Requirements Mapping

Add to existing mapping:

| Task Category | Primary Requirements | Secondary Requirements |
|---------------|---------------------|----------------------|
| `CREATION_CONTENT` (quality-critical) | `ITERATIVE`, `CREATIVE` | `STRUCTURE`, `EMPATHY` |
| `CREATION_TECHNICAL` (complex) | `ITERATIVE`, `DETAIL` | `OBJECTIVE`, `STRUCTURE` |
| `PROBLEM_SOLVING` (multi-step) | `AGENTIC`, `OBJECTIVE` | `EXPLORE`, `RISK` |
| `ANALYSIS` (principle-based) | `ABSTRACTION`, `OBJECTIVE` | `DETAIL` |
| `LEARNING` (conceptual) | `ABSTRACTION`, `STRUCTURE` | Varies by learner |
| `RESEARCH` (tool-intensive) | `AGENTIC`, `EXPLORE` | `OBJECTIVE`, `DETAIL` |

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

---

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

---

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

---

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

---

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

## Question Quantity Adjustments for New Frameworks

| Framework | Base Questions | Adjustment Rationale |
|-----------|----------------|---------------------|
| CO-STAR | 5-7 | Needs audience, style, tone input |
| ReAct | 4-6 | Task and tool context sufficient |
| Self-Refine | 4-5 | Quality criteria focus |
| Step-Back | 3-5 | Clear question required |
| Skeleton-of-Thought | 4-6 | Scope and structure needs |
| Meta Prompting | 6-8 | Complex requirements gathering |

### Additional personality adjustments to base:

| Factor | Adjustment |
|--------|------------|
| Framework is iterative (Self-Refine, Reflexion) + High-T-identity | +1 question (quality criteria clarification) |
| Framework is agentic (ReAct) + High-J | +1 question (termination criteria) |
| Framework requires abstraction (Step-Back) + High-S | +1 question (principle identification help) |
| Framework is structured (CO-STAR, Skeleton) + High-P | No additional needed (structure is built-in) |

---

## Output Format Updates

When using new frameworks, include these additional metadata fields:

```json
{
  "framework_used": {
    "name": "Self-Refine",
    "code": "SELF_REFINE",
    "iteration_expected": 2,
    "termination_criteria": "All quality criteria met"
  },
  
  "task_cognitive_requirements": [
    "Iterative Self-Improvement",
    "Objective Analysis"
  ],
  
  "trait_alignment": {
    "amplified": [...],
    "counterbalanced": [
      {
        "trait": "High A (84%)",
        "requirement_opposed": "Iterative Self-Improvement",
        "reason": "Assertive users may accept first draft without genuine critique",
        "injections_added": [
          "Explicit requirement for 3+ improvement areas",
          "Mandatory second refinement cycle",
          "Quality gate preventing premature completion"
        ]
      }
    ],
    "neutral": [...]
  },
  
  "framework_personality_fit": {
    "natural_fit_score": 0.7,
    "counterbalance_weight": "moderate",
    "special_considerations": [
      "User may resist iteration—emphasise quality benefits"
    ]
  }
}
```
