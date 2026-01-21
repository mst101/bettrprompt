# BettrPrompt Pricing Analysis

## Executive Summary

**Verdict:** The three-tier pricing model (Free-Pro-Private) is well-designed and competitively positioned. Prices are fair and sustainable, assuming 25-50 prompts/month average per paid user.

**Critical assumption:** All prices are VAT-inclusive (standard for B2C in the UK). This reduces net revenue but remains profitable.

---

## Current Pricing Structure

| Tier | GBP (Monthly) | GBP (Annual) | Net Revenue (excl. VAT) |
|------|---------------|--------------|------------------------|
| **Free** | £0 (10 prompts) | N/A | £0 |
| **Pro** | £12 | £120 | £10.00/month, £100/year |
| **Private** | £20 | £200 | £16.67/month, £166.67/year |

**Cost per prompt:** ~£0.07 (6-8 pence)

---

## Unit Economics (VAT-Inclusive Pricing)

### Gross Margin Analysis

| Tier | Gross Price | VAT (20%) | Net Revenue | Cost @ 40 prompts | Gross Margin |
|------|-------------|-----------|-------------|-------------------|--------------|
| **Pro** | £12 | £2.00 | £10.00 | £2.80 | **72%** |
| **Private** | £20 | £3.33 | £16.67 | £2.80 | **83%** |

**Assessment:** Excellent SaaS margins. Industry benchmark is 60-80% gross margin; you're in the upper range.

### Breakeven Analysis

The point at which cost exceeds net revenue:

| Tier | Breakeven Prompts | Typical User at 40 prompts | Danger Zone Starts |
|------|-------------------|---------------------------|--------------------|
| **Pro** | 143 prompts/month | **72% margin** | >143 prompts (5% of users?) |
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

- **BettrPrompt Pro (£12):** Cheaper than ChatGPT/Claude, more expensive than pure prompt tools. Justified because you offer personality calibration (differentiated value).
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
| Pro | £10.00 | £2.45 | **75.5%** |
| Private | £16.67 | £2.45 | **85.3%** |

✅ Healthy margins. Low risk.

### Scenario B: 60 Prompts/Month (Active User)

| Tier | Revenue | Cost | Gross Margin |
|------|---------|------|--------------|
| Pro | £10.00 | £4.20 | **58%** |
| Private | £16.67 | £4.20 | **74.8%** |

✅ Still profitable. Private tier absorbs outliers.

### Scenario C: 143 Prompts/Month (Power User - Breakeven)

| Tier | Revenue | Cost | Gross Margin |
|------|---------|------|--------------|
| Pro | £10.00 | £10.01 | **-0.1%** ❌ |
| Private | £16.67 | £10.01 | **39.9%** ✅ |

⚠️ Pro breaks even at 143 prompts. Estimate 5% of power users reach this. Consider rate limits or tiering if becomes a problem.

---

## Pricing Model Assessment

### The Three-Tier Approach: Why It Works

1. **Separation of concerns**: Free/Pro address "usage", Private addresses "privacy". Competitors bundle everything.
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
| **Avg prompts/month (Pro)** | 30-50 | Good | If >100, monitor for abuse |
| **Pro→Private upgrade rate** | 5-15% | Review privacy messaging | Good sign, promote more |
| **CAC** | <£40 | Review marketing | Invest more |

### Risk Management

1. **Heavy user abuse (143+ prompts/month on Pro):**
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

**Your pricing is solid.** The Free-Pro-Private structure is clever, the numbers work, and you're positioned competitively. Launch with confidence, monitor usage closely, and iterate based on real data.

**The key assumption:** Average paid user generates 25-50 prompts/month. If this is significantly wrong (e.g., 100+ prompts average), revisit pricing within first 6 months.

---

---

## Multi-Currency Strategy

### Current Implementation: GBP, EUR, USD

**Decision: Limit to 3 currencies** (British Pound, Euro, US Dollar)

### Why Only 3 Currencies?

**1. Market Coverage (75-80% of SaaS Revenue)**
- **GBP:** UK market (primary target based on domain and British English focus)
- **EUR:** Eurozone (27 countries, 450M people)
- **USD:** United States, Canada, and international default

**2. Stripe Subscription Mode Constraint**
- Stripe's [Adaptive Pricing](https://docs.stripe.com/payments/currencies/localize-prices/adaptive-pricing) **does not work for subscription mode**
- Each currency requires manual [price configuration](https://docs.stripe.com/payments/checkout/localize-prices/manual-currency-prices)
- More currencies = exponentially more maintenance

**3. Technical Complexity per Currency**

Each additional currency requires:
- 4 new Stripe Price IDs (Pro/Private × Monthly/Yearly)
- 4 new environment variables
- Updates to 5+ language files (backend PHP + frontend JSON)
- GeolocationService country mapping updates
- Separate testing scenarios

**Current:** 3 currencies × 2 tiers × 2 intervals = **12 Stripe Price IDs**
**With 10 currencies:** 10 × 2 × 2 = **40 Stripe Price IDs to manage**

**4. Operational Benefits of Database-Stored Prices**

Prices are now stored in the `prices` table with relationships to currencies:
- Single source of truth eliminates synchronization issues
- Easy to update prices across all locales
- Supports A/B testing and promotional pricing
- Audit trail for price changes

### Price Localization Strategy

**Not Simple Conversion:**
- £12 GBP ≠ $15 USD (direct conversion would be ~$15.12)
- €13.99 EUR ≠ £12 GBP (accounts for Eurozone purchasing power)
- Prices are **psychologically optimized per market**, not mathematically converted

**Annual Discount Consistency:**
- All currencies: ~17% annual discount
- Pro: 10× monthly price for yearly
- Private: 10× monthly price for yearly

### Currency Assignment Flow

1. **Initial Detection:** GeolocationService maps user's country to currency
2. **Stored in Visitor:** Anonymous users get `currency_code` in visitors table
3. **Transferred to User:** On registration, currency_code copies to users table
4. **Manual Override:** Users can change currency via:
   - Profile settings → Location section
   - Pricing page currency switcher (new feature)
5. **Priority:** User selection > GeoIP detection > Default (GBP)

### Stripe Currency Fees

When using Stripe's exchange rates:
- Stripe adds [2-4% currency conversion fee](https://docs.stripe.com/payments/currencies/localize-prices/adaptive-pricing) to customer
- Fee is paid by customer, not merchant
- Better to offer local currencies natively to avoid this

### Future Currency Expansion (If Needed)

**Only add if customer demand proves strong:**

**Tier 1 Priority (High-Value English-Speaking Markets):**
- **CAD** (Canadian Dollar) - Large market, close to US pricing psychology
- **AUD** (Australian Dollar) - High GDP per capita, tech-savvy
- **CHF** (Swiss Franc) - Premium market, high purchasing power

**Tier 2 Priority (Medium Value):**
- **SEK** (Swedish Krona) - Nordic countries, strong SaaS adoption
- **JPY** (Japanese Yen) - Requires price psychology adjustments (¥1,500 vs £12)
- **SGD** (Singapore Dollar) - High-value Asian hub

**Avoid (Operational Complexity):**
- **INR** (Indian Rupee) - Requires purchasing power parity pricing (~$2-3/mo)
- **BRL** (Brazilian Real) - Currency volatility, frequent price updates needed
- **CNY** (Chinese Yuan) - Regulatory complexity, requires WeChat Pay integration

### Technical Debt Addressed

**Before (Problems):**
- Hardcoded prices in 10+ files (controller, language files, JSON translations)
- No single source of truth
- Price updates required changes across entire codebase
- Inconsistencies (en-US showed £ instead of $)

**After (Solutions):**
- Prices stored in database `prices` table
- Single query to fetch all pricing for a currency
- Currency switcher on pricing page persists user preference
- API endpoint to update currency_code in visitors/users tables
- Automatic synchronization across all locales

### Monitoring Metrics

Track these to inform currency expansion decisions:
- Checkout abandonment rate by country
- Customer requests for specific currencies
- Conversion rate difference between supported currencies
- Support tickets about currency confusion

**Threshold for adding currency:** 50+ customer requests or 10% conversion rate drop in specific country

---

## References

- [ChatGPT Users Statistics (January 2026) – DemandSage](https://www.demandsage.com/chatgpt-statistics/)
- [Tom's Guide: Claude Pro vs ChatGPT Plus](https://www.tomsguide.com/ai/claude-pro-vs-chatgpt-plus-i-tested-both-subscriptions-to-see-which-ones-actually-worth-usd20)
- [AI Pricing Comparison 2025 – AIonX](https://aionx.co/ai-comparisons/ai-pricing-comparison/)
- [TechCrunch: ChatGPT users send 2.5 billion prompts a day](https://techcrunch.com/2025/07/21/chatgpt-users-send-2-5-billion-prompts-a-day/)
- [Stripe Adaptive Pricing Documentation](https://docs.stripe.com/payments/currencies/localize-prices/adaptive-pricing)
- [Stripe Manual Currency Prices](https://docs.stripe.com/payments/checkout/localize-prices/manual-currency-prices)
- [Stripe Multi-Currency Payments Guide](https://stripe.com/resources/more/what-are-multicurrency-payments-how-they-work-and-how-to-use-them)
