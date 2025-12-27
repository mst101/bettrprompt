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

## Step-Back Prompting Framework Template

**Components**: Abstract → Ground → Reason

**Best For**: STEM problems, knowledge-intensive questions, multi-hop reasoning

**Complexity**: Medium

**Academic Source**: DeepMind 2023 (shows up to 36% improvement over Chain of Thought)

---

### Template Structure

```
[CONTEXT]
{Background on the domain or topic}
{Any relevant constraints or prior knowledge}

[PRIMARY QUESTION]
{The specific question or problem to solve}

[STEP-BACK PROCESS]

**Step 1: Abstraction**
Before answering the primary question, first consider:

"What is a more general question that would help answer this?"
OR
"What principles or concepts underlie this type of problem?"

Generate and answer the step-back question:
- Step-back question: {Higher-level question}
- Answer: {Principles, concepts, or general knowledge}

**Step 2: Grounding**
Connect the abstract principles to the specific question:
- How do these principles apply here?
- What constraints or specifics modify the general case?
- What additional details are relevant?

**Step 3: Reasoning**
Using the principles from Step 1 and grounding from Step 2:
Apply systematic reasoning to answer the primary question.

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-S users who may skip abstraction:
"The step-back question is MANDATORY—research shows skipping it reduces accuracy by 36%
- Before addressing specifics, identify the general category and principles
- Step back from the details—what broader concepts apply?
- Connect specific question to underlying framework or theory"}

{For High-N users who may over-abstract:
"Focus the abstraction on principles directly relevant to THIS question
- Avoid tangential explorations
- Use abstraction as a tool, not an end"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-N amplified: "Connect to broader patterns and frameworks"}
{If High-T amplified: "Identify logical principles and rules that apply"}
{If High-S amplified: "Walk through each specific detail methodically"}
{If High-J amplified: "Structure reasoning with clear logical steps"}

[FINAL ANSWER]
{Clear answer to the primary question}
{If requested: Show how step-back principles were applied}

[QUALITY CRITERIA]
- Step-back question is genuinely more general (not just rephrased)
- Abstract principles are relevant and correctly stated
- Application to specific question is logical and complete
- {Injected counterbalance criteria}
```

### Abstraction Level by Personality

| Trait | Abstraction Style |
|-------|-------------------|
| High N | Conceptual frameworks, theoretical principles |
| High S | Procedural principles, rule-based abstractions |
| High T | Logical axioms, formal principles |
| High F | Human-centred principles, values-based frameworks |

### When to Use Step-Back Prompting

Step-Back Prompting excels when:
- Question requires domain knowledge
- Direct approach leads to errors
- Problem benefits from principled reasoning
- STEM or knowledge-intensive questions
- Multi-hop reasoning is needed

### Example Step-Back Questions

| Primary Question | Step-Back Question |
|------------------|-------------------|
| "What happens to pressure when gas is compressed at constant temp?" | "What are the gas laws and how do they relate pressure, volume, and temperature?" |
| "Why did the Roman Empire fall?" | "What factors typically lead to the decline of empires?" |
| "How do I optimise this SQL query?" | "What are the principles of query optimisation and indexing?" |

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High N | **Strong fit** | Natural abstract thinking |
| High T | **Strong fit** | Principle-based reasoning |
| High S | Misaligned | May resist abstraction—needs explicit justification |
| High F | Neutral | May naturally consider human principles |
