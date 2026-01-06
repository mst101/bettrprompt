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
5. Click **Add Domain**

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

### 4.1 Get SMTP Password

1. In Mailgun, go to **Sending → Domain Settings → SMTP credentials**
2. You'll see:
   ```
   Hostname: smtp.eu.mailgun.org
   Port: 587 (or 465 for SSL)
   Username: postmaster@mg.bettrprompt.ai
   Password: [Click "Reset password" to generate]
   ```

3. Click **Reset password** button
4. **Copy the password immediately** - you won't see it again!

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
3. **For each event type**, configure:

**Webhook URL:**
```
https://bettrprompt.ai/api/webhooks/mailgun/events
```

**Events to enable:**
- ✅ **delivered** - Email successfully delivered
- ✅ **opened** - Recipient opened the email
- ✅ **clicked** - Recipient clicked a link
- ✅ **bounced** - Email bounced (hard or soft)
- ✅ **complained** - Recipient marked as spam
- ✅ **unsubscribed** - Recipient unsubscribed

**For each event:**
1. Select event type from dropdown
2. Enter webhook URL: `https://bettrprompt.ai/api/webhooks/mailgun/events`
3. Click **Create webhook**
4. Repeat for all 6 events

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

**Match Expression:**
```
match_recipient(".*@mg.bettrprompt.ai")
```
This matches any email sent to `*@mg.bettrprompt.ai`

**Actions:**
- Select **forward()**
- Enter URL: `https://bettrprompt.ai/api/webhooks/mailgun/inbound`

**Priority:** 0 (highest)

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

## Part 7: Sending Configuration

### 7.1 Configure Sender Identity

1. Go to **Sending → Domain Settings → Sender Identity**
2. Set **Default FROM address**: `hello@bettrprompt.ai`
3. Set **Default FROM name**: `BettrPrompt`

### 7.2 Enable Tracking (Optional)

1. Go to **Sending → Domain Settings → Tracking**

**Click tracking:**
- ✅ Enable - Tracks when users click links
- Choose: HTML only (or HTML and text)

**Open tracking:**
- ✅ Enable - Tracks when users open emails
- Adds invisible pixel to emails

⚠️ **Privacy consideration**: Open tracking uses invisible pixels. Consider user privacy preferences.

### 7.3 Configure Unsubscribe Handling

1. Go to **Sending → Domain Settings → Unsubscribe**
2. Enable **Unsubscribe tracking**
3. When users unsubscribe:
   - Mailgun sends `unsubscribed` event to your webhook
   - Your `MailgunEventService` processes it
   - Update user preferences in your database

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
