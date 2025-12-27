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

## ReAct Framework Template

**Components**: Thought → Action → Observation (Interleaved)

**Best For**: tool-using tasks, information retrieval, multi-step decision-making, agentic workflows

**Complexity**: High

**Academic Source**: Yao et al. 2023

---

### Template Structure

```
[ROLE]
You are a {role} equipped with the ability to reason through problems step-by-step
and take actions to gather information or execute tasks.

[CONTEXT]
{Background on the problem, available tools, and constraints}

[AVAILABLE TOOLS/ACTIONS]
{List of available actions the AI can take}
Example:
- Search: Query external knowledge bases
- Calculate: Perform mathematical operations
- Lookup: Retrieve specific facts
- Verify: Cross-check information
- Execute: Run code or commands

[TASK]
{The problem to solve or goal to achieve}

[REASONING STRUCTURE]
For each step, follow this pattern:

**Thought**: {Reason about current state and what to do next}
- What do I know?
- What do I need to find out?
- What action would help?

**Action**: {Specific action to take}
[Action Type]: [Action Input]

**Observation**: {Result of the action}
{Record what was learned}

**Repeat** until:
- Task is complete
- Sufficient information gathered
- Clear answer can be provided

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-J users who may conclude prematurely:
"Before reaching a final answer:
- Generate at least 2-3 different approaches
- Explicitly consider what information might be missing
- Challenge your initial assumptions"}

{For High-N users who may skip practical verification:
"For each thought, ground it with:
- Specific evidence or data
- Concrete verification steps
- Practical feasibility check"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}
{Maximum number of action cycles}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-T amplified: "Support with evidence gathered during reasoning"}
{If High-F counterbalanced: "Acknowledge limitations and uncertainties"}
{If High-A counterbalanced: "Include caveats where appropriate"}

[FINAL ANSWER]
Synthesise findings into a clear, complete response.

[QUALITY CRITERIA]
- Each thought-action-observation cycle is documented
- Actions are purposeful and move toward the goal
- Final answer directly addresses the original task
- {Injected counterbalance criteria}
```

### Loop-Termination Considerations by Personality

| Trait | Risk | Mitigation |
|-------|------|------------|
| High J | Premature termination | "Continue until 3+ observations gathered" |
| High A | Over-confidence in initial answer | "Verify conclusion with at least one more action" |
| High P | Never-ending exploration | "Conclude when question is definitively answered" |
| High T-identity | Excessive verification loops | "Set maximum iteration count" |

### When to Use ReAct

ReAct excels when:
- Task requires external information gathering
- Multiple tools or data sources are available
- Problem-solving benefits from explicit reasoning
- Verification of intermediate results is important
- Agentic workflows with tool calling

### Example ReAct Cycle

```
Thought: I need to find the current CEO of Apple to answer this question.
Action: Search["Apple CEO 2024"]
Observation: Tim Cook has been CEO of Apple since 2011.

Thought: Now I have the information needed.
Action: None (ready to answer)
Final Answer: Tim Cook is the current CEO of Apple.
```

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High T | **Strong fit** | Logical step-by-step reasoning |
| High P | **Strong fit** | Adaptive problem-solving |
| High J | Partially aligned | May need counterbalancing for exploration |
| High N | Misaligned | May skip verification actions—needs grounding |
