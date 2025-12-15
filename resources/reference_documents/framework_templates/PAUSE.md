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

## PAUSE Framework Template

**Components**: Prepare, Assess, Uncover, Synthesize, Execute

**Best For**: management decisions

**Complexity**: Medium

---

### Template Structure

```
[CONTEXT]
{Background information from user responses}

[PREPARE]
{Prepare details}

[ASSESS]
{Assess details}

[UNCOVER]
{Uncover details}

[SYNTHESIZE]
{Synthesize details}

[EXECUTE]
{Execute details}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}

[QUALITY CRITERIA]
- {Criterion based on user's success definition}
- {Criterion based on constraints}
- {Injected counterbalance criteria}
```
