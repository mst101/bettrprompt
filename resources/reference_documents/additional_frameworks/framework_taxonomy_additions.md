# Framework Taxonomy Additions

## New Frameworks to Add

Based on comprehensive research, the following frameworks are recommended additions to BettrPrompt's framework library. These frameworks address gaps in your current coverage, particularly around iterative refinement, agentic workflows, and structured prompting for specific use cases.

---

## NEW: Structured Clarity Frameworks

### CO-STAR (`COSTAR`)
**Context, Objective, Style, Tone, Audience, Response**

Originally developed by data scientist Sheila Teo, this framework won Singapore's first GPT-4 Prompt Engineering competition. It's one of the most structured and adaptable prompt engineering frameworks in use today.

**Components:**
- **Context**: Background information on the task
- **Objective**: What you want the AI to accomplish
- **Style**: The desired writing style (formal, casual, technical)
- **Tone**: The emotional character (professional, friendly, authoritative)
- **Audience**: Who will receive the output
- **Response**: The desired format (paragraph, list, JSON, etc.)

**Best For**: Content creation, marketing copy, professional communications, any task requiring precise tone control
**Complexity**: Medium
**Primary Categories**: CREATION_CONTENT, PERSUASION, FEEDBACK
**Secondary Categories**: STRATEGY, ANALYSIS

---

### ICIO (`ICIO`)
**Instruction, Context, Input, Output**

A streamlined framework focused on clear task specification with explicit input/output requirements.

**Components:**
- **Instruction**: The specific task or action required
- **Context**: Background and situational information
- **Input**: The data or content to be processed
- **Output**: Desired format and style of response

**Best For**: Data processing, content transformation, structured tasks
**Complexity**: Low
**Primary Categories**: CREATION_TECHNICAL, ANALYSIS
**Secondary Categories**: CREATION_CONTENT

---

### CRAFT/CRAFTED (`CRAFT`)
**Context, Request, Action, Frame, Template (+ Examples, Develop)**

A comprehensive framework that emphasises constraint-setting and output templating.

**Components:**
- **Context**: Persona, tone, audience (who the AI should be)
- **Request**: The specific task or question
- **Action**: Steps or methodology to follow
- **Frame**: Constraints and boundaries (what to include/exclude)
- **Template**: Desired output format and structure
- **Examples** (optional): Sample outputs to guide style
- **Develop** (optional): Iteration and refinement instructions

**Best For**: Repeatable prompts, content generation, structured outputs
**Complexity**: Medium-High
**Primary Categories**: CREATION_CONTENT, CREATION_TECHNICAL
**Secondary Categories**: IDEATION, FEEDBACK

---

## NEW: Iterative Refinement Frameworks

### Self-Refine (`SELF_REFINE`)
**Generate → Critique → Refine (Iterative)**

A framework where the LLM generates initial output, provides self-feedback, and then refines based on that feedback—iteratively improving without human intervention.

**Components:**
1. **Initial Generation**: Produce first output
2. **Self-Feedback**: Critique the output against criteria
3. **Refinement**: Improve based on feedback
4. **Iteration**: Repeat until quality threshold met

**Best For**: Writing improvement, code refinement, quality-sensitive outputs
**Complexity**: High
**Primary Categories**: CREATION_CONTENT, FEEDBACK, CREATION_TECHNICAL
**Secondary Categories**: ANALYSIS

---

### Reflexion (`REFLEXION`)
**Act → Evaluate → Reflect → Learn → Act**

An agentic framework that enables LLMs to learn from mistakes through verbal reinforcement and self-reflection, maintaining memory across attempts.

**Components:**
1. **Action**: Execute initial task attempt
2. **Evaluation**: Assess outcome (success/failure)
3. **Reflection**: Generate verbal reflection on what went wrong
4. **Memory**: Store reflection for future reference
5. **Retry**: Use reflection to improve next attempt

**Best For**: Complex problem-solving, code generation, multi-step reasoning tasks
**Complexity**: High
**Primary Categories**: PROBLEM_SOLVING, CREATION_TECHNICAL
**Secondary Categories**: ANALYSIS, DECISION

---

### Step-Back Prompting (`STEP_BACK`)
**Abstract → Ground → Reason**

A DeepMind-developed framework that prompts abstraction before tackling specific problems, showing up to 36% improvement over Chain of Thought.

**Components:**
1. **Abstraction**: First ask a higher-level, principle-based question
2. **Grounding**: Use the abstract answer as foundation
3. **Reasoning**: Apply principles to the specific question

**Best For**: STEM problems, knowledge-intensive questions, multi-hop reasoning
**Complexity**: Medium
**Primary Categories**: ANALYSIS, PROBLEM_SOLVING, LEARNING
**Secondary Categories**: RESEARCH

---

## NEW: Agentic & Reasoning Frameworks

### ReAct (`REACT`)
**Reasoning and Acting (Thought → Action → Observation)**

A framework that interleaves reasoning traces with task-specific actions, enabling LLMs to interact with external tools and environments.

**Components:**
1. **Thought**: Verbal reasoning about current state
2. **Action**: Execute a specific action (tool call, search, etc.)
3. **Observation**: Record the result
4. **Repeat**: Continue until task complete

**Best For**: Tool-using tasks, information retrieval, multi-step decision-making, agentic workflows
**Complexity**: High
**Primary Categories**: PROBLEM_SOLVING, RESEARCH
**Secondary Categories**: ANALYSIS, DECISION

---

### Skeleton-of-Thought (`SKELETON_OF_THOUGHT`)
**Skeleton → Parallel Expansion**

A Microsoft Research framework that generates an answer skeleton first, then expands points in parallel—reducing latency while often improving quality.

**Components:**
1. **Skeleton Generation**: Create outline of 3-10 key points
2. **Point Expansion**: Elaborate each point independently (can be parallelised)
3. **Assembly**: Combine expanded points into final answer

**Best For**: Structured explanations, listicles, consultancy responses, any task where parallel processing is possible
**Complexity**: Medium
**Primary Categories**: CREATION_CONTENT, LEARNING
**Secondary Categories**: IDEATION

**NOT Suitable For**: Step-by-step reasoning (math, coding), short answers, content requiring strict coherence between points

---

### Meta Prompting (`META_PROMPTING`)
**Prompt → Evaluate → Refine Prompt → Execute**

A technique where LLMs generate or improve prompts themselves, enabling autonomous prompt optimisation.

**Components:**
1. **Initial Prompt Design**: Start with basic prompt
2. **Meta-Level Instruction**: Ask LLM to improve the prompt
3. **Prompt Evaluation**: Assess generated prompt quality
4. **Execution**: Use refined prompt for final task

**Best For**: Prompt optimisation, complex task setup, when optimal prompt structure is unclear
**Complexity**: High
**Primary Categories**: All categories (meta-level tool)

---

## Updated Framework-to-Category Mapping

### DECISION
Primary: RICE, Pros and Cons, Tree of Thought, **Step-Back**
Secondary: SMART, Six Thinking Hats, ORID, PAUSE, **ReAct**

### STRATEGY
Primary: COAST, 3Cs Model, GOPA, SMART, **CO-STAR**
Secondary: Chain of Thought, ROSES, RELIC

### ANALYSIS
Primary: Chain of Thought, Tree of Thought, FOCUS, **Step-Back**
Secondary: Five Ws and One H, PROMPT, Socratic Method, Six Thinking Hats, **ReAct**

### CREATION_CONTENT
Primary: BLOG, TAG, APE, 4S Method, **CO-STAR**, **CRAFT**
Secondary: Hamburger Model, CRISPE, TRACI, **Skeleton-of-Thought**, **Self-Refine**

### CREATION_TECHNICAL
Primary: RASCEF, CRISPE, RTF, **ICIO**
Secondary: CIDI, Zero-Shot, GRADE, **Self-Refine**, **Reflexion**

### IDEATION
Primary: SCAMPER, HMW, What If, Imagine, **Skeleton-of-Thought**
Secondary: Tree of Thought, SPARK, Six Thinking Hats

### PROBLEM_SOLVING
Primary: Chain of Thought, GOPA, ROSES, PAUSE, **ReAct**, **Step-Back**
Secondary: Tree of Thought, Six Thinking Hats, Five Ws and One H, **Reflexion**

### LEARNING
Primary: ELI5, Bloom's Taxonomy, TQA, Help Me Understand, **Step-Back**
Secondary: Socratic Method, Few-Shot, **Skeleton-of-Thought**

### PERSUASION
Primary: BAB, Challenge-Solution-Benefit, TRACE, PEE, **CO-STAR**
Secondary: CAR, PAR, STAR, RACE

### FEEDBACK
Primary: RISE, ROSES, PEE, **Self-Refine**
Secondary: Chain of Destiny, RACEF

### RESEARCH
Primary: PROMPT, Five Ws and One H, Elicitation, **ReAct**
Secondary: Chain of Thought, RODES, **Step-Back**

### GOAL_SETTING
Primary: SMART, GOPA
Secondary: COAST, FOCUS

---

## Cognitive Requirement Updates

### New Cognitive Requirement: ITERATIVE

| Code | Description | Aligned Traits | Opposed Traits |
|------|-------------|----------------|----------------|
| `ITERATIVE` | Self-improvement, refinement, learning from feedback | High P, High T-identity | High J, High A |

**Tasks requiring ITERATIVE**: Quality-critical content, code refinement, complex problem-solving where first attempt unlikely to succeed.

**Frameworks supporting ITERATIVE**: Self-Refine, Reflexion, Chain of Destiny, Meta Prompting

---

## Selection Algorithm Update

Add to step 2:

```
2b. Check if task benefits from iteration:
    - If quality-critical output → Consider Self-Refine, Reflexion
    - If multi-step with potential failures → Consider ReAct, Reflexion
    - If structure can be parallelised → Consider Skeleton-of-Thought
    - If abstraction would help reasoning → Consider Step-Back
```

Add to complexity criteria:

```
- **Iterative**: Tasks where refinement cycles improve output significantly
  → Add Self-Refine or Reflexion as secondary option
```
