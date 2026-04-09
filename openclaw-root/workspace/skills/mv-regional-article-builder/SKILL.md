---
name: mv-regional-article-builder
description: Create Mercado Veiculos regional or state-level B2B articles for automotive business niches such as guincho, autoelétrica, oficina, autopeças, pneus, funilaria, estética, ar-condicionado, revenda and related services. Use when the user wants a new article focused on a state, macro-region, or broader regional market rather than a single city, especially for SEO traffic capture, local-commercial visibility, and CTA paths that lead to a free Mercado Veiculos profile. Triggers on requests like 'crie um artigo para o nicho X no estado Y', 'faça um artigo regional', 'artigo para região', or 'conteúdo para estado'.
---

# MV Regional Article Builder

## Overview

Create original regional or state-level Mercado Veiculos articles in pt-BR for business owners. Keep the article useful for the reader first, commercially useful for Mercado Veiculos second, and difficult to replace with a shallow AI summary because it carries local friction, decision value, and specific consequence.

Read `references/regional-patterns.md` when choosing geography, niche angle, CTA framing, or next-article suggestions.

## Workflow

1. Define the niche, geography, and commercial goal.
2. Decide whether the article should target **estado** or **região**.
3. Build the angle around local discovery, trust, contact, operational friction, and decision value.
4. Write a unique article architecture instead of reusing a previous section ladder.
5. Insert the required CTA sequence, but adapt the bridge text to the niche and geography.
6. Run a final anti-template review before treating the draft as ready.
7. Suggest next regional articles with strong Google potential.

## Geography decision

Prefer **state/regional** scope when:
- city-level intent would be too narrow for the topic
- the niche has broader service radius or statewide demand
- the user explicitly asks for estado, região, interior, capital + entorno, or macro demand
- the topic works better as a market explainer than as a hyperlocal comparison piece

Prefer a state capital or broader state framing over “Brasil” when local-commercial intent matters. Use “Brasil” only when the article is genuinely national and the user benefit would drop if localized.

## Writing standard

- Write for the business owner, never about the writing process.
- Do not mention AI, AI summaries, anti-AI logic, replaceability, or how the article was engineered.
- Do not open with formulas like `Muita empresa...`, `Muita oficina...`, `Para muita empresa...`, `Veja por que...`, or similar cluster filler.
- Open with a concrete local tension, hidden loss, comparison friction, or operational consequence.
- Avoid portable heading packs that make related articles sound interchangeable.
- Use practical commercial language: visibilidade, contato, orçamento, agenda, confiança, descoberta, indicação, demanda, conversão.
- Keep the body modular. Use only the sections needed for that article.
- Make every article materially different in lead, section order, examples, and payoff.

## CTA contract

Always adapt the CTA to the niche, but keep this two-step logic:

1. **Leitura útil antes de decidir como começar**
   Introduce a complementary read that explains why a simple, well-built presence may already solve part of the problem.

   Use this exact destination link with editorial wording adapted around it:
   `[Perfil gratuito no Mercado Veiculos: quando ele já resolve a base da sua presença comercial](https://mercadoveiculos.com/perfil-gratuito-mercado-veiculos)`

2. **Próximo passo prático**
   End with a niche-aware CTA that explains who should act now, why, and what to do.

   Use this destination:
   `https://mercadoveiculos.com/anuncie`

Do not paste raw URLs into normal body prose when a Markdown link can be used.

## Final review checklist

Before returning the article, verify:
- the lead is specific and not templated
- the geography is woven into the decision logic, not bolted on
- the body does not mention AI or editorial process
- the CTA bridge is dynamic, not institutional filler
- there are no repeated “Muita empresa / muita oficina” patterns
- the article feels useful enough that the reader should not need another search to understand the next move
- next-article suggestions are relevant to the niche and geography

## Output contract

Return:
- suggested title
- excerpt
- SEO title
- SEO description
- full `body_md`
- short note on why the angle fits that geography
- 3 to 5 suggested next articles with strong search potential
