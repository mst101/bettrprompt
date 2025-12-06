# Framework Taxonomy Reference Document

## Purpose

This document provides the AI system with comprehensive information about prompt frameworks, task categories, and the mapping between them. Use this to classify user tasks and select the most appropriate framework.

---

## Task Category Taxonomy

Classify every user task into ONE primary category and optionally ONE secondary category.

### Category Definitions

| Category | Code | Description | Trigger Patterns |
|----------|------|-------------|------------------|
| Decision Making | `DECISION` | Choosing between options, prioritising, evaluating alternatives | "decide", "choose", "which", "should I", "compare", "prioritise", "evaluate options", "better option", "pros and cons" |
| Strategic Planning | `STRATEGY` | Business strategy, roadmaps, long-term planning, go-to-market | "strategy", "plan", "roadmap", "approach", "how to achieve", "long-term", "go-to-market", "growth plan" |
| Analysis | `ANALYSIS` | Understanding data, examining situations, root cause analysis | "analyse", "understand why", "explain", "break down", "examine", "assess", "diagnose", "investigate" |
| Content Creation | `CREATION_CONTENT` | Writing, marketing copy, articles, emails, communications | "write", "draft", "create content", "blog", "email", "copy", "article", "post", "newsletter" |
| Technical Creation | `CREATION_TECHNICAL` | Code, documentation, specifications, technical writing | "code", "build", "develop", "implement", "technical", "documentation", "API", "script", "program" |
| Ideation | `IDEATION` | Brainstorming, innovation, generating new ideas | "ideas", "brainstorm", "innovate", "creative", "new ways", "possibilities", "what could", "suggestions" |
| Problem Solving | `PROBLEM_SOLVING` | Fixing issues, troubleshooting, overcoming obstacles | "solve", "fix", "problem", "issue", "stuck", "challenge", "overcome", "trouble", "not working" |
| Learning | `LEARNING` | Understanding concepts, education, explanations | "learn", "understand", "explain to me", "how does", "teach me", "what is", "help me understand" |
| Persuasion | `PERSUASION` | Convincing, selling, pitching, proposals | "convince", "persuade", "pitch", "sell", "proposal", "make the case", "win over", "negotiate" |
| Feedback | `FEEDBACK` | Reviewing, critiquing, improving existing work | "review", "feedback", "improve", "critique", "what's wrong with", "refine", "strengthen", "evaluate my" |
| Research | `RESEARCH` | Gathering information, investigation, synthesis | "research", "find out", "investigate", "what do we know about", "gather information", "summarise findings" |
| Goal Setting | `GOAL_SETTING` | Defining objectives, KPIs, targets, milestones | "goal", "objective", "target", "KPI", "milestone", "measure success", "set targets" |

### Classification Rules

1. **Primary category**: The dominant intent of the task
2. **Secondary category**: Only assign if the task clearly spans two categories
3. **When ambiguous**: Prefer the category that describes the OUTPUT the user needs
4. **Hybrid tasks**: Use primary for framework selection, secondary for supplementary questions

---

## Task Cognitive Requirements

Each task category has inherent cognitive requirements that may align with or oppose specific personality traits. This information is used for Task-Trait Alignment analysis (amplification, counterbalancing, or neutral handling).

### Cognitive Requirement Definitions

| Requirement Code | Description |
|------------------|-------------|
| `EMPATHY` | Empathy & Stakeholder Awareness — understanding feelings, relationships, interpersonal impact |
| `VISION` | Big-Picture Strategic Vision — future thinking, pattern recognition, conceptual frameworks |
| `DETAIL` | Detailed Execution Planning — step-by-step specificity, concrete actions, practical implementation |
| `DECISIVE` | Decisive Recommendations — clear conclusions, prioritised options, confident guidance |
| `EXPLORE` | Exploring Multiple Options — divergent thinking, option generation, avoiding premature closure |
| `OBJECTIVE` | Objective Analysis — dispassionate evaluation, logical reasoning, evidence-based conclusions |
| `RISK` | Risk Awareness — identifying potential problems, downsides, failure modes |
| `CREATIVE` | Creative Innovation — novel ideas, unconventional thinking, breaking patterns |
| `STRUCTURE` | Structured Communication — clear organisation, logical flow, professional presentation |
| `WARM` | Warm/Relational Tone — warmth, rapport-building, relationship-focused communication |

### Task Category to Cognitive Requirements Mapping

| Task Category | Primary Requirements | Secondary Requirements |
|---------------|---------------------|----------------------|
| `DECISION` | `OBJECTIVE`, `RISK` | `DECISIVE`, `EXPLORE` |
| `STRATEGY` | `VISION`, `DETAIL` | `RISK`, `DECISIVE` |
| `ANALYSIS` | `OBJECTIVE`, `DETAIL` | `RISK` |
| `CREATION_CONTENT` | Varies by content type (see below) | `STRUCTURE` |
| `CREATION_TECHNICAL` | `DETAIL`, `OBJECTIVE` | `STRUCTURE` |
| `IDEATION` | `CREATIVE`, `EXPLORE` | `VISION` |
| `PROBLEM_SOLVING` | `OBJECTIVE`, `DETAIL` | `RISK`, `EXPLORE` |
| `LEARNING` | `STRUCTURE` | Varies by learner |
| `PERSUASION` | `EMPATHY`, `WARM` | `STRUCTURE` |
| `FEEDBACK` | `EMPATHY`, `OBJECTIVE` | `WARM` |
| `RESEARCH` | `OBJECTIVE`, `EXPLORE` | `DETAIL` |
| `GOAL_SETTING` | `DETAIL`, `DECISIVE` | `VISION` |

### Content-Type Specific Requirements (for CREATION_CONTENT)

When task category is `CREATION_CONTENT`, identify the content type and apply specific requirements:

| Content Type | Trigger Words | Primary Requirements | Secondary Requirements |
|--------------|---------------|---------------------|----------------------|
| Customer email | "email to customer", "client email" | `EMPATHY`, `WARM`, `STRUCTURE` | |
| Marketing copy | "marketing", "ad copy", "landing page" | `EMPATHY`, `CREATIVE`, `DECISIVE` | |
| Technical blog | "technical post", "how-to article" | `OBJECTIVE`, `STRUCTURE`, `DETAIL` | |
| Executive summary | "executive summary", "brief for leadership" | `DECISIVE`, `STRUCTURE` | |
| Apology/bad news | "apologise", "bad news", "discontinuing" | `EMPATHY`, `WARM`, `RISK` | |
| Sales pitch | "sales", "pitch", "proposal" | `EMPATHY`, `DECISIVE`, `CREATIVE` | |
| Internal memo | "internal", "memo", "team update" | `STRUCTURE`, `OBJECTIVE` | |
| Social media | "social media", "tweet", "LinkedIn post" | `CREATIVE`, `WARM` | |
| Personal email | "personal", "thank you", "congratulations" | `WARM`, `EMPATHY` | |

### Trait-Requirement Alignment Reference

This table shows which personality traits align with or oppose each cognitive requirement:

| Requirement | Aligned Traits | Opposed Traits |
|-------------|----------------|----------------|
| `EMPATHY` | High F, High E | High T |
| `VISION` | High N, High P | High S |
| `DETAIL` | High S, High J | High N, High P |
| `DECISIVE` | High J, High A | High P, High T-identity |
| `EXPLORE` | High P, High N | High J |
| `OBJECTIVE` | High T, High S | High F |
| `RISK` | High T-identity, High T | High A |
| `CREATIVE` | High N, High P | High S, High J |
| `STRUCTURE` | High J, High T | High P |
| `WARM` | High F, High E | High T |

---

## Framework Definitions

### 1. Structured Clarity Frameworks

#### CRISPE Framework
- **Components**: Clarity, Relevance, Iteration, Specificity, Parameters, Examples
- **Best For**: Technical documentation, strategic planning, content requiring precision
- **Strengths**: Comprehensive coverage, flexible, ensures targeted outputs
- **Weaknesses**: Time-intensive for simple tasks, may limit creativity
- **Complexity**: Medium
- **Time Investment**: Medium

#### RELIC Framework
- **Components**: Role, Emphasis, Limitation, Information, Challenge
- **Best For**: Content creation, strategic planning, customer interactions
- **Strengths**: Clear structure, eliminates ambiguity, highly versatile
- **Weaknesses**: Learning curve, requires upfront effort
- **Complexity**: Medium
- **Time Investment**: Medium

#### RTF Framework
- **Components**: Request, Task, Format
- **Best For**: Data retrieval, instructional content, simple clear requests
- **Strengths**: Simple, efficient, clear communication
- **Weaknesses**: Less suitable for complex, exploratory tasks
- **Complexity**: Low
- **Time Investment**: Low

### 2. Iterative Refinement Frameworks

#### RACEF Framework
- **Components**: Rephrase, Append, Contextualize, Examples, Follow-Up
- **Best For**: Brainstorming, data analysis, iterative problem-solving
- **Strengths**: Highly flexible, encourages iterative refinement, adaptable
- **Weaknesses**: Learning curve, time-intensive for simple tasks
- **Complexity**: Medium
- **Time Investment**: High

#### Chain of Destiny Framework
- **Components**: Baseline prompt, Feedback loops, Specific improvement feedback, Progressive integration
- **Best For**: Projects where quality trumps speed, complex ideas
- **Strengths**: Continuous improvement, customized feedback, versatile
- **Weaknesses**: Time-intensive, depends on quality feedback
- **Complexity**: High
- **Time Investment**: High

### 3. Decision-Making & Prioritisation Frameworks

#### RICE Framework
- **Components**: Reach, Impact, Confidence, Effort
- **Formula**: RICE Score = (Reach × Impact × Confidence) / Effort
- **Best For**: Feature prioritisation, marketing campaigns, project selection
- **Strengths**: Quantitative analysis, clear metrics, time-saving
- **Weaknesses**: Requires accurate data, effort estimation can be tricky
- **Complexity**: Low
- **Time Investment**: Low

#### SMART Framework
- **Components**: Specific, Measurable, Achievable, Relevant, Time-bound
- **Best For**: Goal-setting, project planning, personal development
- **Strengths**: Goal-oriented, ensures efficiency, widely recognized
- **Weaknesses**: Can be rigid, may limit creative exploration
- **Complexity**: Low
- **Time Investment**: Low

#### COAST Framework
- **Components**: Challenge, Objective, Actions, Strategy, Tactics
- **Best For**: Project management, strategic planning
- **Strengths**: Comprehensive, strategic alignment
- **Weaknesses**: Requires clear problem definition
- **Complexity**: Medium
- **Time Investment**: Medium

#### Pros and Cons Analysis Framework
- **Components**: Benefits list, Drawbacks list, Weighted evaluation
- **Best For**: Decision-making, strategic analysis
- **Strengths**: Balanced evaluation, comprehensive
- **Weaknesses**: Can oversimplify complex decisions
- **Complexity**: Low
- **Time Investment**: Low

### 4. Analytical & Problem-Solving Frameworks

#### Chain of Thought Framework
- **Components**: Introduction, Breakdown, Logical Progression, Conclusion
- **Best For**: Mathematical problems, market analysis, scientific explanations
- **Strengths**: Enhances reasoning, promotes depth and clarity, adaptable
- **Weaknesses**: Requires precise prompting, can be time-consuming
- **Complexity**: High
- **Time Investment**: High

#### Tree of Thought Framework
- **Components**: Nodes (options), Edges (connections), Outcomes
- **Best For**: Complex problem-solving, scenario planning, brainstorming
- **Strengths**: Fosters creativity, strategic thinking, iterative refinement
- **Weaknesses**: Can become unwieldy for simple problems
- **Complexity**: High
- **Time Investment**: High

#### FOCUS Framework
- **Components**: Focus areas identification, Prioritisation, Resource allocation
- **Best For**: AI projects requiring precise goal-setting and prioritisation
- **Strengths**: Enhances clarity, maximizes efficiency
- **Weaknesses**: Requires clear initial understanding
- **Complexity**: Medium
- **Time Investment**: Medium

#### Six Thinking Hats Framework
- **Components**: White (facts), Red (emotions), Black (risks), Yellow (benefits), Green (creativity), Blue (process)
- **Best For**: Decision-making, problem-solving, team brainstorming
- **Strengths**: Multi-perspective evaluation, comprehensive
- **Weaknesses**: Time-intensive, requires understanding of all hats
- **Complexity**: Medium
- **Time Investment**: Medium

### 5. Storytelling & Narrative Frameworks

#### BAB Framework
- **Components**: Before, After, Bridge
- **Best For**: Marketing copy, case studies, persuasive content
- **Strengths**: Storytelling approach, emotionally resonant, simple
- **Weaknesses**: May oversimplify complex issues
- **Complexity**: Low
- **Time Investment**: Low

#### CAR Framework
- **Components**: Context, Action, Result
- **Best For**: Behavioral interviews, performance reviews, case studies
- **Strengths**: Clear narrative structure, results-focused
- **Complexity**: Low
- **Time Investment**: Low

#### PAR Framework
- **Components**: Problem, Action, Result
- **Best For**: Problem-focused narratives, success stories
- **Strengths**: Problem-solution clarity
- **Complexity**: Low
- **Time Investment**: Low

#### STAR Framework
- **Components**: Situation, Task, Action, Result
- **Best For**: Behavioral interviews, detailed case studies
- **Strengths**: Comprehensive narrative coverage
- **Complexity**: Low
- **Time Investment**: Low

#### Challenge-Solution-Benefit Framework
- **Components**: Challenge identification, Solution proposal, Benefit highlight
- **Best For**: Marketing, product development, strategic planning
- **Strengths**: Clear value demonstration
- **Complexity**: Low
- **Time Investment**: Low

### 6. Content Creation Frameworks

#### BLOG Framework
- **Components**: Background, Logic, Outline, Goal
- **Best For**: Blog posts, articles, marketing content
- **Strengths**: Enhances coherence, simplifies writing, boosts engagement
- **Complexity**: Low
- **Time Investment**: Low

#### APE Framework
- **Components**: Audience, Purpose, Execution
- **Best For**: Content marketing, customer support, personalized communication
- **Strengths**: Highly targeted, effective messaging
- **Complexity**: Low
- **Time Investment**: Low

#### TAG Framework
- **Components**: Topic, Audience, Goal
- **Best For**: Content marketing, educational resources, public communications
- **Strengths**: Ensures relevance and effectiveness
- **Complexity**: Low
- **Time Investment**: Low

#### 4S Method
- **Components**: Structure, Style, Substance, Speed
- **Best For**: Content creation, digital marketing, corporate communications
- **Strengths**: Well-rounded content approach
- **Complexity**: Low
- **Time Investment**: Low

#### Hamburger Model
- **Components**: Introduction (top bun), Body content (meat), Conclusion (bottom bun)
- **Best For**: Blogs, articles, educational materials
- **Strengths**: Clear structure, easy to follow
- **Complexity**: Low
- **Time Investment**: Low

### 7. Creative & Innovation Frameworks

#### SCAMPER Framework
- **Components**: Substitute, Combine, Adapt, Modify, Put to another use, Eliminate, Reverse
- **Best For**: Innovation workshops, product development, creative problem-solving
- **Strengths**: Proven method, actionable strategies, structured innovation
- **Complexity**: Medium
- **Time Investment**: Medium

#### HMW (How Might We) Framework
- **Components**: Open-ended questions starting with "How might we..."
- **Best For**: Design thinking, brainstorming, innovation workshops
- **Strengths**: Opens possibilities, fosters collaboration
- **Complexity**: Low
- **Time Investment**: Low

#### Imagine Framework
- **Components**: Future scenario visioning, Possibility exploration
- **Best For**: Strategic planning, product development, creative writing
- **Strengths**: Fosters innovation, explores possibilities
- **Complexity**: Low
- **Time Investment**: Low

#### What If Framework
- **Components**: Hypothetical scenario exploration
- **Best For**: Strategic planning, creative writing, problem-solving
- **Strengths**: Encourages creative thinking, diverse outcomes
- **Complexity**: Low
- **Time Investment**: Low

#### SPARK Framework
- **Components**: Situation, Problem, Aspiration, Result, Kismet (serendipity)
- **Best For**: Product development, marketing strategy, creative writing
- **Strengths**: Engaging, adds surprise element
- **Complexity**: Medium
- **Time Investment**: Medium

### 8. Educational & Learning Frameworks

#### Bloom's Taxonomy Prompts Framework
- **Levels**: Remember → Understand → Apply → Analyze → Evaluate → Create
- **Best For**: Educational content, learning assessments, training materials
- **Strengths**: Depth-inclusive, encourages critical thinking
- **Complexity**: Medium
- **Time Investment**: Medium

#### ELI5 Framework
- **Components**: Explain Like I'm 5 - simplification approach
- **Best For**: Education, customer support, simplifying complex concepts
- **Strengths**: Clarity, simplicity, bridges knowledge gaps
- **Complexity**: Low
- **Time Investment**: Low

#### Help Me Understand Framework
- **Components**: Comprehension-focused breakdown
- **Best For**: Educational AI, customer support, simplifying complex information
- **Strengths**: Enhances learning, user-friendly
- **Complexity**: Low
- **Time Investment**: Low

#### TQA Approach
- **Components**: Thematic, Question, Answer
- **Best For**: E-learning modules, educational materials
- **Strengths**: Structured learning and inquiry
- **Complexity**: Low
- **Time Investment**: Low

#### Socratic Method
- **Components**: Progressive questioning to stimulate critical thinking
- **Best For**: Education, philosophy, critical analysis
- **Strengths**: Fosters inquiry, challenges assumptions
- **Complexity**: Medium
- **Time Investment**: Medium

### 9. Communication & Engagement Frameworks

#### RACE Framework
- **Components**: Reach, Act, Convert, Engage
- **Best For**: Marketing campaigns, customer relations, public engagement
- **Strengths**: Full-funnel coverage from attraction to retention
- **Complexity**: Medium
- **Time Investment**: Medium

#### ERA Framework
- **Components**: Engage, React, Act
- **Best For**: Marketing, behavioral change, user engagement
- **Strengths**: Dynamic interactions, motivates action
- **Complexity**: Low
- **Time Investment**: Low

#### CARE Framework
- **Components**: Compassion, Awareness, Response, Engagement
- **Best For**: Customer service, therapeutic chatbots, educational tools
- **Strengths**: Considers emotional needs, builds trust
- **Complexity**: Medium
- **Time Investment**: Medium

### 10. Strategic Analysis Frameworks

#### 3Cs Model
- **Components**: Company, Customer, Competitor
- **Best For**: Market analysis, strategic planning, business environment analysis
- **Strengths**: Well-rounded perspective, strategic insights
- **Complexity**: Medium
- **Time Investment**: Medium

#### GOPA Framework
- **Components**: Goals, Obstacles, Plans, Actions
- **Best For**: Goal setting, problem-solving, project planning
- **Strengths**: Clear actionable steps, systematic approach
- **Complexity**: Low
- **Time Investment**: Low

### 11. Feedback & Improvement Frameworks

#### RISE Framework
- **Components**: Reflect, Inquire, Suggest, Elevate
- **Best For**: Education, performance management, personal growth
- **Strengths**: Facilitates meaningful dialogue, continuous improvement
- **Complexity**: Medium
- **Time Investment**: Medium

#### ROSES Framework
- **Components**: Recognize, Observe, Strategize, Execute, Study
- **Best For**: Complex projects, business strategy, organizational development
- **Strengths**: Structured yet flexible, comprehensive approach
- **Complexity**: Medium
- **Time Investment**: Medium

#### PEE Framework
- **Components**: Point, Evidence, Explanation
- **Best For**: Academic writing, legal analysis, persuasive content
- **Strengths**: Well-substantiated, logically sound
- **Complexity**: Low
- **Time Investment**: Low

### 12. Advanced Structured Frameworks

#### RASCEF Framework
- **Components**: Role, Action, Steps, Context, Examples, Format
- **Best For**: Technical documentation, instructional design, complex reports
- **Strengths**: Comprehensive, ensures depth and structure
- **Complexity**: Medium
- **Time Investment**: Medium

#### RHODES Framework
- **Components**: Role, Objective, Details, Examples, Sense Check
- **Best For**: Creative endeavors, marketing content, stylistic alignment
- **Strengths**: Creatively aligned outputs
- **Complexity**: Medium
- **Time Investment**: Medium

#### RISEN Framework
- **Components**: Role, Input, Steps, Expectation, Novelty
- **Best For**: Research, development, content creation requiring innovation
- **Strengths**: Encourages novel ideas
- **Complexity**: Medium
- **Time Investment**: Medium

#### GRADE Framework
- **Components**: Goal, Request, Action, Details, Example
- **Best For**: Project management, content creation, analytical tasks
- **Strengths**: Specificity, directly tied to objectives
- **Complexity**: Medium
- **Time Investment**: Medium

#### TRACI Framework
- **Components**: Task, Role, Audience, Create, Intent
- **Best For**: Marketing, education, customer service
- **Strengths**: Meaningful, personalized, aligned with outcomes
- **Complexity**: Medium
- **Time Investment**: Medium

#### RODES Framework
- **Components**: Role, Objective, Details, Examples, Sense Check
- **Best For**: Educational content, strategic planning, complex problem-solving
- **Strengths**: High accuracy, contextual understanding
- **Complexity**: Medium
- **Time Investment**: Medium

#### CIDI Framework
- **Components**: Context, Instructions, Details, Input
- **Best For**: Project management, content creation, innovative problem-solving
- **Strengths**: Actionable results, clarity
- **Complexity**: Medium
- **Time Investment**: Medium

### 13. Argumentation Frameworks

#### TRACE Framework
- **Components**: Topic, Reason, Audience, Counterargument, Evidence
- **Best For**: Debate preparation, persuasive writing, critical thinking
- **Strengths**: Compelling, logically sound
- **Complexity**: Medium
- **Time Investment**: Medium

#### SPAR Framework
- **Components**: Situation, Problem, Action, Result
- **Best For**: Case studies, success stories, process descriptions
- **Strengths**: Coherent storyline, impactful
- **Complexity**: Low
- **Time Investment**: Low

#### PROMPT Framework
- **Components**: Precision, Relevance, Objectivity, Method, Provenance, Timeliness
- **Best For**: Research, journalism, data analysis
- **Strengths**: High quality standards, credible
- **Complexity**: Medium
- **Time Investment**: Medium

### 14. Specialised Frameworks

#### SPEAR Framework
- **Components**: Start, Provide, Explain, Ask, Rinse & Repeat
- **Best For**: Everyday tasks, straightforward requests
- **Strengths**: Simple, clear, efficient
- **Complexity**: Low
- **Time Investment**: Low

#### Few-Shot Framework
- **Components**: Task description, Few demonstrations, Query
- **Best For**: Sentiment analysis, text classification, creative content
- **Strengths**: Guides models with examples
- **Complexity**: Low
- **Time Investment**: Low

#### Zero-Shot Prompting
- **Components**: Direct task instruction without examples
- **Best For**: Translation, factual queries, simple classifications
- **Strengths**: Simple, efficient, tests raw capabilities
- **Complexity**: Low
- **Time Investment**: Low

#### ORID Framework
- **Components**: Objective, Reflective, Interpretive, Decisional
- **Best For**: Group discussions, coaching sessions, reflective writing
- **Strengths**: Meaningful interactions, deeper insights
- **Complexity**: Medium
- **Time Investment**: Medium

#### PAUSE Framework
- **Components**: Prepare, Assess, Uncover, Synthesize, Execute
- **Best For**: Management decision-making, conflict resolution
- **Strengths**: Reflective thinking, comprehensive assessment
- **Complexity**: Medium
- **Time Investment**: Medium

#### Elicitation Framework
- **Components**: Structured information extraction techniques
- **Best For**: Research, interviews, data analysis
- **Strengths**: Deep understanding, comprehensive data collection
- **Complexity**: Medium
- **Time Investment**: Medium

### 15. Visual & Image Generation Frameworks

#### Atomic Prompting Framework
- **Components**: Detailed visual specifications (subject, style, lighting, composition, etc.)
- **Best For**: Midjourney, DALL-E 3, Adobe Firefly image generation
- **Strengths**: Precision, detail, customization
- **Complexity**: Medium
- **Time Investment**: Medium

### 16. Dialogue & Questioning Frameworks

#### Five Ws and One H
- **Components**: Who, What, When, Where, Why, How
- **Best For**: Journalism, content creation, research
- **Strengths**: Comprehensive exploration, complete information
- **Complexity**: Low
- **Time Investment**: Low

---

## Framework-to-Category Mapping

Use this mapping to select candidate frameworks based on the classified task category.

### DECISION Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| RICE | Primary | Quantitative prioritisation is its core purpose |
| Pros and Cons | Primary | Direct option comparison |
| Tree of Thought | Primary | Exploring multiple decision branches |
| SMART | Secondary | Useful for framing decision criteria |
| Six Thinking Hats | Secondary | Multi-perspective evaluation |
| ORID | Secondary | Structured reflection before deciding |
| PAUSE | Secondary | Thoughtful consideration |

### STRATEGY Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| COAST | Primary | Challenge→Objective→Actions→Strategy→Tactics flow |
| 3Cs Model | Primary | Company/Customer/Competitor analysis |
| GOPA | Primary | Goals→Obstacles→Plans→Actions |
| SMART | Primary | Goal-setting component of strategy |
| Chain of Thought | Secondary | Step-by-step strategic reasoning |
| ROSES | Secondary | Recognition→Strategy→Execution cycle |
| RELIC | Secondary | Structured strategic planning |

### ANALYSIS Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| Chain of Thought | Primary | Step-by-step logical breakdown |
| Tree of Thought | Primary | Multi-branch exploration |
| FOCUS | Primary | Concentrated analytical attention |
| Five Ws and One H | Secondary | Comprehensive question coverage |
| PROMPT | Secondary | Research rigour |
| Socratic Method | Secondary | Deep questioning |
| Six Thinking Hats | Secondary | Multi-perspective analysis |

### CREATION_CONTENT Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| BLOG | Primary | Background→Logic→Outline→Goal for articles |
| TAG | Primary | Topic→Audience→Goal alignment |
| APE | Primary | Audience→Purpose→Execution |
| 4S Method | Primary | Structure→Style→Substance→Speed |
| Hamburger Model | Secondary | Intro→Body→Conclusion structure |
| CRISPE | Secondary | When precision and parameters matter |
| TRACI | Secondary | Audience-focused content |

### CREATION_TECHNICAL Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| RASCEF | Primary | Role→Action→Steps→Context→Examples→Format |
| CRISPE | Primary | Clarity and specificity for technical work |
| RTF | Primary | Request→Task→Format (simple and direct) |
| CIDI | Secondary | Context→Instructions→Details→Input |
| Zero-Shot | Secondary | When task is straightforward |
| GRADE | Secondary | Goal-oriented technical output |

### IDEATION Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| SCAMPER | Primary | Structured creativity techniques |
| HMW | Primary | Open-ended possibility framing |
| What If | Primary | Scenario exploration |
| Imagine | Primary | Future possibility visioning |
| Tree of Thought | Secondary | Branching idea exploration |
| SPARK | Secondary | Adds serendipity element |
| Six Thinking Hats (Green Hat focus) | Secondary | Creative perspective |

### PROBLEM_SOLVING Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| Chain of Thought | Primary | Logical step-by-step reasoning |
| GOPA | Primary | Obstacle-focused planning |
| ROSES | Primary | Recognise→Observe→Strategise→Execute→Study |
| PAUSE | Primary | Prepare→Assess→Uncover→Synthesise→Execute |
| Tree of Thought | Secondary | Exploring multiple solution paths |
| Six Thinking Hats | Secondary | Multi-perspective examination |
| Five Ws and One H | Secondary | Root cause investigation |

### LEARNING Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| ELI5 | Primary | Simplification for accessibility |
| Bloom's Taxonomy | Primary | Structured learning progression |
| TQA | Primary | Theme→Question→Answer structure |
| Help Me Understand | Primary | Comprehension-focused |
| Socratic Method | Secondary | Learning through questioning |
| Few-Shot | Secondary | Learning by example |

### PERSUASION Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| BAB | Primary | Before→After→Bridge transformation |
| Challenge-Solution-Benefit | Primary | Problem→Solution→Advantage |
| TRACE | Primary | Topic→Reason→Audience→Counterargument→Evidence |
| PEE | Primary | Point→Evidence→Explanation |
| CAR/PAR/STAR | Secondary | Structured achievement narratives |
| RACE | Secondary | Reach→Act→Convert→Engage funnel |
| AIDA (implied in BAB) | Secondary | Attention→Interest→Desire→Action |

### FEEDBACK Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| RISE | Primary | Reflect→Inquire→Suggest→Elevate |
| ROSES | Primary | Systematic review and improvement |
| PEE | Primary | Structured critique with evidence |
| Chain of Destiny | Secondary | Iterative refinement process |
| RACEF | Secondary | Rephrase→Append→Contextualise→Examples→Follow-up |

### RESEARCH Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| PROMPT | Primary | Precision→Relevance→Objectivity→Method→Provenance→Timeliness |
| Five Ws and One H | Primary | Comprehensive information gathering |
| Elicitation | Primary | Deep information extraction |
| Chain of Thought | Secondary | Systematic research reasoning |
| RODES | Secondary | Includes sense-checking |

### GOAL_SETTING Tasks
| Framework | Suitability | Rationale |
|-----------|-------------|-----------|
| SMART | Primary | The standard for goal definition |
| GOPA | Primary | Goals as starting point |
| COAST | Secondary | Objectives within strategic context |
| FOCUS | Secondary | Priority goal identification |

---

## Selection Algorithm

When selecting a framework:

1. **Classify the task** into primary (and optionally secondary) category
2. **Retrieve candidate frameworks** from the mapping above
3. **Filter by suitability**: Prefer "Primary" frameworks unless task has special characteristics
4. **Consider complexity match**:
   - Simple task → Low complexity framework
   - Moderate task → Low or Medium complexity
   - Complex task → Medium or High complexity
5. **Consider user's time constraints** if mentioned
6. **Select ONE primary framework** for prompt construction
7. **Note secondary frameworks** that could be mentioned as alternatives

### Complexity Assessment Criteria

- **Simple**: Single-step task, clear output, minimal context needed
- **Moderate**: Multi-step task, some ambiguity, requires context
- **Complex**: Multi-faceted task, significant ambiguity, extensive context needed, multiple stakeholders

---

## Output Format

When returning framework selection, provide:

```json
{
  "task_classification": {
    "primary_category": "CATEGORY_CODE",
    "secondary_category": "CATEGORY_CODE or null",
    "complexity": "simple | moderate | complex",
    "classification_reasoning": "Brief explanation of why this category",
    "content_type": "For CREATION_CONTENT tasks only - e.g., 'customer_email', 'marketing_copy'"
  },
  "cognitive_requirements": {
    "primary": ["REQUIREMENT_CODE", "REQUIREMENT_CODE"],
    "secondary": ["REQUIREMENT_CODE"],
    "reasoning": "Brief explanation of why these requirements apply"
  },
  "selected_framework": {
    "name": "Framework Name",
    "code": "FRAMEWORK_CODE",
    "components": ["Component1", "Component2", "..."],
    "rationale": "Why this framework is best for this task"
  },
  "alternative_frameworks": [
    {
      "name": "Alternative Framework Name",
      "code": "FRAMEWORK_CODE",
      "when_to_use_instead": "Condition when this would be better"
    }
  ]
}
```

The `cognitive_requirements` field is essential for Task-Trait Alignment analysis in the personality calibration stage. It determines which user traits should be amplified, counterbalanced, or treated neutrally.
