# PUBLISHING_WORKFLOW.md

## Runtime

- API base: `https://mercadoveiculos.com/api/content`
- Local override when needed: `http://localhost:8080/api/content`
- Auth: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: `https://mercadoveiculos.com/api/media`
- Media auth: `Authorization: Bearer $MEDIA_API_KEY`
- Format: JSON with Markdown in `body_md`
- Endpoint contract: read `DOCS_API.md` before any delivery attempt

## API Preflight

1. `GET /health` (no auth) when runtime behavior is uncertain.
2. `GET /categories` to map editorial category names to valid `category_slug`.
3. `GET /articles/by-slug/{slug}` (or `GET /articles?slug=...`) to avoid duplicates.

## Content Packet

Every publish-ready article should ship with:

- title
- slug
- excerpt
- body_md
- category_slug
- seo title
- seo description
- author
- image_source
- cover_media_id or trusted cover_image_url
- status set to `draft` unless explicitly told to publish

## Body Rules

- Never use `#` inside the article body
- Use `##` for major sections
- Use `###` for subsections
- Use `### FAQ: ...` inside `## Perguntas frequentes`
- No HTML tags

## Cover Workflow

1. Generate the hero image through the Media API for every CMS-bound article unless the user explicitly waives imagery.
2. Poll the media job until it is complete.
3. When using Gemini, send `model: gemini-2.5-flash-image` explicitly instead of relying on the backend default.
4. Attach `cover_media_id` and set `image_source` to match the real asset provenance: `ai`, `real`, `press`, or `stock`.
5. If the asset is not AI-generated, never omit `image_source`; omission falls back to `ai`.
6. If cover generation fails, do not say the packet is fully publish-ready.

## Funnel Rules

- TOFU: educational, broad, authority-building, low pressure
- MOFU: comparative, cost-aware, problem-solving, directory-aware
- BOFU: decision-focused, trust-building, direct action
- B2B: ROI, lead flow, local visibility, operational leverage, `/anuncie` CTA
- Prompt-level funnel labels are optional guidance for tone and do not replace backend category funnel mapping
- For CMS payloads, do not send `funnel_stage`; it is resolved from the selected category
- Prioritize TOFU and MOFU output for AdSense-friendly organic monetization; publish BOFU intentionally, not as the default mix

## Validation and Limits

- On invalid `category_slug`, use `422` hints (`valid_category_slugs`) and retry with the corrected slug.
- Respect API limits: `GET 120/min`; write methods (`POST`/`PUT`/`PATCH`/`DELETE`) `30/min`.
- When `429` occurs, wait `retry_after_seconds` before retrying.

## B2B CTA Map

- Generic: `/anuncie`
- Workshops: `/anuncie/oficinas`
- Auto parts: `/anuncie/autopecas`
- Car wash: `/anuncie/lava-jato`
- Body shop: `/anuncie/funilaria-pintura`
- Auto electrical: `/anuncie/autoeletrica`
- Tires and alignment: `/anuncie/pneus-alinhamento`
- AC services: `/anuncie/ar-condicionado`

## Final QA Before Publish

- Correct pt-BR accents
- 2026 present-day references where appropriate
- Realistic BRL ranges
- Heading hierarchy correct
- FAQ syntax correct
- FAQ section heading written in pt-BR
- Cover image present and attached to the CMS payload
- `image_source` matches the actual image origin that the frontend should label
- CTA aligned with funnel and audience
- API payload aligned with `DOCS_API.md`
