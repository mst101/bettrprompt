# Framework Taxonomy (Compressed)

## Task Categories

| Code | Description | Triggers |
|------|-------------|----------|
| `DECISION` | Choose between options, prioritise | "decide", "choose", "which", "compare", "pros and cons" |
| `STRATEGY` | Business strategy, roadmaps, long-term planning | "strategy", "plan", "roadmap", "long-term", "growth" |
| `PLANNING` | Breaking down goals into actionable steps, project execution | "plan", "organize", "coordinate", "schedule", "timeline", "project plan" |
| `ANALYSIS` | Understand data, examine situations, root cause | "analyse", "understand why", "explain", "examine", "diagnose" |
| `CREATION_CONTENT` | Writing, marketing, emails, communications | "write", "draft", "blog", "email", "copy", "article" |
| `CREATION_TECHNICAL` | Code, docs, specs, technical writing | "code", "build", "develop", "API", "script", "documentation" |
| `IDEATION` | Brainstorming, innovation, generating ideas | "ideas", "brainstorm", "creative", "possibilities", "suggestions" |
| `PROBLEM_SOLVING` | Fixing issues, troubleshooting | "solve", "fix", "problem", "issue", "challenge", "overcome" |
| `OPTIMIZATION` | Making existing systems better, faster, cheaper | "optimize", "improve", "enhance", "streamline", "reduce costs", "increase efficiency" |
| `LEARNING` | Understanding concepts, education | "learn", "understand", "explain to me", "teach me", "what is" |
| `PERSUASION` | Convincing, selling, pitching | "convince", "persuade", "pitch", "sell", "proposal", "negotiate" |
| `FEEDBACK` | Reviewing, critiquing, improving work | "review", "feedback", "improve", "critique", "refine" |
| `RESEARCH` | Gathering information, investigation | "research", "find out", "investigate", "gather information" |
| `GOAL_SETTING` | Defining objectives, KPIs, targets | "goal", "objective", "target", "KPI", "milestone" |

## Cognitive Requirements

| Code | Description | Aligned Traits | Opposed Traits |
|------|-------------|----------------|----------------|
| `EMPATHY` | Understanding feelings, relationships | High F, High E | High T |
| `VISION` | Future thinking, patterns, concepts | High N, High P | High S |
| `DETAIL` | Step-by-step specificity, concrete actions | High S, High J | High N, High P |
| `DECISIVE` | Clear conclusions, confident guidance | High J, High A | High P, High T-identity |
| `EXPLORE` | Option generation, avoiding premature closure | High P, High N | High J |
| `OBJECTIVE` | Logic, evidence-based analysis | High T, High S | High F |
| `RISK` | Identifying problems, downsides | High T-identity, High T | High A |
| `CREATIVE` | Novel ideas, unconventional thinking | High N, High P | High S, High J |
| `STRUCTURE` | Clear organisation, logical flow | High J, High T | High P |
| `WARM` | Warmth, rapport-building | High F, High E | High T |
| `ITERATIVE` | Self-improvement, refinement, learning from feedback | High P, High T-identity | High J, High A |
| `ABSTRACTION` | Higher-level principle identification before specifics | High N, High T | High S |
| `AGENTIC` | Interleaved thinking and acting with tool interaction | High T, High P | High N (ungrounded), High J (premature) |
| `SYNTHESIS` | Integrating disparate information into coherent whole | High N, High T | High S, High P |
| `PERSUASION` | Building compelling arguments, convincing others | High E, High F, High J | High I, High T |
| `PEDAGOGY` | Making complex things understandable, scaffolding learning | High F, High S, High J | High N, High P |

## Task → Requirements Mapping

| Task | Primary Requirements | Secondary Requirements |
|------|---------------------|----------------------|
| `DECISION` | `OBJECTIVE`, `RISK` | `DECISIVE`, `EXPLORE` |
| `STRATEGY` | `VISION`, `DETAIL` | `RISK`, `DECISIVE`, `SYNTHESIS` |
| `PLANNING` | `DETAIL`, `STRUCTURE` | `RISK`, `DECISIVE` |
| `ANALYSIS` | `OBJECTIVE`, `DETAIL` | `RISK`, `ABSTRACTION`, `SYNTHESIS` |
| `CREATION_CONTENT` | Varies (see content types) | `STRUCTURE`, `ITERATIVE` |
| `CREATION_TECHNICAL` | `DETAIL`, `OBJECTIVE` | `STRUCTURE`, `ITERATIVE` |
| `IDEATION` | `CREATIVE`, `EXPLORE` | `VISION` |
| `PROBLEM_SOLVING` | `OBJECTIVE`, `DETAIL` | `RISK`, `EXPLORE`, `AGENTIC` |
| `OPTIMIZATION` | `OBJECTIVE`, `DETAIL` | `ITERATIVE`, `RISK` |
| `LEARNING` | `STRUCTURE`, `PEDAGOGY` | `ABSTRACTION` |
| `PERSUASION` | `PERSUASION`, `EMPATHY` | `WARM`, `STRUCTURE` |
| `FEEDBACK` | `EMPATHY`, `OBJECTIVE` | `WARM`, `ITERATIVE` |
| `RESEARCH` | `OBJECTIVE`, `EXPLORE`, `SYNTHESIS` | `DETAIL`, `AGENTIC` |
| `GOAL_SETTING` | `DETAIL`, `DECISIVE` | `VISION` |

### Content Types (CREATION_CONTENT)

| Type | Triggers | Primary Reqs | Secondary Reqs |
|------|----------|--------------|----------------|
| Customer email | "email to customer", "client email" | `EMPATHY`, `WARM`, `STRUCTURE` | |
| Marketing copy | "marketing", "ad copy", "landing page" | `PERSUASION`, `CREATIVE`, `EMPATHY` | `DECISIVE` |
| Technical blog | "technical post", "how-to" | `OBJECTIVE`, `STRUCTURE`, `DETAIL` | `PEDAGOGY` |
| Executive summary | "executive summary", "brief for leadership" | `DECISIVE`, `STRUCTURE`, `SYNTHESIS` | |
| Apology/bad news | "apologise", "bad news", "discontinuing" | `EMPATHY`, `WARM`, `RISK` | |
| Sales pitch | "sales", "pitch", "proposal" | `PERSUASION`, `EMPATHY`, `DECISIVE` | `CREATIVE` |

## Frameworks (Essential Info Only)

**IMPORTANT:** Each framework has an explicit `code` field that MUST be used exactly as shown when referencing the framework template.

### Structured Clarity
- **CRISPE** (`CRISPE`): Clarity, Relevance, Iteration, Specificity, Parameters, Examples. For: technical docs, strategic planning. Complexity: Medium
- **RELIC** (`RELIC`): Role, Emphasis, Limitation, Information, Challenge. For: content creation, strategic planning. Complexity: Medium
- **RTF** (`RTF`): Request, Task, Format. For: data retrieval, simple requests. Complexity: Low
- **CO-STAR** (`CO_STAR`): Context, Objective, Style, Tone, Audience, Response. For: content creation, marketing copy, precise tone control. Complexity: Medium
- **ICIO** (`ICIO`): Instruction, Context, Input, Output. For: data processing, content transformation, structured tasks. Complexity: Low
- **CRAFT** (`CRAFT`): Context, Request, Action, Frame, Template (+Examples, Develop). For: repeatable prompts, structured outputs. Complexity: Medium-High

### Iterative Refinement
- **RACEF** (`RACEF`): Rephrase, Append, Contextualize, Examples, Follow-Up. For: brainstorming, iterative problem-solving. Complexity: Medium
- **Chain of Destiny** (`CHAIN_OF_DESTINY`): Baseline + Feedback loops. For: projects prioritising quality. Complexity: High
- **Self-Refine** (`SELF_REFINE`): Generate → Critique → Refine (iterative). For: writing improvement, code refinement, quality-sensitive outputs. Complexity: High
- **Reflexion** (`REFLEXION`): Act → Evaluate → Reflect → Learn → Act. For: complex problem-solving, code generation, multi-step reasoning. Complexity: High

### Decision-Making & Prioritisation
- **RICE** (`RICE`): Reach, Impact, Confidence, Effort. For: feature prioritisation, project selection. Complexity: Low
- **SMART** (`SMART`): Specific, Measurable, Achievable, Relevant, Time-bound. For: goal-setting. Complexity: Low
- **COAST** (`COAST`): Challenge, Objective, Actions, Strategy, Tactics. For: project management. Complexity: Medium
- **Pros and Cons** (`PROS_AND_CONS`): Benefits vs Drawbacks. For: decision-making. Complexity: Low

### Analytical & Problem-Solving
- **Chain of Thought** (`CHAIN_OF_THOUGHT`): Intro, Breakdown, Logical Progression, Conclusion. For: complex reasoning. Complexity: High
- **Tree of Thought** (`TREE_OF_THOUGHT`): Nodes, Edges, Outcomes. For: complex problem-solving, scenario planning. Complexity: High
- **FOCUS** (`FOCUS`): Focus areas, Prioritisation, Resources. For: goal-setting and prioritisation. Complexity: Medium
- **Six Thinking Hats** (`SIX_THINKING_HATS`): White (facts), Red (emotions), Black (risks), Yellow (benefits), Green (creativity), Blue (process). For: multi-perspective analysis. Complexity: Medium
- **Step-Back** (`STEP_BACK`): Abstraction → Grounding → Reasoning. For: STEM problems, knowledge-intensive questions, multi-hop reasoning. Complexity: Medium
- **ReAct** (`REACT`): Thought → Action → Observation (loop). For: tool-using tasks, information retrieval, agentic workflows. Complexity: High
- **Skeleton-of-Thought** (`SKELETON_OF_THOUGHT`): Skeleton → Parallel Expansion → Assembly. For: structured explanations, listicles, consultancy responses. Complexity: Medium. NOT for: sequential reasoning, math, coding.

### Storytelling & Narrative
- **BAB** (`BAB`): Before, After, Bridge. For: marketing, persuasion. Complexity: Low
- **CAR** (`CAR`): Context, Action, Result. For: case studies, interviews. Complexity: Low
- **PAR** (`PAR`): Problem, Action, Result. For: success stories. Complexity: Low
- **STAR** (`STAR`): Situation, Task, Action, Result. For: interviews, case studies. Complexity: Low
- **Challenge-Solution-Benefit** (`CHALLENGE_SOLUTION_BENEFIT`): For: marketing, product development. Complexity: Low

### Content Creation
- **BLOG** (`BLOG`): Background, Logic, Outline, Goal. For: blog posts, articles. Complexity: Low
- **APE** (`APE`): Audience, Purpose, Execution. For: content marketing. Complexity: Low
- **TAG** (`TAG`): Topic, Audience, Goal. For: content marketing. Complexity: Low
- **4S Method** (`4S_METHOD`): Structure, Style, Substance, Speed. For: digital marketing. Complexity: Low
- **Hamburger** (`HAMBURGER`): Intro, Body, Conclusion. For: blogs, articles. Complexity: Low

### Creative & Innovation
- **SCAMPER** (`SCAMPER`): Substitute, Combine, Adapt, Modify, Put to another use, Eliminate, Reverse. For: innovation, product development. Complexity: Medium
- **HMW** (`HMW`): "How might we..." questions. For: design thinking, brainstorming. Complexity: Low
- **Imagine** (`IMAGINE`): Future scenario visioning. For: strategic planning. Complexity: Low
- **What If** (`WHAT_IF`): Hypothetical scenarios. For: creative problem-solving. Complexity: Low
- **SPARK** (`SPARK`): Situation, Problem, Aspiration, Result, Kismet. For: product development, marketing. Complexity: Medium

### Educational & Learning
- **Bloom's Taxonomy** (`BLOOMS_TAXONOMY`): Remember → Understand → Apply → Analyze → Evaluate → Create. For: educational content. Complexity: Medium
- **ELI5** (`ELI5`): Explain Like I'm 5. For: simplifying complex concepts. Complexity: Low
- **Help Me Understand** (`HELP_ME_UNDERSTAND`): Comprehension-focused. For: customer support, education. Complexity: Low
- **TQA** (`TQA`): Thematic, Question, Answer. For: e-learning. Complexity: Low
- **Socratic Method** (`SOCRATIC_METHOD`): Progressive questioning. For: education, critical analysis. Complexity: Medium

### Communication & Engagement
- **RACE** (`RACE`): Reach, Act, Convert, Engage. For: marketing campaigns. Complexity: Medium
- **ERA** (`ERA`): Engage, React, Act. For: marketing, engagement. Complexity: Low
- **CARE** (`CARE`): Compassion, Awareness, Response, Engagement. For: customer service. Complexity: Medium

### Strategic Analysis
- **3Cs** (`3CS`): Company, Customer, Competitor. For: market analysis. Complexity: Medium
- **GOPA** (`GOPA`): Goals, Obstacles, Plans, Actions. For: goal setting, problem-solving. Complexity: Low

### Feedback & Improvement
- **RISE** (`RISE`): Reflect, Inquire, Suggest, Elevate. For: performance management. Complexity: Medium
- **ROSES** (`ROSES`): Recognize, Observe, Strategize, Execute, Study. For: complex projects. Complexity: Medium
- **PEE** (`PEE`): Point, Evidence, Explanation. For: academic writing, critiques. Complexity: Low

### Advanced Structured
- **RASCEF** (`RASCEF`): Role, Action, Steps, Context, Examples, Format. For: technical documentation. Complexity: Medium
- **RHODES** (`RHODES`): Role, Objective, Details, Examples, Sense Check. For: creative content, marketing. Complexity: Medium
- **RISEN** (`RISEN`): Role, Input, Steps, Expectation, Novelty. For: research, innovation. Complexity: Medium
- **GRADE** (`GRADE`): Goal, Request, Action, Details, Example. For: project management. Complexity: Medium
- **TRACI** (`TRACI`): Task, Role, Audience, Create, Intent. For: marketing, education. Complexity: Medium
- **RODES** (`RODES`): Role, Objective, Details, Examples, Sense Check. For: educational content. Complexity: Medium
- **CIDI** (`CIDI`): Context, Instructions, Details, Input. For: project management. Complexity: Medium

### Argumentation
- **TRACE** (`TRACE`): Topic, Reason, Audience, Counterargument, Evidence. For: debate, persuasive writing. Complexity: Medium
- **SPAR** (`SPAR`): Situation, Problem, Action, Result. For: case studies. Complexity: Low
- **PROMPT** (`PROMPT`): Precision, Relevance, Objectivity, Method, Provenance, Timeliness. For: research, journalism. Complexity: Medium

### Specialised
- **SPEAR** (`SPEAR`): Start, Provide, Explain, Ask, Rinse & Repeat. For: everyday tasks. Complexity: Low
- **Few-Shot** (`FEW_SHOT`): Task + Demonstrations + Query. For: classification, creative content. Complexity: Low
- **Zero-Shot** (`ZERO_SHOT`): Direct instruction without examples. For: translation, factual queries. Complexity: Low
- **ORID** (`ORID`): Objective, Reflective, Interpretive, Decisional. For: group discussions, coaching. Complexity: Medium
- **PAUSE** (`PAUSE`): Prepare, Assess, Uncover, Synthesize, Execute. For: management decisions. Complexity: Medium
- **Elicitation** (`ELICITATION`): Structured information extraction. For: research, interviews. Complexity: Medium
- **Meta Prompting** (`META_PROMPTING`): Prompt → Evaluate → Refine Prompt → Execute. For: prompt optimisation, complex task setup. Complexity: High

### Visual & Dialogue
- **Atomic Prompting** (`ATOMIC_PROMPTING`): Detailed visual specs. For: image generation (Midjourney, DALL-E). Complexity: Medium
- **Five Ws and One H** (`FIVE_WS_AND_ONE_H`): Who, What, When, Where, Why, How. For: journalism, research. Complexity: Low

## Framework Cognitive Requirements Support

This section maps each framework to the cognitive requirements it supports. Use this for requirement-based framework scoring.

**Scoring Guidelines:**
- Primary requirements supported by framework: +3 points
- Secondary requirements supported by framework: +1 point
- Select framework with highest total score
- On tie, prefer lower complexity for simple tasks

### Structured Clarity Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `CRISPE` | `DETAIL`, `STRUCTURE`, `OBJECTIVE` | `ITERATIVE` |
| `RELIC` | `STRUCTURE`, `DETAIL`, `CREATIVE` | `EMPATHY` |
| `RTF` | `DETAIL`, `STRUCTURE` | `OBJECTIVE` |
| `CO_STAR` | `EMPATHY`, `WARM`, `STRUCTURE` | `PERSUASION`, `CREATIVE` |
| `ICIO` | `DETAIL`, `OBJECTIVE`, `STRUCTURE` | - |
| `CRAFT` | `STRUCTURE`, `DETAIL`, `ITERATIVE` | `OBJECTIVE` |

### Iterative Refinement Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `RACEF` | `ITERATIVE`, `CREATIVE`, `EXPLORE` | `STRUCTURE` |
| `CHAIN_OF_DESTINY` | `ITERATIVE`, `DETAIL`, `OBJECTIVE` | `RISK` |
| `SELF_REFINE` | `ITERATIVE`, `OBJECTIVE` | `STRUCTURE`, `DETAIL` |
| `REFLEXION` | `ITERATIVE`, `OBJECTIVE`, `AGENTIC` | `ABSTRACTION`, `RISK` |

### Decision-Making & Prioritisation Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `RICE` | `OBJECTIVE`, `DECISIVE`, `RISK` | `DETAIL` |
| `SMART` | `DETAIL`, `DECISIVE`, `STRUCTURE` | `OBJECTIVE` |
| `COAST` | `VISION`, `DETAIL`, `STRUCTURE`, `DECISIVE` | `RISK` |
| `PROS_AND_CONS` | `OBJECTIVE`, `RISK`, `EXPLORE` | `DECISIVE` |

### Analytical & Problem-Solving Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `CHAIN_OF_THOUGHT` | `OBJECTIVE`, `ABSTRACTION`, `DETAIL` | `STRUCTURE` |
| `TREE_OF_THOUGHT` | `OBJECTIVE`, `EXPLORE`, `ABSTRACTION` | `VISION` |
| `FOCUS` | `OBJECTIVE`, `DETAIL`, `DECISIVE` | `VISION` |
| `SIX_THINKING_HATS` | `EXPLORE`, `OBJECTIVE`, `EMPATHY` | `CREATIVE`, `RISK` |
| `STEP_BACK` | `ABSTRACTION`, `OBJECTIVE` | `VISION`, `STRUCTURE` |
| `REACT` | `AGENTIC`, `OBJECTIVE`, `ITERATIVE` | `DETAIL`, `RISK` |
| `SKELETON_OF_THOUGHT` | `STRUCTURE`, `VISION` | `DETAIL`, `CREATIVE` |

### Storytelling & Narrative Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `BAB` | `PERSUASION`, `EMPATHY`, `STRUCTURE` | `WARM`, `CREATIVE` |
| `CAR` | `STRUCTURE`, `OBJECTIVE` | `PERSUASION` |
| `PAR` | `STRUCTURE`, `OBJECTIVE` | `PERSUASION` |
| `STAR` | `STRUCTURE`, `DETAIL`, `OBJECTIVE` | - |
| `CHALLENGE_SOLUTION_BENEFIT` | `PERSUASION`, `EMPATHY` | `OBJECTIVE`, `CREATIVE` |

### Content Creation Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `BLOG` | `STRUCTURE`, `DETAIL` | `CREATIVE`, `PEDAGOGY` |
| `APE` | `EMPATHY`, `PERSUASION` | `STRUCTURE` |
| `TAG` | `EMPATHY`, `DECISIVE` | `STRUCTURE` |
| `4S_METHOD` | `STRUCTURE`, `CREATIVE` | `DETAIL`, `PERSUASION` |
| `HAMBURGER` | `STRUCTURE` | `DETAIL`, `PEDAGOGY` |

### Creative & Innovation Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `SCAMPER` | `CREATIVE`, `EXPLORE` | `VISION` |
| `HMW` | `CREATIVE`, `EXPLORE`, `EMPATHY` | - |
| `IMAGINE` | `CREATIVE`, `VISION` | - |
| `WHAT_IF` | `CREATIVE`, `EXPLORE` | `VISION` |
| `SPARK` | `CREATIVE`, `EMPATHY`, `VISION` | `PERSUASION` |

### Educational & Learning Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `BLOOMS_TAXONOMY` | `PEDAGOGY`, `STRUCTURE`, `ABSTRACTION` | `OBJECTIVE` |
| `ELI5` | `PEDAGOGY`, `EMPATHY` | `STRUCTURE` |
| `HELP_ME_UNDERSTAND` | `PEDAGOGY`, `EMPATHY`, `STRUCTURE` | - |
| `TQA` | `PEDAGOGY`, `STRUCTURE` | - |
| `SOCRATIC_METHOD` | `PEDAGOGY`, `OBJECTIVE`, `EXPLORE` | `ABSTRACTION` |

### Communication & Engagement Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `RACE` | `PERSUASION`, `STRUCTURE`, `DECISIVE` | `EMPATHY` |
| `ERA` | `PERSUASION`, `EMPATHY`, `WARM` | - |
| `CARE` | `EMPATHY`, `WARM` | `STRUCTURE` |

### Strategic Analysis Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `3CS` | `OBJECTIVE`, `SYNTHESIS`, `VISION` | `STRUCTURE` |
| `GOPA` | `DETAIL`, `STRUCTURE`, `DECISIVE` | `OBJECTIVE` |

### Feedback & Improvement Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `RISE` | `EMPATHY`, `OBJECTIVE`, `WARM` | `ITERATIVE` |
| `ROSES` | `STRUCTURE`, `DETAIL`, `ITERATIVE` | `OBJECTIVE`, `VISION` |
| `PEE` | `OBJECTIVE`, `STRUCTURE` | `PERSUASION` |

### Advanced Structured Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `RASCEF` | `DETAIL`, `STRUCTURE`, `OBJECTIVE` | - |
| `RHODES` | `STRUCTURE`, `DETAIL`, `CREATIVE` | `EMPATHY` |
| `RISEN` | `STRUCTURE`, `DETAIL`, `CREATIVE` | `VISION` |
| `GRADE` | `STRUCTURE`, `DETAIL`, `DECISIVE` | `OBJECTIVE` |
| `TRACI` | `STRUCTURE`, `EMPATHY`, `PERSUASION` | `CREATIVE` |
| `RODES` | `STRUCTURE`, `DETAIL`, `PEDAGOGY` | `EMPATHY` |
| `CIDI` | `STRUCTURE`, `DETAIL`, `OBJECTIVE` | - |

### Argumentation Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `TRACE` | `PERSUASION`, `OBJECTIVE`, `RISK` | `EMPATHY`, `STRUCTURE` |
| `SPAR` | `STRUCTURE`, `OBJECTIVE` | `PERSUASION` |
| `PROMPT` | `OBJECTIVE`, `DETAIL`, `STRUCTURE` | `SYNTHESIS` |

### Specialised Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `SPEAR` | `STRUCTURE`, `DETAIL` | - |
| `FEW_SHOT` | `STRUCTURE`, `DETAIL` | `PEDAGOGY` |
| `ZERO_SHOT` | `OBJECTIVE`, `DETAIL` | - |
| `ORID` | `OBJECTIVE`, `EMPATHY`, `DECISIVE` | `STRUCTURE` |
| `PAUSE` | `OBJECTIVE`, `SYNTHESIS`, `DECISIVE` | `RISK` |
| `ELICITATION` | `OBJECTIVE`, `DETAIL`, `STRUCTURE` | - |
| `META_PROMPTING` | `ITERATIVE`, `OBJECTIVE`, `ABSTRACTION` | `SYNTHESIS` |

### Visual & Dialogue Frameworks

| Framework | Primary Requirements | Secondary Requirements |
|-----------|---------------------|----------------------|
| `ATOMIC_PROMPTING` | `DETAIL`, `CREATIVE`, `VISION` | - |
| `FIVE_WS_AND_ONE_H` | `OBJECTIVE`, `DETAIL`, `STRUCTURE` | - |

## Framework-to-Category Mapping

**NOTE:** Use the framework codes shown in parentheses when selecting frameworks.

### DECISION
Primary: RICE (`RICE`), Pros and Cons (`PROS_AND_CONS`), Tree of Thought (`TREE_OF_THOUGHT`), Step-Back (`STEP_BACK`)
Secondary: SMART (`SMART`), Six Thinking Hats (`SIX_THINKING_HATS`), ORID (`ORID`), PAUSE (`PAUSE`), ReAct (`REACT`)

### STRATEGY
Primary: COAST (`COAST`), 3Cs Model (`3CS`), GOPA (`GOPA`), SMART (`SMART`), CO-STAR (`CO_STAR`)
Secondary: Chain of Thought (`CHAIN_OF_THOUGHT`), ROSES (`ROSES`), RELIC (`RELIC`)

### ANALYSIS
Primary: Chain of Thought (`CHAIN_OF_THOUGHT`), Tree of Thought (`TREE_OF_THOUGHT`), FOCUS (`FOCUS`), Step-Back (`STEP_BACK`)
Secondary: Five Ws and One H (`FIVE_WS_AND_ONE_H`), PROMPT (`PROMPT`), Socratic Method (`SOCRATIC_METHOD`), Six Thinking Hats (`SIX_THINKING_HATS`), ReAct (`REACT`)

### CREATION_CONTENT
Primary: BLOG (`BLOG`), TAG (`TAG`), APE (`APE`), 4S Method (`4S_METHOD`), CO-STAR (`CO_STAR`), CRAFT (`CRAFT`)
Secondary: Hamburger Model (`HAMBURGER`), CRISPE (`CRISPE`), TRACI (`TRACI`), Skeleton-of-Thought (`SKELETON_OF_THOUGHT`), Self-Refine (`SELF_REFINE`)

### CREATION_TECHNICAL
Primary: RASCEF (`RASCEF`), CRISPE (`CRISPE`), RTF (`RTF`), ICIO (`ICIO`)
Secondary: CIDI (`CIDI`), Zero-Shot (`ZERO_SHOT`), GRADE (`GRADE`), Self-Refine (`SELF_REFINE`), Reflexion (`REFLEXION`)

### IDEATION
Primary: SCAMPER (`SCAMPER`), HMW (`HMW`), What If (`WHAT_IF`), Imagine (`IMAGINE`), Skeleton-of-Thought (`SKELETON_OF_THOUGHT`)
Secondary: Tree of Thought (`TREE_OF_THOUGHT`), SPARK (`SPARK`), Six Thinking Hats (`SIX_THINKING_HATS`)

### PROBLEM_SOLVING
Primary: Chain of Thought (`CHAIN_OF_THOUGHT`), GOPA (`GOPA`), ROSES (`ROSES`), PAUSE (`PAUSE`), ReAct (`REACT`), Step-Back (`STEP_BACK`)
Secondary: Tree of Thought (`TREE_OF_THOUGHT`), Six Thinking Hats (`SIX_THINKING_HATS`), Five Ws and One H (`FIVE_WS_AND_ONE_H`), Reflexion (`REFLEXION`)

### LEARNING
Primary: ELI5 (`ELI5`), Bloom's Taxonomy (`BLOOMS_TAXONOMY`), TQA (`TQA`), Help Me Understand (`HELP_ME_UNDERSTAND`), Step-Back (`STEP_BACK`)
Secondary: Socratic Method (`SOCRATIC_METHOD`), Few-Shot (`FEW_SHOT`), Skeleton-of-Thought (`SKELETON_OF_THOUGHT`)

### PERSUASION
Primary: BAB (`BAB`), Challenge-Solution-Benefit (`CHALLENGE_SOLUTION_BENEFIT`), TRACE (`TRACE`), PEE (`PEE`), CO-STAR (`CO_STAR`)
Secondary: CAR (`CAR`), PAR (`PAR`), STAR (`STAR`), RACE (`RACE`)

### FEEDBACK
Primary: RISE (`RISE`), ROSES (`ROSES`), PEE (`PEE`), Self-Refine (`SELF_REFINE`)
Secondary: Chain of Destiny (`CHAIN_OF_DESTINY`), RACEF (`RACEF`)

### RESEARCH
Primary: PROMPT (`PROMPT`), Five Ws and One H (`FIVE_WS_AND_ONE_H`), Elicitation (`ELICITATION`), ReAct (`REACT`)
Secondary: Chain of Thought (`CHAIN_OF_THOUGHT`), RODES (`RODES`), Step-Back (`STEP_BACK`)

### GOAL_SETTING
Primary: SMART (`SMART`), GOPA (`GOPA`)
Secondary: COAST (`COAST`), FOCUS (`FOCUS`)

## Selection Algorithm

1. Classify task into category
2. Retrieve candidate frameworks from mapping
3. Check for special indicators:
   - Tone/audience critical? → Prefer CO-STAR
   - Tool-using/research task? → Consider ReAct
   - Quality-critical output? → Consider Self-Refine
   - Principle-based reasoning needed? → Consider Step-Back
   - Parallelisable structure? → Consider Skeleton-of-Thought
   - Complex task setup? → Consider Meta Prompting
4. Prefer "Primary" frameworks
5. Match complexity:
   - Simple task → Low complexity framework
   - Moderate task → Low or Medium complexity
   - Complex task → Medium or High complexity
6. Check iteration benefit:
   - If quality-critical output → Consider Self-Refine, Reflexion
   - If multi-step with potential failures → Consider ReAct, Reflexion
   - If structure can be parallelised → Consider Skeleton-of-Thought
   - If abstraction would help reasoning → Consider Step-Back
7. Select ONE framework for construction
8. Note alternatives

Complexity criteria:
- **Simple**: Single-step, clear output, minimal context
- **Moderate**: Multi-step, some ambiguity, needs context
- **Complex**: Multi-faceted, significant ambiguity, extensive context, multiple stakeholders
- **Iterative**: Tasks where refinement cycles improve output significantly → Add Self-Refine or Reflexion as secondary option