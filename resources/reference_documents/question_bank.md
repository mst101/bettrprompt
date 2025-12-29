# Question Bank Reference Document

## Purpose

This document contains all clarifying questions organised by task category and framework. Use this to generate appropriate questions after classifying the user's task. Questions should be selected based on task category, adjusted for personality (if provided), and limited to the appropriate quantity.

---

## Universal Questions

These questions are relevant across most task categories. Select 2-3 from this pool for every task.

### Core Universal Questions

| ID | Question | Purpose | Cognitive Reqs | When Essential |
|----|----------|---------|----------------|----------------|
| U1 | What is the scope or boundary of this task? What's explicitly out of scope? | Define limits | `STRUCTURE`, `DETAIL` | Complex or ambiguous tasks |
| U2 | How will you measure success? What does a good outcome look like? | Success criteria | `DECISIVE`, `OBJECTIVE` | All tasks except simple factual |
| U3 | Who is the intended audience for this output? | Audience clarity | `EMPATHY`, `PERSUASION` | Any task with an output |
| U4 | What constraints exist (time, budget, length, format, resources)? | Limitation awareness | `DETAIL`, `OBJECTIVE` | Most tasks |
| U5 | What background context is essential to understand? | Context gathering | `SYNTHESIS`, `DETAIL` | Tasks requiring domain knowledge |
| U6 | What have you already tried or considered? | Avoid redundancy | `EXPLORE`, `ITERATIVE` | Problem-solving, iteration tasks |

### Personality-Adjusted Universal Questions

For each universal question, select the phrasing that matches the user's personality (or use neutral if no personality data):

#### U2: Success Criteria (Personality Variants)

| Personality Pattern | Phrasing |
|---------------------|----------|
| High T + High J | "What are the measurable criteria for success?" |
| High F + High J | "What would a successful outcome mean for the people involved?" |
| High T + High P | "What signals would indicate this is working?" |
| High F + High P | "How would you feel if this turned out well? What would that look like?" |
| High A | "What does success look like?" |
| High T-identity | "What would success look like? And what pitfalls do you want to avoid?" |
| Neutral | "How will you measure whether this is successful?" |

#### U4: Constraints (Personality Variants)

| Personality Pattern | Phrasing |
|---------------------|----------|
| High S | "What specific limitations apply — budget, time, word count, format?" |
| High N | "What boundaries should this stay within?" |
| High T | "What are the hard constraints versus nice-to-haves?" |
| High F | "What limitations should we respect to keep everyone comfortable?" |
| High J | "What are the non-negotiable constraints?" |
| High P | "What constraints exist, and which might be flexible?" |
| Neutral | "What constraints or limitations should be considered?" |

---

## Framework-Specific Questions

### CO-STAR Tasks (Content with Tone/Style Requirements)

Select 4-6 questions when CO-STAR framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| COS1 | Who is the specific audience for this content? What's their role and knowledge level? | Audience definition | `EMPATHY`, `PEDAGOGY` | High |
| COS2 | What's the primary objective—what should the reader do, think, or feel after reading? | Objective clarity | `DECISIVE`, `PERSUASION` | High |
| COS3 | What writing style fits best—formal, conversational, technical, storytelling? | Style determination | `STRUCTURE`, `CREATIVE` | High |
| COS4 | What tone should come through—professional, friendly, urgent, empathetic, authoritative? | Tone setting | `WARM`, `EMPATHY` | High |
| COS5 | What format works best for this content—paragraphs, bullet points, sections with headers? | Response format | `STRUCTURE` | Medium |
| COS6 | What background context does the AI need to understand the situation? | Context gathering | `SYNTHESIS` | Medium |
| COS7 | Are there any words, phrases, or topics to avoid? | Boundaries | `RISK` | Medium |
| COS8 | Is there existing content or examples to match in style? | Style reference | `CREATIVE`, `ITERATIVE` | Low |

#### Personality-Adjusted Phrasing for CO-STAR

| Trait | Question Adaptation |
|-------|---------------------|
| High T | "What are the key facts and data points the content must convey?" |
| High F | "How should the reader feel after engaging with this content?" |
| High S | "What specific details and examples should be included?" |
| High N | "What's the big-picture message or theme?" |

---

### ReAct Tasks (Agentic/Tool-Using Workflows)

Select 4-5 questions when ReAct framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| REA1 | What tools, resources, or information sources are available? | Tool inventory | `AGENTIC`, `DETAIL` | High |
| REA2 | What's the end goal—what specific outcome are you trying to achieve? | Goal clarity | `DECISIVE`, `OBJECTIVE` | High |
| REA3 | How will you know when the task is complete? What signals success? | Termination criteria | `DECISIVE`, `OBJECTIVE` | High |
| REA4 | What constraints or rules must be followed during the process? | Guardrails | `RISK`, `DETAIL` | Medium |
| REA5 | If initial approaches fail, what alternatives should be considered? | Fallback planning | `EXPLORE`, `ITERATIVE` | Medium |
| REA6 | Are there any actions or sources that should be avoided? | Boundaries | `RISK` | Low |

#### Conditional Questions for ReAct

- If research task: "What sources are considered authoritative?"
- If technical task: "What error handling is needed?"
- If multi-step: "What are the dependencies between steps?"

---

### Self-Refine Tasks (Quality-Critical Iterative Work)

Select 4-5 questions when Self-Refine framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| SRF1 | What are the specific quality criteria for this output? | Quality standards | `ITERATIVE`, `OBJECTIVE` | High |
| SRF2 | What aspects are most important to get right? | Priority focus | `DECISIVE`, `OBJECTIVE` | High |
| SRF3 | How many refinement iterations are acceptable? | Iteration bounds | `ITERATIVE` | Medium |
| SRF4 | What's the minimum acceptable quality threshold? | Quality floor | `OBJECTIVE`, `DECISIVE` | Medium |
| SRF5 | Are there examples of excellent outputs to aspire to? | Quality reference | `ITERATIVE`, `CREATIVE` | Medium |
| SRF6 | What common mistakes or pitfalls should be watched for? | Error awareness | `RISK`, `ITERATIVE` | Low |

#### Personality-Adjusted Phrasing for Self-Refine

| Trait | Question Adaptation |
|-------|---------------------|
| High A | "What would make this output genuinely excellent versus merely acceptable?" |
| High J | "What are the non-negotiable quality criteria?" |
| High P | "What aspects would benefit most from exploration and refinement?" |
| High T-identity | "What quality threshold should stop further iteration?" |

---

### Step-Back Tasks (Principle-Based Reasoning)

Select 3-4 questions when Step-Back framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| STB1 | What domain or field does this question belong to? | Domain identification | `ABSTRACTION`, `SYNTHESIS` | High |
| STB2 | What general principles or theories might apply? | Principle prompting | `ABSTRACTION`, `VISION` | High |
| STB3 | What's the specific question you need answered? | Question clarity | `DETAIL`, `OBJECTIVE` | High |
| STB4 | What level of detail is needed in the answer? | Depth calibration | `DETAIL` | Medium |
| STB5 | Are there related concepts you already understand? | Build on existing | `SYNTHESIS`, `PEDAGOGY` | Low |

#### Conditional Questions for Step-Back

- If STEM task: "What formulas or laws might be relevant?"
- If knowledge task: "What categories of information would help?"
- If reasoning task: "What logical principles apply?"

---

### Skeleton-of-Thought Tasks (Structured Parallel Content)

Select 3-5 questions when Skeleton-of-Thought framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| SOT1 | What's the main topic or question to address? | Topic focus | `STRUCTURE`, `DETAIL` | High |
| SOT2 | How many main points should the response cover? | Scope sizing | `STRUCTURE`, `DETAIL` | High |
| SOT3 | Is there a preferred logical order for the points? | Structure guidance | `STRUCTURE`, `OBJECTIVE` | Medium |
| SOT4 | How detailed should each point be? | Depth calibration | `DETAIL` | Medium |
| SOT5 | Does coherence between points matter, or can they be independent? | Framework fit check | `STRUCTURE`, `PARALLEL` | Medium |

#### Framework Fit Verification

If answer to SOT5 indicates high interdependence, consider switching to Chain of Thought instead:

| Response | Action |
|----------|--------|
| "Points are independent" | Continue with Skeleton-of-Thought |
| "Some connection needed" | Use Skeleton-of-Thought with assembly review |
| "Sequential logic required" | Switch to Chain of Thought |
| "Each point builds on previous" | Switch to Chain of Thought |

---

### Meta Prompting Tasks (Prompt Optimisation)

Select 5-7 questions when Meta Prompting framework is identified.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| MET1 | What task will the generated prompt be used for? | Target task | `DETAIL`, `OBJECTIVE` | High |
| MET2 | What model will execute the prompt (if known)? | Model targeting | `DETAIL` | High |
| MET3 | What output format and quality is expected? | Output requirements | `STRUCTURE`, `OBJECTIVE` | High |
| MET4 | What's the user profile for the generated prompt (if known)? | Personality targeting | `EMPATHY`, `PEDAGOGY` | Medium |
| MET5 | Are there existing prompts to improve, or starting from scratch? | Starting point | `ITERATIVE` | Medium |
| MET6 | What constraints must the prompt respect? | Boundaries | `RISK`, `DETAIL` | Medium |
| MET7 | What has been tried that didn't work well? | Failure learning | `ITERATIVE`, `RISK` | Low |

---

## Category-Specific Questions

### DECISION Tasks

Select 3-5 questions from this pool based on task specifics.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| D1 | What options are you currently considering? | Map the decision space | `EXPLORE`, `DETAIL` | High |
| D2 | What criteria matter most in making this decision? | Identify decision factors | `OBJECTIVE`, `DECISIVE` | High |
| D3 | What's the cost or impact of choosing wrong? | Understand stakes | `RISK`, `OBJECTIVE` | Medium |
| D4 | Is this decision reversible, or are you locked in once you choose? | Assess commitment level | `RISK` | Medium |
| D5 | What information would make this decision obvious? | Identify knowledge gaps | `SYNTHESIS`, `OBJECTIVE` | Medium |
| D6 | What's your timeline for making this decision? | Urgency assessment | `DETAIL` | Medium |
| D7 | Who else is affected by or involved in this decision? | Stakeholder mapping | `EMPATHY`, `SYNTHESIS` | Medium |
| D8 | Have you made similar decisions before? What worked or didn't? | Learn from history | `ITERATIVE`, `SYNTHESIS` | Low |
| D9 | What's your gut telling you, and why might you be hesitating? | Surface intuition | `EMPATHY`, `RISK` | Low (F types) |
| D10 | If you had to decide right now, what would you choose? | Force prioritisation | `DECISIVE` | Low |

#### Conditional Questions

- If D1 reveals many options (>4): "Can we narrow to the top 3-4 contenders?"
- If D2 is vague: "If you could only optimise for one factor, which would it be?"
- If high stakes (D3): "What's your risk tolerance here?"

---

### STRATEGY Tasks

Select 4-6 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| S1 | What's the time horizon for this strategy? | Temporal scope | `VISION`, `DETAIL` | High |
| S2 | What resources are available (budget, team, tools)? | Resource constraints | `DETAIL`, `OBJECTIVE` | High |
| S3 | What does success look like at the end of this period? | Goal definition | `VISION`, `DECISIVE` | High |
| S4 | Who are the key stakeholders who need to buy in? | Stakeholder mapping | `EMPATHY`, `PERSUASION` | High |
| S5 | What's been tried before, and what were the results? | Historical context | `ITERATIVE`, `SYNTHESIS` | Medium |
| S6 | What are the biggest obstacles or risks you foresee? | Risk identification | `RISK`, `VISION` | Medium |
| S7 | Who are your main competitors or alternatives? | Competitive context | `OBJECTIVE`, `SYNTHESIS` | Medium |
| S8 | What's your unique advantage or differentiation? | Positioning | `VISION`, `CREATIVE` | Medium |
| S9 | What's the current state you're starting from? | Baseline assessment | `DETAIL`, `OBJECTIVE` | Medium |
| S10 | What dependencies or external factors could affect this? | External awareness | `RISK`, `SYNTHESIS` | Low |
| S11 | What would cause this strategy to fail? | Failure mode analysis | `RISK`, `OBJECTIVE` | Low (T-identity types) |
| S12 | What's your appetite for risk versus proven approaches? | Risk tolerance | `RISK`, `DECISIVE` | Low |

#### Conditional Questions

- If startup context: "What stage is your company (idea, MVP, growth)?"
- If marketing strategy: "What channels have you used before?"
- If product strategy: "Who is your target customer persona?"

---

### ANALYSIS Tasks

Select 3-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| A1 | What data or information do you have access to? | Data inventory | `DETAIL`, `OBJECTIVE` | High |
| A2 | What's the specific question you're trying to answer? | Focus the analysis | `OBJECTIVE`, `DETAIL` | High |
| A3 | What's the hypothesis you're testing, if any? | Frame the inquiry | `OBJECTIVE`, `VISION` | Medium |
| A4 | What level of depth is needed (surface scan vs. deep dive)? | Scope calibration | `DETAIL` | Medium |
| A5 | Are there specific angles or perspectives you want explored? | Direction setting | `EXPLORE`, `SYNTHESIS` | Medium |
| A6 | Who needs to act on this analysis? | Audience/action | `EMPATHY`, `PERSUASION` | Medium |
| A7 | What would change your mind or surprise you? | Challenge assumptions | `EXPLORE`, `OBJECTIVE` | Low |
| A8 | What's the deadline or urgency for this analysis? | Time constraints | `DETAIL` | Medium |
| A9 | What format should the analysis take? | Output format | `STRUCTURE` | Low |
| A10 | Are there any known biases or blind spots to watch for? | Quality control | `OBJECTIVE`, `RISK` | Low |

#### Conditional Questions

- If data analysis: "What's the data format and size?"
- If market analysis: "What geography or segment?"
- If root cause analysis: "When did the issue first appear?"

---

### CREATION_CONTENT Tasks

Select 3-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| C1 | Who is the target audience for this content? | Audience definition | `EMPATHY`, `PEDAGOGY` | High |
| C2 | What's the primary goal or call to action? | Purpose clarity | `PERSUASION`, `DECISIVE` | High |
| C3 | What tone is appropriate (formal, casual, technical, friendly)? | Voice/style | `WARM`, `STRUCTURE` | High |
| C4 | What's the target length or word count? | Scope | `DETAIL` | Medium |
| C5 | Are there examples of style or content you want to emulate? | Style reference | `CREATIVE`, `ITERATIVE` | Medium |
| C6 | What key messages or points must be included? | Required content | `DETAIL`, `STRUCTURE` | Medium |
| C7 | What should definitely be avoided? | Boundaries | `RISK` | Medium |
| C8 | Where will this content be published or used? | Channel context | `DETAIL`, `SYNTHESIS` | Medium |
| C9 | What's the deadline? | Urgency | `DETAIL` | Low |
| C10 | Is this part of a larger campaign or standalone? | Context | `SYNTHESIS`, `VISION` | Low |
| C11 | What do you want the reader to feel or do after reading? | Emotional goal | `EMPATHY`, `PERSUASION` | Low (F types) |

#### Conditional Questions

- If blog post: "What's the SEO keyword or topic focus?"
- If email: "What's the relationship with the recipient?"
- If marketing copy: "What's the unique selling proposition?"

---

### CREATION_TECHNICAL Tasks

Select 3-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| T1 | What technology stack or language is required? | Technical constraints | `DETAIL`, `OBJECTIVE` | High |
| T2 | What's the skill level of people who'll use this? | User context | `PEDAGOGY`, `EMPATHY` | High |
| T3 | Are there existing conventions, style guides, or patterns to follow? | Standards | `STRUCTURE`, `DETAIL` | Medium |
| T4 | What error handling or edge cases matter? | Robustness | `RISK`, `OBJECTIVE` | Medium |
| T5 | Is this for production or prototype/proof of concept? | Quality level | `DETAIL`, `DECISIVE` | Medium |
| T6 | What's the expected input and output? | Interface definition | `DETAIL`, `STRUCTURE` | Medium |
| T7 | Are there performance requirements? | Non-functional needs | `OBJECTIVE`, `DETAIL` | Low |
| T8 | What documentation level is needed? | Documentation scope | `PEDAGOGY`, `STRUCTURE` | Low |
| T9 | How will this be tested or validated? | Quality assurance | `OBJECTIVE`, `RISK` | Low |
| T10 | What's the deployment environment? | Infrastructure context | `DETAIL`, `OBJECTIVE` | Low |

#### Conditional Questions

- If API: "What authentication method?"
- If documentation: "What's the reader's existing knowledge?"
- If code: "What's the existing codebase context?"

---

### IDEATION Tasks

Select 3-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| I1 | What problem or opportunity are you generating ideas for? | Focus area | `CREATIVE`, `DETAIL` | High |
| I2 | What constraints should ideas respect? | Boundaries | `DETAIL`, `RISK` | High |
| I3 | How wild can ideas be — incremental improvements or moonshots? | Creativity scope | `CREATIVE`, `EXPLORE` | Medium |
| I4 | What's already been considered or tried? | Avoid repetition | `EXPLORE`, `SYNTHESIS` | Medium |
| I5 | What definitely won't work or isn't feasible? | Eliminate dead ends | `RISK`, `OBJECTIVE` | Medium |
| I6 | What resources would be available to implement ideas? | Feasibility context | `DETAIL`, `OBJECTIVE` | Medium |
| I7 | Who are the users or beneficiaries of these ideas? | User focus | `EMPATHY`, `VISION` | Medium |
| I8 | What inspires you or what examples do you admire? | Inspiration sources | `CREATIVE`, `VISION` | Low |
| I9 | What's the timeline for implementing ideas? | Urgency | `DETAIL` | Low |
| I10 | How will ideas be evaluated or prioritised? | Selection criteria | `DECISIVE`, `OBJECTIVE` | Low |

#### Conditional Questions

- If product ideation: "What's the core user pain point?"
- If process improvement: "What's the current process?"
- If creative project: "What mood or feeling are you going for?"

---

### PROBLEM_SOLVING Tasks

Select 4-6 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| P1 | What exactly is the problem? Can you describe the symptoms? | Problem definition | `DETAIL`, `OBJECTIVE` | High |
| P2 | When did this problem start or when was it first noticed? | Timeline | `DETAIL`, `SYNTHESIS` | High |
| P3 | What have you already tried? | Avoid redundancy | `ITERATIVE`, `EXPLORE` | High |
| P4 | What happens if this isn't solved? | Stakes/urgency | `RISK`, `VISION` | Medium |
| P5 | Are there symptoms versus root causes to distinguish? | Depth of problem | `OBJECTIVE`, `ABSTRACTION` | Medium |
| P6 | Who has relevant expertise or has seen this before? | Resource identification | `SYNTHESIS`, `EMPATHY` | Medium |
| P7 | What changed right before the problem appeared? | Cause hunting | `DETAIL`, `SYNTHESIS` | Medium |
| P8 | Is this a recurring problem or first occurrence? | Pattern | `ITERATIVE`, `SYNTHESIS` | Medium |
| P9 | What would a solution need to achieve? | Success criteria | `DECISIVE`, `OBJECTIVE` | Medium |
| P10 | What constraints affect possible solutions? | Solution boundaries | `DETAIL`, `RISK` | Medium |
| P11 | What's your hypothesis about the cause? | Current thinking | `OBJECTIVE`, `ABSTRACTION` | Low |
| P12 | What quick wins might provide relief while solving the deeper issue? | Pragmatic relief | `DECISIVE`, `EXPLORE` | Low |

#### Conditional Questions

- If technical problem: "What error messages or logs are available?"
- If people problem: "Who are the key parties involved?"
- If process problem: "Where does the process break down?"

---

### LEARNING Tasks

Select 2-4 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| L1 | What's your current understanding level of this topic? | Baseline assessment | `PEDAGOGY`, `DETAIL` | High |
| L2 | What specifically is confusing or unclear? | Focus area | `PEDAGOGY`, `DETAIL` | High |
| L3 | What do you need to do with this knowledge? | Application context | `DETAIL`, `VISION` | Medium |
| L4 | Do you learn better from examples, explanations, or analogies? | Learning style | `PEDAGOGY`, `EMPATHY` | Medium |
| L5 | How deep do you need to go (overview vs. expert level)? | Depth calibration | `PEDAGOGY`, `DETAIL` | Medium |
| L6 | What related concepts do you already understand? | Build on existing | `PEDAGOGY`, `SYNTHESIS` | Low |
| L7 | Is there a specific format that works well for you? | Output preference | `STRUCTURE`, `PEDAGOGY` | Low |
| L8 | What's the urgency — immediate application or general knowledge? | Timeline | `DETAIL` | Low |

#### Conditional Questions

- If technical topic: "What's your technical background?"
- If abstract concept: "Would a real-world analogy help?"
- If skill-based: "Have you attempted this before?"

---

### PERSUASION Tasks

Select 4-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| PE1 | Who specifically needs to be convinced? | Target audience | `EMPATHY`, `PERSUASION` | High |
| PE2 | What are their current beliefs or position? | Starting point | `PERSUASION`, `SYNTHESIS` | High |
| PE3 | What objections or concerns might they raise? | Anticipate resistance | `RISK`, `PERSUASION` | High |
| PE4 | What evidence or arguments do they find compelling? | Persuasion style | `PERSUASION`, `OBJECTIVE` | Medium |
| PE5 | What's their motivation — what do they care about? | Values alignment | `EMPATHY`, `PERSUASION` | Medium |
| PE6 | What do you want them to do after being persuaded? | Call to action | `DECISIVE`, `PERSUASION` | Medium |
| PE7 | What's your relationship with this audience? | Trust level | `WARM`, `EMPATHY` | Medium |
| PE8 | What's at stake for them? | Their perspective | `EMPATHY`, `RISK` | Low |
| PE9 | Have you tried to persuade them before? What happened? | History | `ITERATIVE`, `SYNTHESIS` | Low |
| PE10 | What's the format for this persuasion (written, verbal, presentation)? | Medium | `STRUCTURE`, `PERSUASION` | Low |

#### Conditional Questions

- If pitch/proposal: "What's the ask (funding, approval, buy-in)?"
- If negotiation: "What's your BATNA (best alternative)?"
- If sales: "Where are they in the buyer journey?"

---

### FEEDBACK Tasks

Select 3-4 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| F1 | What aspects do you most want feedback on? | Focus area | `OBJECTIVE`, `ITERATIVE` | High |
| F2 | What's working well that should be preserved? | Identify strengths | `OBJECTIVE`, `EMPATHY` | High |
| F3 | What's the purpose of this work? | Context for evaluation | `SYNTHESIS`, `OBJECTIVE` | Medium |
| F4 | How candid can the feedback be? | Calibrate directness | `WARM`, `EMPATHY` | Medium |
| F5 | What will you do with the feedback? | Action orientation | `ITERATIVE`, `DECISIVE` | Medium |
| F6 | Who is the intended audience for this work? | Evaluation criteria | `EMPATHY`, `OBJECTIVE` | Medium |
| F7 | What's the timeline for revisions? | Urgency | `DETAIL` | Low |
| F8 | Are there specific criteria or standards to evaluate against? | Benchmarks | `OBJECTIVE`, `STRUCTURE` | Low |

#### Conditional Questions

- If writing: "What style guide or standards apply?"
- If design: "What's the brand or aesthetic context?"
- If code: "What's the testing/review process?"

---

### RESEARCH Tasks

Select 3-5 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| R1 | What do you already know about this topic? | Starting point | `SYNTHESIS`, `DETAIL` | High |
| R2 | What specific questions need to be answered? | Research focus | `DETAIL`, `OBJECTIVE` | High |
| R3 | What sources are acceptable or preferred? | Source criteria | `OBJECTIVE`, `RISK` | Medium |
| R4 | What time period is relevant? | Temporal scope | `DETAIL` | Medium |
| R5 | How rigorous does this need to be (quick scan vs. thorough)? | Depth | `DETAIL`, `OBJECTIVE` | Medium |
| R6 | What format should findings take? | Output format | `STRUCTURE` | Medium |
| R7 | Who will use this research? | Audience | `EMPATHY`, `PERSUASION` | Medium |
| R8 | What would be surprising or change your approach? | Challenge assumptions | `EXPLORE`, `OBJECTIVE` | Low |
| R9 | What's the deadline? | Urgency | `DETAIL` | Low |
| R10 | Are there competing perspectives to consider? | Balance | `EXPLORE`, `SYNTHESIS` | Low |

#### Conditional Questions

- If market research: "What geography or segment?"
- If academic research: "What's the citation style needed?"
- If competitive research: "Who are the key competitors to include?"

---

### GOAL_SETTING Tasks

Select 3-4 questions from this pool.

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| G1 | What's the overarching vision this goal supports? | Strategic context | `VISION`, `SYNTHESIS` | High |
| G2 | What time frame applies to this goal? | Temporal scope | `DETAIL`, `VISION` | High |
| G3 | How will progress be measured? | Metrics definition | `OBJECTIVE`, `DETAIL` | High |
| G4 | What resources are available? | Feasibility | `DETAIL`, `OBJECTIVE` | Medium |
| G5 | What obstacles are anticipated? | Risk awareness | `RISK`, `VISION` | Medium |
| G6 | Who else needs to be aligned on this goal? | Stakeholders | `EMPATHY`, `PERSUASION` | Medium |
| G7 | What happens if the goal isn't achieved? | Stakes | `RISK`, `VISION` | Low |
| G8 | What's the stretch version versus minimum acceptable? | Ambition calibration | `DECISIVE`, `RISK` | Low |

#### Conditional Questions

- If team goal: "How will individual contributions be tracked?"
- If personal goal: "What accountability structures help you?"
- If business goal: "How does this connect to revenue or growth?"

---

## Framework Selection Questions

When task category is unclear or multiple frameworks could apply, ask:

| ID | Question | Purpose | Guides Selection |
|----|----------|---------|------------------|
| FS1 | Is precise tone and audience targeting critical? | CO-STAR indicator | Yes → CO-STAR |
| FS2 | Does this require looking things up or using tools? | ReAct indicator | Yes → ReAct |
| FS3 | Is first-draft quality insufficient—does this need iteration? | Self-Refine indicator | Yes → Self-Refine |
| FS4 | Would stepping back to principles help before tackling specifics? | Step-Back indicator | Yes → Step-Back |
| FS5 | Can the response be broken into parallel, independent points? | Skeleton-of-Thought indicator | Yes → SoT |
| FS6 | Is the optimal prompt structure unclear? | Meta Prompting indicator | Yes → Meta |

---

## Cross-Framework Question Combinations

When tasks might benefit from combined frameworks:

### CO-STAR + Self-Refine (High-Quality Content)

Ask both:
- COS1-COS4 (audience, objective, style, tone)
- SRF1-SRF2 (quality criteria, priority aspects)

### ReAct + Step-Back (Complex Problem-Solving)

Ask both:
- REA1-REA3 (tools, goal, termination)
- STB1-STB2 (domain, principles)

### Skeleton-of-Thought + CO-STAR (Structured Tonal Content)

Ask both:
- SOT1-SOT3 (topic, scope, order)
- COS3-COS4 (style, tone)

---

## Question Selection Algorithm

```
1. Identify task category (primary and secondary if applicable)

2. Check for framework-specific indicators:
   - Tone/style critical? → CO-STAR questions
   - Tool-using/research? → ReAct questions
   - Quality-critical/iterative? → Self-Refine questions
   - Principle-based reasoning? → Step-Back questions
   - Parallel structure possible? → Skeleton-of-Thought questions
   - Prompt creation task? → Meta Prompting questions

3. Start with Universal Questions:
   - Select 2-3 from U1-U6 based on task nature
   - Use personality-adjusted phrasing if available

4. Add Framework-Specific Questions (if framework identified):
   - Select from the identified framework pool
   - Priority order: High → Medium → Low
   - Stop when sufficient clarity achieved

5. Add Category-Specific Questions:
   - Select from the primary category pool
   - Base quantity on complexity:
     Simple: 2-3 category questions
     Moderate: 3-5 category questions
     Complex: 5-7 category questions

6. Apply Personality Adjustments:
   - Adjust total count (see personality_calibration.md)
   - Adjust phrasing for personality patterns
   - If no personality: use neutral phrasing

7. Add Framework Fit Verification if uncertain:
   - For Skeleton-of-Thought: Check interdependence
   - For ReAct: Verify tool availability
   - For Self-Refine: Confirm iteration acceptable

8. Add Conditional Questions if triggered by context

9. Sequence questions logically:
   - Context/scope questions first
   - Goal/success questions second
   - Constraints third
   - Specific details last

10. Cap total questions:
    - Simple task + clear framework: 4-5 questions
    - Moderate task: 5-8 questions
    - Complex task + framework uncertainty: 8-12 questions
```

---

## Output Format for Questions

Return questions in this structure:

```json
{
  "questions": [
    {
      "id": "U2",
      "question": "What does success look like?",
      "purpose": "Define success criteria",
      "required": true
    },
    {
      "id": "COS3",
      "question": "What writing style fits best—formal, conversational, technical, storytelling?",
      "purpose": "Style determination for CO-STAR framework",
      "required": true
    },
    {
      "id": "S4",
      "question": "Who are the key stakeholders who need to buy in?",
      "purpose": "Identify stakeholder landscape",
      "required": false
    }
  ],
  "total_questions": 6,
  "complexity_assessment": "moderate",
  "framework_identified": "CO-STAR",
  "question_rationale": "Selected CO-STAR-focused questions with emphasis on audience and tone given the content creation context."
}
```
