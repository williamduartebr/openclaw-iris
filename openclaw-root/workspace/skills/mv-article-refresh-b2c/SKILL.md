---
name: mv-article-refresh-b2c
description: Rewrite and modernize existing Mercado Veiculos B2C articles for drivers and vehicle owners. Use when refreshing legacy maintenance, symptoms, service-choice, costs, timing, risk, or repair articles while keeping the same slug/URL unless explicitly told otherwise, improving search intent capture, anti-AI usefulness, and practical decision value.
---

# MV Article Refresh B2C

Rewrite old driver-facing articles so they become more useful in real ownership decisions.

## Default outcome

Produce an updated B2C article that:

- targets the reader's real problem, symptom, risk, cost, or timing question
- adds practical consequence and wallet logic
- stays useful after AI gives the obvious answer
- sounds more human and more specific
- preserves the slug unless explicitly asked to change it

## Preferred reframing

Usually anchor the rewrite in:

- symptom plus urgency
- risk plus timing
- decision plus cost
- common mistake plus consequence
- hidden expense of delay or wrong diagnosis

## Workflow

1. Fetch live page and CMS article.
2. Preserve slug/URL by default.
3. Keep or replace cover according to user instruction.
4. Rewrite intro around a concrete driver problem or consequence.
5. Add decision value, practical scenarios, and cost logic.
6. Replace weak title, excerpt, and SEO description.
7. Remove stale inline images if needed.
8. PATCH with latest version.
9. Report the update and any old image URLs that can be deleted.

## Writing rules

- Do not open with generic category explanation.
- Do not repeat the same broad opener pattern across adjacent articles (`muita gente`, `muito motorista`, `muito dono`, etc.). Rotate lead structures deliberately.
- Do not use `Resposta rápida` as default first H2.
- Do not write encyclopedia-style copy.
- Do not leave cost logic implicit when the topic plausibly affects the wallet.
- Do not repeat formulaic excerpt openings across related articles.
- Use variation on purpose: alternate between symptom-first, cost-first, urgency-first, mistake-first, and consequence-first openings so related articles do not read like one template.
- Prefer language of real ownership: bolso, risco, atraso, urgência, diagnóstico, troca, custo evitável.

## Preferred section behavior

Choose headings that express:

- when this becomes urgent
- what this changes in real life
- what mistake costs more later
- what makes the case cheaper or more expensive
- when paying more is justified
- what the reader should do next

## Final report

Tell the user:

- article id
- whether slug stayed the same
- whether the cover stayed or changed
- current version
- what improved in the rewrite
- which old image path(s)/URL(s) can be deleted
