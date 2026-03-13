# DOCS_API.md

## Purpose

This document defines the article delivery contract for Mercado Veiculos.
Read it before sending any article to the local publishing stack.

## Base URL

- Agent runtime base URL: `http://host.docker.internal:8080/api/content`
- Browser/local host base URL: `http://localhost:8080/api/content`
- Default auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Content type: `application/json`
- Accept header: `application/json`
- Body format: Markdown in `body_md`
- Media API runtime base URL: `http://host.docker.internal:8080/api/media`
- Media API auth header: `Authorization: Bearer $MEDIA_API_KEY`

## Endpoint Contract

Primary routes:

- `GET /articles?search={slug}&per_page=1`: find an article by slug before creating or rewriting
- `POST /articles`: create a new article
- `GET /articles/{id}`: fetch a full article resource, including `body_md` and `version`
- `PATCH /articles/{id}`: update specific fields, requires `version`
- `PUT /articles/{id}`: full replacement, requires `version`

Lifecycle routes:

- `POST /articles/{id}/publish`
- `POST /articles/{id}/unpublish`
- `POST /articles/{id}/schedule`
- `POST /articles/{id}/archive`
- `POST /articles/{id}/restore`

Media routes:

- `POST /media/images/generate`: start cover or inline image generation
- `GET /media/images/{id}`: poll the image job until it is `completed`

## Safe Workflow

1. Search by slug first.
2. Resolve the real CMS category slug before create when the planning label may differ from the backend slug.
3. For a publish-ready article, generate the main cover image before create or before publish, then attach `cover_media_id`.
4. If the article exists, fetch it by `id`.
5. If you only need targeted edits, use `PATCH` with the current `version`.
6. If you need a full rewrite, use `PUT` with the current `version`.
7. Only use publish/schedule/archive actions after final QA.

## Minimal Create Payload

```json
{
  "title": "Quanto custa trocar a bateria do carro em 2026?",
  "excerpt": "Veja a faixa de preco da troca, os sinais de bateria fraca e quando procurar uma autoeletrica.",
  "body_md": "## Resposta rapida\n\n...",
  "category_slug": "autoeletrica-e-eletronica"
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
- `body_md`: include FAQ as `## Perguntas frequentes` and `### FAQ: ...`
- `category_slug`: use a valid category slug when known; if omitted, the backend may default to `geral`
- `category_slugs`: optional extra categories
- `status`: use `draft` by default; valid values are `draft`, `review`, `scheduled`, `published`, `archived`
- `seo_title`: optional, max 70 chars
- `seo_description`: optional, max 160 chars
- `cover_media_id`: preferred for publish-ready articles because it resolves the real hero image from the Media API
- `cover_image_url`: must be absolute `https://` when provided
- `gallery_image_urls`: max 20 entries
- `video_urls`: max 10 entries
- `published_at`: use ISO 8601 when scheduling
- `author`: use a plain text byline

## Image Workflow

Generate the hero image through the Media API when the article is heading to the CMS:

1. `POST /media/images/generate`
2. Poll `GET /media/images/{id}` until `status = completed`
3. Attach the resulting `cover_media_id` on `POST /articles` or `PATCH /articles/{id}`
4. When using Gemini image generation, set `model` explicitly to `gemini-2.5-flash-image` instead of relying on the backend default

Recommended cover payload shape:

```json
{
  "prompt": "Autoeletricista brasileiro testando a bateria de um carro de passeio em oficina realista, foco no multimetro e no cofre do motor, luz natural, visual editorial premium, sem marcas visiveis",
  "provider": "google_gemini",
  "model": "gemini-2.5-flash-image",
  "style": "natural",
  "quality": "high",
  "width": 1600,
  "height": 900,
  "metadata": {
    "usage": "cover_image",
    "section": "editorial"
  }
}
```

## Update Rules

- `PATCH /articles/{id}` is for partial edits and always requires the latest `version`
- `PUT /articles/{id}` is for full replacement and always requires the latest `version`
- Never overwrite an existing article without reading its current `body_md` and `version` first
- If the API returns `409 Conflict`, fetch the article again, merge your changes, and retry with the new `version`

Example patch:

```json
{
  "version": 3,
  "seo_title": "Quanto custa trocar a bateria do carro em 2026? Guia atualizado",
  "seo_description": "Faixa de preco, sinais de desgaste e quando procurar autoeletrica."
}
```

## Response Expectation

Successful create/update responses return a `data` wrapper with the article resource:

```json
{
  "data": {
    "id": 123,
    "slug": "quanto-custa-trocar-bateria-carro-2026",
    "status": "draft",
    "version": 1,
    "url": "/quanto-custa-trocar-bateria-carro-2026"
  }
}
```

Common failures:

- `401`: missing or invalid bearer token
- `404`: article not found
- `409`: version conflict
- `422`: validation failure

## Delivery Standard

- Default to draft creation unless the user explicitly asks to publish now.
- Search by slug before creating to avoid duplicates.
- Do not call an article publish-ready if it has no cover image attached.
- Use `PATCH` for surgical edits and `PUT` for full rewrites.
- Keep `body_md` in Markdown only.
- If prices or regulations are volatile, keep the article in `draft` until factual QA is complete.
- Base every write operation on the real module contract documented under `DocsApi/Content`.
