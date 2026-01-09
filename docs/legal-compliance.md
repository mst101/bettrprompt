# Legal Compliance & Considerations for BettrPrompt

**IMPORTANT DISCLAIMER:** This document is for informational purposes only and does not constitute legal advice. You should consult with a qualified attorney before launching BettrPrompt commercially.

---

## 16personalities.com & MBTI Framework

### Intellectual Property Overview

The **Myers-Briggs Type Indicator (MBTI)** framework is based on Carl Jung's psychological theories from the early 20th century. The 16 personality types (INTJ, ENFP, etc.) and the four dichotomies (E/I, S/N, T/F, J/P) are widely used across psychology, HR, and personal development and are not solely owned by 16personalities.com.

### What's Generally Safe

| Practice | Status |
|----------|--------|
| Referencing the 16 types by letter codes (INTJ, ENFP, etc.) | ✅ Safe |
| Using the general MBTI framework concept | ✅ Safe |
| Explaining personality dimensions in your own words | ✅ Safe |
| Encouraging users to discover their type | ✅ Safe |
| Linking to 16personalities.com with text | ✅ Safe |

### What's Potentially Problematic

| Practice | Risk | Notes |
|----------|------|-------|
| Using 16personalities' branded character names ("Architect", "Mediator") | 🟡 Medium | Could imply endorsement or partnership |
| Reproducing their questionnaire verbatim | 🔴 High | Likely copyright/trademark infringement |
| Copying their type descriptions verbatim | 🔴 High | Copyright infringement |
| Using their logo without permission | 🔴 High | Trademark infringement, even with good intent |
| Implying partnership/endorsement | 🔴 High | Potentially fraudulent misrepresentation |

### Logo Usage - Action Required

**Current Status:** Using 16personalities' logo on your site ❌

**Risk:** Even with benign intent (driving traffic to them), they could send cease-and-desist letters citing trademark infringement.

**Recommended Action:** Remove their logo and replace with a text link. Example:
```
Discover your personality type at 16personalities.com
```

### Linking to Their Site

Simply linking to their public website with a text link is generally acceptable practice. However:

- ✅ Use phrases like "we recommend" rather than "our partner"
- ✅ Be clear it's a third-party service
- ❌ Don't imply partnership or endorsement that doesn't exist
- ❌ Don't suggest they've reviewed or endorsed BettrPrompt

### The "Making Money Off Their Back" Argument

This is a real concern. While you have valid counterarguments (you're driving traffic to them, educating users about personality frameworks), they could argue:

- You're commercially benefiting from their brand recognition
- Users might associate BettrPrompt's quality with their framework
- You're creating a derivative product competing for the same users

**Recommended Action:** Proactive outreach
1. Contact 16personalities.com directly
2. Explain what BettrPrompt does and why it benefits their users
3. Ask if they have partnership or affiliate programmes
4. Request written permission or acknowledgment

This converts a potential adversary into a potential ally or partner.

---

## Prompt Frameworks Intellectual Property

BettrPrompt uses approximately 60+ prompt frameworks sourced from various origins. This section analyses the IP status of these frameworks and provides guidance on compliant usage.

### Framework Sources

The frameworks were primarily collated from:
- [Juuzt.ai Knowledge Base](https://juuzt.ai/knowledge-base/prompt-frameworks/) - claims copyright on their content
- Academic research papers (Google, Meta, universities)
- Well-established business methodologies
- AI/prompt engineering community resources

### Framework Categories by IP Status

#### 1. Trademarked/Licensed Frameworks - ⚠️ HIGH RISK

| Framework | Owner | IP Status | Risk Level | Action Required |
|-----------|-------|-----------|------------|-----------------|
| **Six Thinking Hats** | Edward de Bono Limited | [Registered trademark](https://trademarks.justia.com/745/58/six-thinking-74558979.html) (USPTO #74558979) | 🔴 HIGH | See below |

**Six Thinking Hats - Detailed Analysis:**

Edward de Bono Limited has an explicit [IP Policy](https://www.debono.com/ip-policy) stating:
- "Six Thinking Hats" is a registered trademark
- All published material is protected by copyright
- Use without permission may constitute infringement
- They have licensed an official ChatGPT tool under their authorisation

**Options:**
1. **Remove entirely** - Safest option; replace with alternative multi-perspective frameworks
2. **Seek licence** - Contact their [authorised distributors](https://www.debono.com/authorised-distributors)
3. **Generic alternative** - Use "Multi-Perspective Analysis" with similar (but original) prompts

**Recommendation:** Consider removing Six Thinking Hats or creating an original "Perspective Analysis" framework that achieves similar goals without using their trademarked terminology.

#### 2. Copyrighted but Widely Used - 🟡 MEDIUM RISK

| Framework | Origin | Status | Recommendation |
|-----------|--------|--------|----------------|
| **SCAMPER** | Bob Eberle (1971 book) | Copyrighted book, but methodology widely used | ✅ Safe to use concept; write original descriptions |
| **Bloom's Taxonomy** | Benjamin Bloom (1956) | Original likely public domain; 2001 revision copyrighted | ✅ Safe to use concept; cite original source |

**SCAMPER Analysis:**
- Developed by Bob Eberle in his 1971 book "SCAMPER: Games for Imagination Development"
- The [SCAMPER technique](https://en.wikipedia.org/wiki/SCAMPER) is widely used in education and business
- The acronym and methodology are broadly applied without licensing
- **Recommendation:** Safe to use; ensure descriptions are in your own words

**Bloom's Taxonomy Analysis:**
- Original taxonomy published in 1956 (likely public domain)
- Revised taxonomy by Anderson & Krathwohl (2001) may be copyrighted
- Extremely widely used in educational contexts globally
- **Recommendation:** Safe to use; reference the educational concept generally

#### 3. Academic Research Frameworks - ✅ LOW RISK

These frameworks originated from published academic papers. Academic research is generally shared for knowledge advancement, and the concepts are freely usable with proper citation.

| Framework | Academic Source | Year | Safe to Use |
|-----------|-----------------|------|-------------|
| **Chain of Thought** | Wei et al. (Google) | 2022 | ✅ Yes - cite paper |
| **ReAct** | Yao et al. | 2023 | ✅ Yes - cite paper |
| **Self-Refine** | Madaan et al. | 2023 | ✅ Yes - cite paper |
| **Reflexion** | Shinn et al. | 2023 | ✅ Yes - cite paper |
| **Tree of Thought** | Yao et al. | 2023 | ✅ Yes - cite paper |
| **Step-Back Prompting** | Zheng et al. (Google DeepMind) | 2023 | ✅ Yes - cite paper |
| **Skeleton-of-Thought** | Ning et al. | 2023 | ✅ Yes - cite paper |
| **Few-Shot Learning** | Brown et al. (OpenAI) | 2020 | ✅ Yes - foundational concept |
| **Zero-Shot Learning** | Various | - | ✅ Yes - foundational concept |
| **Meta Prompting** | Various | - | ✅ Yes - general technique |

**Recommendation:** These are safe to use. Consider adding academic citations to your documentation for transparency and credibility.

#### 4. Attribution-Required Frameworks - 🟡 MEDIUM RISK

| Framework | Origin | Attribution Required |
|-----------|--------|---------------------|
| **CO-STAR** | GovTech Singapore / Sheila Teo | Yes |

**CO-STAR Analysis:**

The CO-STAR framework was developed by [GovTech Singapore's Data Science & AI team](https://towardsdatascience.com/how-i-won-singapores-gpt-4-prompt-engineering-competition-34c195a93d41/) and popularised by Sheila Teo, winner of Singapore's GPT-4 Prompt Engineering competition.

Sheila Teo has [publicly requested](https://medium.com/@sheilateozy/hello-there-it-appears-that-majority-of-your-content-on-co-star-was-almost-verbatim-taken-from-my-320b38da750a) that those using CO-STAR content "kindly credit my article as well as GovTech Singapore, who were the original inventors of this framework."

**Recommendation:**
- Add attribution in your framework documentation
- Example: "CO-STAR framework developed by GovTech Singapore"
- Do not copy her article verbatim; use your own descriptions

#### 5. Public Domain / Widely Established - ✅ LOW RISK

These frameworks are well-established methodologies used across industries without licensing requirements:

| Framework | Origin | Status |
|-----------|--------|--------|
| **SMART Goals** | Peter Drucker / George Doran (1981) | ✅ Public methodology |
| **STAR Method** | Behavioural interviewing standard | ✅ Public methodology |
| **RICE Scoring** | Product management standard | ✅ Public methodology |
| **Pros and Cons** | General reasoning | ✅ Public methodology |
| **5W1H (Five Ws and One H)** | Journalism standard | ✅ Public methodology |
| **Socratic Method** | Ancient Greek philosophy | ✅ Public domain |
| **CAR/PAR/BAB** | Storytelling structures | ✅ Public methodology |
| **HMW (How Might We)** | Design thinking | ✅ Public methodology |

**Recommendation:** These are safe to use without attribution, though citing origins adds credibility.

#### 6. Community/AI Prompt Frameworks - ✅ LOW RISK

These frameworks appear to be community-developed within the AI/prompt engineering space. Origins are often unclear, and they're freely shared across multiple websites:

| Frameworks | Status |
|------------|--------|
| CRISPE, RELIC, RISEN, RACEF, RODES, RHODES, GRADE, TRACI, CIDI, COAST, FOCUS, SPARK, SPEAR, ERA, CARE, TAG, APE, RTF, ICIO, CRAFT, etc. | ✅ Likely safe - community-developed |

**Recommendation:** Safe to use, but ensure your descriptions are original (not copied verbatim from any source).

### Juuzt.ai Content - Specific Guidance

**Status:** Juuzt.ai claims "Copyright 2020-2026 Juuzt | All rights reserved" on their content.

**What This Means:**
- The underlying framework **concepts** (SMART, STAR, etc.) are generally not copyrightable
- Juuzt's specific **instructional text and explanations** may be copyrighted
- You cannot reproduce their content verbatim

**Recommendation:**
- ✅ Safe: Using the framework concepts and acronyms
- ✅ Safe: Writing your own descriptions and explanations
- ❌ Avoid: Copying their text verbatim
- 🟡 Consider: Adding a general attribution if you used their site as a research source

### Action Items for Framework Compliance

#### Immediate Actions

- [ ] **Six Thinking Hats** - Decide: remove, licence, or create generic alternative
- [ ] **CO-STAR** - Add attribution to GovTech Singapore / Sheila Teo
- [ ] **Review all framework descriptions** - Ensure none are copied verbatim from Juuzt.ai or other sources
- [ ] **Rewrite any copied content** - All descriptions should be in your own words

#### Recommended Additions

- [ ] **Add academic citations** - For Chain of Thought, ReAct, Self-Refine, etc.
- [ ] **Create attribution page** - "Framework sources and acknowledgments"
- [ ] **Document framework origins** - Add `origin` field to framework templates where known

### Summary Table

| Risk Level | Frameworks | Action |
|------------|------------|--------|
| 🔴 HIGH | Six Thinking Hats | Remove or licence |
| 🟡 MEDIUM | CO-STAR, SCAMPER, Bloom's | Add attribution; use own words |
| ✅ LOW | All academic frameworks | Safe; cite papers for credibility |
| ✅ LOW | All public methodologies (SMART, STAR, RICE, etc.) | Safe to use |
| ✅ LOW | Community prompt frameworks | Safe; write original descriptions |

### Resources

- [de Bono IP Policy](https://www.debono.com/ip-policy)
- [Six Thinking Hats Trademark](https://trademarks.justia.com/745/58/six-thinking-74558979.html)
- [CO-STAR Framework Origin (Sheila Teo)](https://towardsdatascience.com/how-i-won-singapores-gpt-4-prompt-engineering-competition-34c195a93d41/)
- [SCAMPER Wikipedia](https://en.wikipedia.org/wiki/SCAMPER)
- [Chain of Thought Paper](https://arxiv.org/abs/2201.11903)
- [ReAct Paper](https://arxiv.org/abs/2210.03629)

---

## Privacy & Data Protection (Critical Priority)

### GDPR Compliance (If Serving EU Users)

Personality data is likely considered **sensitive personal data** under GDPR, triggering enhanced protections:

**Required Actions:**
- ✅ Explicit user consent before collecting personality data
- ✅ Clear privacy policy explaining what data you collect and why
- ✅ Data subject rights: access, correction, deletion (right to be forgotten)
- ✅ Data processing agreements if using third parties (Stripe, Mailgun, n8n, MaxMind)
- ✅ Breach notification procedures (notify users within 72 hours of discovery)
- ✅ Data Protection Impact Assessment (DPIA) for high-risk processing

**Current Gaps to Address:**
- [ ] Review current privacy policy for personality data specifics
- [ ] Ensure consent mechanisms for personality data collection
- [ ] Document data retention periods and deletion policies
- [ ] Review third-party vendor agreements for GDPR compliance

### CCPA Compliance (If Serving California Users)

California's Consumer Privacy Act requires:

- ✅ Privacy policy disclosure of data collection practices
- ✅ "Do Not Sell My Personal Information" option
- ✅ Right to know, delete, and opt-out
- ✅ Non-discrimination for exercising privacy rights

### General Privacy Best Practices

**Essential Privacy Policy Sections:**
1. What data you collect (personality type, prompts, email, payment info)
2. How you use it (prompt generation, personalisation, analytics)
3. Who you share it with (n8n, Stripe, Mailgun, MaxMind)
4. How long you keep it (specify retention periods)
5. User rights (access, correction, deletion)
6. Cookie usage and consent

**Data Collection Checklist:**
- [ ] Identify all data collection points
- [ ] Obtain explicit consent for each data type
- [ ] Encrypt sensitive data (passwords, payment info)
- [ ] Implement access controls (who can view user data)
- [ ] Document all data flows and third-party processors

### Staff Access Controls (Highly Recommended)

If you are processing sensitive prompt content and personality/profile data, treat staff access as an exceptional event:

- **Default-deny staff access** to user-generated content; grant access only via scoped workflows.
- **Private mode**: use **user-consented support sessions** (time-limited, scope-limited, revocable) rather than an admin “backdoor”.
- **Free tier** (if you support content review): allow **break-glass** access only for admins, with strict limits:
  - required reason + ticket reference
  - short TTL, read-only by default
  - immutable audit logs for every access
  - user notification after access unless prohibited by law

**Law enforcement / legal requests**: define an internal policy requiring legal review and minimal disclosure. If private-mode content is designed to be inaccessible without user consent, disclose that reality in policy and respond with what you can (typically metadata and any plaintext you legitimately retain elsewhere).

---

## Terms of Service

You must have clear terms covering:

### User-Generated Content

- Who owns the prompts users create?
- Can BettrPrompt use them to improve the service?
- Can users export/download their prompts?
- Can BettrPrompt share anonymised prompt data for research?

### Acceptable Use Policy

- Prohibition on harassment, illegal activity, spam
- Restrictions on reverse-engineering, scraping, or automated access
- Requirements for respecting others' intellectual property

### Limitations of Liability

**Critical Disclaimer Needed:**
> BettrPrompt provides AI-generated prompts as a tool for inspiration and guidance. The generated content is not professional advice. Users are responsible for:
> - Verifying prompt accuracy and relevance
> - Ensuring their use complies with applicable laws
> - Understanding that AI outputs may contain errors or biases

**Example Language:**
```
IN NO EVENT SHALL BETTRPROMPT BE LIABLE FOR:
- Inaccuracy or incompleteness of AI-generated content
- Decisions made based on generated prompts
- Loss of data, revenue, or profits
- Indirect, incidental, or consequential damages
```

### Account Termination Rights

- BettrPrompt's right to suspend/terminate accounts for policy violations
- User's right to cancel subscription and request data deletion

### Intellectual Property

- Your content ownership (text, logos, UI)
- User content ownership (their prompts)
- License grants (users can use their prompts, but BettrPrompt retains platform IP)

---

## AI-Specific Disclosures

As regulations evolve, be proactive with AI transparency:

### Disclosure Requirements

- ✅ Clearly state that prompts are **AI-generated**
- ✅ Don't imply human review or professional expertise where there isn't any
- ✅ Avoid language suggesting "personalized professional advice"

### Proposed Language

```
BettrPrompt uses artificial intelligence to generate personalized prompts
based on your task description and personality type. These are suggestions
and starting points, not professional advice or guarantees of success.
```

### Known AI Limitations to Disclose

- AI outputs may contain inaccuracies, biases, or outdated information
- No personalised professional advice (legal, financial, medical, etc.)
- Users are responsible for fact-checking generated content
- AI may produce different results for similar inputs

---

## Payment Processing & Subscriptions (Stripe)

### PCI Compliance

**Good news:** Stripe handles most PCI compliance. Ensure:

- ✅ Never store card data on your servers (Stripe handles this)
- ✅ Use Stripe's secure payment forms
- ✅ Only transmit payment data over HTTPS
- ✅ Comply with Stripe's requirements

### Subscription & Refund Policy

**Required Disclosures:**
- Clear pricing for monthly and yearly plans
- Billing frequency and renewal date
- Refund eligibility (e.g., "7-day refund guarantee", "non-refundable")
- Cancellation process and timing

**Recommended Policy:**
```
REFUNDS: We offer a 7-day money-back guarantee from purchase date.
After 7 days, subscriptions are non-refundable but can be cancelled
to prevent future charges. Cancellations take effect at the end of
the current billing cycle.
```

**Fair Billing Practices:**
- Clear confirmation before charging
- Easy cancellation option (not hidden behind multiple menus)
- Clear reminder of renewal before next billing cycle
- Prompt issue resolution for billing disputes

---

## Intellectual Property Management

### Your Content

Ensure you own or have explicit licenses for:
- Code and architecture
- Design and branding (logos, colour schemes)
- Copy and written content
- Third-party libraries and frameworks (check licenses)

**Action Items:**
- [ ] Review all npm dependencies for license compliance (MIT, Apache, GPL, etc.)
- [ ] Ensure Laravel and Vue licenses are compatible
- [ ] Document any open-source components used

### User Content

**Critical Question: Who owns user-generated prompts?**

**Option 1: User Ownership (Recommended for trust)**
```
Users retain full ownership of prompts they create.
BettrPrompt has a licence to use anonymised prompts for service improvement.
```

**Option 2: Platform Ownership (Higher risk of user resistance)**
```
Users grant BettrPrompt a perpetual, worldwide licence to use their content
(including prompts) for any purpose, including commercial purposes.
```

### AI-Generated Content Copyright

**Important:** The copyright status of AI-generated content is currently uncertain:
- U.S. Copyright Office: AI outputs may not be copyrightable
- EU approach: Varies by member state
- UK approach: Emphasises human authorship

**Recommendation:** Include disclaimer in terms:
```
The copyright status of AI-generated content is uncertain. Users should
consider generated prompts as starting points rather than copyrighted works.
```

---

## Regulatory Compliance Checklist

### Before Launch

- [ ] **Privacy Policy** - Written, comprehensive, accessible on site
- [ ] **Terms of Service** - Clear, legally reviewed
- [ ] **Cookie Policy** - Consent mechanism for non-essential cookies
- [ ] **Accessibility** - WCAG 2.1 AA compliance (legal requirement in many jurisdictions)
- [ ] **16personalities Logo** - Removed or explicit permission obtained
- [ ] **Six Thinking Hats** - Removed or licence obtained from de Bono Limited
- [ ] **CO-STAR Attribution** - Added credit to GovTech Singapore / Sheila Teo
- [ ] **Framework Descriptions** - Reviewed to ensure none copied verbatim from sources
- [ ] **AI Disclaimers** - Clearly state prompts are AI-generated, not professional advice
- [ ] **Stripe Agreement** - Reviewed and accepted
- [ ] **Data Processing Agreements** - With Mailgun, n8n, MaxMind
- [ ] **GDPR/CCPA Review** - By qualified legal counsel

### Ongoing Compliance

- [ ] **Monitoring** - Track regulatory changes (AI regulation, privacy laws)
- [ ] **Updates** - Refresh terms and policies annually
- [ ] **Support** - Fast response to data subject access requests
- [ ] **Retention** - Delete user data per stated retention policy
- [ ] **Incidents** - Log and report data breaches appropriately

---

## Jurisdiction-Specific Considerations

### United States

- **CAN-SPAM Act** - If sending marketing emails, comply with unsubscribe requirements
- **FTC Act** - Avoid deceptive marketing; disclose AI use
- **State Laws** - Some states (California, Connecticut, Virginia) have privacy laws
- **COPPA** - If users under 13, enhanced protections required

### European Union

- **GDPR** - Strict data protection and consent requirements
- **ePrivacy Directive** - Cookie and tracking consent
- **Digital Services Act** - Transparency about algorithmic recommendations
- **Consumer Rights Directive** - 14-day cancellation rights (subscriptions)

### United Kingdom

- **UK GDPR** - Post-Brexit version of GDPR
- **Data Protection Act 2018** - UK-specific data handling rules

---

## Red Flags to Avoid

🚫 **Don't:**
- Promise AI outputs are "professional advice" (legal, financial, medical)
- Imply endorsement from 16personalities.com without permission
- Use their logo or branding without consent
- Use trademarked framework names (Six Thinking Hats) without licence
- Copy framework descriptions verbatim from other websites
- Collect personal data without explicit consent
- Make false or exaggerated claims about AI capabilities
- Hide privacy policies or make them difficult to find
- Make subscription cancellation overly complicated
- Ignore data subject access requests or deletion requests
- Sell user data without explicit consent

✅ **Do:**
- Be transparent about AI limitations
- Make privacy controls easily accessible
- Provide clear, honest terms and conditions
- Respond promptly to legal inquiries
- Maintain detailed records of user consent
- Encrypt sensitive data in transit and at rest
- Review terms annually with legal counsel
- Add attribution for frameworks where creators request it (CO-STAR)
- Write original descriptions for all frameworks
- Cite academic papers for research-based frameworks

---

## Recommended Next Steps

### Immediate (Before Launch)

1. **Remove 16personalities logo** - Replace with text link
2. **Legal review** - Have qualified attorney review Terms of Service, Privacy Policy, AI disclaimers
3. **Privacy audit** - Ensure GDPR/CCPA compliance if applicable

### Short-term (Within 30 days of launch)

4. **Contact 16personalities.com** - Discuss partnership or get written permission
5. **Implement cookie consent** - If using analytics cookies
6. **Document data flows** - Map all data collection and processing

### Ongoing

7. **Monitor legal updates** - Subscribe to relevant regulatory updates
8. **Annual review** - Update terms, policies, and compliance measures
9. **Incident response plan** - Procedures for data breaches or legal issues

---

## Resources

- [GDPR Official Guidance](https://gdpr-info.eu/)
- [CCPA Official Guidance](https://oag.ca.gov/privacy/ccpa)
- [FTC Guide to Endorsements & Testimonials](https://www.ftc.gov/news-events/news/2023/10/ftc-issues-revised-endorsement-guides-featuring-ai-generated-content)
- [OpenAI Content Policy](https://openai.com/policies/usage-policies/)
- [Stripe Legal Resources](https://stripe.com/legal)

---

**Last Updated:** January 8, 2026
**Next Review:** Recommended before first paying user onboarding
