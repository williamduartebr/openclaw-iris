# DOCS_API.md

## Purpose

This document defines the current Content API contract used by OpenClaw agents.
Read it before any CMS handoff, create, update, or publish action.

## Base URL

- Agent runtime base URL: [Mercado Veiculos Content API](https://mercadoveiculos.com/api/content)
- Content auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Content headers: `Accept: application/json`, `Content-Type: application/json`
- Media API runtime base URL: [Mercado Veiculos Media API](https://mercadoveiculos.com/api/media)
- Media API auth header: `Authorization: Bearer $MEDIA_API_KEY`
- Body format: Markdown in `body_md`

## Endpoint Contract

Readiness routes:

- `GET /health` (no auth): lightweight Content API health check
- `GET /categories`: list valid categories, slugs, and `funnel_stage`

Discovery routes:

- `GET /articles/by-slug/{slug}`: exact slug lookup, returns 404 when missing
- `GET /articles?slug={slug}&per_page=1`: slug lookup without 404 (0 or 1 item)
- `GET /articles/{id}`: fetch full article resource including `body_md` and `version`

Write routes:

- `POST /articles`: create article
- `PATCH /articles/{id}`: partial update, requires `version`
- `PUT /articles/{id}`: full replacement, requires `version`

Lifecycle routes:

- `POST /articles/{id}/publish`
- `POST /articles/{id}/unpublish`
- `POST /articles/{id}/schedule`
- `POST /articles/{id}/archive`
- `POST /articles/{id}/restore`

Media routes:

- `POST /media/images/generate`: start cover or inline image generation
- `GET /media/images/{id}`: poll image job status

## Safe Workflow

1. Call `GET /categories` to resolve the real `category_slug`.
2. Check duplicates with `GET /articles/by-slug/{slug}` (or `GET /articles?slug=...`).
3. Generate/pin cover image, set the correct `image_source`, and keep `cover_media_id` or trusted `cover_image_url`.
4. Create as `draft` using `POST /articles`.
5. Read by `id`, then revise with `PATCH`/`PUT` using current `version`.
6. Publish only after QA by calling `POST /articles/{id}/publish`.

## Minimal Create Payload

```json
{
  "title": "Quanto custa trocar a bateria do carro em 2026?",
  "excerpt": "Veja a faixa de preco da troca, os sinais de bateria fraca e quando procurar uma autoeletrica.",
  "body_md": "## Resposta rapida\n\n...",
  "category_slug": "autoeletrica-e-eletronica",
  "image_source": "ai"
}
```

## Preferred Editorial Payload

```json
{
  "title": "Quanto custa trocar a bateria do carro em 2026?",
  "subtitle": "Veja a faixa de preco, os sinais de troca e quando procurar uma autoeletrica",
  "slug": "quanto-custa-trocar-bateria-carro-2026",
  "excerpt": "Veja a faixa de preco da troca, os sinais de bateria fraca e quando procurar uma autoeletrica.",
  "body_md": "## Resposta rapida\n\n...",
  "category_slug": "autoeletrica-e-eletronica",
  "status": "draft",
  "author": "Equipe Editorial Mercado Veiculos",
  "seo_title": "Quanto custa trocar a bateria do carro em 2026?",
  "seo_description": "Entenda a faixa de preco, os sinais de desgaste e quando o diagnostico eletrico e necessario.",
  "cover_media_id": 101,
  "image_source": "ai",
  "featured": false
}
```

## Payload Rules

- `title`: plain text, no markdown
- `slug`: optional on create, lowercase, hyphenated, no leading slash
- `excerpt`: preferred even when optional, max 300 chars
- `body_md`: required on create, must start at `##`
- `body_md`: use `##` for major sections and `###` for subsections
- `body_md`: never include HTML tags
- `body_md`: when links appear in prose, use standard Markdown `[label](https://full-url)`
- `body_md`: include FAQ as `## Perguntas frequentes` and `### FAQ: ...`
- `category_slug`: use a slug returned by `GET /categories`
- `category_slugs`: optional extra categories
- `funnel_stage`: never send in article payloads; backend resolves it from category
- `status`: default `draft`; valid values are `draft`, `review`, `scheduled`, `published`, `archived`
- `seo_title`: optional, max 70 chars
- `seo_description`: optional, max 160 chars
- `cover_media_id`: preferred for publish-ready articles
- `image_source`: valid values are `ai`, `real`, `press`, `stock`
- `image_source`: backend defaults to `ai` when omitted; only omit it when AI imagery is truly intended
- `cover_image_url`: must be absolute `https://` when provided
- `gallery_image_urls`: max 20 entries
- `video_urls`: max 10 entries
- `published_at`: use ISO 8601 when scheduling
- `author`: plain text byline

Editorial funnel notes for prompts:

- `TOFU`/`MOFU`/`BOFU` prompt labels are optional tone guidance
- If provided, keep funnel label coherent with selected category
- Category is the source of truth for persisted `funnel_stage`
- Prioritize TOFU and MOFU output for AdSense-friendly monetization
- Use BOFU intentionally for conversion flows (ads are blocked there)

## Category Discovery

`GET /categories` response includes:

- `id`
- `name`
- `slug`
- `description`
- `role`
- `funnel_stage`

Always use this route before creating articles when editorial labels may differ from technical slugs.
Example: `Autoeletrica e Baterias` maps to `autoeletrica-e-eletronica`.

## Response Expectations

Successful create/update responses return a `data` wrapper with the article resource.
Article payloads now include enriched category metadata with funnel stage and image attribution:

```json
{
  "data": {
    "id": 123,
    "slug": "quanto-custa-trocar-bateria-carro-2026",
    "status": "draft",
    "version": 1,
    "image_source": "ai",
    "category": {
      "id": 6,
      "name": "Dicas e Curiosidades",
      "slug": "dicas-e-curiosidades",
      "funnel_stage": "TOFU"
    }
  }
}
```

## Validation and Hints

When `category_slug` is invalid, `422` can include:

```json
{
  "message": "Validation failed.",
  "errors": {
    "category_slug": [
      "The selected category slug is invalid."
    ]
  },
  "hints": {
    "valid_category_slugs": [
      "novidades-automotivas",
      "dicas-e-curiosidades",
      "autoeletrica-e-eletronica"
    ]
  }
}
```

Use `hints.valid_category_slugs` to correct and retry.

## Rate Limiting

- Read endpoints (`GET`): `120/min` per IP
- Write endpoints (`POST`/`PUT`/`PATCH`/`DELETE`): `30/min` per IP

Typical `429` payload:

```json
{
  "message": "Too many requests. Please slow down.",
  "retry_after_seconds": 42,
  "scope": "per-ip",
  "hint": "Wait 42s before retrying. Read-only endpoints (GET) have higher limits (120/min) than write endpoints (POST/PUT/PATCH/DELETE: 30/min)."
}
```

## Error Map

- `401`: missing or invalid token
- `404`: article or slug not found
- `409`: version conflict
- `422`: validation failure (may include hints)
- `429`: rate-limited

## Delivery Standard

- Default to draft creation unless explicitly told to publish.
- Resolve category with `GET /categories` before writing.
- Check duplicates with slug lookup before creating.
- Never label an article publish-ready without a real cover image.
- Set `image_source` intentionally; the frontend uses it for image attribution/caption behavior.
- Use `PATCH` for targeted edits and `PUT` for full rewrites.
- Keep `body_md` Markdown-only and start body sections at `##`.
