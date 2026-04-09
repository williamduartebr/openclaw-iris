---
name: mv-article-refresh-commercial-funnel
description: Refresh Mercado Veiculos commercial-funnel articles for profiles, free plan, paid plan, visibility products, claiming/creating profiles, and upgrade logic. Use when improving articles that should feed a B2B funnel from TOFU to free-profile hub to MOFU/commercial maturity, while keeping the same slug/URL unless explicitly told otherwise.
---

# MV Article Refresh Commercial Funnel

Use this for commercial-funnel assets tied to Mercado Veiculos products and profile journeys.

## Default outcome

Refresh the article so it fits the ladder:

- pain-first TOFU by segment
- softer free-profile logic
- maturity and upgrade only when appropriate

## Funnel rules

- Do not jump straight into upgrade-heavy framing unless the article is explicitly MOFU.
- For free-profile-first assets, center the value of starting with `1 perfil` and basic presence.
- If upgrade is mentioned in free-plan contexts, keep it subtle and conditional.
- Treat upgrade articles as shared commercial destinations fed by multiple segment TOFUs.

## Rewrite workflow

1. Fetch live page and CMS article.
2. Identify whether the article should behave as TOFU, free-profile hub, or MOFU.
3. Preserve slug by default.
4. Rebuild the angle around the right ladder stage.
5. Remove product-language-first intros when pain-language should lead.
6. Upgrade title, excerpt, and SEO description.
7. Keep or replace cover depending on user instruction.
8. PATCH and report changes.

## Writing rules

- Pain first, product second.
- The owner searches the problem, not the plan name.
- Make the article still useful after AI summarizes the obvious answer.
- Avoid repetitive excerpt leads.
- Avoid generic `muita empresa...` openings.
- Avoid repeating the same broad opener structure across related funnel articles; rotate between pain, invisibility, friction, dependency, and maturity framings.
- Avoid making the free-plan article sound like a disguised upsell.

## Typical article roles

### TOFU by segment

Focus on invisibility, weak discovery, lost calls, poor local presence, dependence on indication.

### Free-profile hub

Focus on why basic organized presence with `1 perfil` already solves part of the problem.

### MOFU upgrade

Focus on when the free setup stops being enough and when stronger visibility or capacity starts making economic sense.

## Final report

Tell the user:

- article id
- ladder role after refresh
- whether slug stayed the same
- whether the cover stayed or changed
- current version
- what changed in the commercial logic
- any old image path(s)/URL(s) safe to delete
