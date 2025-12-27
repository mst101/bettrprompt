# Framework Template

## Prompt Structure Overview

Every optimised prompt follows this general structure:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROLE ASSIGNMENT (if framework uses roles)                            │
├─────────────────────────────────────────────────────────────────────────┤
│ 2. CONTEXT BLOCK                                                        │
├─────────────────────────────────────────────────────────────────────────┤
│ 3. TASK SPECIFICATION (framework-specific structure)                    │
├─────────────────────────────────────────────────────────────────────────┤
│ 4. COUNTERBALANCE INJECTIONS (if applicable)                            │
├─────────────────────────────────────────────────────────────────────────┤
│ 5. CONSTRAINTS                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│ 6. OUTPUT SPECIFICATION (with amplification adjustments)                │
├─────────────────────────────────────────────────────────────────────────┤
│ 7. EXAMPLES (if framework includes them)                                │
├─────────────────────────────────────────────────────────────────────────┤
│ 8. QUALITY CRITERIA                                                     │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Self-Refine Framework Template

**Components**: Generate → Critique → Refine (Iterative)

**Best For**: quality-critical content, code refinement, writing improvement

**Complexity**: High

**Academic Source**: Madaan et al. 2023

---

### Template Structure

```
[CONTEXT]
{Background on what needs to be created or refined}

[TASK]
{What you want to create or accomplish}

[QUALITY CRITERIA]
{Explicit criteria for evaluating the output}
- Criterion 1: {Specific quality measure}
- Criterion 2: {Specific quality measure}
- Criterion 3: {Specific quality measure}

[ITERATION STRUCTURE]

**Phase 1: Initial Generation**
Create an initial response to the task.

**Phase 2: Self-Critique**
Review your output against each quality criterion:

For each criterion, assess:
- Does the output meet this criterion? (Yes/Partially/No)
- Specific examples of what works or doesn't
- Concrete suggestions for improvement

**Phase 3: Refinement**
Based on your critique, generate an improved version that addresses:
- All "No" assessments must be fixed
- All "Partially" assessments should be improved
- Maintain or enhance what already works

[ITERATION LIMIT]
Perform {2-3} refinement cycles, or until all criteria are met.

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-A users who may skip refinement:
"Before finalising, you MUST:
- Identify at least 3 weaknesses in your initial output
- Explicitly state what could be improved
- Generate a meaningfully improved version"}

{For High-J users who may resist iteration:
"Approach this as a multi-pass process:
- First pass: Get ideas down
- Second pass: Critical evaluation
- Third pass: Refinement
Do not combine passes or skip the evaluation step"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-N amplified: "Focus on big-picture structure first"}
{If High-S amplified: "Ensure all required details are included"}
{If High-T amplified: "Use objective criteria ruthlessly"}
{If High-F counterbalanced: "Also consider emotional resonance and reader experience"}

[FINAL OUTPUT]
Present the final refined version with a brief summary of key improvements made.

[QUALITY CRITERIA]
- {Criterion based on task requirements}
- {Criterion based on iteration completeness}
- {Injected counterbalance criteria}
```

### Iteration Cycle Adjustments by Personality

| Trait | Cycles | Rationale |
|-------|--------|-----------|
| High A | 3+ required | Ensure genuine critique |
| High T-identity | 2 with quality check | Prevent over-iteration |
| High J | 2 minimum, explicitly mandated | Force iteration |
| High P | 2-3 with convergence check | Prevent endless tweaking |

### When to Use Self-Refine

Self-Refine is ideal when:
- Output quality is critical
- First-pass output is likely insufficient
- Clear quality criteria can be defined
- Time allows for iteration
- Task benefits from self-evaluation

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High T-identity (Turbulent) | **Strong fit** | Natural quality sensitivity |
| High P | Aligned | Comfortable with non-linear improvement |
| High J | Misaligned | May resist iteration—needs explicit mandating |
| High A | Misaligned | May accept first draft—needs critique reinforcement |
