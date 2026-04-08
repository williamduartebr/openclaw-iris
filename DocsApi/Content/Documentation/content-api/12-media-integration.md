# 12 — Content × Media Integration

## Overview

The Content API integrates with the Media API to support AI-generated images in articles. Images generated via `POST /api/media/images/generate` can be referenced by `media_id` when creating articles, and by default are **automatically embedded as Markdown `![alt](url)` inside `body_md`** so they appear in the rendered article.

## How Images Appear in Articles

The article page renders images from **two sources only**:

1. **`featured_image`** (cover) — displayed in a dedicated hero section above the article
2. **`body_md`** — Markdown `![alt](url)` converted to `<img>` tags via CommonMark

The `gallery_image_urls` and `gallery_media` JSON columns are **metadata for traceability**. They are not rendered by the Blade template. To make images visible to readers, they must be embedded in `body_md`.

## `gallery_mode` Field

Controls how `gallery_media` images are handled:

| Value | Behavior | When to use |
|-------|----------|-------------|
| `"inline"` (default) | Resolved URLs are appended as `![alt](url)` to the end of `body_md`. Also stored in `gallery_media` and `gallery_image_urls` for traceability. | Orchestrator wants images in the article without manual Markdown placement |
| `"gallery"` | Stored in `gallery_media` and `gallery_image_urls` only. `body_md` is NOT modified. | Orchestrator already embedded images in `body_md` manually, or wants structured metadata only |

If `gallery_mode` is omitted, it defaults to `"inline"`.

## Architecture

### Storage: Hybrid (media_id + denormalized URL + body embed)

- `cover_media_id` (nullable bigint) on `articles` — references `media_assets.id`
- `featured_image` (string) on `articles` — resolved S3 URL from `cover_media_id`
- `gallery_media` (nullable JSON) on `articles` — structured array with `media_id` and `url` per item
- `gallery_image_urls` (nullable JSON) on `articles` — flat array of URLs
- `content` (longtext) on `articles` — `body_md` with inline `![alt](url)` images (when `gallery_mode: "inline"`)

### Module Boundary

- Content does **not** import any class from `Src\Media`
- `MediaAssetResolver` uses `DB::table('media_assets')` queries
- No foreign key constraint between tables

## API Fields

### Image fields (all optional)

| Field | Type | Description |
|-------|------|-------------|
| `cover_media_id` | integer | References a completed `media_assets.id`. URL auto-resolved to `featured_image`. Takes precedence over `cover_image_url`. |
| `cover_image_url` | URL | Direct HTTPS URL for cover. Ignored if `cover_media_id` is provided. |
| `gallery_media` | array | Array of `{media_id, alt?}` or `{url, alt?}` objects. |
| `gallery_media.*.media_id` | integer | References a completed `media_assets.id`. |
| `gallery_media.*.url` | URL | Direct HTTPS URL. Required if `media_id` is not provided. |
| `gallery_media.*.alt` | string | Alt text for the image. Defaults to `"{title} — imagem {n}"`. |
| `gallery_mode` | string | `"inline"` (default) or `"gallery"`. Controls whether images are embedded in `body_md`. |
| `gallery_image_urls` | array | Legacy: flat array of HTTPS URLs. Prefer `gallery_media`. |

### Precedence Rules

- `cover_media_id` takes precedence over `cover_image_url` when both provided
- `gallery_media` takes precedence over `gallery_image_urls` when both provided
- PATCH `cover_image_url` (without `cover_media_id`) clears `cover_media_id`
- PATCH `cover_media_id: null` clears the media reference but keeps `featured_image`

### Validation

```
cover_media_id:            nullable|integer|min:1
gallery_media:             nullable|array|max:20
gallery_media.*.media_id:  required_without:gallery_media.*.url|integer|min:1
gallery_media.*.url:       required_without:gallery_media.*.media_id|url:https|max:2048
gallery_media.*.alt:       nullable|string|max:255
gallery_mode:              nullable|string|in:inline,gallery
```

For PATCH requests, all fields are prefixed with `sometimes`.

### Response Format

```json
{
  "data": {
    "body_md": "## Introduction\n\n...\n\n![Oil filter](https://s3.../Media/10/uuid.webp)\n\n## Next Section\n\n...",
    "cover_image_url": "https://s3.../Media/7/cover.webp",
    "cover_media_id": 7,
    "gallery_image_urls": ["https://s3.../Media/10/uuid.webp", "https://s3.../Media/11/uuid.webp"],
    "gallery_media": [
      { "media_id": 10, "url": "https://s3.../Media/10/uuid.webp" },
      { "media_id": 11, "url": "https://s3.../Media/11/uuid.webp" }
    ]
  }
}
```

**Note**: When `gallery_mode: "inline"` is used, the `body_md` in the response contains the appended `![alt](url)` images. The `gallery_media` and `gallery_image_urls` fields also contain the same URLs for traceability.

## Orchestrator Patterns

### Pattern A: Orchestrator embeds images in `body_md` (recommended)

The orchestrator generates images, waits for completion, then composes `body_md` with `![alt](final_url)` at precise positions. This gives full control over where each image appears.

```bash
# 1. Generate images
POST /api/media/images/generate
Authorization: Bearer {MEDIA_API_KEY}
Content-Type: application/json

{ "prompt": "Mecânico verificando nível de óleo em motor", "provider": "google_gemini" }
# → { "data": { "id": 7, "status": "pending" } }

# 2. Poll until completed
GET /api/media/images/7
# → { "data": { "id": 7, "status": "completed", "final_url": "https://s3.../Media/7/uuid.webp" } }

# 3. Create article with images pre-embedded in body_md
POST /api/content/articles
Authorization: Bearer {CONTENT_API_KEY}
Content-Type: application/json

{
  "title": "Como Verificar o Nível de Óleo do Motor",
  "body_md": "## Introdução\n\nVerificar o nível de óleo...\n\n![Vareta de óleo sendo verificada](https://s3.../Media/10/uuid.webp)\n\n## Passo a Passo\n\n...\n\n![Nível correto entre MIN e MAX](https://s3.../Media/11/uuid.webp)\n\n## Conclusão\n\n...",
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Vareta de óleo sendo verificada" },
    { "media_id": 11, "alt": "Nível correto entre MIN e MAX" }
  ],
  "gallery_mode": "gallery",
  "seo_title": "Como Verificar Nível de Óleo do Motor",
  "seo_description": "Passo a passo para verificar o nível de óleo do motor."
}
```

**Key**: Use `gallery_mode: "gallery"` because images are already in `body_md`. The `gallery_media` field stores traceability metadata without duplicating images in the body.

### Pattern B: Auto-append (simpler, less precise)

The orchestrator generates images and sends `gallery_media` without embedding in `body_md`. The API auto-appends `![alt](url)` to the end of `body_md`.

```bash
POST /api/content/articles
Authorization: Bearer {CONTENT_API_KEY}
Content-Type: application/json

{
  "title": "Como Verificar o Nível de Óleo do Motor",
  "body_md": "## Introdução\n\nVerificar o nível...\n\n## Passo a Passo\n\n...\n\n## Conclusão\n\n...",
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Vareta de óleo sendo verificada" },
    { "media_id": 11, "alt": "Nível correto entre MIN e MAX" }
  ]
}
```

With the default `gallery_mode: "inline"`, the resulting `body_md` stored in the database will be:

```markdown
## Introdução

Verificar o nível...

## Passo a Passo

...

## Conclusão

...

![Vareta de óleo sendo verificada](https://s3.../Media/10/uuid.webp)

![Nível correto entre MIN e MAX](https://s3.../Media/11/uuid.webp)
```

### Pattern C: Gallery-only metadata (rare)

For cases where images are stored as structured data only (e.g., for a future gallery component or API consumers), without appearing in the article body.

```bash
POST /api/content/articles
Authorization: Bearer {CONTENT_API_KEY}
Content-Type: application/json

{
  "title": "Como Verificar o Nível de Óleo do Motor",
  "body_md": "## Introdução\n\n...",
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10 },
    { "media_id": 11 }
  ],
  "gallery_mode": "gallery"
}
```

Images are stored in `gallery_media` and `gallery_image_urls` but `body_md` is NOT modified. Images will NOT appear in the rendered article.

## Complete End-to-End Example (curl)

```bash
# Step 1: Generate cover image
curl -X POST https://mercadoveiculos.com/api/media/images/generate \
  -H "Authorization: Bearer ${MEDIA_API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Mecânico profissional inspecionando motor de carro em oficina moderna no Brasil",
    "provider": "google_gemini",
    "quality": "high",
    "style": "natural",
    "metadata": { "usage": "cover_image" }
  }'
# → {"data":{"id":7,"status":"pending",...}}

# Step 2: Generate body images
curl -X POST https://mercadoveiculos.com/api/media/images/generate \
  -H "Authorization: Bearer ${MEDIA_API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Close-up de vareta de óleo sendo verificada com óleo limpo visível",
    "provider": "google_gemini",
    "quality": "high",
    "metadata": { "usage": "gallery" }
  }'
# → {"data":{"id":10,"status":"pending",...}}

# Step 3: Poll until completed
curl https://mercadoveiculos.com/api/media/images/7 \
  -H "Authorization: Bearer ${MEDIA_API_KEY}"
# → {"data":{"id":7,"status":"completed","final_url":"https://s3.../Media/7/uuid.webp",...}}

curl https://mercadoveiculos.com/api/media/images/10 \
  -H "Authorization: Bearer ${MEDIA_API_KEY}"
# → {"data":{"id":10,"status":"completed","final_url":"https://s3.../Media/10/uuid.webp",...}}

# Step 4: Create article (Pattern A — images pre-embedded in body_md)
curl -X POST https://mercadoveiculos.com/api/content/articles \
  -H "Authorization: Bearer ${CONTENT_API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Como Verificar o Nível de Óleo do Motor",
    "body_md": "## Introdução\n\nVerificar o nível de óleo...\n\n![Vareta de óleo](https://s3.../Media/10/uuid.webp)\n\n## Conclusão\n\n...",
    "cover_media_id": 7,
    "gallery_media": [{"media_id": 10, "alt": "Vareta de óleo"}],
    "gallery_mode": "gallery",
    "seo_title": "Como Verificar Nível de Óleo do Motor",
    "seo_description": "Passo a passo para verificar o nível de óleo."
  }'
# → {"data":{"id":53,"status":"draft","version":1,...}}

# Step 5: Publish
curl -X POST https://mercadoveiculos.com/api/content/articles/53/publish \
  -H "Authorization: Bearer ${CONTENT_API_KEY}" \
  -H "Content-Type: application/json"
# → {"data":{"id":53,"status":"published","published_at":"2026-03-09T...",...}}
```

## Error Handling

| Scenario | HTTP Status | Error |
|----------|-------------|-------|
| `cover_media_id` not found | 422 | `Invalid media reference: media_id X not found.` |
| `cover_media_id` not completed | 422 | `Invalid media reference: media_id X is not completed (status: pending).` |
| Gallery `media_id` not found | 422 | Same pattern as cover |
| Gallery `media_id` not completed | 422 | Same pattern as cover |
| Invalid `gallery_mode` value | 422 | Validation error: `in:inline,gallery` |

## Backward Compatibility

- All existing API payloads (plain `cover_image_url`, `gallery_image_urls`) continue to work exactly as before
- New fields (`gallery_mode`, `gallery_media.*.alt`) are purely additive and optional
- Default `gallery_mode: "inline"` is a **behavior change** for payloads that use `gallery_media` — images are now embedded in `body_md`. To preserve old behavior, send `gallery_mode: "gallery"`.
- `category_slug` is now optional (defaults to `geral`)
- No changes to the `src/Media/` module
- No changes to Blade templates, RSS feeds, sitemaps, or SEO output

## Files

### New
- `src/Content/Infrastructure/Database/Migrations/2026_03_10_000001_add_media_references_to_articles_table.php`
- `src/Content/Domain/Exceptions/InvalidMediaReferenceException.php`
- `src/Content/Application/Services/MediaAssetResolver.php`
- `tests/Feature/Content/ContentApiMediaIntegrationTest.php`
- `tests/Unit/Content/MediaAssetResolverTest.php`

### Modified
- `src/Content/Domain/Models/Article.php` — `cover_media_id`, `gallery_media` in fillable/casts
- `src/Content/Application/Services/ArticleCrudService.php` — media resolution, `gallery_mode` inline embedding, `category_slug` default
- `src/Content/Application/Requests/Create|Update|PatchArticleRequest.php` — `gallery_mode`, `gallery_media.*.alt` validation
- `src/Content/Application/Resources/ArticleResource.php` — response fields
- `src/Content/Application/Resources/ArticleCollectionResource.php` — response fields
- `src/Content/Application/Controllers/ContentApiController.php` — error handling
