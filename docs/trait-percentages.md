# Trait Percentages: How They Influence Prompt Optimisation

## Overview

AI Buddy uses trait percentages from 16personalities.com to calibrate prompt optimisation intensity. This document explains how trait strength affects framework selection and final prompt generation.

## Understanding Trait Percentages

Trait percentages measure the **strength and consistency** of each personality trait, NOT comparison to other people:

### The Five Traits

1. **Mind**: Introversion (I) vs Extraversion (E)
2. **Energy**: Intuitive (N) vs Observant (S)
3. **Nature**: Thinking (T) vs Feeling (F)
4. **Tactics**: Judging (J) vs Prospecting (P)
5. **Identity**: Assertive (A) vs Turbulent (T)

### Trait Strength Categories

| Percentage | Category | Meaning | Behaviour Pattern |
|------------|----------|---------|-------------------|
| **50-60%** | **BALANCED** | Flexible, exhibits behaviours from both ends of spectrum | Sometimes one way, sometimes the other depending on context |
| **61-75%** | **MODERATE** | Clear preference but adaptable when needed | Usually prefers one side but can access the opposite |
| **76-100%** | **STRONG** | Very consistent, pronounced characteristic | Almost always exhibits this trait; struggles with opposite |

### Examples

**Mind (Introversion/Extraversion):**
- 55% Introversion (Balanced): Sometimes needs alone time, sometimes energised by others
- 70% Introversion (Moderate): Prefers independent work but can collaborate when needed
- 95% Introversion (Strong): Strongly prefers solitary work, finds social interaction draining

**Nature (Thinking/Feeling):**
- 58% Thinking (Balanced): Can be analytical OR empathetic depending on context
- 68% Thinking (Moderate): Usually logical but can engage with emotions
- 88% Thinking (Strong): Almost exclusively logical, struggles with emotional decisions

## How Trait Strength Affects Framework Selection

The Framework Selector workflow analyses trait strength to:

1. **Choose appropriate frameworks**:
   - Balanced traits → flexible frameworks (RACEF, Chain of Thought)
   - Strong traits → frameworks that leverage extreme strengths (CRISPE, RICE, SMART)

2. **Decide AMPLIFY vs COUNTERBALANCE strategy**:
   - **AMPLIFY**: Leverage natural strengths (works best with strong traits)
   - **COUNTERBALANCE**: Compensate for weaknesses (needs more scaffolding for strong traits)

3. **Generate targeted questions**:
   - Strong traits: Focused questions that assume preferences
   - Balanced traits: Exploratory questions that explore different approaches

### Framework Selection Examples

**Scenario: "Build a marketing strategy"**

**User A - INTJ-A with Balanced Traits (52-60%)**
- Framework: RACEF (flexible, iterative)
- Approach: Light AMPLIFY (user can adapt between styles)
- Questions: "Would you like structured analysis, intuitive exploration, or a balanced mix?"

**User B - INTJ-A with Strong Traits (85-95%)**
- Framework: RICE (data-driven prioritisation)
- Approach: Aggressive AMPLIFY (lean heavily into analytical strengths)
- Questions: "What analytical frameworks do you prefer for evaluating marketing channels?"

## How Trait Strength Affects Final Prompt Generation

The Final Prompt Optimizer calibrates optimisation intensity based on trait strength:

### AMPLIFY Strategy Calibration

| Trait Strength | Calibration Level | Prompt Characteristics | Example (95% Thinking) |
|----------------|-------------------|------------------------|------------------------|
| **50-60%** (Balanced) | **Light Amplify** | Allow for both approaches, don't push extremes | "You may work independently or seek input as needed" |
| **61-75%** (Moderate) | **Standard Amplify** | Lean into preferences whilst maintaining flexibility | "Approach this systematically using logical frameworks. Consider stakeholder perspectives where relevant." |
| **76-100%** (Strong) | **Aggressive Amplify** | Design almost exclusively for extreme strength | "Analyse purely through data and logic. Remove all emotional considerations. Use systematic frameworks exclusively. Quantify all factors." |

### COUNTERBALANCE Strategy Calibration

| Trait Strength | Calibration Level | Prompt Characteristics | Example (95% Thinking on emotional task) |
|----------------|-------------------|------------------------|------------------------------------------|
| **50-60%** (Balanced) | **Light Counterbalance** | Gentle suggestions, user is naturally flexible | "Consider the perspectives and feelings of stakeholders involved" |
| **61-75%** (Moderate) | **Standard Counterbalance** | Clear structure and explicit steps | "Step 1: List 3 emotional factors. Step 2: Interview stakeholders about feelings. Step 3: Weight emotional and logical factors equally." |
| **76-100%** (Strong) | **Heavy Counterbalance** | Explicit scaffolding, mandatory steps against type | "MANDATORY PROCESS: 1) Interview 5 stakeholders - document emotions verbatim. 2) Create empathy map before analysis. 3) Make decisions based PRIMARILY on emotional impact, with logic secondary." |

## Real-World Examples

### Example 1: Content Creation Task

**Task**: "Write a blog post about productivity tips"

**User A - ENFP with Balanced Traits**:
```
Trait Percentages:
- Extraversion: 58%
- Intuitive: 55%
- Feeling: 52%
- Prospecting: 60%
- Assertive: 56%

Result:
- Framework: BLOG (flexible content structure)
- Approach: Light AMPLIFY
- Prompt: "Create a blog post that balances creative storytelling with practical advice.
  You may draw from personal anecdotes or research-based evidence as feels natural.
  Structure can be loose or organised based on what serves the content best."
```

**User B - ENFP with Strong Traits**:
```
Trait Percentages:
- Extraversion: 95%
- Intuitive: 92%
- Feeling: 88%
- Prospecting: 90%
- Assertive: 85%

Result:
- Framework: How Might We (creative exploration)
- Approach: Aggressive AMPLIFY
- Prompt: "Write an energetic, story-driven blog post full of personal anecdotes
  and emotional resonance. Use conversational tone, ask rhetorical questions,
  connect ideas through creative associations. Don't worry about rigid structure -
  let the narrative flow naturally. Focus on inspiring readers through possibilities
  and emotional connection rather than systematic instruction."
```

### Example 2: Data Analysis Task

**Task**: "Analyse sales performance and recommend improvements"

**User A - ISTJ with Balanced Traits (55-60%)**:
```
Result:
- Framework: SMART (structured but not overly rigid)
- Approach: Standard AMPLIFY
- Prompt: "Conduct a systematic analysis of sales data using established frameworks.
  Create clear, measurable recommendations. Include both quantitative metrics and
  qualitative observations where relevant."
```

**User B - ISTJ with Strong Traits (85-95%)**:
```
Result:
- Framework: RICE (highly data-driven)
- Approach: Aggressive AMPLIFY
- Prompt: "Perform a rigorous quantitative analysis of sales performance using
  the RICE framework (Reach, Impact, Confidence, Effort). Score each potential
  improvement on all four factors with specific numerical values. Create detailed
  spreadsheets with calculations. Recommendations must be backed by statistical
  significance. Exclude subjective opinions or unquantifiable factors. Follow a
  strict sequential process: 1) Data collection, 2) Statistical analysis,
  3) RICE scoring, 4) Ranked recommendations with confidence intervals."
```

## For Developers

### Testing Trait Percentage Influence

To validate that trait percentages are working:

1. **Create test prompts** with same personality type but different trait strengths:
   - Balanced profile: all traits 50-60%
   - Moderate profile: all traits 65-75%
   - Strong profile: all traits 85-95%

2. **Expected differences**:
   - Different framework selections
   - Different AMPLIFY/COUNTERBALANCE decisions
   - Significantly different prompt tone and structure
   - Different question focus areas

3. **Measure**:
   - Framework distribution by trait strength
   - Prompt length variation
   - Keyword analysis (e.g., "structured" vs "flexible" vs "systematic")
   - Directive language count (must/should/could/may)

### Implementation Details

Trait percentage interpretation is implemented in:
- `n8n/Framework Selector.json` - "Build LLM Prompt" node (system prompt)
- `n8n/Final Prompt Optimizer.json` - "Build LLM Prompt" node (system prompt)

Both workflows receive trait percentages in this format:
```json
{
  "trait_percentages": {
    "mind": 65,
    "energy": 82,
    "nature": 55,
    "tactics": 70,
    "identity": 88
  }
}
```

The system prompts interpret these values and calibrate the optimisation strategy accordingly.

## User-Facing Guidance

Users can optionally provide trait percentages in their profile settings:

- **Location**: Profile page → "Personality Type" section → "Add trait percentages (optional)"
- **Source**: Copy percentages from 16personalities.com test results
- **Effect**: More precisely calibrated prompts based on trait strength
- **Optional**: System works fine with just personality type (e.g., INTJ-A)

### When to Add Trait Percentages

**Recommended if:**
- You have strong traits (75%+) in any dimension
- You notice prompts feel too generic
- You want highly optimised results for your specific trait blend

**Not necessary if:**
- You're satisfied with personality-type-only optimisation
- You have very balanced traits across the board
- You prefer simpler setup

## Future Enhancements

Potential improvements to trait percentage usage:

1. **Trait-specific framework recommendations**: Build a matrix of which frameworks work best for each trait strength pattern
2. **Learning from feedback**: Correlate user feedback scores with trait strengths to improve calibration
3. **Trait strength visualisation**: Show users how their trait strengths influenced the prompt
4. **A/B testing**: Compare outputs with and without trait percentage calibration to measure effectiveness
5. **Trait-based templates**: Pre-built prompt templates optimised for common trait strength patterns

---

Last updated: 2025-11-24
Version: 1.0
