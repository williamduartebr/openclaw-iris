# API

This repository uses the Content API contract implemented under `DocsApi/Content`.
OpenClaw runtime docs mirror that contract so agents can safely create and update articles.

## Canonical Source

Runtime behavior is aligned to:

- `DocsApi/Content/Routes/api.php`
- `DocsApi/Content/Application/Requests/CreateArticleRequest.php`
- `DocsApi/Content/Application/Requests/UpdateArticleRequest.php`
- `DocsApi/Content/Application/Requests/PatchArticleRequest.php`
- `DocsApi/Content/Documentation/content-api/04-agent-integration-guide.md`
- `DocsApi/Content/Documentation/content-api/10-api-usage-guide.md`

## Base URL and Auth

- Agent runtime base: `https://mercadoveiculos.com/api/content`
- Auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Required headers: `Accept: application/json`, `Content-Type: application/json`
- Media API runtime base: `https://mercadoveiculos.com/api/media`
- Media API auth header: `Authorization: Bearer $MEDIA_API_KEY`

## Main Endpoints

Readiness and discovery:

- `GET /health` (no auth)
- `GET /categories`
- `GET /articles/by-slug/{slug}`
- `GET /articles?slug={slug}&per_page=1`

Article writes:

- `POST /articles`
- `GET /articles/{id}`
- `PATCH /articles/{id}` (requires `version`)
- `PUT /articles/{id}` (requires `version`)

Lifecycle:

- `POST /articles/{id}/publish`
- `POST /articles/{id}/unpublish`
- `POST /articles/{id}/schedule`
- `POST /articles/{id}/archive`
- `POST /articles/{id}/restore`

## Critical Rules

- Resolve slug mapping with `GET /categories` before creating content.
- Editorial labels may differ from technical slugs (example: `Autoeletrica e Baterias` -> `autoeletrica-e-eletronica`).
- `funnel_stage` is a category property; never send it in article payloads.
- Prompt funnel labels (`TOFU`/`MOFU`/`BOFU`) are optional tone guidance only.
- Default to draft creation unless explicitly asked to publish.
- Fetch current `version` before patch/put.
- Send `image_source` on create/update when imagery exists; valid values are `ai`, `real`, `press`, `stock`.
- If `image_source` is omitted, the backend defaults to `ai`, and `GET` responses return `data.image_source`.
- If `422` includes `hints.valid_category_slugs`, use hints and retry.
- Respect limits: `GET 120/min`, writes `30/min`.

## Safe Workflow

1. `GET /categories`
2. `GET /articles/by-slug/{slug}` (or query route)
3. Generate/attach cover media (`cover_media_id` preferred) and classify it with `image_source`
4. `POST /articles` as `draft`
5. `PATCH` or `PUT` with current `version`
6. `POST /articles/{id}/publish` only after QA

## Error Codes

- `401` token missing/invalid
- `404` article or slug not found
- `409` optimistic-lock conflict
- `422` validation failed (can include category hints)
- `429` rate-limited (`retry_after_seconds` provided)

## Runtime Notes

- Agents read the operational contract from `openclaw-root/workspace/DOCS_API.md`.
- Keep runtime secrets in ignored local config, not in Git.
