# 01 — Execution Plan

## Purpose

Phased implementation plan for the agent-facing Content API. Each phase has clear inputs, outputs, and completion criteria. Implementation must follow this order.

---

## Concrete Implementation Sequence

This is the exact order of execution for implementation:

1. **Migration** — consolidate `create_articles_table`: status, subtitle, seo_title, seo_description, canonical_url, gallery_image_urls, gallery_media, video_urls, featured, version, cover_media_id, deleted_at.
2. **Article model** — Add SoftDeletes, new fillable/casts, scopes, incrementVersion(), status sync with is_published.
3. **Domain exceptions** — InvalidStatusTransitionException, VersionConflictException.
4. **Domain services** — ArticleLifecycleService (status transitions), ArticleSlugService (generation + uniqueness).
5. **Form requests** — CreateArticleRequest, UpdateArticleRequest, PatchArticleRequest, ListArticlesRequest.
6. **JSON resources** — ArticleResource, ArticleCollectionResource.
7. **CRUD service** — ArticleCrudService (create, update, patch, delete, restore with version check + slug + category sync).
8. **Controller** — ContentApiController (index, store, show, update, patch, destroy, restore, publish, unpublish, schedule, archive).
9. **Middleware** — VerifyContentApiToken.
10. **Routes** — src/Content/Routes/api.php with all endpoints.
11. **Provider** — Update ContentServiceProvider to register API routes.
12. **Config** — Add CONTENT_API_KEY to config/services.php and .env.example.
13. **Scheduled publish command** — content:publish-scheduled Artisan command + scheduler registration.
14. **Tests** — Feature tests (CRUD, lifecycle, filters) + Unit tests (lifecycle service, slug service).
15. **Documentation updates** — Final pass on all docs reflecting actual implementation.

---

## Phase 1 — Database Schema Evolution

**Goal**: Extend the `articles` table to support the full article lifecycle, soft deletes, versioning, and structured media/SEO fields.

### Tasks

1. **Consolidate base migration** `create_articles_table`
   - Add `status` column (`draft`, `review`, `scheduled`, `published`, `archived`) — default `draft`
   - Add `subtitle` (string, nullable, max 255)
   - Add `seo_title` (string, nullable, max 70)
   - Add `seo_description` (string, nullable, max 160)
   - Add `canonical_url` (string, nullable, max 2048)
   - Add `gallery_image_urls` (json, nullable) — array of image URL strings
   - Add `gallery_media` (json, nullable) — structured media payload
   - Add `video_urls` (json, nullable) — array of video URL strings
   - Add `cover_media_id` (unsigned bigint, nullable)
   - Add `featured` (boolean, default false)
   - Add `version` (unsigned integer, default 1)
   - Add `deleted_at` (timestamp, nullable) — for SoftDeletes
   - Add index on `status`
   - Add index on `featured`
   - Add index on `cover_media_id`

2. **Update `Article` model**
   - Add `SoftDeletes` trait
   - Add new columns to `$fillable`
   - Add casts: `gallery_image_urls` → array, `video_urls` → array, `featured` → boolean, `deleted_at` → datetime
   - Add `status` accessor that maintains backward compatibility with `is_published`
   - Add `incrementVersion()` method for optimistic locking
   - Add scopes: `scopeDraft`, `scopeScheduled`, `scopeArchived`, `scopeFeatured`

### Completion criteria
- Migration runs without errors
- Existing articles retain their published state
- `is_published` remains functional for backward compatibility with web views

---

## Phase 2 — Domain Layer

**Goal**: Define the article lifecycle service and domain rules.

### Tasks

1. **Create `ArticleLifecycleService`** (`Domain/Services/ArticleLifecycleService.php`)
   - `transitionStatus(Article, string $newStatus): Article` — validates allowed transitions
   - `publish(Article): Article` — sets status=published, published_at=now
   - `unpublish(Article): Article` — sets status=draft, clears published_at
   - `schedule(Article, Carbon $publishAt): Article` — sets status=scheduled, published_at
   - `archive(Article): Article` — sets status=archived
   - `restore(Article): Article` — restores soft-deleted article

2. **Create `ArticleSlugService`** (`Domain/Services/ArticleSlugService.php`)
   - `generate(string $title): string` — creates URL-safe slug
   - `ensureUnique(string $slug, ?int $excludeId = null): string` — appends suffix if needed

3. **Define status transition rules** (enforced in `ArticleLifecycleService`)
   ```
   draft     → review, scheduled, published
   review    → draft, scheduled, published
   scheduled → draft, published
   published → draft, archived
   archived  → draft
   ```

4. **Create `InvalidStatusTransitionException`** (`Domain/Exceptions/`)

### Completion criteria
- All transitions enforced with clear exception messages
- Slug generation and uniqueness guaranteed
- No direct status mutation outside the lifecycle service

---

## Phase 3 — Application Layer (CRUD)

**Goal**: Build the full CRUD controller, form requests, actions, and JSON resources.

### Tasks

1. **Create `ArticleResource`** (`Application/Resources/ArticleResource.php`)
   - Full article representation with all fields
   - Conditional inclusion of relationships (category, categories)
   - Computed fields: `url`, `content_html` (optional, on request)

2. **Create `ArticleCollectionResource`** (`Application/Resources/ArticleCollectionResource.php`)
   - Lightweight list representation (no `body_md`, no `content_html`)
   - Pagination metadata

3. **Create Form Requests**
   - `CreateArticleRequest` — validates all fields for creation
   - `UpdateArticleRequest` — validates all fields for full replacement (PUT)
   - `PatchArticleRequest` — validates partial fields (PATCH), includes `version` for optimistic locking
   - `ListArticlesRequest` — validates query parameters (filters, sorting, pagination)
   - All extend a base that checks Bearer token authorization

4. **Create `ContentApiController`** (`Application/Controllers/ContentApiController.php`)
   - `index(ListArticlesRequest): AnonymousResourceCollection` — list with filters, sorting, pagination
   - `store(CreateArticleRequest): ArticleResource` — create article
   - `show(Article): ArticleResource` — get single article (by id or slug)
   - `update(UpdateArticleRequest, Article): ArticleResource` — full replacement
   - `patch(PatchArticleRequest, Article): ArticleResource` — partial update
   - `destroy(Article): JsonResponse` — soft delete
   - `restore(int $id): ArticleResource` — restore soft-deleted
   - `publish(Article): ArticleResource` — transition to published
   - `unpublish(Article): ArticleResource` — transition to draft
   - `schedule(Article): ArticleResource` — transition to scheduled
   - `archive(Article): ArticleResource` — transition to archived

5. **Create `ArticleCrudService`** (`Application/Services/ArticleCrudService.php`)
   - `create(array $data): Article`
   - `update(Article, array $data): Article` — full update with version check
   - `patch(Article, array $data): Article` — partial update with version check
   - `delete(Article): void` — soft delete
   - `restore(int $id): Article`
   - Handles slug generation, category sync, version incrementing

### Completion criteria
- All CRUD operations functional via API
- Version conflict returns HTTP 409
- Soft delete with restore capability
- Consistent JSON Resource responses

---

## Phase 4 — API Routes and Authentication

**Goal**: Register API routes following module conventions.

### Tasks

1. **Create `Routes/api.php`** in `src/Content/`
   - Resource routes under `/api/content/articles`
   - Action routes for lifecycle transitions
   - Throttle middleware (60 req/min for reads, 30 req/min for writes)

2. **Create `VerifyContentApiToken` middleware** (`Application/Middleware/VerifyContentApiToken.php`)
   - Validates `Authorization: Bearer {token}` against `config('services.content_api.key')`
   - Returns 401 with standard error body on failure
   - Reusable across all Content API endpoints

3. **Update `ContentServiceProvider`** to register API routes
   - Load `Routes/api.php` with `api` middleware group and `/api/content` prefix

4. **Add config** to `config/services.php`
   - `content_api.key` (can reuse or separate from `article_api.key`)

### Route table

| Method | URI | Action | Name |
|--------|-----|--------|------|
| `GET` | `/api/content/articles` | `index` | `content.api.articles.index` |
| `POST` | `/api/content/articles` | `store` | `content.api.articles.store` |
| `GET` | `/api/content/articles/{article}` | `show` | `content.api.articles.show` |
| `PUT` | `/api/content/articles/{article}` | `update` | `content.api.articles.update` |
| `PATCH` | `/api/content/articles/{article}` | `patch` | `content.api.articles.patch` |
| `DELETE` | `/api/content/articles/{article}` | `destroy` | `content.api.articles.destroy` |
| `POST` | `/api/content/articles/{article}/publish` | `publish` | `content.api.articles.publish` |
| `POST` | `/api/content/articles/{article}/unpublish` | `unpublish` | `content.api.articles.unpublish` |
| `POST` | `/api/content/articles/{article}/schedule` | `schedule` | `content.api.articles.schedule` |
| `POST` | `/api/content/articles/{article}/archive` | `archive` | `content.api.articles.archive` |
| `POST` | `/api/content/articles/{article}/restore` | `restore` | `content.api.articles.restore` |

### Completion criteria
- All routes registered and accessible
- Authentication enforced on all endpoints
- Rate limiting active
- Existing `POST /api/content/articles` (old) deprecated with redirect or alias

---

## Phase 5 — Filtering, Sorting, and Pagination

**Goal**: Implement query capabilities for the list endpoint.

### Tasks

1. **Filters** (query parameters on `GET /api/content/articles`)
   - `status` — filter by status (single or comma-separated)
   - `category` — filter by category slug
   - `featured` — filter by featured flag (`true`/`false`)
   - `author` — filter by author_name
   - `search` — full-text search on title + excerpt
   - `created_after` / `created_before` — date range
   - `published_after` / `published_before` — date range
   - `trashed` — include soft-deleted (`only`, `with`, default excluded)

2. **Sorting** (`sort` parameter)
   - Format: `field` (asc) or `-field` (desc)
   - Allowed fields: `created_at`, `updated_at`, `published_at`, `title`
   - Default: `-created_at`

3. **Pagination**
   - `per_page` — items per page (default 15, max 100)
   - `page` — page number
   - Standard Laravel pagination envelope

### Completion criteria
- All filters work independently and in combination
- Sort parameter validated against allowed fields
- Pagination metadata included in response

---

## Phase 6 — Deprecation of Legacy Endpoint

**Goal**: Gracefully transition from the old `POST /api/content/articles` to the new API.

### Tasks

1. Add `Deprecated` response header to old `ArticleApiController@store`
2. Document migration path in agent guide
3. Optionally redirect old endpoint to new after transition period

### Completion criteria
- Old endpoint still functional but marked deprecated
- New endpoint is the documented default

---

## Phase 7 — Testing

**Goal**: Comprehensive test coverage for all API operations.

### Tasks

1. **Feature tests** (`tests/Feature/Content/ContentApiTest.php`)
   - CRUD operations (create, read, list, update, patch, delete)
   - Authentication (valid token, invalid token, missing token)
   - Validation (required fields, max lengths, slug uniqueness)
   - Status transitions (valid and invalid)
   - Optimistic locking (version conflict)
   - Soft delete and restore
   - Filtering, sorting, pagination
   - Markdown content validation
   - Media URL validation

2. **Unit tests** (`tests/Unit/Content/`)
   - `ArticleLifecycleServiceTest` — transition rules
   - `ArticleSlugServiceTest` — slug generation and uniqueness
   - `ArticleCrudServiceTest` — service logic

### Completion criteria
- All endpoints have happy-path and error-path tests
- Test count target: 40-60 tests
- All tests pass with `composer test`

---

## Phase 8 — Documentation

**Goal**: Complete the documentation set in `src/Content/Documentation/content-api/`.

### Deliverables

All 9 documents in this directory (this plan is document 01).

### Completion criteria
- All documents written in English Markdown
- No HTML in examples
- Agent-ready with unambiguous instructions

---

## Implementation Order Summary

```
Phase 1: Database migration           → schema ready
Phase 2: Domain services              → business rules enforced
Phase 3: Application layer (CRUD)     → endpoints functional
Phase 4: Routes and auth              → endpoints accessible
Phase 5: Filtering/sorting/pagination → list endpoint complete
Phase 6: Legacy deprecation           → clean transition
Phase 7: Testing                      → verified and stable
Phase 8: Documentation                → agent-ready handoff
```

---

## Estimated File Count

| Layer | New Files | Modified Files |
|-------|-----------|----------------|
| Migrations | 1 | 0 |
| Domain Models | 0 | 1 (Article.php) |
| Domain Services | 3 | 0 |
| Domain Exceptions | 1 | 0 |
| Application Controllers | 1 | 0 |
| Application Services | 1 | 0 |
| Application Requests | 4 | 0 |
| Application Resources | 2 | 0 |
| Application Middleware | 1 | 0 |
| Routes | 1 | 0 |
| Providers | 0 | 1 (ContentServiceProvider.php) |
| Config | 0 | 1 (services.php) |
| Tests | 3-4 | 0 |
| Documentation | 9 | 0 |
| **Total** | **~27** | **~3** |

---

*Previous: [00-discovery-summary.md](./00-discovery-summary.md)*
*Next: [02-api-specification.md](./02-api-specification.md)*
