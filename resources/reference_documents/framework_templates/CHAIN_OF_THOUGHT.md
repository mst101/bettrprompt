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

## Chain of Thought Framework Template

**Components**: Intro, Breakdown, Logical Progression, Conclusion

**Best For**: complex reasoning

**Complexity**: High

---

### Template Structure

```
[CONTEXT]
{Background information from user responses}

[INTRO]
{Intro details}

[BREAKDOWN]
{Breakdown details}

[LOGICAL PROGRESSION]
{Logical Progression details}

[CONCLUSION]
{Conclusion details}

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
