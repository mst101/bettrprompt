## ANALYSIS FROM WORKFLOW 1

Task: STRATEGY (moderate)
Framework: COAST
Personality: full

**Task-Trait Alignment Analysis:**

```json
{
    "NOTE": "Analyzing ONLY the five traits provided: I(65%), N(64%), T(84%), P(57%), A(84%)",
    "amplified": [
        {
            "trait": "High N (64%)",
            "requirement_aligned": "VISION",
            "reason": "Intuition excels at pattern recognition across bird migration cycles, seasonal ecosystems, and regional variations. Natural ability to conceptualize the 'big picture' of when/where birds congregate."
        },
        {
            "trait": "High T (84%)",
            "requirement_aligned": "OBJECTIVE",
            "reason": "Strong analytical thinking enables evidence-based species research, objective evaluation of birding sites based on data (sighting records, species lists), and logical assessment of travel logistics."
        },
        {
            "trait": "High A (84%)",
            "requirement_aligned": "DECISIVE",
            "reason": "Assertiveness supports confident decision-making on itinerary, accommodation choices, and guide selection without excessive second-guessing. Helps commit to specific dates/locations."
        }
    ],
    "counterbalanced": [
        {
            "trait": "High N (64%)",
            "requirement_opposed": "DETAIL",
            "reason": "Intuitive preference for concepts/patterns may cause skipping over concrete logistical details (exact accommodation addresses, specific transport times, packing lists). Risk of 'big picture only' planning."
        },
        {
            "trait": "High P (57%)",
            "requirement_opposed": "DETAIL",
            "reason": "Perceiving preference for flexibility may resist committing to specific itinerary structure, daily schedules, or firm bookings. Could lead to under-planning of logistics."
        },
        {
            "trait": "High T (84%)",
            "requirement_opposed": "RISK",
            "reason": "Analytical thinking focuses on logical optimization but may underweight practical travel risks (health precautions, visa requirements, weather contingencies, guide reliability). Tendency to assume 'if logical, it will work.'"
        }
    ],
    "neutral": [
        {
            "trait": "High I (65%)",
            "reason": "Introversion/Extraversion not directly relevant to solo holiday planning. Introversion may prefer independent exploration over group tours, but this is preference-based, not a task requirement."
        }
    ]
}
```

IMPORTANT: Apply the amplifications and counterbalancing as specified above. For each counterbalanced trait, ensure the
injection text is explicitly included in the generated prompt.

---

## ORIGINAL TASK

I want to plan a birdwatching holiday to the Gambia

## PERSONALITY DATA

Type: INTP-A
Percentages: {"mind":65,"energy":64,"nature":84,"tactics":57,"identity":84}

## PRE-ANALYSIS CONTEXT

Context gathered during pre-analysis:

```json
{
    "detail_level": {
        "question": "How comprehensive would you like your birdwatching holiday planning to be?",
        "answer": "comprehensive",
        "answer_label": "Extensive research including specific bird species, accommodation, travel routes, and seasonal considerations"
    },
    "audience": {
        "question": "Who is this birdwatching holiday plan for?",
        "answer": "self",
        "answer_label": "Planning for myself"
    },
    "experience_level": {
        "question": "What is your birdwatching experience level?",
        "answer": "expert",
        "answer_label": "Experienced birder seeking advanced details"
    }
}
```

## USER'S ANSWERS TO CLARIFYING QUESTIONS

**Q1: What is your target timeframe for this trip (specific dates or month range), and how long will you be in the
Gambia?**
A: Some time this winter for one week.

**Q2: What are your specific bird-watching objectives — are you targeting particular species, a species count goal, or
specific bird families/habitats?**
A: I want to see the Egyptian Plover and Painted Snipe

**Q3: What is your budget range for accommodation, guides, and transport combined?**
A: £3000 inc. flights

**Q4: Will you be birding independently, hiring a local guide, or joining an organized birding tour group?**
A: Either hiring a guide or joining a group.

**Q5: What specific habitats or regions within the Gambia are you most interested in (wetlands, savanna, coastal areas,
forest reserves)?**
A: Any

**Q6: What are your non-negotiable constraints or requirements (e.g., flight dates locked in, specific accommodation
standards, health/visa considerations)?**
A: Flights from Birmingham. Mid-range lodges. Uk citizen

**Q7: What travel risks or contingencies concern you most (health precautions, visa complexity, weather disruptions,
guide reliability)?**
A: null

**Q8: Do you have existing accommodation or guide recommendations, or are you starting from scratch with research?**
A: null

---

Now construct the optimised prompt. Remember to:

1. Use the COAST framework structure
2. Incorporate all user answers naturally into the prompt
3. Apply AMPLIFICATION for aligned traits (leverage strengths)
4. Apply COUNTERBALANCING for opposed traits (inject explicit requirements)
5. Make the prompt self-contained and immediately usable
6. Recommend models considering the complexity of counterbalancing needed

# User Context

- Location: United Kingdom (Redditch)
- Timezone: Europe/London
- Currency: GBP
- Language: en-GB

Use this context when optimising the prompt (e.g., use local currency in examples, recommend appropriate tools, adjust
complexity level for experience).
