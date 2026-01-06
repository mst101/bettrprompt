# Mailgun Setup Plan for bettrprompt.ai (Laravel 12 + DigitalOcean)

## Overview

This document describes the **end-to-end setup** of Mailgun for the BettrPrompt.ai Laravel application, covering:

- Mailgun account & domain configuration (EU region)
- DNS records (Cloudflare)
- Laravel 12 integration
- Webhook setup (events + inbound email parsing)
- DigitalOcean server considerations
- Security, testing, and go-live checklist
- Future-proofing and migration safety

Target outcome:
> A production-ready email infrastructure supporting **campaign emails**, **transactional emails**, and **inbound email ingestion for CRM**, with minimal vendor lock-in.

---

## 1. Architecture Summary (Target State)

User Action / Campaign  
→ Laravel App (Mailables / Jobs / Queues)  
→ Mailgun (EU region)  
→ Recipient Inbox  
→ Event Webhooks (opens, clicks, bounces)  
→ Inbound Routes (replies, CRM ingestion)  
→ Laravel Webhook Controllers  
→ PostgreSQL (events, CRM activity)

Key principles:
- Laravel owns all business logic and analytics
- Mailgun is transport + parsing only
- All migration-critical data is stored locally

---

## 2. Mailgun Account Setup

### 2.1 Create Account
1. Sign up at https://www.mailgun.com  
2. Complete email + phone verification  
3. Add billing details

### 2.2 Select Region
- Choose **EU region**
- Region cannot be changed later per domain

---

## 3. Mailgun Domain Configuration

### 3.1 Domain Strategy
Use a subdomain:

```
mg.bettrprompt.ai
```

Benefits:
- Isolated reputation
- Easier migration later
- Cleaner DNS

### 3.2 Create Domain
- Mailgun Dashboard → Domains → Add Domain
- Domain: `mg.bettrprompt.ai`
- Region: EU

---

## 4. DNS Configuration (Cloudflare)

### Required Records

**SPF**
```
TXT  mg  v=spf1 include:mailgun.org ~all
```

**DKIM**
Provided by Mailgun (TXT or CNAME)

**Tracking**
```
CNAME  email.mg  eu.mailgun.org
```

**DMARC (root domain)**
```
TXT _dmarc.bettrprompt.ai
v=DMARC1; p=none; rua=mailto:dmarc@bettrprompt.ai; ruf=mailto:dmarc@bettrprompt.ai; fo=1
```

Cloudflare:
- DNS only (grey cloud)
- TTL: Auto

---

## 5. Laravel 12 Integration

### Environment Variables

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.eu.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@mg.bettrprompt.ai
MAIL_PASSWORD=********
MAIL_ENCRYPTION=tls

MAIL_FROM_ADDRESS=hello@bettrprompt.ai
MAIL_FROM_NAME="BettrPrompt"
```

### Queues
- Always queue mail
- Redis + Supervisor

---

## 6. DigitalOcean Server

- Outbound access to:
  - smtp.eu.mailgun.org:587
  - api.eu.mailgun.net:443
- No MTA required (no Postfix / Sendmail)

---

## 7. Webhooks

### 7.1 Event Webhooks

Endpoint:
```
POST /webhooks/mailgun/events
```

Events:
- delivered
- opened
- clicked
- bounced
- complained
- unsubscribed

Responsibilities:
- Signature verification
- Persist normalized events
- Update suppression & consent

### 7.2 Inbound Email Parsing

Endpoint:
```
POST /webhooks/mailgun/inbound
```

Inbound Route:
```
*@mg.bettrprompt.ai → webhook
```

Responsibilities:
- Parse body, headers, attachments
- Match thread via plus-addressing
- Store as CRM activity

---

## 8. Security

- Verify Mailgun webhook signatures
- Reject old or invalid payloads
- Rate-limit webhook endpoints

---

## 9. Testing Checklist

- SPF / DKIM / DMARC pass
- Open + click events received
- Bounce suppresses recipient
- Inbound replies ingested once
- Attachments handled correctly

---

## 10. Go-Live Checklist

- Domain verified
- SMTP credentials working
- Queues running
- Webhooks live
- Suppression logic tested
- Consent model active

---

## 11. Vendor Lock-In Mitigation

- SMTP transport
- Local analytics + events
- Provider-agnostic schema
- Templates rendered in Laravel

Migration path:
1. Add new provider
2. Dual-send + warmup
3. Switch DNS & credentials
4. Disable Mailgun

---

## 12. Future Enhancements

- Dedicated IP
- Multiple sending domains
- DMARC quarantine/reject
- SES fallback

---

Mailgun is infrastructure — BettrPrompt owns the intelligence.
