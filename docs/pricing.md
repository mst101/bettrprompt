# BettrPrompt Pricing Analysis

## Executive Summary

**Verdict:** The three-tier pricing model (Free-Unlimited-Private) is well-designed and competitively positioned. Prices are fair and sustainable, assuming 25-50 prompts/month average per paid user.

**Critical assumption:** All prices are VAT-inclusive (standard for B2C in the UK). This reduces net revenue but remains profitable.

---

## Current Pricing Structure

| Tier | GBP (Monthly) | GBP (Annual) | Net Revenue (excl. VAT) |
|------|---------------|--------------|------------------------|
| **Free** | £0 (10 prompts) | N/A | £0 |
| **Unlimited** | £12 | £120 | £10.00/month, £100/year |
| **Private** | £20 | £200 | £16.67/month, £166.67/year |

**Cost per prompt:** ~£0.07 (6-8 pence)

---

## Unit Economics (VAT-Inclusive Pricing)

### Gross Margin Analysis

| Tier | Gross Price | VAT (20%) | Net Revenue | Cost @ 40 prompts | Gross Margin |
|------|-------------|-----------|-------------|-------------------|--------------|
| **Unlimited** | £12 | £2.00 | £10.00 | £2.80 | **72%** |
| **Private** | £20 | £3.33 | £16.67 | £2.80 | **83%** |

**Assessment:** Excellent SaaS margins. Industry benchmark is 60-80% gross margin; you're in the upper range.

### Breakeven Analysis

The point at which cost exceeds net revenue:

| Tier | Breakeven Prompts | Typical User at 40 prompts | Danger Zone Starts |
|------|-------------------|---------------------------|--------------------|
| **Unlimited** | 143 prompts/month | **72% margin** | >143 prompts (5% of users?) |
| **Private** | 238 prompts/month | **83% margin** | >238 prompts (rare) |

**Risk assessment:** Low. Breakeven is high enough that you'd only lose money on extreme power users. Consider soft rate limits if usage abuse becomes a problem.

---

## Market Comparison

### Direct Competitors

| Service | Monthly Price | Target User | Key Difference |
|---------|---------------|-------------|-----------------|
| **ChatGPT Plus** | $20 (~£16) | General AI needs | General-purpose assistant |
| **Claude Pro** | $20 (~£16) | General AI needs | General-purpose assistant |
| **Jasper AI** | $49+ (~£39+) | Marketing teams | Brand-focused writing |
| **Prompt Genie** | $7-10 (~£6-8) | Prompt librarians | Library + optimization only |

### Competitive Positioning

- **BettrPrompt Unlimited (£12):** Cheaper than ChatGPT/Claude, more expensive than pure prompt tools. Justified because you offer personality calibration (differentiated value).
- **BettrPrompt Private (£20):** Parity with ChatGPT/Claude but with privacy guarantees. Defensible premium.

**Verdict:** Appropriately priced. Perhaps slightly on the conservative side (could test £15/£25), but good for launch.

---

## Usage Estimate: Prompts Per Month

### Market Data
- ChatGPT users average 4.5 interactions/day, but ChatGPT is used for everything
- Structured pilot studies show ~16 prompts per session
- BettrPrompt is a specialised tool (not a general assistant), so usage patterns differ

### Segmentation by User Type

| User Type | Monthly Prompts | Behaviour | % of Users (estimate) |
|-----------|-----------------|-----------|----------------------|
| **Casual/Curious** | 3-8 | Try a few prompts, maybe weekly | 20% |
| **Regular** | 15-40 | Weekly workflow integration | 50% |
| **Power User** | 60-150 | Daily use, multiple projects | 25% |
| **Heavy Professional** | 200+ | Agency/consultant workload | 5% |

### Best Estimate: Average Paid User

**25-50 prompts/month** (central estimate: ~35 prompts)

**Reasoning:**
1. Specialised tool (not always-on like ChatGPT)
2. Users must intentionally switch context to use it
3. Higher quality per prompt (personality calibration reduces iteration)
4. Integration into workflows takes time

---

## Profitability Scenarios

### Scenario A: 35 Prompts/Month (Conservative)

| Tier | Revenue | Cost | Gross Margin |
|------|---------|------|--------------|
| Unlimited | £10.00 | £2.45 | **75.5%** |
| Private | £16.67 | £2.45 | **85.3%** |

✅ Healthy margins. Low risk.

### Scenario B: 60 Prompts/Month (Active User)

| Tier | Revenue | Cost | Gross Margin |
|------|---------|------|--------------|
| Unlimited | £10.00 | £4.20 | **58%** |
| Private | £16.67 | £4.20 | **74.8%** |

✅ Still profitable. Private tier absorbs outliers.

### Scenario C: 143 Prompts/Month (Power User - Breakeven)

| Tier | Revenue | Cost | Gross Margin |
|------|---------|------|--------------|
| Unlimited | £10.00 | £10.01 | **-0.1%** ❌ |
| Private | £16.67 | £10.01 | **39.9%** ✅ |

⚠️ Unlimited breaks even at 143 prompts. Estimate 5% of power users reach this. Consider rate limits or tiering if becomes a problem.

---

## Pricing Model Assessment

### The Three-Tier Approach: Why It Works

1. **Separation of concerns**: Free/Unlimited address "usage", Private addresses "privacy". Competitors bundle everything.
2. **No feature envy**: Users can't feel cheated. Each tier has a clear reason to exist.
3. **Privacy as premium**: Privacy-conscious users (consultants, therapists, businesses) are willing to pay extra.
4. **Freemium conversion funnel**: 10 prompts/month is enough to deliver value, few enough to drive upgrade desire.

### The Free Tier: Is 10 Prompts/Month Right?

**Yes.**
- Too generous (20+): Reduces upgrade conversion
- Too stingy (<5): Won't deliver enough value to convert
- 10 is the Goldilocks zone—enough to try personality calibration, but you'll hit the ceiling within 1-2 weeks

### Pricing Levels: Should You Consider £15/£25?

**Possibly for future iterations.** Current pricing is safe for launch:
- £12 feels accessible
- £20 feels premium but fair
- Annual discount (17%) is standard

**Future testing points:**
- If conversion >10%, test raising to £15/£25
- If you discover higher-than-expected usage (80+ prompts), raise prices now
- If CAC is low, you can afford to raise prices

---

## Recommendations

### Launch Strategy (Immediate)

1. ✅ **Keep prices as-is.** They're competitive, sustainable, and clear.
2. ✅ **Emphasise VAT-inclusive pricing** in marketing ("From £12/month" not "£12 + VAT").
3. ✅ **Free tier is good.** Don't change 10 prompts/month.

### Monitoring Metrics (Critical)

Track these post-launch:

| Metric | Target | Action if Below | Action if Above |
|--------|--------|-----------------|-----------------|
| **Free→Paid conversion** | 5-10% | Review value prop | Test price increase |
| **Avg prompts/month (Unlimited)** | 30-50 | Good | If >100, monitor for abuse |
| **Unlimited→Private upgrade rate** | 5-15% | Review privacy messaging | Good sign, promote more |
| **CAC** | <£40 | Review marketing | Invest more |

### Risk Management

1. **Heavy user abuse (143+ prompts/month on Unlimited):**
   - Implement soft rate limits (e.g., 20 prompts/day)
   - Or grandfathered/auto-upgrade to Private at threshold

2. **Unexpected churn on Private tier:**
   - Privacy features may not deliver perceived value
   - Gather feedback early

3. **Prompt cost inflation (if Claude prices rise):**
   - Monitor OpenAI/Anthropic pricing
   - Consider dynamic pricing if costs exceed 10% of revenue

---

## Future Pricing (Not Now)

### When Costs Rise
If your per-prompt cost increases to £0.10+ (from £0.07 now), consider:
- Raise prices to £15/£25 (small enough not to shock customers)
- Or introduce tiered usage-based add-ons (e.g., "Buy 50 extra prompts for £5")

### When Feature Parity Is Needed
If competitors offer similar features at lower prices:
- Don't race to the bottom
- Differentiate on outcomes (e.g., "Our prompts save X hours vs. ChatGPT")
- Consider freemium competition by marketing free tier more aggressively

### Team/Enterprise Pricing (Later)
When ready:
- Team: £8-10/seat/month (volume discount)
- Enterprise: Custom (seat-based + annual commitment)

---

## VAT & Tax Compliance Notes

- **UK pricing:** VAT-inclusive (20% standard rate)
- **EU customers:** Register for EU One-Stop-Shop (OSS) if your revenue scales. Single quarterly return covers all EU sales.
- **US/Rest of world:** No VAT required
- **Stripe Tax (optional):** Automate VAT calculation at £0.50/transaction. Consider enabling after £10k/month revenue threshold.

---

## Conclusion

**Your pricing is solid.** The Free-Unlimited-Private structure is clever, the numbers work, and you're positioned competitively. Launch with confidence, monitor usage closely, and iterate based on real data.

**The key assumption:** Average paid user generates 25-50 prompts/month. If this is significantly wrong (e.g., 100+ prompts average), revisit pricing within first 6 months.

---

## References

- [ChatGPT Users Statistics (January 2026) – DemandSage](https://www.demandsage.com/chatgpt-statistics/)
- [Tom's Guide: Claude Pro vs ChatGPT Plus](https://www.tomsguide.com/ai/claude-pro-vs-chatgpt-plus-i-tested-both-subscriptions-to-see-which-ones-actually-worth-usd20)
- [AI Pricing Comparison 2025 – AIonX](https://aionx.co/ai-comparisons/ai-pricing-comparison/)
- [TechCrunch: ChatGPT users send 2.5 billion prompts a day](https://techcrunch.com/2025/07/21/chatgpt-users-send-2-5-billion-prompts-a-day/)
