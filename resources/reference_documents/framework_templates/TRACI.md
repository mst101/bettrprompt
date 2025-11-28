# Framework Template

## Prompt Structure Overview

Every optimised prompt follows this general structure:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROLE ASSIGNMENT (if framework uses roles)                           │
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


## TRACI Framework Template

**Components**: Task, Role, Audience, Create, Intent

**Best For**: marketing, education

**Complexity**: Medium

---

### Template Structure

```
[CONTEXT]
{Background information from user responses}

[TASK]
{Task details}

[ROLE]
{Role details}

[AUDIENCE]
{Audience details}

[CREATE]
{Create details}

[INTENT]
{Intent details}

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
