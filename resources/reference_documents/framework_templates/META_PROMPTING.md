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

## Meta Prompting Framework Template

**Components**: Prompt → Evaluate → Refine Prompt → Execute

**Best For**: prompt optimisation, complex task setup, when optimal prompt structure is unclear

**Complexity**: High

**Note**: This is a meta-level framework—it generates or improves other prompts

---

### Template Structure

```
[META-LEVEL INSTRUCTION]
You are a prompt engineering expert. Your task is to create or improve a prompt
that will be used for the following purpose:

[TARGET TASK DESCRIPTION]
{Description of what the final prompt should accomplish}
{Desired output type and quality}
{Constraints and requirements}

[CURRENT PROMPT (if refining)]
{Existing prompt to improve, if applicable}

[TASK-TRAIT ALIGNMENT FOR TARGET USER]
The prompt being created is for a user with these characteristics:
- Personality type: {MBTI type}
- Key traits to consider: {relevant traits}
- Cognitive requirements of their task: {requirements}

{Note: Apply Task-Trait Alignment principles to the GENERATED prompt}

[META-PROMPT INSTRUCTIONS]

**Phase 1: Analysis**
Analyse the target task:
- What type of task is this? (creation, analysis, decision, etc.)
- What framework(s) would be most effective?
- What elements must the prompt include?
- What personality adjustments should be incorporated?

**Phase 2: Prompt Generation**
Create an optimised prompt that:
- Uses an appropriate framework structure
- Includes all necessary components (context, task, constraints, format)
- Incorporates personality-aware language
- Specifies clear quality criteria
- Is clear and unambiguous

**Phase 3: Self-Critique**
Review the generated prompt for:
- Completeness: Does it include all necessary elements?
- Clarity: Is every instruction unambiguous?
- Personality fit: Does language match target user?
- Framework adherence: Does it follow best practices?

**Phase 4: Refinement**
Address any issues identified in critique.
Produce final, polished prompt.

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-N meta-prompters who may skip practical details:
"Ensure the generated prompt includes:
- Specific output format requirements
- Concrete examples where helpful
- Clear success criteria"}

{For High-J meta-prompters who may over-constrain:
"Allow flexibility in the generated prompt for:
- Creative interpretation where appropriate
- Multiple valid approaches
- User context variations"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT]
1. Analysis summary (brief)
2. Generated/Refined prompt
3. Notes on personality adjustments made
4. Suggested model and usage tips

[QUALITY CRITERIA]
- Prompt follows established framework structure
- All ambiguities are resolved
- Personality adjustments are appropriate
- Output format is clearly specified
- Quality criteria are explicit and measurable
- {Injected counterbalance criteria}
```

### When to Use Meta Prompting

Meta Prompting is ideal when:
- Optimal prompt structure is unclear
- Task requirements are complex
- You need to create prompts for others
- Existing prompts need optimisation
- Building prompt libraries or templates

### Meta Prompting for BettrPrompt

This framework is particularly valuable for BettrPrompt itself—using AI to help design better prompts for users. It enables:
- Framework selection guidance
- Personality-aware prompt generation
- Quality assessment of generated prompts
- Iterative prompt improvement

### Example Meta Prompt Use Case

**Target task**: Generate marketing copy for a software product
**User personality**: INTJ

**Meta prompt output would include**:
- Selection of CO-STAR framework (tone/audience control)
- High-T amplification (data-driven language)
- High-N amplification (strategic positioning)
- Counterbalancing for High-J (allow creative flexibility)

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High T | Aligned | Systematic prompt analysis |
| High N | Aligned | Big-picture framework thinking |
| High J | Aligned | Structured prompt generation |
| High S | May need | Abstraction support |
| High P | May need | Structure scaffolding |
