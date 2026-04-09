---
name: mv-regional-refresh-router
description: Organize and route Mercado Veiculos regional editorial work stored under editorial/artigos-2026/regionais-2026. Use when the user wants to territorialize content by city, capital, state, or region; refactor an old article into a regional asset; decide whether a niche plus territory combination should enter now; plan scaling by niche × territory × intent; or keep the regional machine separate from the main editorial calendar.
---

# MV Regional Refresh Router

Use this skill to manage the regional editorial machine for Mercado Veiculos.

## Core rule
Treat `editorial/artigos-2026/regionais-2026` as a separate operational lane for territorial scale.

Do not collapse this lane into the normal calendar by default.

## Primary paths
- Base folder: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026`
- Index: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026/INDEX.md`
- Operational rules: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026/README-OPERACIONAL.md`
- Master matrix: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026/MATRIZ-MESTRA-REGIONAL.md`
- Priority map: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026/MAPA-PRIORIDADES-TERRITORIAIS.md`
- Refactor queue: `/root/.openclaw/workspace/editorial/artigos-2026/regionais-2026/REFATORACOES-PRIORITARIAS.md`

## Read pattern
1. Read `INDEX.md` first for orientation.
2. Read `README-OPERACIONAL.md` when deciding how the regional lane should behave.
3. Read `MATRIZ-MESTRA-REGIONAL.md` when selecting combinations of niche, territory, and intent.
4. Read `REFATORACOES-PRIORITARIAS.md` when the user asks what old articles should become regional first.
5. Read model files only when working inside a specific niche.

## Output contract
When routing or recommending regional work:
- state the niche
- state the territory
- state the intent
- say whether the item is better as a new regional article or a refactored old URL
- explain briefly why the territorial cut changes the value of the page
- make the next action explicit: shortlist, refactor, draft, scale, or hold

## Strong rules
- Do not create fake regionalization by only swapping city or state names.
- Change the practical utility of the article with the territorial cut.
- Adapt openings, examples, decision tension, and CTA to the place and niche.
- Block repetitive metadata formulas across adjacent regionals.
- Block generic opening fillers such as `Muita oficina...`, `Muita empresa...`, and similar category-level flattening.

## Best use cases
- regionalizing old B2B articles
- creating city/state article waves by niche
- planning local-commercial SEO scale
- choosing which territories to attack first
- separating the regional machine from the main calendar

## References
Read these only when needed:
- `references/regional-rules.md`
- `references/refactor-patterns.md`
