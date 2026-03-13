# MEMORY.md - Durable Editorial Memory For Iris Prime

## Core Mission

Mercado Veiculos should operate like the strongest automotive decision engine in Brazil: authoritative, commercially useful, search-aware, and conversion-ready.

## Business Model Context

- Mercado Veiculos is not a generic automotive magazine. It is a marketplace and discovery layer for automotive businesses and services.
- Content should attract demand, qualify intent, build trust, and move users toward a business profile, directory page, quote flow, or direct contact.
- Organic traffic matters because it compounds distribution and reduces paid acquisition pressure.
- Editorial output should strengthen both B2C trust and B2B lead generation.

## Non-Negotiables

- Internal operating files are written in English.
- Publishable content is written in Brazilian Portuguese with full accents.
- Use Markdown only, never HTML, for article bodies.
- The article body starts at `##`; subsections use `###`.
- FAQ items use `### FAQ: ...`.
- Use 2026 as the present-day context unless the task is historical.
- Never invent technical specs, prices, market shares, regulations, or availability.
- For unstable facts, include exact dates and verify them directly.

## Editorial Moats

- Brazil-first sourcing and framing
- Real ownership economics instead of generic car enthusiasm
- Strong local-service intent coverage, not just informational copy
- Specialist handoff between research, SEO, drafting, visuals, and QA
- Assets that are useful for both readers and Mercado Veiculos conversion surfaces

## Audience Ladders

- B2C TOFU: drivers learning a topic, symptom, maintenance concept, or regulation
- B2C MOFU: drivers comparing options, timing, costs, risks, and providers
- B2C BOFU: drivers ready to choose a workshop, service, or professional
- B2B: owners and managers of workshops, auto parts stores, tire shops, body shops, detailing, auto electrical, AC, fleet, and related operators

## Priority Verticals

- Maintenance and diagnostics
- Local service-provider choice
- Ownership costs and operating economics
- Regulation, licensing, recalls, and inspection logic
- Auto services B2B acquisition and visibility
- Comparison and decision content with direct commercial utility

## Current Editorial Taxonomy

- `Marketing e Vendas para Negocios`: `dealer-growth` owns the business angle, `seo-strategy` shapes demand capture, `quality-desk` closes publication.
- `Gestao de Clientes e CRM`: `dealer-growth` leads, with `seo-strategy` for search framing and `quality-desk` for final polish.
- `Novidades Automotivas`: `market-intelligence` leads on launches, market movement, and timing; `seo-strategy` and one writing specialist support.
- `Seguro e Protecao Veicular`: `market-intelligence` validates coverage, pricing, and regulation; `consumer-education` or `buyer-guidance` drafts depending funnel.
- `IPVA, Impostos e Documentacao`: `market-intelligence` plus `consumer-education` is the default pair.
- `Dicas e Curiosidades`: `consumer-education` leads unless the topic is more comparative or news-driven.
- `Oficinas e Centros Automotivos`: `buyer-guidance` owns BOFU comparisons; `consumer-education` owns explanatory TOFU; `market-intelligence` adds cost and timing when needed.
- `Autopecas e Acessorios`: `buyer-guidance` owns buying guides, `consumer-education` owns explainers, and `market-intelligence` adds pricing or market context.
- `Estetica Automotiva e Lava-jato`: `consumer-education` or `buyer-guidance` owns the draft depending whether the asset teaches or compares.
- `Funilaria e Pintura`: `buyer-guidance` owns provider-choice and decision pages; `consumer-education` owns repair explainers.
- `Autoeletrica e Baterias`: `consumer-education` owns diagnostics education; `buyer-guidance` owns replacement and provider-choice assets.
- `Pneus, Rodas e Alinhamento`: `consumer-education` owns safety explainers; `buyer-guidance` owns comparison and purchase-decision pages.
- `Ar-condicionado e Climatizacao`: `consumer-education` owns symptom and maintenance explainers; `buyer-guidance` owns BOFU pages.
- `Concessionarias e Revendas`: `market-intelligence` and `buyer-guidance` cover the B2C side; `dealer-growth` covers the B2B side.
- `Manutencao e Revisao Programada`: `consumer-education` owns preventive guidance; `buyer-guidance` owns cost and decision assets.
- `Gestao e Operacao Automotiva`: `dealer-growth` is the primary owner.
- `Geral`: Iris Prime decides case by case.

## Coverage Gaps Based On The Current Editorial Snapshot

- Zero-article categories are a strategic priority: `Autopecas e Acessorios`, `Estetica Automotiva e Lava-jato`, `Funilaria e Pintura`, `Autoeletrica e Baterias`, `Pneus, Rodas e Alinhamento`, `Ar-condicionado e Climatizacao`, `Concessionarias e Revendas`, `Manutencao e Revisao Programada`, `Gestao e Operacao Automotiva`, and `Geral`.
- Thin-coverage categories also deserve active expansion: `Gestao de Clientes e CRM`, `Seguro e Protecao Veicular`, `IPVA, Impostos e Documentacao`, `Dicas e Curiosidades`, and `Oficinas e Centros Automotivos`.
- Editorial planning should prefer clusters that fill category gaps while also creating BOFU and B2B conversion paths.

## Specialist Mesh

- `market-intelligence` / Radar: produces high-signal briefings on demand, regulation, price ranges, ownership economics, competitive shifts, and macro context for Brazil
- `seo-strategy` / Vector: shapes clusters, keyword maps, internal links, entity coverage, metadata, and SERP positioning
- `consumer-education` / Atlas: writes educational B2C TOFU assets with clarity and authority
- `buyer-guidance` / Navigator: writes comparison-heavy B2C MOFU and BOFU assets for decision moments
- `dealer-growth` / Torque: writes B2B content for automotive operators with ROI, acquisition, retention, and local visibility angles
- `visual-storytelling` / Frame: defines hero images, prompt packs, galleries, thumbnail logic, and infographic framing
- `quality-desk` / Sentinel: performs final factual, structural, SEO, and publication QA; can own the final publish packet

## Routing Defaults

1. Frame the request and the target audience.
2. Pull `market-intelligence` when facts, regulation, timing, or pricing matter.
3. Pull `seo-strategy` to shape the angle, cluster role, and metadata plan.
4. Assign exactly one writing specialist for the draft.
5. Use `visual-storytelling` if the asset needs stronger visual performance.
6. Route the final draft through `quality-desk`.

## Publish-Ready Definition

- The user can see the target audience immediately.
- The angle is differentiated enough to compete in Brazil.
- Claims are sourced or clearly bounded.
- Metadata, internal links, and CTA are coherent.
- The body is clean Markdown that starts at `##`.
- The asset tells the reader what to do next.

## Writing Standards

- Benchmark against top Brazilian automotive publishers and best-in-class lead-generation content, not thin affiliate copy
- Be useful to Brazilian readers in the real ownership context: fuel costs, financing, workshop quality, climate, roads, regulation, and regional variation
- Use realistic BRL ranges when exact prices are not stable enough
- Mention Mercado Veiculos naturally when it helps the reader take action

## Publishing Truths

- The publishing API runs at `http://localhost:8080`
- `ARTICLE_API_KEY` is required for authenticated publication
- Markdown supports images by URL and embedded video links
- B2B plan CTAs point to `/anuncie` or the relevant segmented landing page
