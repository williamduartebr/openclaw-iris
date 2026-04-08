# Content API — Implementation Handoff

## Created Files (22)

### Domain Layer
| File | Purpose |
|------|---------|
| `src/Content/Domain/Exceptions/InvalidStatusTransitionException.php` | Thrown on illegal state machine transitions |
| `src/Content/Domain/Exceptions/VersionConflictException.php` | Thrown when optimistic lock version mismatches |
| `src/Content/Domain/Services/ArticleLifecycleService.php` | State machine: draft→review→scheduled→published→archived |
| `src/Content/Domain/Services/ArticleSlugService.php` | Slug generation with transliteration and uniqueness |

### Application Layer
| File | Purpose |
|------|---------|
| `src/Content/Application/Controllers/ContentApiController.php` | 12 endpoints: CRUD + lifecycle actions |
| `src/Content/Application/Middleware/VerifyContentApiToken.php` | Bearer token auth against `CONTENT_API_KEY` |
| `src/Content/Application/Requests/CreateArticleRequest.php` | Validation for POST (create) |
| `src/Content/Application/Requests/UpdateArticleRequest.php` | Validation for PUT (full replace) |
| `src/Content/Application/Requests/PatchArticleRequest.php` | Validation for PATCH (partial update) |
| `src/Content/Application/Requests/ListArticlesRequest.php` | Validation for GET index query params |
| `src/Content/Application/Resources/ArticleResource.php` | Full article JSON (includes body_md) |
| `src/Content/Application/Resources/ArticleCollectionResource.php` | Lightweight list JSON (no body_md) |
| `src/Content/Application/Services/ArticleCrudService.php` | Create/update/patch/delete/restore orchestration |

### Infrastructure
| File | Purpose |
|------|---------|
| `src/Content/Infrastructure/Database/Migrations/2026_01_31_000002_create_articles_table.php` | Consolidated articles base migration with status, subtitle, seo_*, gallery, media references, featured, version and soft deletes |
| `src/Content/Infrastructure/Database/Migrations/2026_01_31_000001_create_categories_table.php` | Consolidated categories base migration with funnel stage |

### Console
| File | Purpose |
|------|---------|
| `src/Content/Console/Commands/PublishScheduledArticlesCommand.php` | `content:publish-scheduled` — auto-publishes scheduled articles |

### Routes
| File | Purpose |
|------|---------|
| `src/Content/Routes/api.php` | 12 API routes under `/api/content` |

### Tests (5 files, 80 tests, 160 assertions)
| File | Tests | Scope |
|------|-------|-------|
| `tests/Feature/Content/ContentApiCrudTest.php` | 36 | Auth, create, read, update, patch, delete, restore, validation |
| `tests/Feature/Content/ContentApiLifecycleTest.php` | 14 | Publish, unpublish, schedule, archive, transitions |
| `tests/Feature/Content/ContentApiFilterSortTest.php` | 13 | Status/category/featured/search filters, sorting, pagination |
| `tests/Unit/Content/ArticleLifecycleServiceTest.php` | 10 | State machine transitions |
| `tests/Unit/Content/ArticleSlugServiceTest.php` | 7 | Slug generation, uniqueness |

## Modified Files (7)

| File | Change |
|------|--------|
| `src/Content/Domain/Models/Article.php` | SoftDeletes, status constants, scopes, casts, fillable, version increment |
| `src/Content/Providers/ContentServiceProvider.php` | Registers API routes + middleware + publish command |
| `config/services.php` | Added `content_api.key` |
| `.env.example` | Added `CONTENT_API_KEY` placeholder |
| `routes/api.php` | Legacy endpoint moved to `/api/content/articles/legacy` |
| `routes/console.php` | Added `content:publish-scheduled` every-minute schedule |
| `tests/Feature/Content/ArticleApiTest.php` | Updated legacy tests to use new legacy path |

## Routes

| Method | Path | Name | Throttle |
|--------|------|------|----------|
| GET | `/api/content/articles` | `content.api.articles.index` | 60/min |
| POST | `/api/content/articles` | `content.api.articles.store` | 30/min |
| GET | `/api/content/articles/{id}` | `content.api.articles.show` | 60/min |
| PUT | `/api/content/articles/{id}` | `content.api.articles.update` | 30/min |
| PATCH | `/api/content/articles/{id}` | `content.api.articles.patch` | 30/min |
| DELETE | `/api/content/articles/{id}` | `content.api.articles.destroy` | 30/min |
| POST | `/api/content/articles/{id}/publish` | `content.api.articles.publish` | 30/min |
| POST | `/api/content/articles/{id}/unpublish` | `content.api.articles.unpublish` | 30/min |
| POST | `/api/content/articles/{id}/schedule` | `content.api.articles.schedule` | 30/min |
| POST | `/api/content/articles/{id}/archive` | `content.api.articles.archive` | 30/min |
| POST | `/api/content/articles/{id}/restore` | `content.api.articles.restore` | 30/min |

## Database Indexes Added

- `articles.status` — index
- `articles.featured` — index
- `articles.cover_media_id` — index
- `categories.funnel_stage` — index

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `CONTENT_API_KEY` | Yes | Bearer token for Content API authentication |

## Authentication Format

```
Authorization: Bearer {CONTENT_API_KEY}
Content-Type: application/json
Accept: application/json
```

## Scheduled Commands

| Command | Schedule | Purpose |
|---------|----------|---------|
| `content:publish-scheduled` | Every minute | Publishes articles where status=scheduled and published_at <= now |
