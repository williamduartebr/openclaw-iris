---
name: mv-tofu-trends-router
description: Organize and route Mercado Veiculos TOFU trend/title-bank work stored under editorial/artigos-2026/tofus-tendencias-2026. Use when the user wants to classify, shortlist, map, schedule, adapt, or select titles from the April-July 2026 trend/discovery bank without mixing them into the main editorial calendar; also use when deciding whether a TOFU trend title fits current Mercado Veiculos categories, should enter now, needs adaptation, or should stay separate from BOFU/B2B/core editorial flows.
---

# MV TOFU Trends Router

Use this skill to manage the **parallel TOFU trends front** for Mercado Veiculos.

## Core rule

Treat `editorial/artigos-2026/tofus-tendencias-2026` as a **separate editorial lane**.

Do not mix this lane automatically into the main calendar. Use it as a curated bank for:
- discovery traffic
- trend-sensitive TOFU
- market-change topics
- technology, behavior, aftermarket, regulation, and sector-shift articles

## Primary workspace paths

- Base folder: `/root/.openclaw/workspace/editorial/artigos-2026/tofus-tendencias-2026`
- Index: `/root/.openclaw/workspace/editorial/artigos-2026/tofus-tendencias-2026/INDEX.md`
- Operational rules: `/root/.openclaw/workspace/editorial/artigos-2026/tofus-tendencias-2026/README-OPERACIONAL.md`
- Mapping guide: `/root/.openclaw/workspace/editorial/artigos-2026/tofus-tendencias-2026/triagem/csv-mapeado-mv/MAPEAMENTO-CATEGORIAS-MV.md`
- Triage summary: `/root/.openclaw/workspace/editorial/artigos-2026/tofus-tendencias-2026/triagem/TRIAGEM-OPERACIONAL.md`

## Read pattern

Read only what is needed:

1. Read `INDEX.md` first when you need orientation.
2. Read `README-OPERACIONAL.md` when deciding how this lane should be used.
3. Read the mapping guide when the user asks how CSV categories fit Mercado Veiculos categories.
4. Read the triage summary when the user asks what enters now, needs adaptation, or should stay out.
5. Read the month shortlist file when the user asks what exists for April, May, June, or later months.

## Month files

- April shortlist: `2026-04/SHORTLIST-ABRIL.md`
- May shortlist: `2026-05/SHORTLIST-MAIO.md`
- June shortlist: `2026-06/SHORTLIST-JUNHO.md`

If July is requested and not yet prepared, create it in the same format before routing titles.

## Decision rules

### Use this lane when the request is about:
- new/trending automotive topics
- TOFU traffic capture from novelty, market change, or technology shifts
- title-bank organization from the imported CSV
- selecting titles without contaminating the core editorial calendar
- deciding whether a title fits MV now, fits with adaptation, or should wait

### Do not use this lane as the default place for:
- practical maintenance pain articles
- BOFU local/service-choice content
- B2B plan/upgrade/commercial funnel content
- CMS taxonomies unless the user explicitly wants taxonomic changes

## Output contract

When routing or recommending titles from this lane:
- state the month
- state whether the item is **entra já**, **entra com adaptação**, or **não prioritário** when relevant
- state the best-fit Mercado Veiculos category
- explain briefly why the title belongs in this separate TOFU trends lane
- make the next action explicit: shortlist, adapt, schedule, draft, or hold

## File creation pattern

When adding a new monthly shortlist, follow the existing structure:
- title: `# Shortlist operacional — <Mês> de 2026`
- include: function, main selection, second layer, editorial recommendation, production order, next operational steps
- keep the tone operational and Mercado Veiculos-specific

## Adaptation rule

If a title sounds too global, abstract, or industry-generic:
- add Brazil context
- add buyer/owner/operator consequence
- add market, repair, cost, trust, or adoption friction
- prefer real reader tension over pure trend theater

When this lane becomes article copy or a production brief:
- block generic opening formulas such as `Muita oficina...`, `Muita empresa...`, `Muitos negócios...`, `Muita autopeça...` and similar category-level filler
- replace them with a concrete conflict, commercial loss, search behavior change, buyer friction, or operational consequence
- treat this as a strong editorial rule, not a stylistic suggestion
- if the CTA is B2B and the requested destination is a specific article or page, point to that exact destination instead of falling back to a vague institutional close

## References

Read these only when needed:
- `references/structure.md` for the folder structure and file roles
- `references/routing-rules.md` for operational routing logic
