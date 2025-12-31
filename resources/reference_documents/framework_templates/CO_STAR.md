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

## CO-STAR Framework Template

**Components**: Context, Objective, Style, Tone, Audience, Response

**Best For**: content creation, marketing copy, professional communications

**Complexity**: Medium

**Origin**: Award-winning framework from Singapore's first GPT-4 Prompt Engineering competition (Sheila Teo)

---

### Template Structure

```
[CONTEXT]
{Background information about the task, situation, or problem}
{Include relevant history, constraints, and stakeholder information}

[OBJECTIVE]
{Clear statement of what you want to achieve}
{Success criteria and specific goals}

[STYLE]
{Writing style requirements}
Examples: formal, conversational, technical, storytelling, journalistic

[TONE]
{Emotional character of the content}
Examples: professional, friendly, authoritative, empathetic, urgent, calm

[AUDIENCE]
{Detailed audience description}
- Demographics and role
- Knowledge level
- Pain points and motivations
- Decision-making factors

[RESPONSE]
{Desired output format}
- Structure (paragraphs, lists, sections)
- Length requirements
- Required elements to include/exclude

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-T users on empathy-requiring tasks:
"When developing this content, ensure you:
- Consider how the audience will emotionally respond
- Use language that builds rapport and connection
- Acknowledge the reader's perspective and concerns"}

{For High-N users needing concrete detail:
"Ground your content with:
- Specific examples and scenarios
- Concrete action items, not just concepts
- Measurable outcomes where applicable"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-J amplified: "Use clear sections with logical flow"}
{If High-P amplified: "Allow for adaptive structure based on content needs"}

[QUALITY CRITERIA]
- {Criterion based on objective}
- {Criterion based on audience needs}
- Match the specified style and tone throughout
- {Injected counterbalance criteria}
```

### Personality Alignment Notes

| Trait | Emphasis | Consideration |
|-------|----------|---------------|
| High T | Objective, Context, Response format | May need counterbalancing for Tone/Audience empathy |
| High F | Style, Tone, Audience | Natural fit—leverage empathy |
| High N | Objective (strategic), Context (big picture) | May resist Response format constraints |
| High S | Context (detailed), Response (specific format) | May overliteralise Style |
| High J | All components (loves structure) | Natural fit for framework |
| High P | Context, Objective | May want flexibility in Response format |