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

## CRAFT Framework Template

**Components**: Context, Request, Action, Frame, Template (+ Examples, Develop)

**Best For**: repeatable prompts, content generation, structured outputs

**Complexity**: Medium-High

**Also known as**: CRAFTED (when including Examples and Develop components)

---

### Template Structure

```
[CONTEXT]
{Persona, tone, audience—who the AI should be}
{Background information and situational context}

[REQUEST]
{The specific task or question}
{Clear statement of what needs to be accomplished}

[ACTION]
{Steps or methodology to follow}
{Process the AI should use to complete the request}

[FRAME]
{Constraints and boundaries}
{What to include and exclude}
{Limitations and scope boundaries}

[TEMPLATE]
{Desired output format and structure}
{Specific layout requirements}
{Section headers or format specifications}

[EXAMPLES] (optional)
{Sample outputs to guide style}
{Reference materials showing desired quality}

[DEVELOP] (optional)
{Iteration and refinement instructions}
{How to improve upon initial output}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-N users who may ignore Frame constraints:
"Strictly adhere to the Frame boundaries—do not expand scope"}

{For High-J users who may resist Develop iteration:
"The Develop phase is mandatory—initial output is always a draft"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}

[QUALITY CRITERIA]
- {Criterion based on Request fulfillment}
- {Criterion based on Frame adherence}
- {Criterion based on Template compliance}
- {Injected counterbalance criteria}
```

### When to Use CRAFT

CRAFT excels when:
- You need repeatable, consistent outputs
- Precise constraints are important
- Output format must follow a specific template
- Multiple similar tasks will use the same prompt structure

### Extended CRAFTED Version

For complex tasks requiring iteration, use the full CRAFTED framework:
- **C**ontext: Who and why
- **R**equest: What to do
- **A**ction: How to do it
- **F**rame: Boundaries and constraints
- **T**emplate: Output structure
- **E**xamples: Reference outputs
- **D**evelop: Iteration instructions

### Personality Alignment Notes

| Trait | Emphasis | Consideration |
|-------|----------|---------------|
| High T | Action (methodology), Frame (constraints) | Appreciates systematic approach |
| High F | Context (persona), Examples (tone reference) | Benefits from style examples |
| High N | Request (big picture), Develop (refinement) | May need Frame reinforcement |
| High S | Template (format), Action (steps) | Natural fit for detailed structure |
| High J | All components | Loves comprehensive framework |
| High P | Context, Request | May resist strict Template/Frame |
