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

## Skeleton-of-Thought Framework Template

**Components**: Skeleton → Parallel Expansion → Assembly

**Best For**: structured explanations, listicles, consultancy responses

**Complexity**: Medium

**Academic Source**: Microsoft Research, ICLR 2024 (reduces latency 2x+, improves quality in 60% of cases)

---

### Template Structure

```
[CONTEXT]
{Background on what you're explaining or creating}

[SUITABILITY CHECK]
This framework is NOT suitable for:
- Step-by-step reasoning tasks (use Chain of Thought)
- Math or coding problems where later steps depend on earlier ones
- Very short answers (use direct prompting)
- Content requiring strict sequential coherence

[TASK]
{What you want the AI to explain, describe, or create}

[PHASE 1: SKELETON GENERATION]
First, provide only the skeleton of your answer as a numbered list.
Each point should be very brief (3-5 words maximum).
Generate 3-10 points covering the full scope of the topic.

Rules for skeleton:
- Points should be independently expandable
- Order should be logical but points shouldn't depend on each other
- Cover the complete scope of the topic

Example format:
1. [Brief point]
2. [Brief point]
3. [Brief point]
...

[PHASE 2: POINT EXPANSION]
Now expand each skeleton point into 1-2 sentences.
Each point can be expanded independently.

For each point:
- Provide sufficient detail to be useful
- Keep expansions concise and focused
- Maintain consistent depth across points

[OUTPUT ASSEMBLY]
Combine expanded points into a coherent response.

[TASK-TRAIT ALIGNMENT INJECTIONS]
{Counterbalance injections if applicable}

{For High-P users who may resist committing to skeleton:
"Complete skeleton BEFORE any expansion—no partial builds
- Constrain expansion to skeleton points—no tangents
- Each point should be independently complete"}

{For High-N users who may expand before skeleton is complete:
"Finish the entire skeleton first
- Do not elaborate until all points are listed
- Use skeleton to ground your thinking"}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-N amplified: "Ensure skeleton captures conceptual breadth"}
{If High-S amplified: "Ensure skeleton covers all practical aspects"}
{If High-T amplified: "Support each point with evidence or reasoning"}
{If High-F counterbalanced: "Include human impact where relevant"}
{If High-J amplified: "Ensure clear transitions between sections"}
{If High-P amplified: "Allow natural flow between ideas"}

[QUALITY CRITERIA]
- Skeleton covers the complete topic
- Each point is independently meaningful
- Expansions are appropriately detailed
- Final assembly is coherent and complete
- {Injected counterbalance criteria}
```

### When to Use Skeleton-of-Thought

Skeleton-of-Thought excels when:
- Answer can be broken into parallel components
- Topic is broad with multiple distinct aspects
- Listicle or structured format is appropriate
- Speed is important (enables parallel processing)
- Consultancy-style comprehensive answers needed

### When NOT to Use Skeleton-of-Thought

Avoid this framework for:
- Mathematical problems requiring sequential steps
- Code where later lines depend on earlier outputs
- Narrative content requiring chronological flow
- Very simple questions needing short answers
- Any task where coherence between points is critical

### Example Skeleton

**Question**: "What are the benefits of remote work?"

**Skeleton**:
1. Flexibility and autonomy
2. Reduced commute time
3. Cost savings
4. Work-life balance
5. Access to global talent
6. Environmental benefits

Each point can then be expanded independently in 1-2 sentences.

### Personality Alignment Notes

| Trait | Alignment | Consideration |
|-------|-----------|---------------|
| High J | **Strong fit** | Natural organisation preference |
| High S | **Strong fit** | Methodical component handling |
| High P | Misaligned | May resist committing to skeleton first |
| High N | Neutral | May need grounding for each component |
