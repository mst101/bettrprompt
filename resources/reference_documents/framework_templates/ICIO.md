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

## ICIO Framework Template

**Components**: Instruction, Context, Input, Output

**Best For**: data processing, content transformation, structured tasks

**Complexity**: Low

---

### Template Structure

```
[INSTRUCTION]
{The specific task or action required}
{Clear directive of what needs to be done}

[CONTEXT]
{Background and situational information}
{Why this task is being performed}
{Any relevant constraints or requirements}

[INPUT]
{The data or content to be processed}
{Source material, raw data, or reference content}

[OUTPUT]
{Desired format and style of response}
{Structure, length, and format specifications}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-N users who may skip detail:
"Ensure all input data is explicitly addressed in output"}

{For High-P users who may vary format:
"Follow the exact output structure specified"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}

[QUALITY CRITERIA]
- {Criterion based on instruction completeness}
- {Criterion based on output format adherence}
- {Injected counterbalance criteria}
```

### When to Use ICIO

ICIO is ideal for straightforward transformation tasks where:
- Input data is clearly defined
- Output format is specific
- Task is procedural rather than creative
- Minimal ambiguity exists in requirements

### Personality Alignment Notes

| Trait | Emphasis | Consideration |
|-------|----------|---------------|
| High T | Instruction clarity, Output precision | Natural fit for structured processing |
| High S | Input handling, Output format | Excels at detailed transformation |
| High J | All components | Appreciates clear structure |
| High P | May need | Explicit output format constraints |
