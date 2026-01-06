# Mailgun Configuration Guide - Step by Step

This guide walks you through setting up Mailgun for BettrPrompt.ai from scratch.

---

## Part 1: Account Setup

### 1.1 Create Mailgun Account

1. Go to https://www.mailgun.com
2. Click **Sign Up** (top right)
3. Fill in your details:
   - Email address
   - Password
   - Company name (optional)
4. Click **Create Account**
5. **Verify your email** - Check inbox and click verification link
6. **Add payment method** - Required even for free tier (won't be charged until you exceed limits)

### 1.2 Select Region

**CRITICAL**: Region cannot be changed later per domain!

1. In the Mailgun dashboard, when creating your first domain, you'll see **Region** dropdown
2. Select **EU** (Europe)
3. This ensures:
   - GDPR compliance
   - Lower latency for European users
   - EU-based data storage

---

## Part 2: Domain Configuration

### 2.1 Add Your Domain

1. Navigate to **Sending → Domains** (left sidebar)
2. Click **Add New Domain** button
3. Enter domain name: `mg.bettrprompt.ai`
   - ✅ Use subdomain (not `bettrprompt.ai`)
   - ✅ Isolates email reputation
   - ✅ Easier migration later
4. Select **EU** region
5. **IP assignment option**: Select **Shared IP** (only option for most plans)
6. **Advanced settings**:
   - ✅ Choose **"Use automatic sender security"** (recommended)
   - ❌ Don't choose "Self-manage DKIM keys" unless you have specific compliance requirements

   **Why automatic?**
   - Mailgun generates and manages DKIM keys for you
   - Automatic key rotation for better security
   - Simpler setup with less room for error
   - Industry best practice

7. Click **Add Domain**

### 2.2 Get DNS Records

After adding the domain, Mailgun will show you DNS records to configure. You'll see:

**TXT Records** (SPF & DKIM):
- 2-3 TXT records for DKIM verification
- 1 TXT record for SPF

**CNAME Record** (Tracking):
- 1 CNAME record for click/open tracking

**Copy these values** - you'll need them for Cloudflare setup (next section).

---

## Part 3: DNS Configuration in Cloudflare

### 3.1 Add SPF Record

1. Log in to **Cloudflare**
2. Select your **bettrprompt.ai** domain
3. Go to **DNS** → **Records**
4. Click **Add record**

**Settings:**
```
Type:    TXT
Name:    mg
Content: v=spf1 include:mailgun.org ~all
TTL:     Auto
Proxy:   DNS only (grey cloud icon)
```

5. Click **Save**

### 3.2 Add DKIM Records

Mailgun provides 2 DKIM records. For each one:

1. Click **Add record**

**First DKIM Record:**
```
Type:    TXT
Name:    [exact value from Mailgun - usually starts with "k1._domainkey.mg"]
Content: [exact value from Mailgun - long string starting with "k=rsa; p=..."]
TTL:     Auto
Proxy:   DNS only (grey cloud)
```

**Second DKIM Record** (if provided):
```
Type:    TXT
Name:    [exact value from Mailgun]
Content: [exact value from Mailgun]
TTL:     Auto
Proxy:   DNS only (grey cloud)
```

2. Click **Save**

⚠️ **Important**: Copy values exactly as shown in Mailgun - don't add quotes or modify them.

### 3.3 Add Tracking CNAME

1. Click **Add record**

**Settings:**
```
Type:    CNAME
Name:    email.mg
Target:  eu.mailgun.org
TTL:     Auto
Proxy:   DNS only (grey cloud)
```

2. Click **Save**

### 3.4 Add DMARC Record (Optional but Recommended)

This goes on your **root domain** (not the subdomain):

1. Click **Add record**

**Settings:**
```
Type:    TXT
Name:    _dmarc
Content: v=DMARC1; p=none; rua=mailto:dmarc@bettrprompt.ai; ruf=mailto:dmarc@bettrprompt.ai; fo=1
TTL:     Auto
Proxy:   DNS only (grey cloud)
```

2. Click **Save**

**What this does:**
- `p=none` - Monitor mode (doesn't reject emails)
- `rua` - Aggregate reports sent to your email
- `ruf` - Forensic reports sent to your email
- `fo=1` - Report if any authentication fails

Later, you can change `p=none` to `p=quarantine` or `p=reject` once you're confident.

### 3.5 Verify DNS in Mailgun

1. Return to **Mailgun Dashboard**
2. Go to **Sending → Domains → mg.bettrprompt.ai**
3. Scroll to **DNS Records** section
4. Click **Verify DNS Settings**

⏰ **Wait time**: DNS propagation can take 5-60 minutes. If verification fails:
- Wait 10 minutes and try again
- Check records in Cloudflare match exactly
- Use `dig` to verify: `dig TXT mg.bettrprompt.ai`

✅ **Success indicators:**
- All records show green checkmarks
- Domain status shows **Verified**

---

## Part 4: SMTP Credentials

### 4.1 Create SMTP User and Get Password

1. In Mailgun, go to **Sending → Domain Settings → SMTP credentials**
2. Click **"Add new SMTP user"** button
3. **Login field**: Enter `postmaster`
   - Mailgun will append `@mg.bettrprompt.ai` automatically
   - This creates: `postmaster@mg.bettrprompt.ai`
   - Standard convention for primary SMTP account

   **Note:** The SMTP username is just for authentication, not the FROM address users see. Your emails will still appear from `hello@bettrprompt.ai` (set in Laravel).

4. Click **Create**
5. Click **Reset password** to generate the password
6. **Copy the password immediately** - you won't see it again!

You should now see:
```
Hostname: smtp.eu.mailgun.org
Port: 587 (or 465 for SSL)
Username: postmaster@mg.bettrprompt.ai
Password: [your-generated-password]
```

### 4.2 Save Credentials

Add these to your production `.env` file:

```bash
MAIL_MAILER=smtp
MAIL_HOST=smtp.eu.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.bettrprompt.ai
MAIL_PASSWORD=<paste-password-here>
MAIL_ENCRYPTION=tls

MAILGUN_DOMAIN=mg.bettrprompt.ai
MAILGUN_SECRET=<paste-password-here>  # Same as MAIL_PASSWORD
MAILGUN_ENDPOINT=api.eu.mailgun.net
```

---

## Part 5: Webhook Configuration

### 5.1 Get Webhook Signing Key

1. Go to **Sending → Webhooks**
2. Scroll to **HTTP webhook signing key** section
3. Click **Show** to reveal the key
4. **Copy the signing key**

Add to your `.env`:
```bash
MAILGUN_WEBHOOK_SIGNING_KEY=<paste-signing-key-here>
```

### 5.2 Configure Event Webhooks

1. Still in **Sending → Webhooks**
2. Click **Add webhook** button

**Webhook URL for all events:**
```
https://bettrprompt.ai/api/webhooks/mailgun/events
```

**Events to enable** (as they appear in Mailgun dashboard):

Create a webhook for each of these **7 events** (skip "Accepted"):

1. ✅ **Clicks** - User clicked a link in your email
2. ✅ **SPAM complaints** - User marked email as spam
3. ✅ **Delivered messages** - Email successfully delivered to inbox
4. ✅ **Opens** - User opened the email
5. ✅ **Permanent failure** - Hard bounce (invalid email, doesn't exist)
6. ✅ **Temporary failure** - Soft bounce (mailbox full, server temporarily down)
7. ✅ **Unsubscribes** - User clicked unsubscribe link

❌ **Skip "Accepted"** - Not useful (just confirms Mailgun received it from Laravel)

**For each of the 7 events:**
1. Select event type from dropdown
2. Enter webhook URL: `https://bettrprompt.ai/api/webhooks/mailgun/events`
3. Click **Create webhook**
4. Repeat for all 7 events

**All webhooks use the same URL** - your Laravel code automatically handles different event types.

**Test the webhook:**
1. Click **Test webhook** next to any configured webhook
2. Mailgun will send a test payload
3. Check your Laravel logs: `tail -f storage/logs/laravel.log`
4. You should see: `Mailgun event webhook received`

---

## Part 6: Inbound Email Configuration

### 6.1 Create Inbound Route

1. Go to **Receiving → Routes** (left sidebar)
2. Click **Create Route** button

**Route Configuration:**

**Expression type:** Match Recipient

**Recipient field:** (just enter the pattern, not the function)
```
.*@mg.bettrprompt.ai
```

**What this means:**
- `.*` = any characters (anything before @)
- `@mg.bettrprompt.ai` = your domain
- Matches: `hello@mg.bettrprompt.ai`, `reply+123@mg.bettrprompt.ai`, etc.

**Note:** Since you selected "Match Recipient" as the expression type, Mailgun automatically wraps this in `match_recipient()`. Just enter the pattern itself.

**Actions:**
1. ✅ Add **Forward** action
   - URL: `https://bettrprompt.ai/api/webhooks/mailgun/inbound`

2. ❌ **Don't add "Store and notify"**
   - Not needed - your webhook receives the complete email immediately
   - You're storing everything in your database
   - Mailgun's 3-day storage limit is restrictive

**Priority:** 0 (highest)

**Stop toggle:** ✅ **Enable "Stop"**
- Prevents other routes from evaluating after this one matches
- Since `.*@mg.bettrprompt.ai` matches everything, stop processing here
- Prevents duplicate processing if you add more routes later

**Description:** Forward all inbound emails to Laravel webhook

3. Click **Create Route**

### 6.2 Test Inbound Email

**Method 1: Send test email**
1. Send an email from your personal email to: `test@mg.bettrprompt.ai`
2. Check Laravel logs for: `Mailgun inbound webhook received`
3. Check database: `SELECT * FROM inbound_emails ORDER BY id DESC LIMIT 1;`

**Method 2: Use Mailgun test**
1. In **Receiving → Routes**, click your route
2. Click **Test Route**
3. Send a test payload
4. Verify it appears in your `inbound_emails` table

---

## Part 7: Tracking & Sending Settings (Optional)

**Note:** This section is optional. Your email system works without these settings. Sender identity is configured in Laravel (`.env`), not in Mailgun.

### 7.1 Skip "Sending Keys" ⚠️

Under **Sending → Domain Settings**, you'll see tabs including **"Sending keys"**.

**Don't create a sending key** - these are for Mailgun's HTTP API only. Since you're using SMTP (configured in Part 4), you don't need API keys.

**Your sender identity** (`hello@bettrprompt.ai`) is configured in Laravel's `.env` file:
```bash
MAIL_FROM_ADDRESS="hello@bettrprompt.ai"
MAIL_FROM_NAME="BettrPrompt"
```

### 7.2 Configure Tracking (Optional)

1. Go to **Sending → Domain Settings → Settings** tab
2. Scroll to **Tracking** section

**Tracking hostname:**
- Should show: `email.mg` or `email.mg.bettrprompt.ai`
- Must match the CNAME record you created in Cloudflare
- If it just shows "email", try typing: `email.mg`

**Click tracking:**
- ✅ Turn **ON** - Tracks when users click links in your emails
- Choose: HTML only (recommended)

**Open tracking:**
- ✅ Turn **ON** - Tracks when users open emails
- ❌ **Don't enable "Place open tracking pixel at top"** - Keep at bottom (default)

**Why bottom placement?**
- Your transactional emails are short (won't be truncated)
- Better email client compatibility
- Industry standard
- Gmail truncates at 102KB (your emails will be much smaller)

**Only use top placement if:**
- Sending long newsletter-style emails (thousands of words)
- Emails approaching 102KB size limit

### 7.3 Configure Unsubscribe Settings (Optional)

1. Still in **Settings** tab, scroll to **Unsubscribes** section
2. ✅ Turn **ON** - Enables unsubscribe tracking

**Customize templates (optional):**
- Click "Customize unsubscribe templates" if you want to brand them
- **For now, keep the defaults** - you can polish later
- Default templates work fine and are clear

**When these are used:**
- Marketing/bulk emails with `List-Unsubscribe` header
- Not used for transactional emails (welcome, password reset)

**When users unsubscribe:**
- Mailgun sends `unsubscribed` event to your webhook
- Your `MailgunEventService` processes it
- Update user preferences in your database (see TODO in code)

---

## Part 8: Testing & Verification

### 8.1 Send Test Email from Laravel

SSH into your production server:

```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;

Mail::raw('This is a test email from BettrPrompt via Mailgun!', function ($message) {
    $message->to('your-email@example.com')
            ->subject('Mailgun Integration Test');
});
```

### 8.2 Verify Email Delivery

**Check your inbox:**
- ✅ Email arrived
- ✅ From: `BettrPrompt <hello@bettrprompt.ai>`
- ✅ Not in spam folder

**Check email headers:**
1. Open the email
2. View source/headers (varies by client)
3. Look for:
   ```
   SPF: PASS
   DKIM: PASS
   DMARC: PASS
   ```

### 8.3 Verify Event Webhooks

**Test opened event:**
1. Open the test email
2. Wait 30 seconds
3. Check your database:
   ```sql
   SELECT * FROM email_events WHERE event_type = 'opened' ORDER BY id DESC LIMIT 1;
   ```

**Test clicked event:**
1. Add a link to your test email
2. Click the link
3. Check database for `clicked` event

### 8.4 Verify Inbound Routing

1. Reply to the test email
2. Check database:
   ```sql
   SELECT * FROM inbound_emails ORDER BY id DESC LIMIT 1;
   ```
3. Verify `from`, `to`, `subject`, and `body_plain` fields are populated

---

## Part 9: Production Checklist

Before going live with customer emails:

### Security
- ✅ Webhook signing key configured and verified
- ✅ HTTPS enabled on webhook endpoints
- ✅ DNS records verified (SPF, DKIM, DMARC)
- ✅ Test signature verification with invalid key (should reject)

### Functionality
- ✅ Test email sends successfully
- ✅ Test email has correct from address/name
- ✅ Email headers show SPF/DKIM/DMARC passing
- ✅ Events received (delivered, opened, clicked)
- ✅ Inbound emails processed and stored
- ✅ User matching works (by email and plus-addressing)

### Monitoring
- ✅ Laravel logs configured: `tail -f storage/logs/laravel.log`
- ✅ Set up log alerts for webhook failures
- ✅ Monitor bounce rates in Mailgun dashboard
- ✅ Set up DMARC report monitoring

### Compliance
- ✅ Unsubscribe links in marketing emails
- ✅ Privacy policy mentions email collection
- ✅ GDPR-compliant (using EU region)

---

## Part 10: Ongoing Management

### Monitor Mailgun Dashboard

**Daily:**
- Check **Sending → Analytics** for send volume and delivery rates
- Review bounce rates (should be < 5%)
- Check complaint rate (should be < 0.1%)

**Weekly:**
- Review DMARC reports (if configured)
- Check suppression list for bounces/complaints
- Monitor webhook delivery success rate

### Handle Bounces

**Hard bounces** (permanent failures):
```sql
SELECT * FROM email_events
WHERE event_type = 'bounced'
AND JSON_EXTRACT(payload, '$.event-data.severity') = 'permanent'
ORDER BY created_at DESC;
```

Action: Mark email as invalid, stop sending to these addresses

**Soft bounces** (temporary failures):
- Mailgun retries automatically
- After 72 hours, converts to permanent if still failing

### Handle Complaints

```sql
SELECT * FROM email_events
WHERE event_type = 'complained'
ORDER BY created_at DESC;
```

Action: Immediately suppress these addresses, review sending practices

### Suppression List Management

1. Go to **Sending → Suppressions**
2. Review:
   - **Bounces** - Addresses that bounced
   - **Unsubscribes** - Users who unsubscribed
   - **Complaints** - Spam reports

Remove addresses only if you're certain they should receive emails again.

---

## Troubleshooting

### DNS Records Not Verifying

**Problem:** DNS records show as unverified after 1 hour

**Solutions:**
1. Check Cloudflare records match Mailgun exactly
2. Ensure Cloudflare proxy is **OFF** (grey cloud)
3. Use `dig` to verify:
   ```bash
   dig TXT mg.bettrprompt.ai
   dig TXT k1._domainkey.mg.bettrprompt.ai
   ```
4. Wait 24 hours for full propagation

### Webhooks Not Firing

**Problem:** Events sent but not appearing in database

**Check:**
1. Laravel logs: `tail -f storage/logs/laravel.log`
2. Webhook signing key is correct in `.env`
3. HTTPS certificate is valid on production server
4. Test webhook manually from Mailgun dashboard
5. Check firewall allows Mailgun IPs

### Emails Going to Spam

**Problem:** Emails delivered but land in spam folder

**Solutions:**
1. Verify SPF/DKIM/DMARC all pass (check email headers)
2. Warm up your domain (start with low volume)
3. Avoid spam trigger words
4. Include unsubscribe link
5. Request users whitelist your domain
6. Check Mailgun reputation score

### Inbound Emails Not Processing

**Problem:** Inbound emails not appearing in database

**Check:**
1. Route expression matches incoming email address
2. Webhook URL is correct and accessible
3. Check Laravel logs for errors
4. Test route in Mailgun dashboard
5. Verify MX records point to Mailgun (if using Mailgun for receiving)

---

## Support Resources

- **Mailgun Docs**: https://documentation.mailgun.com
- **Mailgun Support**: support@mailgun.com
- **Status Page**: https://status.mailgun.com
- **Community Forum**: https://groups.google.com/forum/#!forum/mailgun

---

## Summary

You've now configured:
✅ Mailgun account with EU region
✅ Domain `mg.bettrprompt.ai` with verified DNS
✅ SMTP credentials for sending emails
✅ Event webhooks for tracking
✅ Inbound routing for receiving emails
✅ Security via webhook signing

Your BettrPrompt email infrastructure is ready for production! 🎉
