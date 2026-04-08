# 05 — Domain Impact Analysis

## Purpose

Map every file in `src/Content` (and adjacent locations) that must be created or modified to implement the Content API. Each entry states whether it already exists, what changes are needed, and which execution phase it belongs to.

---

## Legend

- **NEW** — File does not exist, must be created
- **MODIFY** — File exists, needs changes
- **UNCHANGED** — File exists, no changes needed (listed for context)

---

## 1. Database — Migrations

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Infrastructure/Database/Migrations/2026_01_31_000002_create_articles_table.php` | MODIFY | 1 | Consolidated base migration with `status`, `subtitle`, SEO fields, structured media fields, `featured`, `version`, `cover_media_id`, `deleted_at` and related indexes. |
| `Infrastructure/Database/Migrations/2026_01_31_000001_create_categories_table.php` | MODIFY | 1 | Consolidated base migration with `funnel_stage` and its index. |

---

## 2. Domain — Models

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Domain/Models/Article.php` | MODIFY | 1 | Add `SoftDeletes` trait. Add new columns to `$fillable`. Add casts for `gallery_image_urls`, `video_urls`, `featured`, `deleted_at`. Add `status` accessor for backward compatibility with `is_published`. Add scopes: `scopeDraft`, `scopeScheduled`, `scopeArchived`, `scopeFeatured`. Add `incrementVersion()` method. Preserve all existing functionality (`getContentHtmlAttribute`, `getFeaturedImageAttribute`, boot hook). |
| `Domain/Models/Category.php` | UNCHANGED | — | No changes needed. |
| `Domain/Models/Comment.php` | UNCHANGED | — | No changes needed. |
| `Domain/Models/NewsletterSubscriber.php` | UNCHANGED | — | No changes needed. |

### Backward compatibility notes for Article model

The `is_published` field and `scopePublished` must remain functional. The web views, SEO service, and structured data service all depend on them. The new `status` field coexists:

- `is_published = true` maps to `status = 'published'`
- `is_published = false` maps to any non-published status
- `scopePublished` continues to work as-is (it checks `is_published` and `published_at`)
- The `saving` boot hook that computes `full_url` remains untouched

---

## 3. Domain — Services

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Domain/Services/ArticleLifecycleService.php` | NEW | 2 | Manages status transitions with validation. Methods: `transitionStatus()`, `publish()`, `unpublish()`, `schedule()`, `archive()`, `restore()`. Enforces the transition rules defined in the execution plan. |
| `Domain/Services/ArticleSlugService.php` | NEW | 2 | Generates URL-safe slugs from titles. Ensures uniqueness by appending numeric suffixes when needed. Methods: `generate()`, `ensureUnique()`. |
| `Domain/Services/ContentGenerationService.php` | UNCHANGED | — | AI generation pipeline. Not affected by API changes. |
| `Domain/Services/ContentSEOService.php` | UNCHANGED | — | SEO meta tag service for Blade views. Not affected. |
| `Domain/Services/CommentCorrectionService.php` | UNCHANGED | — | Comment AI correction. Not affected. |
| `Domain/Services/ContentGeneration/` | UNCHANGED | — | All sub-services (PromptBuilder, ProviderGateway, ResponseParser). Not affected. |

---

## 4. Domain — Exceptions

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Domain/Exceptions/InvalidStatusTransitionException.php` | NEW | 2 | Thrown when an invalid status transition is attempted. Contains `currentStatus`, `targetStatus`, and `allowedTransitions` for clear error messages. |
| `Domain/Exceptions/VersionConflictException.php` | NEW | 3 | Thrown when optimistic locking detects a version mismatch. Contains `currentVersion` and `providedVersion`. |

---

## 5. Application — Controllers

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Application/Controllers/ContentApiController.php` | NEW | 3 | Full CRUD controller for the Content API. Methods: `index()`, `store()`, `show()`, `update()`, `patch()`, `destroy()`, `restore()`, `publish()`, `unpublish()`, `schedule()`, `archive()`. Delegates business logic to `ArticleCrudService` and `ArticleLifecycleService`. |
| `Application/Controllers/ArticleApiController.php` | MODIFY | 6 | Add `Deprecated` response header. Optionally redirect to new controller. Existing functionality preserved during transition period. |
| `Application/Controllers/ArticleController.php` | UNCHANGED | — | Web (Blade) controller. Not affected. |
| `Application/Controllers/NewsletterApiController.php` | UNCHANGED | — | Newsletter data API. Not affected. |
| `Application/Controllers/NewsletterController.php` | UNCHANGED | — | Web newsletter. Not affected. |

---

## 6. Application — Services

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Application/Services/ArticleCrudService.php` | NEW | 3 | Orchestrates CRUD operations. Methods: `create()`, `update()`, `patch()`, `delete()`, `restore()`. Handles slug generation, category sync, version incrementing, and delegates to domain services. |
| `Application/Services/ArticlePageQueryService.php` | UNCHANGED | — | Web page query service. Not affected. |
| `Application/Services/ArticleImageStorageService.php` | UNCHANGED | — | S3 image upload. Not directly used by the new API (media by URL), but remains available. |
| `Application/Services/ArticleStructuredDataService.php` | UNCHANGED | — | Schema.org data for Blade views. Not affected. |
| `Application/Services/CommentResponseService.php` | UNCHANGED | — | Comment response helpers. Not affected. |

---

## 7. Application — Requests (Form Requests)

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Application/Requests/CreateArticleRequest.php` | NEW | 3 | Validates article creation payload. Required: `title`, `body_md`, `category_slug`. Optional: all other fields. Auth: Bearer token check. |
| `Application/Requests/UpdateArticleRequest.php` | NEW | 3 | Validates full article replacement (PUT). Required: `version`, `title`, `body_md`, `category_slug`, `excerpt`. Auth: Bearer token check. |
| `Application/Requests/PatchArticleRequest.php` | NEW | 3 | Validates partial update (PATCH). Required: `version`. All other fields optional. Auth: Bearer token check. |
| `Application/Requests/ListArticlesRequest.php` | NEW | 5 | Validates query parameters for list endpoint. Validates `sort` against allowed fields, `per_page` range, `status` values, date formats. |
| `Application/Requests/StoreArticleApiRequest.php` | UNCHANGED | — | Legacy create validation. Preserved for backward compatibility during deprecation. |
| `Application/Requests/StoreCommentRequest.php` | UNCHANGED | — | |
| `Application/Requests/UpdateCommentRequest.php` | UNCHANGED | — | |
| `Application/Requests/StoreNewsletterRequest.php` | UNCHANGED | — | |

---

## 8. Application — Resources

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Application/Resources/ArticleResource.php` | NEW | 3 | Full article JSON Resource. Includes all fields, category relationship, computed `url`. Conditionally includes `content_html` when requested. Maps `content` DB column to `body_md` response field. |
| `Application/Resources/ArticleCollectionResource.php` | NEW | 3 | Lightweight collection resource. Omits `body_md` for list performance. Includes pagination metadata via Laravel's resource collection. |

---

## 9. Application — Middleware

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Application/Middleware/VerifyContentApiToken.php` | NEW | 4 | Validates `Authorization: Bearer {token}` against `config('services.content_api.key')`. Returns 401 JSON on failure. Reusable across all Content API routes. |
| `Application/Middleware/VerifyN8nWebhookSecret.php` | UNCHANGED | — | Newsletter webhook auth. Not affected. |

---

## 10. Routes

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Routes/api.php` | NEW | 4 | Module-level API routes. Registers all Content API endpoints under `/api/content/articles` with Bearer token middleware and rate limiting. |
| `Routes/web.php` | UNCHANGED | — | Web routes. Not affected. |

---

## 11. Providers

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Providers/ContentServiceProvider.php` | MODIFY | 4 | Add API route registration in `boot()`. Load `Routes/api.php` with `api` middleware group and `/api/content` prefix. Register `VerifyContentApiToken` middleware alias. |

---

## 12. Configuration (outside module)

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `config/services.php` | MODIFY | 4 | Add `content_api.key` entry (can alias or extend `article_api.key`). |
| `routes/api.php` (global) | MODIFY | 6 | Add deprecation comment to old `POST /api/content/articles` route pointing to new module route. |

---

## 13. Tests

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `tests/Feature/Content/ContentApiCrudTest.php` | NEW | 7 | Feature tests for create, read, list, update, patch, delete, restore. |
| `tests/Feature/Content/ContentApiLifecycleTest.php` | NEW | 7 | Feature tests for publish, unpublish, schedule, archive transitions. |
| `tests/Feature/Content/ContentApiFilterSortTest.php` | NEW | 7 | Feature tests for filtering, sorting, and pagination. |
| `tests/Unit/Content/ArticleLifecycleServiceTest.php` | NEW | 7 | Unit tests for status transition rules. |
| `tests/Unit/Content/ArticleSlugServiceTest.php` | NEW | 7 | Unit tests for slug generation and uniqueness. |
| `tests/Feature/Content/ArticleApiTest.php` | UNCHANGED | — | Existing tests for legacy endpoint. Preserved. |

---

## 14. Documentation

| File | Status | Phase | Description |
|------|--------|-------|-------------|
| `Documentation/content-api/00-discovery-summary.md` | NEW | 8 | This documentation set |
| `Documentation/content-api/01-execution-plan.md` | NEW | 8 | |
| `Documentation/content-api/02-api-specification.md` | NEW | 8 | |
| `Documentation/content-api/03-markdown-content-contract.md` | NEW | 8 | |
| `Documentation/content-api/04-agent-integration-guide.md` | NEW | 8 | |
| `Documentation/content-api/05-domain-impact-analysis.md` | NEW | 8 | This file |
| `Documentation/content-api/06-validation-rules.md` | NEW | 8 | |
| `Documentation/content-api/07-testing-strategy.md` | NEW | 8 | |
| `Documentation/content-api/08-open-questions-and-decisions.md` | NEW | 8 | |

---

## Summary

| Category | New Files | Modified Files | Unchanged |
|----------|-----------|----------------|-----------|
| Migrations | 1 | 0 | 2 |
| Models | 0 | 1 | 3 |
| Domain Services | 2 | 0 | 5 |
| Domain Exceptions | 2 | 0 | 0 |
| Controllers | 1 | 1 | 3 |
| App Services | 1 | 0 | 4 |
| Form Requests | 4 | 0 | 3 |
| Resources | 2 | 0 | 0 |
| Middleware | 1 | 0 | 1 |
| Routes | 1 | 0 | 1 |
| Providers | 0 | 1 | 0 |
| Config (outside) | 0 | 2 | 0 |
| Tests | 5 | 0 | 1 |
| Documentation | 9 | 0 | 3 |
| **Total** | **29** | **5** | **26** |

---

## Dependency Graph

```
Phase 1: Migration
  └── Phase 2: Domain Services + Exceptions
        └── Phase 3: Controller + CRUD Service + Requests + Resources
              └── Phase 4: Routes + Middleware + Provider + Config
                    └── Phase 5: Filtering/Sorting (extends ListArticlesRequest + index())
                          └── Phase 6: Legacy deprecation
                                └── Phase 7: Tests
                                      └── Phase 8: Documentation (this set — written first as planning)
```

Each phase depends on the previous. No phase can start before its dependency is complete.

---

*Previous: [04-agent-integration-guide.md](./04-agent-integration-guide.md)*
*Next: [06-validation-rules.md](./06-validation-rules.md)*
