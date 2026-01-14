# Question Bank Reference Document

**Version:** 2026.01.14.150531
**Generated:** Wednesday, 14 January 2026 at 15:05:31
**Total Questions:** 210
**Total Personality Variants:** 22

**Generation Source:** Database (questions & question_variants tables)

## Purpose

This document contains all clarifying questions organised by task category and framework. Use this to generate appropriate questions after classifying the user's task. Questions should be selected based on task category, adjusted for personality (if provided), and limited to the appropriate quantity.

---

## Universal Questions

These questions are relevant across most task categories. Select 2-3 from this pool for every task.

### Core Universal Questions

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| U1 | What is the scope or boundary of this task? What's explicitly out of scope? | Define limits | `STRUCTURE`, `DETAIL` | high |
| U2 | How will you measure success? What does a good outcome look like? | Success criteria | `DECISIVE`, `OBJECTIVE` | high |
| U3 | Who is the intended audience for this output? | Audience clarity | `EMPATHY`, `PERSUASION` | high |
| U4 | What constraints exist (time, budget, length, format, resources)? | Limitation awareness | `DETAIL`, `OBJECTIVE` | high |
| U5 | What background context is essential to understand? | Context gathering | `SYNTHESIS`, `DETAIL` | high |
| U6 | What have you already tried or considered? | Avoid redundancy | `EXPLORE`, `ITERATIVE` | high |

### Personality-Adjusted Universal Questions

For each universal question, select the phrasing that matches the user's personality (or use neutral if no personality data):

#### U2: Success criteria (Personality Variants)

| Personality Pattern | Phrasing |
|---------------------|----------|
| high_t_high_j | "What are the measurable criteria for success?" |
| high_f_high_j | "What would a successful outcome mean for the people involved?" |
| high_t_high_p | "What signals would indicate this is working?" |
| high_f_high_p | "How would you feel if this turned out well? What would that look like?" |
| high_a | "What does success look like?" |
| high_t_identity | "What would success look like? And what pitfalls do you want to avoid?" |
| neutral | "How will you measure whether this is successful?" |

#### U4: Limitation awareness (Personality Variants)

| Personality Pattern | Phrasing |
|---------------------|----------|
| high_s | "What specific limitations apply — budget, time, word count, format?" |
| high_n | "What boundaries should this stay within?" |
| high_t | "What are the hard constraints versus nice-to-haves?" |
| high_f | "What limitations should we respect to keep everyone comfortable?" |
| high_j | "What are the non-negotiable constraints?" |
| high_p | "What constraints exist, and which might be flexible?" |
| neutral | "What constraints or limitations should be considered?" |

---

## Framework-Specific Questions

### CO-STAR Tasks (Content with Tone/Style Requirements)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| COS1 | Who is the specific audience for this content? What's their role and knowledge level? | Audience definition | `EMPATHY`, `PEDAGOGY` | high |
| COS2 | What's the primary objective—what should the reader do, think, or feel after reading? | Objective clarity | `DECISIVE`, `PERSUASION` | high |
| COS3 | What writing style fits best—formal, conversational, technical, storytelling? | Style determination | `STRUCTURE`, `CREATIVE` | high |
| COS4 | What tone should come through—professional, friendly, urgent, empathetic, authoritative? | Tone setting | `WARM`, `EMPATHY` | high |
| COS5 | What format works best for this content—paragraphs, bullet points, sections with headers? | Response format | `STRUCTURE` | medium |
| COS6 | What background context does the AI need to understand the situation? | Context gathering | `SYNTHESIS` | medium |
| COS7 | Are there any words, phrases, or topics to avoid? | Boundaries | `RISK` | medium |
| COS8 | Is there existing content or examples to match in style? | Style reference | `CREATIVE`, `ITERATIVE` | low |

#### Personality-Adjusted Phrasing for CO-STAR  (Content with Tone/Style Requirements)

| Trait | Question Adaptation |
|-------|---------------------|
| high_t | "What are the key facts and data points the content must convey?" |
| high_f | "How should the reader feel after engaging with this content?" |
| high_s | "What specific details and examples should be included?" |
| high_n | "What's the big-picture message or theme?" |

---

## Framework-Specific Questions

### ReAct Tasks (Agentic/Tool-Using Workflows)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| REA1 | What tools, resources, or information sources are available? | Tool inventory | `AGENTIC`, `DETAIL` | high |
| REA2 | What's the end goal—what specific outcome are you trying to achieve? | Goal clarity | `DECISIVE`, `OBJECTIVE` | high |
| REA3 | How will you know when the task is complete? What signals success? | Termination criteria | `DECISIVE`, `OBJECTIVE` | high |
| REA4 | What constraints or rules must be followed during the process? | Guardrails | `RISK`, `DETAIL` | medium |
| REA5 | If initial approaches fail, what alternatives should be considered? | Fallback planning | `EXPLORE`, `ITERATIVE` | medium |
| REA6 | Are there any actions or sources that should be avoided? | Boundaries | `RISK` | low |

#### Conditional Questions for ReAct  (Agentic/Tool-Using Workflows)

- If research task: "What sources are considered authoritative?"
- If technical task: "What error handling is needed?"
- If multi-step task: "What are the dependencies between steps?"

---

## Framework-Specific Questions

### Self-Refine Tasks (Quality-Critical Iterative Work)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| SRF1 | What are the specific quality criteria for this output? | Quality standards | `ITERATIVE`, `OBJECTIVE` | high |
| SRF2 | What aspects are most important to get right? | Priority focus | `DECISIVE`, `OBJECTIVE` | high |
| SRF3 | How many refinement iterations are acceptable? | Iteration bounds | `ITERATIVE` | medium |
| SRF4 | What's the minimum acceptable quality threshold? | Quality floor | `OBJECTIVE`, `DECISIVE` | medium |
| SRF5 | Are there examples of excellent outputs to aspire to? | Quality reference | `ITERATIVE`, `CREATIVE` | medium |
| SRF6 | What common mistakes or pitfalls should be watched for? | Error awareness | `RISK`, `ITERATIVE` | low |

#### Personality-Adjusted Phrasing for Self-Refine  (Quality-Critical Iterative Work)

| Trait | Question Adaptation |
|-------|---------------------|
| high_a | "What would make this output genuinely excellent versus merely acceptable?" |
| high_j | "What are the non-negotiable quality criteria?" |
| high_p | "What aspects would benefit most from exploration and refinement?" |
| high_t_identity | "What quality threshold should stop further iteration?" |

---

## Framework-Specific Questions

### Step-Back Tasks (Principle-Based Reasoning)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| STB1 | What domain or field does this question belong to? | Domain identification | `ABSTRACTION`, `SYNTHESIS` | high |
| STB2 | What general principles or theories might apply? | Principle prompting | `ABSTRACTION`, `VISION` | high |
| STB3 | What's the specific question you need answered? | Question clarity | `DETAIL`, `OBJECTIVE` | high |
| STB4 | What level of detail is needed in the answer? | Depth calibration | `DETAIL` | medium |
| STB5 | Are there related concepts you already understand? | Build on existing | `SYNTHESIS`, `PEDAGOGY` | low |

#### Conditional Questions for Step-Back  (Principle-Based Reasoning)

- If STEM task: "What formulas or laws might be relevant?"
- If knowledge task: "What categories of information would help?"
- If reasoning task: "What logical principles apply?"

---

## Framework-Specific Questions

### Skeleton-of-Thought Tasks (Structured Parallel Content)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| SOT1 | What's the main topic or question to address? | Topic focus | `STRUCTURE`, `DETAIL` | high |
| SOT2 | How many main points should the response cover? | Scope sizing | `STRUCTURE`, `DETAIL` | high |
| SOT3 | Is there a preferred logical order for the points? | Structure guidance | `STRUCTURE`, `OBJECTIVE` | medium |
| SOT4 | How detailed should each point be? | Depth calibration | `DETAIL` | medium |
| SOT5 | Does coherence between points matter, or can they be independent? | Framework fit check | `STRUCTURE`, `PARALLEL` | medium |

---

## Framework-Specific Questions

### Meta Prompting Tasks (Prompt Optimisation)

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| MET1 | What task will the generated prompt be used for? | Target task | `DETAIL`, `OBJECTIVE` | high |
| MET2 | What model will execute the prompt (if known)? | Model targeting | `DETAIL` | high |
| MET3 | What output format and quality is expected? | Output requirements | `STRUCTURE`, `OBJECTIVE` | high |
| MET4 | What's the user profile for the generated prompt (if known)? | Personality targeting | `EMPATHY`, `PEDAGOGY` | medium |
| MET5 | Are there existing prompts to improve, or starting from scratch? | Starting point | `ITERATIVE` | medium |
| MET6 | What constraints must the prompt respect? | Boundaries | `RISK`, `DETAIL` | medium |
| MET7 | What has been tried that didn't work well? | Failure learning | `ITERATIVE`, `RISK` | low |

---

## Category-Specific Questions

### DECISION Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| D1 | What options are you currently considering? | Map the decision space | `EXPLORE`, `DETAIL` | high |
| D2 | What criteria matter most in making this decision? | Identify decision factors | `OBJECTIVE`, `DECISIVE` | high |
| D3 | What's the cost or impact of choosing wrong? | Understand stakes | `RISK`, `OBJECTIVE` | medium |
| D4 | Is this decision reversible, or are you locked in once you choose? | Assess commitment level | `RISK` | medium |
| D5 | What information would make this decision obvious? | Identify knowledge gaps | `SYNTHESIS`, `OBJECTIVE` | medium |
| D6 | What's your timeline for making this decision? | Urgency assessment | `DETAIL` | medium |
| D7 | Who else is affected by or involved in this decision? | Stakeholder mapping | `EMPATHY`, `SYNTHESIS` | medium |
| D8 | Have you made similar decisions before? What worked or didn't? | Learn from history | `ITERATIVE`, `SYNTHESIS` | low |
| D9 | What's your gut telling you, and why might you be hesitating? | Surface intuition | `EMPATHY`, `RISK` | low |
| D10 | If you had to decide right now, what would you choose? | Force prioritisation | `DECISIVE` | low |

#### Conditional Questions

- If many options revealed: "Can we narrow to the top 3-4 contenders?"
- If vague criteria: "If you could only optimise for one factor, which would it be?"
- If high stakes: "What's your risk tolerance here?"

---

## Category-Specific Questions

### STRATEGY Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| S1 | What's the time horizon for this strategy? | Temporal scope | `VISION`, `DETAIL` | high |
| S2 | What resources are available (budget, team, tools)? | Resource constraints | `DETAIL`, `OBJECTIVE` | high |
| S3 | What does success look like at the end of this period? | Goal definition | `VISION`, `DECISIVE` | high |
| S4 | Who are the key stakeholders who need to buy in? | Stakeholder mapping | `EMPATHY`, `PERSUASION` | high |
| S5 | What's been tried before, and what were the results? | Historical context | `ITERATIVE`, `SYNTHESIS` | medium |
| S6 | What are the biggest obstacles or risks you foresee? | Risk identification | `RISK`, `VISION` | medium |
| S7 | Who are your main competitors or alternatives? | Competitive context | `OBJECTIVE`, `SYNTHESIS` | medium |
| S8 | What's your unique advantage or differentiation? | Positioning | `VISION`, `CREATIVE` | medium |
| S9 | What's the current state you're starting from? | Baseline assessment | `DETAIL`, `OBJECTIVE` | medium |
| S10 | What dependencies or external factors could affect this? | External awareness | `RISK`, `SYNTHESIS` | low |
| S11 | What would cause this strategy to fail? | Failure mode analysis | `RISK`, `OBJECTIVE` | low |
| S12 | What's your appetite for risk versus proven approaches? | Risk tolerance | `RISK`, `DECISIVE` | low |

#### Conditional Questions

- If startup context: "What stage is your company (idea, MVP, growth)?"
- If marketing strategy: "What channels have you used before?"
- If product strategy: "Who is your target customer persona?"

---

## Category-Specific Questions

### ANALYSIS Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| A1 | What data or information do you have access to? | Data inventory | `DETAIL`, `OBJECTIVE` | high |
| A2 | What's the specific question you're trying to answer? | Focus the analysis | `OBJECTIVE`, `DETAIL` | high |
| A3 | What's the hypothesis you're testing, if any? | Frame the inquiry | `OBJECTIVE`, `VISION` | medium |
| A4 | What level of depth is needed (surface scan vs. deep dive)? | Scope calibration | `DETAIL` | medium |
| A5 | Are there specific angles or perspectives you want explored? | Direction setting | `EXPLORE`, `SYNTHESIS` | medium |
| A6 | Who needs to act on this analysis? | Audience/action | `EMPATHY`, `PERSUASION` | medium |
| A7 | What would change your mind or surprise you? | Challenge assumptions | `EXPLORE`, `OBJECTIVE` | low |
| A8 | What's the deadline or urgency for this analysis? | Time constraints | `DETAIL` | medium |
| A9 | What format should the analysis take? | Output format | `STRUCTURE` | low |
| A10 | Are there any known biases or blind spots to watch for? | Quality control | `OBJECTIVE`, `RISK` | low |

#### Conditional Questions

- If data analysis: "What's the data format and size?"
- If market analysis: "What geography or segment?"
- If root cause analysis: "When did the issue first appear?"

---

## Category-Specific Questions

### CREATION_CONTENT Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| C1 | Who is the target audience for this content? | Audience definition | `EMPATHY`, `PEDAGOGY` | high |
| C2 | What's the primary goal or call to action? | Purpose clarity | `PERSUASION`, `DECISIVE` | high |
| C3 | What tone is appropriate (formal, casual, technical, friendly)? | Voice/style | `WARM`, `STRUCTURE` | high |
| C4 | What's the target length or word count? | Scope | `DETAIL` | medium |
| C5 | Are there examples of style or content you want to emulate? | Style reference | `CREATIVE`, `ITERATIVE` | medium |
| C6 | What key messages or points must be included? | Required content | `DETAIL`, `STRUCTURE` | medium |
| C7 | What should definitely be avoided? | Boundaries | `RISK` | medium |
| C8 | Where will this content be published or used? | Channel context | `DETAIL`, `SYNTHESIS` | medium |
| C9 | What's the deadline? | Urgency | `DETAIL` | low |
| C10 | Is this part of a larger campaign or standalone? | Context | `SYNTHESIS`, `VISION` | low |
| C11 | What do you want the reader to feel or do after reading? | Emotional goal | `EMPATHY`, `PERSUASION` | low |

#### Conditional Questions

- If blog post: "What's the SEO keyword or topic focus?"
- If email: "What's the relationship with the recipient?"
- If marketing copy: "What's the unique selling proposition?"

---

## Category-Specific Questions

### CREATION_TECHNICAL Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| T1 | What technology stack or language is required? | Technical constraints | `DETAIL`, `OBJECTIVE` | high |
| T2 | What's the skill level of people who'll use this? | User context | `PEDAGOGY`, `EMPATHY` | high |
| T3 | Are there existing conventions, style guides, or patterns to follow? | Standards | `STRUCTURE`, `DETAIL` | medium |
| T4 | What error handling or edge cases matter? | Robustness | `RISK`, `OBJECTIVE` | medium |
| T5 | Is this for production or prototype/proof of concept? | Quality level | `DETAIL`, `DECISIVE` | medium |
| T6 | What's the expected input and output? | Interface definition | `DETAIL`, `STRUCTURE` | medium |
| T7 | Are there performance requirements? | Non-functional needs | `OBJECTIVE`, `DETAIL` | low |
| T8 | What documentation level is needed? | Documentation scope | `PEDAGOGY`, `STRUCTURE` | low |
| T9 | How will this be tested or validated? | Quality assurance | `OBJECTIVE`, `RISK` | low |
| T10 | What's the deployment environment? | Infrastructure context | `DETAIL`, `OBJECTIVE` | low |

#### Conditional Questions

- If API: "What authentication method?"
- If documentation: "What's the reader's existing knowledge?"
- If code: "What's the existing codebase context?"

---

## Category-Specific Questions

### IDEATION Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| I1 | What problem or opportunity are you generating ideas for? | Focus area | `CREATIVE`, `DETAIL` | high |
| I2 | What constraints should ideas respect? | Boundaries | `DETAIL`, `RISK` | high |
| I3 | How wild can ideas be — incremental improvements or moonshots? | Creativity scope | `CREATIVE`, `EXPLORE` | medium |
| I4 | What's already been considered or tried? | Avoid repetition | `EXPLORE`, `SYNTHESIS` | medium |
| I5 | What definitely won't work or isn't feasible? | Eliminate dead ends | `RISK`, `OBJECTIVE` | medium |
| I6 | What resources would be available to implement ideas? | Feasibility context | `DETAIL`, `OBJECTIVE` | medium |
| I7 | Who are the users or beneficiaries of these ideas? | User focus | `EMPATHY`, `VISION` | medium |
| I8 | What inspires you or what examples do you admire? | Inspiration sources | `CREATIVE`, `VISION` | low |
| I9 | What's the timeline for implementing ideas? | Urgency | `DETAIL` | low |
| I10 | How will ideas be evaluated or prioritised? | Selection criteria | `DECISIVE`, `OBJECTIVE` | low |

#### Conditional Questions

- If product ideation: "What's the core user pain point?"
- If process improvement: "What's the current process?"
- If creative project: "What mood or feeling are you going for?"

---

## Category-Specific Questions

### PROBLEM_SOLVING Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| P1 | What exactly is the problem? Can you describe the symptoms? | Problem definition | `DETAIL`, `OBJECTIVE` | high |
| P2 | When did this problem start or when was it first noticed? | Timeline | `DETAIL`, `SYNTHESIS` | high |
| P3 | What have you already tried? | Avoid redundancy | `ITERATIVE`, `EXPLORE` | high |
| P4 | What happens if this isn't solved? | Stakes/urgency | `RISK`, `VISION` | medium |
| P5 | Are there symptoms versus root causes to distinguish? | Depth of problem | `OBJECTIVE`, `ABSTRACTION` | medium |
| P6 | Who has relevant expertise or has seen this before? | Resource identification | `SYNTHESIS`, `EMPATHY` | medium |
| P7 | What changed right before the problem appeared? | Cause hunting | `DETAIL`, `SYNTHESIS` | medium |
| P8 | Is this a recurring problem or first occurrence? | Pattern | `ITERATIVE`, `SYNTHESIS` | medium |
| P9 | What would a solution need to achieve? | Success criteria | `DECISIVE`, `OBJECTIVE` | medium |
| P10 | What constraints affect possible solutions? | Solution boundaries | `DETAIL`, `RISK` | medium |
| P11 | What's your hypothesis about the cause? | Current thinking | `OBJECTIVE`, `ABSTRACTION` | low |
| P12 | What quick wins might provide relief while solving the deeper issue? | Pragmatic relief | `DECISIVE`, `EXPLORE` | low |

#### Conditional Questions

- If technical problem: "What error messages or logs are available?"
- If people problem: "Who are the key parties involved?"
- If process problem: "Where does the process break down?"

---

## Category-Specific Questions

### LEARNING Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| L1 | What's your current understanding level of this topic? | Baseline assessment | `PEDAGOGY`, `DETAIL` | high |
| L2 | What specifically is confusing or unclear? | Focus area | `PEDAGOGY`, `DETAIL` | high |
| L3 | What do you need to do with this knowledge? | Application context | `DETAIL`, `VISION` | medium |
| L4 | Do you learn better from examples, explanations, or analogies? | Learning style | `PEDAGOGY`, `EMPATHY` | medium |
| L5 | How deep do you need to go (overview vs. expert level)? | Depth calibration | `PEDAGOGY`, `DETAIL` | medium |
| L6 | What related concepts do you already understand? | Build on existing | `PEDAGOGY`, `SYNTHESIS` | low |
| L7 | Is there a specific format that works well for you? | Output preference | `STRUCTURE`, `PEDAGOGY` | low |
| L8 | What's the urgency — immediate application or general knowledge? | Timeline | `DETAIL` | low |

#### Conditional Questions

- If technical topic: "What's your technical background?"
- If abstract concept: "Would a real-world analogy help?"
- If skill-based: "Have you attempted this before?"

---

## Category-Specific Questions

### PERSUASION Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| PE1 | Who specifically needs to be convinced? | Target audience | `EMPATHY`, `PERSUASION` | high |
| PE2 | What are their current beliefs or position? | Starting point | `PERSUASION`, `SYNTHESIS` | high |
| PE3 | What objections or concerns might they raise? | Anticipate resistance | `RISK`, `PERSUASION` | high |
| PE4 | What evidence or arguments do they find compelling? | Persuasion style | `PERSUASION`, `OBJECTIVE` | medium |
| PE5 | What's their motivation — what do they care about? | Values alignment | `EMPATHY`, `PERSUASION` | medium |
| PE6 | What do you want them to do after being persuaded? | Call to action | `DECISIVE`, `PERSUASION` | medium |
| PE7 | What's your relationship with this audience? | Trust level | `WARM`, `EMPATHY` | medium |
| PE8 | What's at stake for them? | Their perspective | `EMPATHY`, `RISK` | low |
| PE9 | Have you tried to persuade them before? What happened? | History | `ITERATIVE`, `SYNTHESIS` | low |
| PE10 | What's the format for this persuasion (written, verbal, presentation)? | Medium | `STRUCTURE`, `PERSUASION` | low |

#### Conditional Questions

- If pitch/proposal: "What's the ask (funding, approval, buy-in)?"
- If negotiation: "What's your BATNA (best alternative)?"
- If sales: "Where are they in the buyer journey?"

---

## Category-Specific Questions

### FEEDBACK Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| F1 | What aspects do you most want feedback on? | Focus area | `OBJECTIVE`, `ITERATIVE` | high |
| F2 | What's working well that should be preserved? | Identify strengths | `OBJECTIVE`, `EMPATHY` | high |
| F3 | What's the purpose of this work? | Context for evaluation | `SYNTHESIS`, `OBJECTIVE` | medium |
| F4 | How candid can the feedback be? | Calibrate directness | `WARM`, `EMPATHY` | medium |
| F5 | What will you do with the feedback? | Action orientation | `ITERATIVE`, `DECISIVE` | medium |
| F6 | Who is the intended audience for this work? | Evaluation criteria | `EMPATHY`, `OBJECTIVE` | medium |
| F7 | What's the timeline for revisions? | Urgency | `DETAIL` | low |
| F8 | Are there specific criteria or standards to evaluate against? | Benchmarks | `OBJECTIVE`, `STRUCTURE` | low |

#### Conditional Questions

- If writing: "What style guide or standards apply?"
- If design: "What's the brand or aesthetic context?"
- If code: "What's the testing/review process?"

---

## Category-Specific Questions

### RESEARCH Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| R1 | What do you already know about this topic? | Starting point | `SYNTHESIS`, `DETAIL` | high |
| R2 | What specific questions need to be answered? | Research focus | `DETAIL`, `OBJECTIVE` | high |
| R3 | What sources are acceptable or preferred? | Source criteria | `OBJECTIVE`, `RISK` | medium |
| R4 | What time period is relevant? | Temporal scope | `DETAIL` | medium |
| R5 | How rigorous does this need to be (quick scan vs. thorough)? | Depth | `DETAIL`, `OBJECTIVE` | medium |
| R6 | What format should findings take? | Output format | `STRUCTURE` | medium |
| R7 | Who will use this research? | Audience | `EMPATHY`, `PERSUASION` | medium |
| R8 | What would be surprising or change your approach? | Challenge assumptions | `EXPLORE`, `OBJECTIVE` | low |
| R9 | What's the deadline? | Urgency | `DETAIL` | low |
| R10 | Are there competing perspectives to consider? | Balance | `EXPLORE`, `SYNTHESIS` | low |

#### Conditional Questions

- If market research: "What geography or segment?"
- If academic research: "What's the citation style needed?"
- If competitive research: "Who are the key competitors to include?"

---

## Category-Specific Questions

### GOAL_SETTING Tasks

| ID | Question | Purpose | Cognitive Reqs | Priority |
|----|----------|---------|----------------|----------|
| G1 | What's the overarching vision this goal supports? | Strategic context | `VISION`, `SYNTHESIS` | high |
| G2 | What time frame applies to this goal? | Temporal scope | `DETAIL`, `VISION` | high |
| G3 | How will progress be measured? | Metrics definition | `OBJECTIVE`, `DETAIL` | high |
| G4 | What resources are available? | Feasibility | `DETAIL`, `OBJECTIVE` | medium |
| G5 | What obstacles are anticipated? | Risk awareness | `RISK`, `VISION` | medium |
| G6 | Who else needs to be aligned on this goal? | Stakeholders | `EMPATHY`, `PERSUASION` | medium |
| G7 | What happens if the goal isn't achieved? | Stakes | `RISK`, `VISION` | low |
| G8 | What's the stretch version versus minimum acceptable? | Ambition calibration | `DECISIVE`, `RISK` | low |

#### Conditional Questions

- If team goal: "How will individual contributions be tracked?"
- If personal goal: "What accountability structures help you?"
- If business goal: "How does this connect to revenue or growth?"

---

## Framework Selection Questions

When task category is unclear or multiple frameworks could apply, ask:

| ID | Question | Purpose | Guides Selection |
|----|----------|---------|------------------|
| FS1 | Is precise tone and audience targeting critical? | CO-STAR indicator | CO-STAR indicator |
| FS2 | Does this require looking things up or using tools? | ReAct indicator | ReAct indicator |
| FS3 | Is first-draft quality insufficient—does this need iteration? | Self-Refine indicator | Self-Refine indicator |
| FS4 | Would stepping back to principles help before tackling specifics? | Step-Back indicator | Step-Back indicator |
| FS5 | Can the response be broken into parallel, independent points? | Skeleton-of-Thought indicator | Skeleton-of-Thought indicator |
| FS6 | Is the optimal prompt structure unclear? | Meta Prompting indicator | Meta Prompting indicator |

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