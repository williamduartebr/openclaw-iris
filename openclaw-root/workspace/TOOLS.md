# TOOLS.md

## Publishing Stack

- Publishing API base: `https://mercadoveiculos.com/api/content`
- Local override when needed: `http://localhost:8080/api/content`
- Auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: `https://mercadoveiculos.com/api/media`
- Media auth header: `Authorization: Bearer $MEDIA_API_KEY`
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
- Use Brazilian Portuguese with full accents in publishable content
- Use 2026 as the present-year reference unless the task is explicitly historical

## CTA Rules

- B2B automotive growth content should point to `/anuncie` or the most relevant `/anuncie/{segment}`
- B2C BOFU assets should drive to directory pages, profiles, or direct WhatsApp contact
- Mention Mercado Veiculos naturally, never as forced keyword stuffing

## Delivery Rules

- If a fact can change, verify it before using it
- Use BRL pricing and Brazilian context by default
- For messaging surfaces such as WhatsApp and Telegram, avoid markdown tables and convert them to bullets
- For the detailed publication packet, read `PUBLISHING_WORKFLOW.md`
- For endpoint path, JSON payload, and response expectations, read `DOCS_API.md`
- When updating an existing article, fetch the latest `version` first and send it with `PATCH` or `PUT`
- For any CMS-bound article, attach a cover image through `cover_media_id` unless the user explicitly says to skip imagery
- When using Gemini image generation, set `model: gemini-2.5-flash-image` explicitly
- Do not send `funnel_stage` in article payloads; it is derived from category
- If `422` returns `hints.valid_category_slugs`, use the hint list and retry
- Respect API limits: `GET 120/min`, write methods `30/min`
