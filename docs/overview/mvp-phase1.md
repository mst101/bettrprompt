# Phase 1 MVP — Prompt Optimizer + n8n Workflow

## 🎯 Objective
Build a minimal, self-contained product slice that:

- Converts a user’s **personality type + task description** into an optimized AI prompt.
- Uses **n8n** for orchestration instead of Laravel queues.
- Demonstrates an end-to-end flow: user input → workflow → model output → display.

---

## 🗺️ Roadmap

### 1️⃣ Prompt Optimizer (Core Engine)
- Reuse and refine logic from the **AI Buddy** project. See [ai-buddy-overview](`./ai-buddy-overview.md`)
- **Input:** personality type, trait percentages, task.
- **Output:** structured, optimized prompt.

### 2️⃣ n8n Integration (Learning Component)
**Goal:** learn n8n by making it your *AI orchestration sandbox.*

**Minimal workflow example:**

    Webhook (POST)
      → Code node (clean + validate input)
      → HTTP node (call LLM API)
      → Set node (format output)
      → Respond to Webhook

**From Laravel:**

    <?php
    Http::post(env('N8N_WEBHOOK_URL'), $payload);

### 3️⃣ Persistence
- Store payload and response in PostgreSQL.
- Add a **Run History** table for future learning metrics.

### 4️⃣ Front End
A minimal **Vue + Tailwind** form:

1. Select personality type.
2. Enter task description.
3. View optimized prompt.

---

## 📦 Deliverables
- Working Prompt Optimizer workflow in n8n.
- Laravel route / controller calling the n8n webhook.
- Minimal front-end form.
- Markdown documentation of your setup.

---

## 🚀 Stretch Goals
- Add Redis queue for fallback.
- Implement feedback field (“Did this help?”).
- Begin collecting prompt-performance data.

---

## 💡 Why Start Here
- **Low complexity** — isolates one critical function.
- **High learning value** — hands-on n8n experience.
- **Reusable core** — foundation for later multi-agent and personality-matching features.
