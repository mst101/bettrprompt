# Prompt Templates Reference Document

## Purpose

This document provides templates for constructing optimised prompts using selected frameworks, guidance on applying Task-Trait Alignment (amplification, counterbalancing, neutral), and criteria for recommending AI models.

---

## Prompt Structure Overview

Every optimised prompt follows this general structure:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ 1. ROLE ASSIGNMENT (if framework uses roles)                           │
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

### Key Addition: Counterbalance Injections Section

When Task-Trait Alignment identifies traits that need counterbalancing, those requirements are explicitly injected into the prompt. This ensures the AI output covers dimensions the user might naturally overlook.

---

## Applying Task-Trait Alignment to Prompts

### Amplification Implementation

When a trait is marked for **amplification**, adjust the prompt to play to the user's strengths:

| Amplified Trait | Prompt Adjustments |
|-----------------|-------------------|
| High T | Use data-first framing, include metrics, logical structure |
| High F | Include stakeholder context, meaning, values alignment |
| High N | Lead with big-picture, include strategic implications |
| High S | Provide step-by-step structure, concrete examples |
| High J | Clear sections, definitive recommendations, timelines |
| High P | Multiple options, flexibility, adaptive paths |
| High A | Confident language, bold recommendations |
| High T-identity | Include validation checkpoints, risk acknowledgment |

### Counterbalance Implementation

When a trait is marked for **counterbalancing**, inject explicit requirements the user might skip:

| Counterbalanced Trait | Injected Requirements |
|----------------------|----------------------|
| High T (needs empathy) | "Acknowledge the emotional impact on [stakeholder]", "Express appreciation for [relationship]", "Consider how this will make the recipient feel" |
| High F (needs objectivity) | "Base conclusions on data and evidence", "Separate facts from feelings", "Apply objective criteria" |
| High N (needs detail) | "Provide specific, actionable steps", "Include dates, owners, and measurable milestones", "Ground each concept in a concrete example" |
| High S (needs vision) | "Consider the broader strategic implications", "Connect to long-term goals", "Identify patterns across examples" |
| High J (needs exploration) | "Generate at least [N] alternatives before recommending", "Explore unconventional options", "What hasn't been considered?" |
| High P (needs decisiveness) | "Provide ONE clear recommendation", "Commit to specific actions", "Limit caveats to essential only" |
| High A (needs risk awareness) | "Identify what could go wrong", "Include risk mitigation strategies", "Consider the skeptic's perspective" |
| High T-identity (needs confidence) | "State recommendations confidently", "Use decisive language", "Limit hedging to one sentence maximum" |

### Injection Placement

Counterbalance injections should be placed:

1. **Early in the prompt** if they affect the overall approach
2. **In the relevant section** if they apply to specific parts
3. **In quality criteria** as explicit requirements to check

---

## Framework-Specific Templates with Task-Trait Alignment

### COAST Framework Template

**Best For**: Strategic planning, project management

```
[ROLE - if applicable]
You are a {role} with expertise in {domain}.

[CONTEXT]
{Background information from user responses}

[TASK-TRAIT ALIGNMENT INJECTIONS - if counterbalancing needed]
{e.g., "In developing this strategy, ensure you:
- Consider the emotional impact on team members affected by changes
- Express the 'why' in terms of values and meaning, not just logic
- Acknowledge concerns stakeholders may have"}

[CHALLENGE]
{The problem or opportunity being addressed}

[OBJECTIVE]
{What success looks like, including specific metrics if provided}
{If High-N counterbalanced: "Include specific, measurable targets with dates"}

[ACTIONS]
{What needs to be done, organised by phase/priority}
{If High-N counterbalanced: "For each action, specify: owner, deadline, success metric"}

[STRATEGY]
{The overarching approach to take}
{If High-S counterbalanced: "Connect this strategy to the larger vision and long-term goals"}

[TACTICS]
{Specific methods and execution details}

[CONSTRAINTS]
{Budget, time, resources, and other limitations}

[OUTPUT SPECIFICATION]
{Adjusted based on amplified traits}
{If High-J amplified: "Provide a clear, structured response with definitive recommendations"}
{If High-P amplified: "Present multiple strategic options with flexibility for adaptation"}

[QUALITY CRITERIA]
The strategy should:
- {Criterion based on user's success definition}
- {Criterion based on constraints}
- {Injected counterbalance criteria, e.g., "Demonstrate consideration of stakeholder concerns"}
```

---

### BAB (Before-After-Bridge) Framework Template

**Best For**: Persuasion, marketing copy, case studies

This framework naturally requires empathy, so High-T users often need counterbalancing.

```
[CONTEXT]
{Background on the audience and situation}

[TASK-TRAIT ALIGNMENT INJECTIONS - for High-T users]
{e.g., "When crafting this message:
- Lead with empathy for the reader's current situation
- Use emotionally resonant language, not just logical arguments
- Acknowledge the human experience behind the 'Before' state"}

[TARGET AUDIENCE]
{Who needs to be persuaded}
{Include emotional state and concerns, not just demographics}

[BEFORE - Current State]
Describe the current problem, pain point, or challenge:
{User-provided problem description}
{If High-T counterbalanced: "Describe how this situation FEELS for the audience, not just what it IS"}

[AFTER - Desired State]
Paint a picture of the ideal outcome:
{User-provided vision of success}
{If High-T counterbalanced: "Include emotional benefits, not just functional outcomes"}

[BRIDGE - The Solution]
Present the solution that connects Before to After:
{User-provided solution/offer}

[TONE]
{User-specified tone}
{If High-T counterbalanced: "Prioritise warmth and connection over efficiency"}
{If High-A counterbalanced: "Balance confidence with humility and relatability"}

[OUTPUT SPECIFICATION]
Create {content type} that:
- Opens with relatable problem (Before)
- {If High-T counterbalanced: "Uses 'you' language and emotional resonance"}
- Transitions to compelling vision (After)
- Presents the solution as the clear path (Bridge)
- Ends with {call to action}

[QUALITY CRITERIA]
- {If High-T counterbalanced: "Reader should feel understood, not just informed"}
- {If High-F amplified: "Leverage natural empathy to create genuine connection"}
```

---

### Chain of Thought Framework Template

**Best For**: Analysis, complex reasoning, problem-solving

This framework naturally suits High-T users; counterbalancing may be needed if output must consider stakeholder factors.

```
[CONTEXT]
{Background information}

[TASK-TRAIT ALIGNMENT INJECTIONS - if applicable]
{e.g., for decision analysis affecting people:
"In your analysis, include a dedicated step for:
- Stakeholder impact assessment
- Emotional/political factors that may influence implementation
- How affected parties are likely to respond"}

[PROBLEM/QUESTION]
{The specific question or problem to analyse}

[INSTRUCTION]
Analyse this step-by-step, showing your reasoning at each stage.

[STRUCTURE]
1. **Initial Understanding**: Restate the problem and key elements
2. **Breakdown**: Decompose into component parts
3. **Analysis of Each Component**: Examine systematically
   {If High-F counterbalanced: "Include both quantitative and qualitative factors"}
4. {If counterbalancing adds stakeholder step: "**Stakeholder Impact**: Consider human factors"}
5. **Synthesis**: Bring findings together
6. **Conclusion**: State your findings clearly
   {If High-P counterbalanced: "Provide a definitive recommendation, not just analysis"}

[OUTPUT SPECIFICATION]
{If High-T amplified: "Use logical structure, data-driven reasoning"}
{If High-N amplified: "Include pattern recognition and strategic implications"}
{If High-S amplified: "Ground each step in specific, concrete evidence"}

[QUALITY CRITERIA]
- Logical progression between steps
- Evidence-based reasoning
- {If counterbalanced: "Demonstrates consideration of non-analytical factors"}
- Clear conclusion that addresses the original question
```

---

### SCAMPER Framework Template

**Best For**: Ideation, innovation, creative problem-solving

This framework naturally suits High-N and High-P users; may need adjustment for High-S and High-J users.

```
[CONTEXT]
{Background on what you're innovating}

[TASK-TRAIT ALIGNMENT INJECTIONS - for High-S or High-J users]
{e.g., "During this ideation exercise:
- Suspend practical concerns until all ideas are generated
- Quantity over quality in initial brainstorming
- No idea is too unconventional to consider
- Resist the urge to evaluate or dismiss ideas prematurely"}

[SUBJECT]
{The product, process, or concept to apply SCAMPER to}

[CONSTRAINTS]
{What ideas must ultimately respect - but don't let these limit initial ideation}

[SCAMPER ANALYSIS]
Apply each lens to generate ideas:

**Substitute**: What can be replaced with something else?
{If High-S counterbalanced: "Consider abstract substitutions, not just physical swaps"}

**Combine**: What can be merged or integrated?
{If High-J counterbalanced: "Explore unexpected combinations before judging feasibility"}

**Adapt**: What can be modified from another context?
{If High-S counterbalanced: "Look to distant fields for inspiration, not just adjacent ones"}

**Modify/Magnify/Minimise**: What can be changed in scale or form?

**Put to another use**: What else could this be used for?
{If High-J counterbalanced: "Include unconventional uses that challenge assumptions"}

**Eliminate**: What can be removed?

**Reverse/Rearrange**: What can be reordered or inverted?

[OUTPUT SPECIFICATION]
For each SCAMPER lens, provide:
- {If High-P amplified: "3-5 diverse ideas ranging from incremental to radical"}
- {If High-J counterbalanced: "At least 2-3 'wild' ideas before any 'safe' ones"}
- Brief rationale for each
- Feasibility note (quick win / medium effort / moonshot)

[CREATIVITY LEVEL]
{User-specified: incremental to moonshot}
{If High-S or High-J counterbalanced: "Bias toward more ambitious ideas"}

[QUALITY CRITERIA]
- {If High-J counterbalanced: "Includes genuinely unconventional options"}
- {If High-S counterbalanced: "Demonstrates abstract/conceptual thinking"}
- {If amplified creative traits: "Leverages natural innovative thinking"}
```

---

### RISE Framework Template (Feedback)

**Best For**: Feedback, review, improvement suggestions

This framework requires balancing honesty (High-T strength) with empathy (potential counterbalance need).

```
[CONTEXT]
{Background on the work being reviewed}

[TASK-TRAIT ALIGNMENT INJECTIONS]
{For High-T users:
"When providing feedback:
- Acknowledge effort and positive intent before critiquing
- Frame suggestions constructively, not as failures
- Consider how feedback will be received emotionally
- Balance directness with encouragement"}

{For High-F users:
"When providing feedback:
- Ensure critical points are clearly stated, not softened into ambiguity
- Include objective assessment alongside supportive comments
- Don't avoid necessary criticism out of concern for feelings"}

[WORK TO REVIEW]
{Description or reference to the work}

[FEEDBACK FOCUS]
{Specific aspects user wants feedback on}

[RISE STRUCTURE]

**Reflect**: Observations about the work
- What is this work trying to accomplish?
- What stands out?
{If High-T counterbalanced: "Note what's working well before identifying gaps"}

**Inquire**: Questions that probe deeper
- What assumptions underlie this?
- Have alternatives been considered?
{If High-F counterbalanced: "Include analytically probing questions, not just supportive ones"}

**Suggest**: Specific improvement recommendations
- Concrete changes that would strengthen the work
- Prioritised by impact
{If High-T counterbalanced: "Frame as opportunities, not deficiencies"}
{If High-F counterbalanced: "Be specific and direct about what needs to change"}

**Elevate**: Opportunities to take it further
- How could this be exceptional?
- What would the next level look like?

[CANDOUR LEVEL]
{User-specified: gentle / balanced / direct}
{Adjust based on alignment analysis}

[OUTPUT SPECIFICATION]
{If High-T amplified: "Provide analytically rigorous feedback with clear criteria"}
{If High-T counterbalanced: "Wrap critical feedback in constructive framing"}
{If High-F amplified: "Leverage natural empathy to deliver feedback supportively"}
{If High-F counterbalanced: "Ensure substantive critiques are not diluted"}

[QUALITY CRITERIA]
- Actionable and specific
- {If High-T counterbalanced: "Demonstrates appreciation for the creator's effort"}
- {If High-F counterbalanced: "Includes clear, direct assessment of weaknesses"}
- Balances honesty with constructiveness
```

---

### Customer Communication Template (Content Creation)

**Best For**: Emails to customers, especially sensitive communications

This template is specifically designed for High-T users who need counterbalancing for empathy.

```
[CONTEXT]
{Background on the customer relationship and situation}

[TASK-TRAIT ALIGNMENT - CRITICAL COUNTERBALANCE]
This communication requires emotional intelligence. Your natural analytical style 
is valuable but must be balanced with warmth and empathy.

REQUIRED elements (do not skip):
1. Open with personal acknowledgment of the relationship/history
2. Express genuine appreciation before any business content
3. Acknowledge the emotional impact of your message
4. Use "you" language that centers the customer's experience
5. Close with relationship-affirming commitment

[COMMUNICATION DETAILS]
Purpose: {What you need to communicate}
Key message: {The core information}
Desired outcome: {What you want the customer to do/feel}

[CUSTOMER CONTEXT]
Relationship history: {How long, what interactions}
Customer's likely emotional state: {How they might feel receiving this}
What matters to them: {Their values, concerns, priorities}

[TONE REQUIREMENTS]
{If High-T counterbalanced:}
- Warm and personal, not transactional
- Empathetic, not just informative
- Relationship-first, business-second
- Acknowledging, not dismissive of concerns

{If High-A counterbalanced:}
- Confident but not presumptuous
- Humble where appropriate
- Not dismissive of the customer's perspective

[STRUCTURE]
1. Personal greeting (use name, reference relationship)
2. Appreciation/acknowledgment (genuine, specific)
3. Context/empathy (acknowledge their situation)
4. Core message (clear but compassionate)
5. Support/next steps (what you'll do for them)
6. Warm close (relationship commitment)

[OUTPUT SPECIFICATION]
Write an email that:
- A customer would describe as "thoughtful" and "caring"
- Maintains the relationship even if delivering difficult news
- Feels personal, not templated
- {If High-N amplified: "Connects to the bigger picture of the relationship"}

[QUALITY CRITERIA]
- [ ] Opens with genuine warmth, not just "Dear Customer"
- [ ] Expresses specific appreciation (not generic thanks)
- [ ] Acknowledges emotional dimension of the message
- [ ] Core message is clear but compassionately framed
- [ ] Reader feels valued and respected
- [ ] Closes with forward-looking relationship commitment
```

---

## Prompt Assembly Algorithm with Task-Trait Alignment

```
1. Select framework template based on task classification

2. Retrieve Task-Trait Alignment analysis:
   - List of traits to AMPLIFY
   - List of traits to COUNTERBALANCE with specific injections
   - List of NEUTRAL traits (no action)

3. Populate template sections:
   - Insert user-provided context
   - Insert answers to clarifying questions
   - Fill in framework-specific components

4. Apply AMPLIFICATION adjustments:
   - Modify language to leverage user's strengths
   - Structure output format to match their preferences
   - Use terminology that resonates with their style

5. Apply COUNTERBALANCE injections:
   - Add the "Task-Trait Alignment Injections" section if significant counterbalancing needed
   - Insert specific requirements into relevant framework sections
   - Add counterbalance criteria to quality checklist
   - Ensure injections are explicit and cannot be overlooked

6. Add quality criteria:
   - Based on user's success definition
   - Based on constraints mentioned
   - Include counterbalance requirements as checkable items

7. Set output specification:
   - Format requirements (amplification-influenced)
   - Length guidance
   - Style notes (counterbalance-influenced if needed)

8. Review for completeness:
   - All user information incorporated
   - Framework components complete
   - Amplifications naturally integrated
   - Counterbalances explicitly stated
   - Clear success criteria including counterbalance checks
```

---

## Model Recommendation Criteria

### Model Capability Profiles

| Model | Strengths | Limitations |
|-------|-----------|-------------|
| **Claude Opus 4.5** | Nuanced reasoning, long-form analysis, careful instruction-following, ethical judgment, creative writing, excellent at following complex counterbalance instructions | Slower, higher cost |
| **Claude Sonnet 4.5** | Good balance of capability and speed, reliable structured output | Less nuanced than Opus for complex counterbalancing |
| **GPT-4 / GPT-4o** | Strong reasoning, excellent code generation, broad knowledge, multimodal | Different style than Claude |
| **GPT-4o-mini** | Fast, cost-effective, good for simpler tasks | Less capable on complex reasoning |
| **Gemini 1.5 Pro** | Very long context (1M tokens), strong reasoning | Different interaction patterns |
| **Gemini 1.5 Flash** | Fast, long context, cost-effective | Less capable than Pro |
| **DeepSeek R1** | Strong reasoning, especially math/logic, cost-effective | Less established |

### Task-to-Model Primary Recommendations

| Task Category | Primary Model | Rationale |
|---------------|---------------|-----------|
| DECISION | Claude Opus 4.5 | Nuanced trade-off analysis |
| STRATEGY | Claude Opus 4.5 | Complex multi-factor reasoning |
| ANALYSIS | Claude Opus 4.5 | Depth of reasoning |
| CREATION_CONTENT | Claude Opus 4.5 | Writing quality and nuance |
| CREATION_TECHNICAL | GPT-4 | Strong code generation |
| IDEATION | Claude Opus 4.5 | Creative breadth |
| PROBLEM_SOLVING | Claude Opus 4.5 | Logical step-by-step reasoning |
| LEARNING | Claude Sonnet 4.5 | Clear explanations, good balance |
| PERSUASION | Claude Opus 4.5 | Nuanced audience understanding |
| FEEDBACK | Claude Opus 4.5 | Balanced, constructive analysis |
| RESEARCH | Gemini 1.5 Pro | Long context for document synthesis |
| GOAL_SETTING | Claude Sonnet 4.5 | Structured output, efficiency |

### Task-Trait Alignment Model Considerations

When significant counterbalancing is required, prefer models better at following nuanced instructions:

| Scenario | Model Adjustment |
|----------|------------------|
| Heavy counterbalancing needed (2+ traits) | Prefer Claude Opus 4.5 for nuanced instruction-following |
| Empathy counterbalance for technical user | Claude Opus 4.5 excels at warm, nuanced communication |
| Structure counterbalance for creative user | Any capable model handles this well |
| Simple task, minimal counterbalancing | Sonnet or GPT-4o-mini sufficient |

---

## Output Format for Generated Prompt

Return the complete output in this structure:

```json
{
  "optimised_prompt": "The complete prompt text, ready for copy/paste",
  
  "metadata": {
    "framework_used": {
      "name": "COAST",
      "code": "COAST",
      "components": ["Challenge", "Objective", "Actions", "Strategy", "Tactics"],
      "explanation": "Why this framework was selected and how it was applied"
    },
    
    "task_trait_alignment": {
      "amplified": [
        {
          "trait": "High N (64%)",
          "requirement_aligned": "Big-Picture Vision",
          "how_applied": "Prompt structured to leverage strategic framing abilities"
        }
      ],
      "counterbalanced": [
        {
          "trait": "High T (84%)",
          "requirement_opposed": "Empathy & Stakeholder Awareness",
          "reason": "Task requires emotional acknowledgment that High-T may naturally skip",
          "injections_added": [
            "Explicit requirement to acknowledge customer's loyalty",
            "Instruction to express appreciation before business content",
            "Quality criterion checking for emotional resonance"
          ]
        },
        {
          "trait": "High A (84%)",
          "requirement_opposed": "Warm/Relational Tone", 
          "reason": "Assertive confidence must not come across as dismissive",
          "injections_added": [
            "Tone requirement for warmth over efficiency",
            "Instruction for humble, relationship-first framing"
          ]
        }
      ],
      "neutral": [
        {
          "trait": "High I (65%)",
          "reason": "Introversion not relevant to this communication task"
        }
      ]
    },
    
    "personality_adjustments_summary": [
      "AMPLIFIED: N-strength used for strategic framing of the change",
      "COUNTERBALANCED: T-tendency with explicit empathy requirements",
      "COUNTERBALANCED: A-tendency with warmth and humility requirements",
      "NEUTRAL: I and P traits not adjusted (not relevant to task)"
    ],
    
    "model_recommendations": [
      {
        "rank": 1,
        "model": "Claude Opus 4.5",
        "model_id": "claude-opus-4-5-20250514",
        "rationale": "Best for nuanced communication requiring both analytical clarity and emotional intelligence. Excels at following complex counterbalance instructions."
      },
      {
        "rank": 2,
        "model": "GPT-4",
        "model_id": "gpt-4",
        "rationale": "Strong alternative capable of warm, nuanced communication"
      },
      {
        "rank": 3,
        "model": "Claude Sonnet 4.5",
        "model_id": "claude-sonnet-4-5-20250514",
        "rationale": "Faster option if iterating; handles counterbalance instructions well"
      }
    ],
    
    "iteration_suggestions": [
      "If output feels too formal/cold, strengthen the empathy injections",
      "If output is too soft/vague, reduce counterbalancing weight on High-T",
      "If customer context changes, update the relationship history section"
    ]
  }
}
```

---

## Quality Checklist

Before returning the generated prompt, verify:

```
□ All user-provided information is incorporated
□ Framework structure is complete
□ Amplifications naturally integrated (playing to user's strengths)
□ Counterbalances explicitly stated (covering user's blind spots)
□ Counterbalance criteria included in quality checklist
□ Constraints are clearly stated
□ Success criteria are defined
□ Output format matches user's amplified preferences
□ Prompt is clear and unambiguous
□ Length is appropriate for task complexity
□ Model recommendations consider counterbalance complexity
□ Task-Trait Alignment documented in metadata
```
