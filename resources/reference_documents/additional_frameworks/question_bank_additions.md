# Question Bank Additions

## New Framework-Specific Questions

---

## CO-STAR Tasks (Content with Tone/Style Requirements)

Select 4-6 questions when CO-STAR framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| COS1 | Who is the specific audience for this content? What's their role and knowledge level? | Audience definition | High |
| COS2 | What's the primary objective—what should the reader do, think, or feel after reading? | Objective clarity | High |
| COS3 | What writing style fits best—formal, conversational, technical, storytelling? | Style determination | High |
| COS4 | What tone should come through—professional, friendly, urgent, empathetic, authoritative? | Tone setting | High |
| COS5 | What format works best for this content—paragraphs, bullet points, sections with headers? | Response format | Medium |
| COS6 | What background context does the AI need to understand the situation? | Context gathering | Medium |
| COS7 | Are there any words, phrases, or topics to avoid? | Boundaries | Medium |
| COS8 | Is there existing content or examples to match in style? | Style reference | Low |

#### Personality-Adjusted Phrasing for CO-STAR

| Trait | Question Adaptation |
|-------|---------------------|
| High T | "What are the key facts and data points the content must convey?" |
| High F | "How should the reader feel after engaging with this content?" |
| High S | "What specific details and examples should be included?" |
| High N | "What's the big-picture message or theme?" |

---

## ReAct Tasks (Agentic/Tool-Using Workflows)

Select 4-5 questions when ReAct framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| REA1 | What tools, resources, or information sources are available? | Tool inventory | High |
| REA2 | What's the end goal—what specific outcome are you trying to achieve? | Goal clarity | High |
| REA3 | How will you know when the task is complete? What signals success? | Termination criteria | High |
| REA4 | What constraints or rules must be followed during the process? | Guardrails | Medium |
| REA5 | If initial approaches fail, what alternatives should be considered? | Fallback planning | Medium |
| REA6 | Are there any actions or sources that should be avoided? | Boundaries | Low |

#### Conditional Questions for ReAct

- If research task: "What sources are considered authoritative?"
- If technical task: "What error handling is needed?"
- If multi-step: "What are the dependencies between steps?"

---

## Self-Refine Tasks (Quality-Critical Iterative Work)

Select 4-5 questions when Self-Refine framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| SRF1 | What are the specific quality criteria for this output? | Quality standards | High |
| SRF2 | What aspects are most important to get right? | Priority focus | High |
| SRF3 | How many refinement iterations are acceptable? | Iteration bounds | Medium |
| SRF4 | What's the minimum acceptable quality threshold? | Quality floor | Medium |
| SRF5 | Are there examples of excellent outputs to aspire to? | Quality reference | Medium |
| SRF6 | What common mistakes or pitfalls should be watched for? | Error awareness | Low |

#### Personality-Adjusted Phrasing for Self-Refine

| Trait | Question Adaptation |
|-------|---------------------|
| High A | "What would make this output genuinely excellent versus merely acceptable?" |
| High J | "What are the non-negotiable quality criteria?" |
| High P | "What aspects would benefit most from exploration and refinement?" |
| High T-identity | "What quality threshold should stop further iteration?" |

---

## Step-Back Tasks (Principle-Based Reasoning)

Select 3-4 questions when Step-Back framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| STB1 | What domain or field does this question belong to? | Domain identification | High |
| STB2 | What general principles or theories might apply? | Principle prompting | High |
| STB3 | What's the specific question you need answered? | Question clarity | High |
| STB4 | What level of detail is needed in the answer? | Depth calibration | Medium |
| STB5 | Are there related concepts you already understand? | Build on existing | Low |

#### Conditional Questions for Step-Back

- If STEM task: "What formulas or laws might be relevant?"
- If knowledge task: "What categories of information would help?"
- If reasoning task: "What logical principles apply?"

---

## Skeleton-of-Thought Tasks (Structured Parallel Content)

Select 3-5 questions when Skeleton-of-Thought framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| SOT1 | What's the main topic or question to address? | Topic focus | High |
| SOT2 | How many main points should the response cover? | Scope sizing | High |
| SOT3 | Is there a preferred logical order for the points? | Structure guidance | Medium |
| SOT4 | How detailed should each point be? | Depth calibration | Medium |
| SOT5 | Does coherence between points matter, or can they be independent? | Framework fit check | Medium |

#### Framework Fit Verification

If answer to SOT5 indicates high interdependence, consider switching to Chain of Thought instead:

| Response | Action |
|----------|--------|
| "Points are independent" | Continue with Skeleton-of-Thought |
| "Some connection needed" | Use Skeleton-of-Thought with assembly review |
| "Sequential logic required" | Switch to Chain of Thought |
| "Each point builds on previous" | Switch to Chain of Thought |

---

## Meta Prompting Tasks (Prompt Optimisation)

Select 5-7 questions when Meta Prompting framework is identified.

| ID | Question | Purpose | Priority |
|----|----------|---------|----------|
| MET1 | What task will the generated prompt be used for? | Target task | High |
| MET2 | What model will execute the prompt (if known)? | Model targeting | High |
| MET3 | What output format and quality is expected? | Output requirements | High |
| MET4 | What's the user profile for the generated prompt (if known)? | Personality targeting | Medium |
| MET5 | Are there existing prompts to improve, or starting from scratch? | Starting point | Medium |
| MET6 | What constraints must the prompt respect? | Boundaries | Medium |
| MET7 | What has been tried that didn't work well? | Failure learning | Low |

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

## Updated Question Selection Algorithm

```
1. Identify task category (primary and secondary)

2. Check for framework-specific indicators:
   - Tone/style critical? → CO-STAR questions
   - Tool-using/research? → ReAct questions
   - Quality-critical/iterative? → Self-Refine questions
   - Principle-based reasoning? → Step-Back questions
   - Parallel structure possible? → Skeleton-of-Thought questions
   - Prompt creation task? → Meta Prompting questions

3. Start with Universal Questions:
   - Select 2-3 from U1-U6 based on task nature
   - Use personality-adjusted phrasing

4. Add Framework-Specific Questions:
   - Select from the identified framework pool
   - Priority order: High → Medium → Low
   - Stop when sufficient clarity achieved

5. Apply Personality Adjustments:
   - Adjust total count per personality rules
   - Adapt phrasing to personality patterns

6. Add Framework Fit Verification if uncertain:
   - For Skeleton-of-Thought: Check interdependence
   - For ReAct: Verify tool availability
   - For Self-Refine: Confirm iteration acceptable

7. Cap total questions:
   - Simple task + clear framework: 4-5 questions
   - Moderate task: 5-7 questions
   - Complex task + framework uncertainty: 7-10 questions
```

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
