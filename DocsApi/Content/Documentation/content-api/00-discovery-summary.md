# 00 — Discovery Summary

## Purpose

This document captures the current state of `src/Content` as of the discovery phase, prior to designing the agent-facing Content API. Every architectural and API decision in later documents is grounded in this analysis.

---

## 1. Module Overview

**Module**: `src/Content`
**Registered**: Yes (`bootstrap/providers.php` via `ContentServiceProvider`)
**Namespace**: `Src\Content`
**Web prefix**: `/artigos`

The Content module manages blog/editorial content — articles, categories, comments, newsletters, and AI-powered article generation via Claude and OpenAI.

---

## 2. Current Directory Structure

```
src/Content/
├── Application/
│   ├── Actions/
│   │   ├── CorrectCommentAction.php
│   │   ├── DeleteCommentAction.php
│   │   ├── GenerateArticleAction.php
│   │   ├── StoreCommentAction.php
│   │   ├── StoreNewsletterAction.php
│   │   └── UpdateCommentAction.php
│   ├── Controllers/
│   │   ├── ArticleApiController.php        ← existing API (create only)
│   │   ├── ArticleController.php           ← web (Blade) controller
│   │   ├── NewsletterApiController.php     ← newsletter data API
│   │   └── NewsletterController.php        ← web newsletter controller
│   ├── Events/
│   │   ├── CommentCreated.php
│   │   └── NewsletterSubscribed.php
│   ├── Listeners/
│   │   ├── NotifyAdminOfNewComment.php
│   │   ├── NotifyUserOfCommentReceipt.php
│   │   └── SendNewsletterVerificationEmail.php
│   ├── Mail/
│   │   ├── NewCommentAdminMail.php
│   │   ├── NewCommentUserMail.php
│   │   └── NewsletterVerificationMail.php
│   ├── Middleware/
│   │   └── VerifyN8nWebhookSecret.php
│   ├── Requests/
│   │   ├── StoreArticleApiRequest.php      ← existing create validation
│   │   ├── StoreCommentRequest.php
│   │   ├── StoreNewsletterRequest.php
│   │   └── UpdateCommentRequest.php
│   └── Services/
│       ├── ArticleImageStorageService.php
│       ├── ArticlePageQueryService.php
│       ├── ArticleStructuredDataService.php
│       └── CommentResponseService.php
├── Console/
│   └── Commands/
│       ├── ConsolidateCategoriesCommand.php
│       ├── GenerateArticleCommand.php
│       └── MigrateWordPressArticlesCommand.php
├── Domain/
│   ├── Models/
│   │   ├── Article.php
│   │   ├── Category.php
│   │   ├── Comment.php
│   │   └── NewsletterSubscriber.php
│   └── Services/
│       ├── CommentCorrectionService.php
│       ├── ContentGenerationService.php
│       ├── ContentSEOService.php
│       └── ContentGeneration/
│           ├── ContentGenerationPromptBuilder.php
│           ├── ContentGenerationProviderGateway.php
│           └── ContentGenerationResponseParser.php
├── Infrastructure/
│   ├── Data/
│   │   └── article-titles-seo.json
│   ├── Database/
│   │   ├── Migrations/
│   │   │   ├── 2026_01_31_000001_create_categories_table.php
│   │   │   ├── 2026_01_31_000002_create_articles_table.php
│   │   │   ├── 2026_02_01_014406_create_comments_table.php
│   │   │   ├── 2026_02_01_023322_create_newsletter_subscribers_table.php
│   │   │   └── 2026_02_01_154500_create_article_category_table.php
│   │   └── Seeders/
│   └── ExternalServices/
├── Presentation/
│   └── Resources/
│       ├── js/
│       │   ├── Components/Comments/
│       │   └── article.js
│       └── views/
│           ├── article/partials/
│           ├── mail/
│           └── newsletter/
├── Routes/
│   └── web.php
├── Providers/
│   └── ContentServiceProvider.php
└── Documentation/
    ├── AI_CONTENT_PROTOCOL.md
    ├── ARTICLE_GENERATION.md
    └── PLANNING.md
```

---

## 3. Domain Models (Current Schema)

### Article (`articles` table)

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| `id` | bigint | no | auto | primary key |
| `category_id` | bigint FK | no | — | cascade delete |
| `wp_post_id` | bigint | yes | — | legacy WordPress import |
| `title` | string | no | — | |
| `slug` | string | no | — | unique index |
| `full_url` | string | yes | — | auto-computed on save |
| `excerpt` | text | no | — | |
| `content` | longtext | no | — | Markdown or HTML (legacy) |
| `needs_review` | boolean | no | false | |
| `is_reviewed` | boolean | no | false | |
| `reviewed_at` | timestamp | yes | — | |
| `featured_image` | string | yes | — | S3 path or URL |
| `author_name` | string | no | 'Equipe Editorial' | |
| `reading_time` | integer | no | 5 | minutes |
| `is_published` | boolean | no | false | |
| `published_at` | timestamp | yes | — | |
| `meta` | json | yes | — | `{description, keywords}` |
| `created_at` | timestamp | no | — | |
| `updated_at` | timestamp | no | — | |

**Missing for new API**: `deleted_at` (no soft deletes), `version` (no concurrency control), `subtitle`, `seo_title`, `canonical_url`, `status` enum, `video_urls`, `gallery_image_urls`.

### Category (`categories` table)

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint | primary key |
| `name` | string | |
| `slug` | string | unique |
| `description` | text | nullable |
| `role` | string | nullable (B2B_RECRUITING, etc.) |
| `role_description` | text | nullable |
| `order` | integer | default 0 |
| `is_active` | boolean | default true |

### Pivot: `article_category`

Many-to-many between articles and categories.

---

## 4. Current API Endpoints

### Existing in `routes/api.php`

| Method | URI | Controller | Auth | Purpose |
|--------|-----|------------|------|---------|
| `POST` | `/api/content/articles` | `ArticleApiController@store` | Bearer token | Create article |
| `GET` | `/api/newsletter/subscribers` | `NewsletterApiController@subscribers` | N8N webhook secret | List subscribers |
| `GET` | `/api/newsletter/subscribers/stats` | `NewsletterApiController@stats` | N8N webhook secret | Subscriber stats |
| `GET` | `/api/newsletter/articles/recent` | `NewsletterApiController@recentArticles` | N8N webhook secret | Recent articles |

### What exists for articles

- **Create only** — `POST /api/content/articles`
- Auth: `Bearer {CONTENT_API_KEY}` (validated in `StoreArticleApiRequest::authorize()`)
- Response: `{id, slug, url, status}` with HTTP 201
- Throttle: 30 req/min
- Always auto-publishes (`is_published: true`, `published_at: now()`)

### What is missing

- `GET /api/content/articles` — list/search
- `GET /api/content/articles/{id}` — read single
- `PATCH /api/content/articles/{id}` — partial update
- `PUT /api/content/articles/{id}` — full update
- `DELETE /api/content/articles/{id}` — delete
- Lifecycle transitions (draft, review, schedule, publish, archive)
- Soft delete support
- Version/concurrency control
- Media management endpoints
- Markdown-first content contract

---

## 5. Content Storage Format

The `content` column stores **mixed formats**:

- **New articles**: Markdown (generated by AI or API)
- **Legacy articles**: Raw HTML (WordPress imports)

Detection logic in `Article::getContentHtmlAttribute()`:
- If content starts with `<` → treat as HTML (legacy passthrough)
- Otherwise → parse as Markdown via `league/commonmark` (GithubFlavoredMarkdown)

Post-processing on display:
1. FAQ accordion injection (`### FAQ: Question` → accordion HTML)
2. YouTube embed injection (YouTube URLs → iframe)

**Key insight**: Markdown is already the intended canonical format. The HTML path exists only for legacy WordPress content. The new API must enforce Markdown-only for all writes.

---

## 6. Authentication Patterns

| Pattern | Used By | Mechanism |
|---------|---------|-----------|
| Bearer token (env) | Article API | `StoreArticleApiRequest::authorize()` checks `config('services.article_api.key')` |
| Custom header | Newsletter API | `VerifyN8nWebhookSecret` middleware checks `X-N8N-Webhook-Secret` |
| Stripe signature | Webhooks | Stripe SDK signature verification |
| Session (web) | User comments | Standard Laravel `auth` middleware |

For the new agent API, the Bearer token pattern is the natural fit — it is already used by the existing article creation endpoint and is simple for agent consumption.

---

## 7. Response Patterns

- **No JSON Resource classes** in the module — all responses are manual arrays
- **No API resource transformers** — data shaped inline in controllers
- **Pagination**: Laravel's built-in `paginate()` (used in `ArticlePageQueryService`)
- **Error responses**: Standard Laravel validation error format `{message, errors: {field: [...]}}`

---

## 8. Existing Validation

`StoreArticleApiRequest` validates:
- `title`: required, string, max 255
- `slug`: required, string, max 80, unique
- `excerpt`: required, string, max 300
- `content`: required, string (no format validation)
- `category_slug`: required, exists in categories
- `reading_time`: nullable, integer, min 1
- `meta`: nullable, array
- `featured_image`: nullable, string (URL)
- `featured_image_file`: nullable, image file (max 5MB)
- `content_images`: nullable, array max 10

---

## 9. Image Handling

`ArticleImageStorageService` uploads to S3 and optionally queues WebP compression via RabbitMQ:
- Featured images: 1200x675, Q82
- Content images: 1200x800, Q82
- Placeholder substitution: `{{IMG:0}}`, `{{IMG:1}}` in content

For the new API (media by URL), image uploads can be separate from content. The API should accept URLs directly — no file uploads required for agent workflows.

---

## 10. AI Content Generation Pipeline

```
Artisan command → GenerateArticleAction → ContentGenerationService
→ PromptBuilder → ProviderGateway (Claude/OpenAI) → ResponseParser
→ Article::firstOrCreate() → auto-publish
```

The new API will **not replace** this pipeline. It provides a separate, agent-driven CRUD interface. Both coexist: the AI pipeline generates content, the API allows agents to manage it.

---

## 11. Key Gaps for Full CRUD API

| Gap | Impact |
|-----|--------|
| No read/list endpoints | Cannot retrieve articles via API |
| No update endpoints | Cannot edit articles via API |
| No delete endpoint | Cannot remove articles via API |
| No soft deletes | No recovery from accidental deletion |
| No `status` enum | Only `is_published` boolean — no draft/review/scheduled/archived states |
| No `version` column | No optimistic concurrency control |
| No `deleted_at` column | No soft delete support (SoftDeletes trait) |
| No JSON Resources | No consistent response transformation |
| No `subtitle` field | Missing from schema |
| No `seo_title` field | SEO title stored in `meta.description` only |
| No `canonical_url` field | Missing from schema |
| No `video_urls` field | YouTube handled via content parsing only |
| No `gallery_image_urls` | Only single `featured_image` |
| No API routes file | All API routes in global `routes/api.php` |

---

## 12. Compatibility Concerns

1. **Legacy HTML content**: Existing articles with HTML content must not be corrupted. The API should return `content` as-is but enforce Markdown-only on writes.
2. **`is_published` boolean**: Currently the only publication control. Migration to a `status` enum must maintain backward compatibility — `is_published` can become a computed attribute.
3. **`full_url` auto-computation**: The `saving` boot hook auto-sets `full_url`. This must be preserved.
4. **`article_category` pivot**: Many-to-many categories must be supported in API payloads.
5. **`meta` JSON field**: Currently stores `{description, keywords}`. The API should support structured SEO fields that map to/from this.
6. **Existing `POST /api/content/articles`**: The new API should supersede this endpoint. A deprecation path is needed.

---

## 13. Summary of Decisions Needed

| Decision | Options | Recommended |
|----------|---------|-------------|
| Status model | Boolean `is_published` vs enum `status` | Enum `status` with migration |
| Soft deletes | Add `SoftDeletes` trait | Yes — required for agent safety |
| Version control | Add `version` column | Yes — optimistic locking for concurrent agents |
| SEO fields | Embedded in `meta` JSON vs dedicated columns | Dedicated `seo_title`, `seo_description` columns |
| Media arrays | JSON columns vs related tables | JSON columns (`gallery_image_urls`, `video_urls`) |
| API auth | Reuse Bearer token pattern | Yes — extend `services.article_api.key` |
| Route location | Global `routes/api.php` vs module `Routes/api.php` | Module `Routes/api.php` (DDD convention) |
| Response format | Manual arrays vs JsonResource | JsonResource classes |

---

*Next: [01-execution-plan.md](./01-execution-plan.md)*
