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

## Reflexion Framework Template

**Components**: Act → Evaluate → Reflect → Learn → Act

**Best For**: complex problem-solving, code generation, multi-step reasoning tasks

**Complexity**: High

**Academic Source**: Shinn et al. 2023

---

### Template Structure

```
[ROLE]
You are an agent capable of learning from your mistakes through
verbal reflection and self-improvement.

[CONTEXT]
{Background on the problem and available resources}
{Previous attempts and their outcomes, if any}

[TASK]
{The problem to solve or goal to achieve}

[REFLECTION STRUCTURE]

**Phase 1: Action**
Execute your initial approach to the task.
Document your reasoning and choices.

**Phase 2: Evaluation**
Assess the outcome:
- Did the attempt succeed? (Yes/No/Partial)
- What specific errors or issues occurred?
- What worked well?

**Phase 3: Reflection**
Generate verbal reflection on your attempt:
- What went wrong and why?
- What assumptions were incorrect?
- What would you do differently?

**Phase 4: Memory Update**
Store key learnings for future reference:
- Specific pitfalls to avoid
- Successful strategies to repeat
- Corrected assumptions

**Phase 5: Retry (if needed)**
Using reflections and memory, attempt the task again.
Explicitly reference what you learned from previous attempts.

[MEMORY BANK]
{Store reflections from previous attempts here}
- Attempt 1: {Reflection and learnings}
- Attempt 2: {Reflection and learnings}
- ...

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-A users who may not reflect deeply:
"After each attempt, you MUST identify:
- At least 2 specific errors or suboptimal choices
- The root cause of each issue
- A concrete change for the next attempt"}

{For High-J users who may conclude prematurely:
"Do not declare success until:
- All task requirements are verifiably met
- You have explicitly checked against success criteria
- Edge cases have been considered"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}
{Maximum number of retry attempts}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}

[SUCCESS CRITERIA]
{Clear definition of task completion}
{Verification steps to confirm success}

[QUALITY CRITERIA]
- {Criterion based on task requirements}
- {Criterion based on reflection quality}
- {Criterion based on learning application}
- {Injected counterbalance criteria}
```

### How Reflexion Differs from Self-Refine

| Aspect | Self-Refine | Reflexion |
|--------|-------------|-----------|
| Focus | Output quality improvement | Learning from failures |
| Memory | No persistent memory | Maintains reflection memory |
| Iteration trigger | Quality criteria | Task failure |
| Best for | Content refinement | Complex problem-solving |

### When to Use Reflexion

Reflexion excels when:
- Task has a clear success/failure condition
- Multiple attempts may be needed
- Learning from mistakes improves future attempts
- Problem-solving benefits from reflection
- Code generation or debugging tasks

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High T | Aligned | Logical failure analysis |
| High P | Aligned | Comfortable with iterative exploration |
| High T-identity | Aligned | Appreciates thorough verification |
| High J | Misaligned | May resist multiple attempts—needs explicit permission |
| High A | Misaligned | May not reflect deeply—needs structured reflection prompts |
