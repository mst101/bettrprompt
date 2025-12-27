# Prompt Templates Additions

## New Framework Templates with Task-Trait Alignment

---

## CO-STAR Framework Template

**Best For**: Content creation, marketing copy, professional communications

CO-STAR excels at tasks requiring precise control over tone and audience targeting. The explicit Style and Tone components make it particularly valuable for High-F users who naturally consider emotional impact, while providing necessary scaffolding for High-T users who might otherwise produce technically correct but tonally misaligned content.

```
[CONTEXT]
{Background information about the task, situation, or problem}
{Include relevant history, constraints, and stakeholder information}

[TASK-TRAIT ALIGNMENT INJECTIONS - if applicable]
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

[OBJECTIVE]
{Clear statement of what you want to achieve}
{Success criteria and specific goals}
{If High-P counterbalanced: "Provide ONE clear recommendation or call-to-action"}

[STYLE]
{Writing style requirements}
Examples: formal, conversational, technical, storytelling, journalistic
{If High-T amplified: "Use data-driven, evidence-based language"}
{If High-F amplified: "Use warm, relationship-building language"}

[TONE]
{Emotional character of the content}
Examples: professional, friendly, authoritative, empathetic, urgent, calm
{Adjust based on Task-Trait Alignment}

[AUDIENCE]
{Detailed audience description}
- Demographics and role
- Knowledge level
- Pain points and motivations
- Decision-making factors
{If High-F amplified: "Leverage natural empathy to deeply understand audience needs"}

[RESPONSE]
{Desired output format}
- Structure (paragraphs, lists, sections)
- Length requirements
- Required elements to include/exclude
{If High-J amplified: "Use clear sections with logical flow"}
{If High-P amplified: "Allow for adaptive structure based on content needs"}

[QUALITY CRITERIA]
The output should:
- {Criterion based on objective}
- {Criterion based on audience needs}
- {If counterbalanced: "Demonstrates [counterbalance requirement]"}
- Match the specified style and tone throughout
```

### CO-STAR Task-Trait Alignment Matrix

| User Trait | Amplify When | Counterbalance When |
|------------|--------------|---------------------|
| High T | Technical content, data-heavy communications | Customer emails, apologies, relationship content |
| High F | Empathetic communications, change management | Objective reports, technical documentation |
| High N | Strategic content, vision documents | How-to guides, step-by-step instructions |
| High S | Procedural content, practical guides | Thought leadership, visionary pieces |
| High J | Structured reports, formal communications | Creative content, exploratory pieces |
| High P | Brainstorming outputs, flexible content | Decisive recommendations, clear CTAs |

---

## ReAct Framework Template

**Best For**: Agentic tasks, tool-using workflows, information retrieval, multi-step problem-solving

ReAct is particularly valuable for High-N users who see possibilities but need structured execution, and provides scaffolding for High-S users to think through novel problems systematically.

```
[ROLE]
You are a {role} equipped with the ability to reason through problems step-by-step
and take actions to gather information or execute tasks.

[CONTEXT]
{Background on the problem, available tools, and constraints}

[TASK-TRAIT ALIGNMENT INJECTIONS]
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

[AVAILABLE TOOLS/ACTIONS]
{List of available actions the AI can take}
Example:
- Search: Query external knowledge bases
- Calculate: Perform mathematical operations
- Lookup: Retrieve specific facts
- Verify: Cross-check information

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

[FINAL ANSWER]
Synthesise findings into a clear, complete response.
{If High-T amplified: "Support with evidence gathered during reasoning"}
{If High-F counterbalanced: "Acknowledge limitations and uncertainties"}
{If High-A counterbalanced: "Include caveats where appropriate"}

[QUALITY CRITERIA]
- Each thought-action-observation cycle is documented
- Actions are purposeful and move toward the goal
- Final answer directly addresses the original task
- {If counterbalanced: "Shows consideration of [counterbalance requirement]"}
```

---

## Self-Refine Framework Template

**Best For**: Quality-critical content, code refinement, iterative improvement tasks

Self-Refine naturally suits High-T-identity (Turbulent) users who appreciate thoroughness and validation, while helping High-A users who might otherwise accept first-draft quality.

```
[CONTEXT]
{Background on what needs to be created or refined}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{For High-A users who may skip refinement:
"Before finalising, you MUST:
- Identify at least 3 weaknesses in your initial output
- Explicitly state what could be improved
- Generate a meaningfully improved version"}

{For High-J users who may resist iteration:
"Approach this as a multi-pass process:
- First pass: Get ideas down
- Second pass: Critical evaluation
- Third pass: Refinement
Do not combine passes or skip the evaluation step"}

[TASK]
{What you want to create or accomplish}

[QUALITY CRITERIA]
{Explicit criteria for evaluating the output}
- Criterion 1: {Specific quality measure}
- Criterion 2: {Specific quality measure}
- Criterion 3: {Specific quality measure}

[ITERATION STRUCTURE]

**Phase 1: Initial Generation**
Create an initial response to the task.
{If High-N amplified: "Focus on big-picture structure first"}
{If High-S amplified: "Ensure all required details are included"}

**Phase 2: Self-Critique**
Review your output against each quality criterion:

For each criterion, assess:
- Does the output meet this criterion? (Yes/Partially/No)
- Specific examples of what works or doesn't
- Concrete suggestions for improvement

{If High-T amplified: "Use objective criteria ruthlessly"}
{If High-F counterbalanced: "Also consider emotional resonance and reader experience"}

**Phase 3: Refinement**
Based on your critique, generate an improved version that addresses:
- All "No" assessments must be fixed
- All "Partially" assessments should be improved
- Maintain or enhance what already works

[ITERATION LIMIT]
Perform {2-3} refinement cycles, or until all criteria are met.
{If High-T-identity amplified: "Continue refining until confident in quality"}
{If High-A counterbalanced: "Do not stop after first revision—genuine improvement required"}

[FINAL OUTPUT]
Present the final refined version with a brief summary of key improvements made.
```

---

## Step-Back Prompting Template

**Best For**: STEM problems, knowledge-intensive questions, complex reasoning requiring principled thinking

Step-Back is particularly valuable for High-S users who may get lost in details, helping them access relevant principles. It also aids High-T users in systematic reasoning.

```
[CONTEXT]
{Background on the specific problem or question}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{For High-S users who may skip abstraction:
"BEFORE attempting the specific question:
- Identify the general category of problem
- Recall fundamental principles that apply
- Only then apply to the specific case"}

{For High-P users who may want to explore:
"Focus the abstraction on principles directly relevant to THIS question
- Avoid tangential explorations
- Use abstraction as a tool, not an end"}

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

{If High-N amplified: "Connect to broader patterns and frameworks"}
{If High-T amplified: "Identify logical principles and rules that apply"}

**Step 2: Grounding**
Connect the abstract principles to the specific question:
- How do these principles apply here?
- What constraints or specifics modify the general case?
- What additional details are relevant?

**Step 3: Reasoning**
Using the principles from Step 1 and grounding from Step 2:
Apply systematic reasoning to answer the primary question.

{If High-S amplified: "Walk through each specific detail methodically"}
{If High-J amplified: "Structure reasoning with clear logical steps"}

[FINAL ANSWER]
{Clear answer to the primary question}
{If requested: Show how step-back principles were applied}

[QUALITY CRITERIA]
- Step-back question is genuinely more general (not just rephrased)
- Abstract principles are relevant and correctly stated
- Application to specific question is logical and complete
- {If counterbalanced: "[Counterbalance requirement]"}
```

---

## Skeleton-of-Thought Template

**Best For**: Structured explanations, consultancy responses, content where parallel development is possible

Skeleton-of-Thought suits High-J users who naturally structure thinking, while helping High-P users impose organisation without constraining creativity.

```
[CONTEXT]
{Background on what you're explaining or creating}

[TASK-TRAIT ALIGNMENT NOTE]
This framework is NOT suitable for:
- Step-by-step reasoning tasks (use Chain of Thought)
- Math or coding problems where later steps depend on earlier ones
- Very short answers (use direct prompting)

{For High-J users: This framework aligns with your natural preference for structure}
{For High-P users: The skeleton provides structure while allowing creative expansion}

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

{If High-N amplified: "Ensure skeleton captures conceptual breadth"}
{If High-S amplified: "Ensure skeleton covers all practical aspects"}

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

{If High-T amplified: "Support each point with evidence or reasoning"}
{If High-F counterbalanced: "Include human impact where relevant"}

[OUTPUT ASSEMBLY]
Combine expanded points into a coherent response.
{If High-J amplified: "Ensure clear transitions between sections"}
{If High-P amplified: "Allow natural flow between ideas"}

[QUALITY CRITERIA]
- Skeleton covers the complete topic
- Each point is independently meaningful
- Expansions are appropriately detailed
- Final assembly is coherent and complete
```

---

## Meta Prompting Template

**Best For**: Prompt optimisation, complex task setup, when optimal prompt structure is unclear

Meta Prompting is a powerful tool for BettrPrompt itself—using AI to help design better prompts. Particularly valuable when task requirements are complex or unclear.

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
```

---

## Framework Interaction Guidelines

### When to Combine Frameworks

Some frameworks work well in combination:

| Primary Framework | Can Combine With | Use Case |
|-------------------|------------------|----------|
| CO-STAR | Self-Refine | High-quality content with iterative improvement |
| ReAct | Step-Back | Complex problems needing both abstraction and action |
| Chain of Thought | Self-Refine | Reasoning tasks requiring verification |
| Skeleton-of-Thought | CO-STAR | Structured content with tone precision |
| Any framework | Meta Prompting | When optimal prompt structure is unclear |

### Framework Selection Quick Reference

| Task Characteristic | Recommended Framework |
|---------------------|----------------------|
| Needs precise tone/audience control | CO-STAR |
| Quality-critical, benefits from iteration | Self-Refine |
| Requires external tools or research | ReAct |
| Benefits from abstraction before specifics | Step-Back |
| Can be broken into parallel components | Skeleton-of-Thought |
| Complex or unclear task setup | Meta Prompting |
| Sequential reasoning required | Chain of Thought (existing) |
| Multiple possible paths to explore | Tree of Thought (existing) |

---

## Model Recommendations for New Frameworks

| Framework | Primary Model | Rationale |
|-----------|---------------|-----------|
| CO-STAR | Claude Opus 4.5 | Nuanced tone and style control |
| ReAct | Claude Opus 4.5 / GPT-4 | Complex multi-step reasoning |
| Self-Refine | Claude Opus 4.5 | Strong self-critique capabilities |
| Reflexion | Claude Opus 4.5 | Memory and reflection handling |
| Step-Back | Claude Opus 4.5 / GPT-4 | Abstract reasoning |
| Skeleton-of-Thought | Claude Sonnet 4.5 / GPT-4o | Efficient structured generation |
| Meta Prompting | Claude Opus 4.5 | Nuanced prompt engineering |
