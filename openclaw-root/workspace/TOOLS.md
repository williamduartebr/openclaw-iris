# TOOLS.md

## Publishing Stack

- Publishing API base: [Mercado Veiculos Content API](https://mercadoveiculos.com/api/content)
- Auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: [Mercado Veiculos Media API](https://mercadoveiculos.com/api/media)
- Media auth header: `Authorization: Bearer $MEDIA_API_KEY`
- Local credential source: project-root `.env.openclaw`
- Keep API keys in `.env.openclaw` only; never paste raw secrets into tracked docs, prompts, or article output
- Primary content format: JSON with Markdown in `body_md`
- Delivery contract: `DOCS_API.md`
- If `CONTENT_API_KEY` is not exported, check the local Laravel project config for `services.content_api.key`
- If `MEDIA_API_KEY` is not exported, check the local Laravel project config for `services.media_api.key`
- Use `GET /categories` to resolve the exact `category_slug` before create/update flows
- Use `GET /articles/by-slug/{slug}` or `GET /articles?slug={slug}` for duplicate checks

## Formatting Rules

- The CMS handles the article title, so `body_md` must start at `##`
- Use `##` for primary sections and `###` for subsections
- Use `## Perguntas frequentes` for the FAQ section heading
- FAQ entries must use `### FAQ: ...`
- Never emit HTML for article bodies
- All article links must use standard Markdown link syntax: `[label](https://full-url)`
- Apply the same Markdown-link rule to `body_md`, editorial prompts, refresh instructions, CTA examples, and operational guidance that may be copied into article work
- Never use inverted or nonstandard link forms such as `()[]`, bare URLs when a labeled link is appropriate, or HTML `<a>` tags in article bodies
- When preserving or adding links inside article copy, prefer descriptive labels and keep the full canonical URL inside `()`
- Use Markdown `**bold**` strategically for scanability: usually 1 to 3 highlights per text block when emphasis truly helps
- Prioritize bold on key terms, benefits, problems, solutions, local modifiers, short answers, price ranges, and decision phrases; never bold whole paragraphs or repeat the same keyword mechanically
- Use Brazilian Portuguese with full accents in publishable content
- Use 2026 as the present-year reference unless the task is explicitly historical

## CTA Rules

- B2B automotive growth content should point to `/anuncie` or the most relevant `/anuncie/{segment}`
- B2C BOFU assets should drive to directory pages, profiles, or direct WhatsApp contact
- For driver-facing discovery and provider-choice articles, default the CTA block to [Buscar serviços no Mercado Veiculos](https://mercadoveiculos.com/busca)
- Make the CTA promise the next action clearly; avoid generic lines such as "Mercado Veiculos entra nessa decisão"
- B2B acquisition articles can invite businesses to create or claim a Mercado Veiculos profile, including free entry points when that is the right first conversion
- Mention Mercado Veiculos naturally, never as forced keyword stuffing

## Tool Link Rules

- Tool links are chosen by editorial function, not restricted to TOFU or MOFU labels
- TOFU is the natural fit for triage, calculation, simulation, and practical education
- MOFU is a strong fit for comparison, scenario testing, and reducing decision error before service or purchase
- BOFU can use a tool only when it prepares the reader to convert; never let a tool compete with the primary CTA to search, compare providers, view a profile, or contact now
- B2B and plan-acquisition content can mention tools as ecosystem proof or value support, but the primary CTA still points to `/anuncie` or the correct commercial landing page
- Use production URLs only: [Ferramentas Mercado Veiculos](https://mercadoveiculos.com/ferramentas) and the live tool slugs defined in the Mercado Veiculos tools routes
- Do not guess, translate, or hyphenate tool slugs from memory; use the canonical route

## Delivery Rules

- If a fact can change, verify it before using it
- Use BRL pricing and Brazilian context by default
- For messaging surfaces such as WhatsApp and Telegram, avoid markdown tables and convert them to bullets
- For the detailed publication packet, read `PUBLISHING_WORKFLOW.md`
- For endpoint path, JSON payload, and response expectations, read `DOCS_API.md`
- When updating an existing article, fetch the latest `version` first and send it with `PATCH` or `PUT`
- For any CMS-bound article, attach a cover image through `cover_media_id` unless the user explicitly says to skip imagery
- Send `image_source` when creating or updating an article with imagery; valid values are `ai`, `real`, `press`, `stock`
- Do not rely on omission for non-AI assets; if `image_source` is omitted, the backend defaults to `ai`
- When using Gemini image generation, set `model: gemini-2.5-flash-image` explicitly
- Do not send `funnel_stage` in article payloads; it is derived from category
- If `422` returns `hints.valid_category_slugs`, use the hint list and retry
- Respect API limits: `GET 120/min`, write methods `30/min`
