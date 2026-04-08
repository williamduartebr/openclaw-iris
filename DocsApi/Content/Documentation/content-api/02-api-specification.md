# 02 — API Specification

## Purpose

Complete REST API specification for the agent-facing Content API. All endpoints, request/response schemas, status codes, pagination, filtering, and error handling are defined here.

---

## Base URL

```
/api/content/articles
```

## Authentication

All endpoints require a Bearer token in the `Authorization` header.

```
Authorization: Bearer {CONTENT_API_KEY}
```

The token is validated against `config('services.content_api.key')`. An invalid or missing token returns HTTP 401.

---

## Common Response Envelope

### Success (single resource)

```json
{
  "data": { ... }
}
```

### Success (collection)

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "/api/content/articles?page=1",
    "last": "/api/content/articles?page=5",
    "prev": null,
    "next": "/api/content/articles?page=2"
  }
}
```

### Error

```json
{
  "message": "Human-readable error summary.",
  "errors": {
    "field_name": [
      "Specific validation error message."
    ]
  }
}
```

For non-validation errors (401, 403, 404, 409, 500):

```json
{
  "message": "Error description."
}
```

---

## Article Resource Schema

The full article object returned in responses:

```json
{
  "id": 42,
  "title": "How to Change Your Car Oil",
  "subtitle": "A step-by-step guide for beginners",
  "slug": "how-to-change-your-car-oil",
  "excerpt": "Learn the essential steps to change your car oil safely at home.",
  "body_md": "## Introduction\n\nChanging your car oil is one of the most important...\n\n![Mechanic draining oil](https://s3.../Media/10/image.webp)",
  "status": "published",
  "featured": false,
  "cover_image_url": "https://s3.../Media/7/cover.webp",
  "image_source": "ai",
  "cover_media_id": 7,
  "gallery_image_urls": [
    "https://s3.../Media/10/image.webp",
    "https://s3.../Media/11/image.webp"
  ],
  "gallery_media": [
    { "media_id": 10, "url": "https://s3.../Media/10/image.webp" },
    { "media_id": 11, "url": "https://s3.../Media/11/image.webp" }
  ],
  "video_urls": [
    "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
  ],
  "category": {
    "id": 3,
    "name": "Dicas",
    "slug": "dicas"
  },
  "categories": [
    { "id": 3, "name": "Dicas", "slug": "dicas" }
  ],
  "author": "Equipe Editorial",
  "reading_time": 7,
  "seo_title": "How to Change Car Oil — Complete Guide 2026",
  "seo_description": "Step-by-step guide to changing your car oil at home. Save money and keep your engine healthy.",
  "canonical_url": null,
  "published_at": "2026-03-15T10:30:00Z",
  "created_at": "2026-03-14T08:00:00Z",
  "updated_at": "2026-03-15T10:30:00Z",
  "deleted_at": null,
  "version": 3,
  "url": "/how-to-change-your-car-oil"
}
```

**Note on `image_source`**: Controls the image credit caption rendered on the article page. Accepted values: `ai` (default), `real`, `press`, `stock`. The front-end renders a `<figcaption>` on the cover and inline image captions based on this value:

| Value | Rendered caption |
|-------|-----------------|
| `ai` | Imagem ilustrativa gerada por IA. |
| `real` | Imagem: acervo Mercado Veículos. |
| `press` | Imagem: divulgação. |
| `stock` | Imagem: banco de imagens. |

**Note on images**: When `gallery_media` is sent with `gallery_mode: "inline"` (default), the resolved URLs are **automatically embedded as Markdown `![alt](url)` at the end of `body_md`**. The images appear both inline in `body_md` and in the structured `gallery_media`/`gallery_image_urls` fields for traceability. See [12-media-integration.md](./12-media-integration.md) for details.

### Collection item schema (lightweight)

The list endpoint returns a reduced representation without `body_md`, `gallery_image_urls`, `gallery_media`, or `video_urls`:

```json
{
  "id": 42,
  "title": "How to Change Your Car Oil",
  "subtitle": "A step-by-step guide for beginners",
  "slug": "how-to-change-your-car-oil",
  "excerpt": "Learn the essential steps to change your car oil safely at home.",
  "status": "published",
  "featured": false,
  "cover_image_url": "https://s3.../Media/7/cover.webp",
  "image_source": "ai",
  "cover_media_id": 7,
  "category": {
    "id": 3,
    "name": "Dicas",
    "slug": "dicas"
  },
  "author": "Equipe Editorial",
  "reading_time": 7,
  "published_at": "2026-03-15T10:30:00Z",
  "created_at": "2026-03-14T08:00:00Z",
  "updated_at": "2026-03-15T10:30:00Z",
  "version": 3,
  "url": "/how-to-change-your-car-oil"
}
```

---

## Endpoints

### 1. List Articles

```
GET /api/content/articles
```

**Purpose**: Retrieve a paginated, filterable, sortable list of articles.

**Query Parameters**:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number |
| `per_page` | integer | 15 | Items per page (max 100) |
| `sort` | string | `-created_at` | Sort field. Prefix with `-` for descending |
| `status` | string | — | Filter by status. Comma-separated for multiple: `draft,published` |
| `category` | string | — | Filter by category slug |
| `featured` | boolean | — | Filter by featured flag |
| `author` | string | — | Filter by author name (partial match) |
| `search` | string | — | Search in title and excerpt |
| `created_after` | ISO 8601 | — | Created at or after this date |
| `created_before` | ISO 8601 | — | Created at or before this date |
| `published_after` | ISO 8601 | — | Published at or after this date |
| `published_before` | ISO 8601 | — | Published at or before this date |
| `trashed` | string | — | `only` (only trashed), `with` (include trashed). Default: excluded |

**Allowed sort fields**: `created_at`, `updated_at`, `published_at`, `title`

**Response**: `200 OK` with paginated collection.

**Example request**:

```
GET /api/content/articles?status=published&category=dicas&sort=-published_at&per_page=10
```

**Example response**:

```json
{
  "data": [
    {
      "id": 42,
      "title": "How to Change Your Car Oil",
      "subtitle": null,
      "slug": "how-to-change-your-car-oil",
      "excerpt": "Learn the essential steps...",
      "status": "published",
      "featured": false,
      "cover_image_url": "https://images.example.com/oil-change.webp",
      "category": { "id": 3, "name": "Dicas", "slug": "dicas" },
      "author": "Equipe Editorial",
      "reading_time": 7,
      "published_at": "2026-03-15T10:30:00Z",
      "created_at": "2026-03-14T08:00:00Z",
      "updated_at": "2026-03-15T10:30:00Z",
      "version": 1,
      "url": "/how-to-change-your-car-oil"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 28,
    "from": 1,
    "to": 10
  },
  "links": {
    "first": "/api/content/articles?page=1",
    "last": "/api/content/articles?page=3",
    "prev": null,
    "next": "/api/content/articles?page=2"
  }
}
```

---

### 2. Create Article

```
POST /api/content/articles
```

**Purpose**: Create a new article. Defaults to `draft` status unless explicitly set.

**Request body**:

```json
{
  "title": "How to Change Your Car Oil",
  "subtitle": "A step-by-step guide for beginners",
  "slug": "how-to-change-your-car-oil",
  "excerpt": "Learn the essential steps to change your car oil safely at home.",
  "body_md": "## Introduction\n\nChanging your car oil is one of the most important maintenance tasks...\n\n## What You Need\n\n- Oil filter\n- New engine oil (check your manual for the right type)\n- Drain pan\n- Wrench set\n\n## Step-by-Step Process\n\n### 1. Warm Up the Engine\n\nRun your engine for 5 minutes...",
  "category_slug": "dicas",
  "category_slugs": ["dicas", "manutencao-e-revisao-programada"],
  "status": "draft",
  "featured": false,
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Mechanic draining engine oil into pan" },
    { "media_id": 11, "alt": "New oil filter being installed" }
  ],
  "gallery_mode": "inline",
  "video_urls": [
    "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
  ],
  "author": "Equipe Editorial",
  "reading_time": 7,
  "seo_title": "How to Change Car Oil — Complete Guide 2026",
  "seo_description": "Step-by-step guide to changing your car oil at home.",
  "image_source": "ai",
  "canonical_url": null,
  "published_at": null
}
```

**Required fields**: `title`, `body_md`

**Optional fields**: All others.
- If `slug` is omitted, it is auto-generated from `title`.
- If `category_slug` is omitted, defaults to `geral`.
- If `gallery_mode` is omitted, defaults to `inline` (images appended to `body_md`). Use `gallery` to store in structured fields only.

**Image fields**:

| Field | Type | Description |
|-------|------|-------------|
| `image_source` | string | Image credit type: `ai` (default), `real`, `press`, `stock`. Determines the caption rendered on the article page. |
| `cover_media_id` | integer | Reference to a completed `media_assets.id`. URL auto-resolved. Takes precedence over `cover_image_url`. |
| `cover_image_url` | URL | Direct HTTPS URL for cover. Ignored if `cover_media_id` is provided. |
| `gallery_media` | array | Array of `{media_id, alt?}` or `{url, alt?}` objects. Default behavior (`gallery_mode: "inline"`): images appended as `![alt](url)` to `body_md`. |
| `gallery_media.*.alt` | string | Alt text for the image. If omitted, defaults to `"{title} — imagem {n}"`. |
| `gallery_mode` | string | `"inline"` (default): embed images in `body_md`. `"gallery"`: store in structured fields only. |
| `gallery_image_urls` | array | Legacy: array of plain HTTPS URLs. Prefer `gallery_media` with `media_id`. |

**Response**: `201 Created` with full article resource.

**Status codes**:

| Code | Condition |
|------|-----------|
| 201 | Article created successfully |
| 401 | Invalid or missing Bearer token |
| 422 | Validation errors |

---

### 3. Get Article

```
GET /api/content/articles/{id}
```

**Purpose**: Retrieve a single article by ID.

The `{id}` parameter accepts a numeric ID. To look up by slug, use the list endpoint with `?search=slug-value` or a dedicated query parameter.

**Response**: `200 OK` with full article resource (including `body_md`).

**Query Parameters**:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `include_html` | boolean | false | If true, include rendered `content_html` field |

**Status codes**:

| Code | Condition |
|------|-----------|
| 200 | Article found |
| 401 | Invalid or missing Bearer token |
| 404 | Article not found or soft-deleted |

**Example response**:

```json
{
  "data": {
    "id": 42,
    "title": "How to Change Your Car Oil",
    "subtitle": "A step-by-step guide for beginners",
    "slug": "how-to-change-your-car-oil",
    "excerpt": "Learn the essential steps to change your car oil safely at home.",
    "body_md": "## Introduction\n\nChanging your car oil...",
    "status": "published",
    "featured": false,
    "cover_image_url": "https://images.example.com/oil-change.webp",
    "gallery_image_urls": [],
    "video_urls": [],
    "category": { "id": 3, "name": "Dicas", "slug": "dicas" },
    "categories": [
      { "id": 3, "name": "Dicas", "slug": "dicas" }
    ],
    "author": "Equipe Editorial",
    "reading_time": 7,
    "seo_title": "How to Change Car Oil — Complete Guide 2026",
    "seo_description": "Step-by-step guide to changing your car oil at home.",
    "canonical_url": null,
    "published_at": "2026-03-15T10:30:00Z",
    "created_at": "2026-03-14T08:00:00Z",
    "updated_at": "2026-03-15T10:30:00Z",
    "deleted_at": null,
    "version": 3,
    "url": "/how-to-change-your-car-oil"
  }
}
```

---

### 4. Update Article (Full Replacement)

```
PUT /api/content/articles/{id}
```

**Purpose**: Replace all mutable fields of an article. Fields not included revert to defaults or null.

**Important**: This is a destructive operation. The `version` field is required for optimistic concurrency control.

**Request body**: Same schema as create, plus required `version`.

```json
{
  "version": 3,
  "title": "How to Change Your Car Oil (Updated)",
  "body_md": "## Introduction\n\nUpdated content...",
  "category_slug": "dicas",
  "excerpt": "Updated excerpt.",
  "status": "published"
}
```

**Required fields**: `version`, `title`, `body_md`, `category_slug`, `excerpt`

**Response**: `200 OK` with full article resource. The `version` field in the response will be incremented.

**Status codes**:

| Code | Condition |
|------|-----------|
| 200 | Article updated successfully |
| 401 | Invalid or missing Bearer token |
| 404 | Article not found |
| 409 | Version conflict — article was modified since last read |
| 422 | Validation errors |

**Version conflict response (409)**:

```json
{
  "message": "Version conflict. The article has been modified since your last read.",
  "current_version": 4,
  "your_version": 3
}
```

---

### 5. Partial Update Article

```
PATCH /api/content/articles/{id}
```

**Purpose**: Update only the provided fields. Omitted fields are not changed.

**Required**: `version` field for optimistic concurrency control.

**Example — update only title and SEO**:

```json
{
  "version": 3,
  "title": "New Title",
  "seo_title": "New SEO Title — Guide 2026",
  "seo_description": "Updated meta description for search engines."
}
```

**Example — update only body**:

```json
{
  "version": 3,
  "body_md": "## Introduction\n\nCompletely new Markdown content..."
}
```

**Example — update only media**:

```json
{
  "version": 3,
  "cover_image_url": "https://images.example.com/new-cover.webp",
  "gallery_image_urls": [
    "https://images.example.com/new-1.webp",
    "https://images.example.com/new-2.webp"
  ],
  "video_urls": [
    "https://www.youtube.com/watch?v=newVideoId"
  ]
}
```

**Response**: `200 OK` with full article resource (version incremented).

**Status codes**: Same as PUT (200, 401, 404, 409, 422).

---

### 6. Delete Article

```
DELETE /api/content/articles/{id}
```

**Purpose**: Soft-delete an article. The article is hidden from public views and the default list but can be restored.

**Request body**: Optional.

```json
{
  "version": 3
}
```

If `version` is provided, it is checked for concurrency. If omitted, delete proceeds without version check.

**Response**: `200 OK`

```json
{
  "message": "Article soft-deleted successfully.",
  "id": 42,
  "deleted_at": "2026-03-15T12:00:00Z"
}
```

**Status codes**:

| Code | Condition |
|------|-----------|
| 200 | Article soft-deleted |
| 401 | Invalid or missing Bearer token |
| 404 | Article not found (already deleted or does not exist) |
| 409 | Version conflict (if version provided) |

**Warning for agents**: This operation is reversible via the restore endpoint. However, published articles become immediately invisible to readers.

---

### 7. Restore Article

```
POST /api/content/articles/{id}/restore
```

**Purpose**: Restore a soft-deleted article. The article returns to its previous status.

**Request body**: None required.

**Response**: `200 OK` with full article resource.

**Status codes**:

| Code | Condition |
|------|-----------|
| 200 | Article restored |
| 401 | Invalid or missing Bearer token |
| 404 | Article not found or not currently deleted |

---

### 8. Publish Article

```
POST /api/content/articles/{id}/publish
```

**Purpose**: Transition article status to `published`. Sets `published_at` to the current time if not already set.

**Request body**: Optional.

```json
{
  "version": 3
}
```

**Allowed source statuses**: `draft`, `review`, `scheduled`

**Response**: `200 OK` with full article resource.

**Status codes**:

| Code | Condition |
|------|-----------|
| 200 | Article published |
| 401 | Invalid or missing Bearer token |
| 404 | Article not found |
| 409 | Version conflict |
| 422 | Invalid status transition (e.g., attempting to publish an archived article) |

---

### 9. Unpublish Article

```
POST /api/content/articles/{id}/unpublish
```

**Purpose**: Transition article status from `published` to `draft`.

**Allowed source statuses**: `published`

**Response and status codes**: Same pattern as publish.

---

### 10. Schedule Article

```
POST /api/content/articles/{id}/schedule
```

**Purpose**: Transition article to `scheduled` status with a future publication date.

**Request body**:

```json
{
  "version": 3,
  "published_at": "2026-04-01T09:00:00Z"
}
```

**Required**: `published_at` must be a future ISO 8601 datetime.

**Allowed source statuses**: `draft`, `review`

**Response and status codes**: Same pattern as publish, plus 422 if `published_at` is in the past.

---

### 11. Archive Article

```
POST /api/content/articles/{id}/archive
```

**Purpose**: Transition article to `archived` status. Archived articles are hidden from public views but not deleted.

**Allowed source statuses**: `published`

**Response and status codes**: Same pattern as publish.

---

## Rate Limiting

| Endpoint Group | Limit | Window |
|---------------|-------|--------|
| Read operations (GET) | 60 requests | 1 minute |
| Write operations (POST, PUT, PATCH, DELETE) | 30 requests | 1 minute |

Rate limit headers are included in all responses:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 58
Retry-After: 42
```

When exceeded: `429 Too Many Requests`

```json
{
  "message": "Too many requests. Please try again in 42 seconds."
}
```

---

## HTTP Status Code Reference

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Resource created |
| 401 | Unauthorized — invalid or missing token |
| 404 | Resource not found |
| 409 | Version conflict — optimistic locking failure |
| 422 | Validation error — check `errors` object |
| 429 | Rate limit exceeded |
| 500 | Server error |

---

## Slug Resolution

Articles are identified by numeric `id` in API paths. The slug is a mutable attribute, not a path identifier, to avoid breaking URLs when slugs change.

To find an article by slug:

```
GET /api/content/articles?search=exact-slug-value&per_page=1
```

---

## Date Format

All dates use ISO 8601 format with UTC timezone:

```
2026-03-15T10:30:00Z
```

---

*Previous: [01-execution-plan.md](./01-execution-plan.md)*
*Next: [03-markdown-content-contract.md](./03-markdown-content-contract.md)*
