# 08 — Open Questions and Decisions

## Purpose

Track all architectural decisions made during planning and flag open questions that require owner input before or during implementation.

---

## Decisions Made

### D1: Status model — Enum column vs boolean

**Decision**: Add a `status` enum column (`draft`, `review`, `scheduled`, `published`, `archived`) alongside the existing `is_published` boolean.

**Rationale**: The existing `is_published` boolean cannot represent the full lifecycle (draft, review, scheduled, archived). A string enum column provides the expressiveness needed. The `is_published` field is preserved for backward compatibility — web views, SEO services, and scopes all depend on it. The new `status` column is the canonical field for the API; `is_published` is kept in sync via model events.

**Alternative rejected**: Removing `is_published` and migrating all consumers to `status`. This was rejected due to high blast radius across Blade views, scopes, and other modules that reference `is_published`.

---

### D2: Soft deletes — Yes

**Decision**: Add `SoftDeletes` trait and `deleted_at` column to articles.

**Rationale**: Agents must not be able to permanently destroy content. Soft deletes provide safety with recoverability. Hard delete is intentionally not exposed through the API.

**Alternative rejected**: No delete endpoint at all. Rejected because agents need the ability to remove content, and hiding (soft delete) is the safe middle ground.

---

### D3: Optimistic locking — Version column

**Decision**: Add a `version` integer column (default 1) for optimistic concurrency control. All update and patch operations require the current version.

**Rationale**: Multiple agents may operate on the same article. Without version control, a slow agent could silently overwrite a fast agent's changes. The `version` field prevents this with minimal overhead. HTTP 409 signals a conflict, and the agent must re-read and retry.

**Alternative rejected**: Pessimistic locking (database-level locks). Rejected because API consumers are stateless HTTP clients — holding locks across requests is impractical.

**Alternative rejected**: `updated_at` timestamp comparison. Rejected due to clock skew risks and lower precision than an integer counter.

---

### D4: Media storage — JSON columns, not related tables

**Decision**: Store `gallery_image_urls` and `video_urls` as JSON array columns on the `articles` table.

**Rationale**: The media data is simple (arrays of URL strings), tightly coupled to a single article, and does not need independent querying or joining. JSON columns keep the schema simple and avoid the overhead of pivot tables for a one-to-one relationship. The existing `featured_image` (string column) is preserved; the new API exposes it as `cover_image_url`.

**Alternative rejected**: Separate `article_media` table with polymorphic type. Rejected as over-engineering for simple URL arrays.

---

### D5: SEO fields — Dedicated columns, not nested in `meta` JSON

**Decision**: Add `seo_title` (max 70) and `seo_description` (max 160) as dedicated string columns. The existing `meta` JSON field (which stores `{description, keywords}`) is preserved but the API uses the new columns as primary.

**Rationale**: Dedicated columns enable database-level length validation, indexing, and simpler query access. The `meta` field remains for backward compatibility and for any additional metadata not covered by dedicated columns. The API response maps `seo_title` and `seo_description` from the new columns.

**Migration note**: Existing `meta.description` values should be copied to `seo_description` during migration for data continuity.

---

### D6: Authentication — Bearer token (reuse pattern)

**Decision**: Use the same Bearer token authentication pattern as the existing `POST /api/content/articles` endpoint. Token stored in `config('services.content_api.key')`.

**Rationale**: The project does not use Sanctum, Passport, or JWT. Adding a full auth framework for a single API consumer (agents) would be over-engineering. The existing pattern is simple, proven, and sufficient for machine-to-machine authentication.

**Security note**: The token grants full CRUD access to all articles. There are no per-article or per-operation permission scopes. This is acceptable because the API consumers are trusted internal agents (OpenClaw), not arbitrary third-party clients.

---

### D7: Route location — Module `Routes/api.php`

**Decision**: Create `src/Content/Routes/api.php` and register it in `ContentServiceProvider`, following the CompanyProfiles module pattern.

**Rationale**: DDD convention requires each module to own its routes. The existing article creation endpoint in the global `routes/api.php` will be deprecated and eventually removed.

---

### D8: Response format — JsonResource classes

**Decision**: Use Laravel `JsonResource` and `ResourceCollection` classes for all API responses.

**Rationale**: The existing codebase uses manual arrays for API responses. For a comprehensive CRUD API with many fields, Resources provide consistent transformation, conditional relationship loading, and pagination integration. This is the standard Laravel approach for APIs with more than trivial response shapes.

---

### D9: Field naming — `body_md` in API, `content` in database

**Decision**: The API uses `body_md` as the field name for article content. The database column remains `content`. The `ArticleResource` maps between them.

**Rationale**: `body_md` explicitly communicates the expected format (Markdown) to API consumers. The database column name `content` is generic and also stores legacy HTML — renaming it would require migrating all database references. The API layer provides the semantic naming.

---

### D10: Slug lookup — Via list search, not path parameter

**Decision**: Articles are identified by numeric `id` in API paths. Slug lookup is done via `GET /api/content/articles?search={slug}`.

**Rationale**: Slugs are mutable (can be updated). Using slugs in URL paths creates ambiguity when a slug changes. Numeric IDs are immutable and unambiguous. The `search` parameter on the list endpoint supports slug-based lookup without adding a second route.

**Alternative considered**: `GET /api/content/articles/by-slug/{slug}`. Rejected to keep the route set simple. May be added later if agents find the search approach cumbersome.

---

### D11: Legacy endpoint deprecation — Gradual

**Decision**: The existing `POST /api/content/articles` (via `ArticleApiController`) continues to work with a `Deprecated` response header. It is not immediately removed.

**Rationale**: External automation (N8N workflows, Claude Code article-generator agent) may still reference the old endpoint. A deprecation period allows migration without breaking existing integrations.

---

### D12: `body_md` minimum length — 500 characters

**Decision**: The `body_md` field requires a minimum of 500 characters.

**Rationale**: This prevents agents from creating stub articles or placeholders that would degrade content quality if accidentally published. 500 characters is approximately 2-3 short paragraphs — enough to be meaningful. The AI generation pipeline typically produces 2,000+ characters.

---

### D13: No hard delete via API

**Decision**: The API provides only soft delete. Hard (permanent) delete is not available.

**Rationale**: Agents should not be able to permanently destroy content. Recovery must always be possible. Administrative hard delete can be done via Artisan commands or database operations by human operators.

---

### D14: Category management — Out of scope

**Decision**: The Content API manages articles only. Category CRUD is not included.

**Rationale**: Categories are a stable, small dataset managed by administrators. Agents do not need to create or modify categories — only reference them via `category_slug`. A separate Category API could be added later if needed.

---

### D15: Media responsibility boundary — Content is a consumer, not a producer

**Decision**: `src/Content` accepts, validates, and associates media URLs with articles. It does **not** generate, transform, compress, or upload media.

**Rationale (owner-confirmed)**:
- Image generation (OpenAI DALL-E, etc.), WebP compression, and S3 upload belong to external pipelines or dedicated modules
- `src/Content` is a **consumer of final assets** — it stores URLs pointing to already-hosted media
- The existing `ArticleImageStorageService` (S3 upload + RabbitMQ compression) remains available for the legacy endpoint but is not used by the new Content API
- Media may originate from: OpenAI image generation, other AI providers, internal upload pipeline, or pre-hosted CDN/S3 assets

**What `src/Content` does with media**:
- Accepts `cover_image_url`, `gallery_image_urls`, `video_urls` as URL strings
- Validates URL format (https, known patterns)
- Stores URLs in database columns
- Returns URLs in API responses

**What `src/Content` does NOT do**:
- Upload files to S3
- Compress or convert images
- Generate images via AI
- Fetch or verify URL reachability

---

## Open Questions

### Q1: Should `content_api.key` reuse `article_api.key` or be a separate secret?

**Context**: The existing article creation endpoint uses `config('services.article_api.key')`. The new API could reuse the same key (simpler) or use a separate key (better security isolation).

**Decision**: Use a **separate `CONTENT_API_KEY`** environment variable, stored in `config('services.content_api.key')`.

**Rationale (owner-confirmed)**:
- Clearer separation of responsibility between legacy and new API
- Easier rotation and revocation per API surface
- Better security posture for future per-agent permission scopes
- Improved auditability for OpenClaw and other LLM integrations

**Status**: Resolved.

---

### Q2: Should the `meta` JSON field be exposed in the API response?

**Context**: The `meta` field stores `{description, keywords}`. The new API has dedicated `seo_title` and `seo_description` fields. Should `meta` also be exposed as a raw JSON field for forward compatibility?

**Recommendation**: Expose `meta` as read-only in the API response. Do not allow writes to `meta` through the new API — use the dedicated SEO fields instead. This preserves backward compatibility without creating two sources of truth for SEO data.

**Status**: Pending owner decision.

---

### Q3: Should `reading_time` be auto-calculated or agent-provided?

**Context**: The current system lets agents set `reading_time` manually. It could also be auto-calculated from `body_md` word count (average reading speed: 200-250 words per minute).

**Recommendation**: Auto-calculate by default. Allow agents to override with an explicit value. If `reading_time` is provided, use it. If omitted, calculate from `body_md`.

**Status**: Pending owner decision.

---

### Q4: Should slug changes on published articles be blocked or warned?

**Context**: Changing a slug on a published article breaks existing URLs and SEO rankings. The API could block slug changes on published articles, warn via response header, or allow silently.

**Recommendation**: Allow slug changes but include a `Warning` response header: `Warning: 199 - "Slug changed on published article. Previous URL will return 404."`. Do not block — agents may have valid reasons.

**Status**: Pending owner decision.

---

### Q5: Should the migration copy `meta.description` to `seo_description`?

**Context**: Existing articles store SEO descriptions in `meta.description`. The migration adds a `seo_description` column. Should the migration copy existing values?

**Decision**: Yes — **copy `meta.description` to `seo_description` during the migration**.

**Rules (owner-confirmed)**:
- Only populate `seo_description` when it is null or absent
- Never overwrite an already-populated `seo_description`
- Preserve backward compatibility during the transition
- This behavior is documented explicitly in the migration code

**Status**: Resolved.

---

### Q6: Rate limiting — Should read and write limits be separate?

**Context**: The plan proposes 60 req/min for reads and 30 req/min for writes. These could be unified or kept separate.

**Recommendation**: Keep separate. Reads are cheaper and agents may need to list/search frequently before making writes. A unified limit would penalize reads unnecessarily.

**Status**: Pending owner decision.

---

### Q7: Should the scheduled article auto-publish job be created now?

**Context**: The `scheduled` status implies that articles should auto-publish when `published_at` arrives. This requires a scheduled Artisan command (e.g., `content:publish-scheduled`) running every minute.

**Decision**: Yes — **include scheduled auto-publish in scope**.

**Implementation (owner-confirmed)**:
- A simple Artisan command `content:publish-scheduled` that queries `status = 'scheduled' AND published_at <= now()` and transitions each to `published`
- Registered in `routes/console.php` on a regular schedule (every minute)
- First version is simple and reliable — no queues/events unless already consistent with repository patterns
- Documented scheduler/cron expectations

**Status**: Resolved.

---

### Q8: Should the API support `include` parameters for relationships?

**Context**: Some REST APIs allow `?include=categories,comments` to eager-load relationships. This adds flexibility but also complexity.

**Recommendation**: For the first version, always include `category` (single) and `categories` (array) in single-resource responses. Do not support dynamic `include` parameters. Add later if needed.

**Status**: Decision made — no dynamic includes in v1.

---

## Audit and Traceability

### Current state

The project has no audit logging for content changes. The only traceability comes from `created_at`, `updated_at`, and `version`.

### Recommendation for the future

After the API is stable, consider adding:

1. **Activity log**: Record who (which agent/token) performed each operation, what changed, and when. Libraries like `spatie/laravel-activitylog` are a good fit.
2. **Request logging**: Log all API requests with token identifier, endpoint, and response status for security monitoring.
3. **Version history**: Store previous versions of `body_md` for rollback capability.

These are **not in scope for the initial implementation** but are recommended as follow-up work.

---

## Security Assumptions

1. **All agents are trusted**: The Bearer token grants full access. There are no role-based restrictions within the API.
2. **Token rotation**: Not automated. The token must be rotated manually by changing the environment variable.
3. **No per-article permissions**: Any agent with the token can modify any article.
4. **No IP allowlisting**: The API does not restrict by source IP.
5. **Rate limiting is the primary abuse prevention**: 30 writes/min prevents runaway agents from flooding the database.
6. **HTTPS is required in production**: The token is transmitted in plain text in the Authorization header. HTTPS is mandatory.

---

*Previous: [07-testing-strategy.md](./07-testing-strategy.md)*

---

## Document Index

| # | Document | Purpose |
|---|----------|---------|
| 00 | [Discovery Summary](./00-discovery-summary.md) | Current state analysis of src/Content |
| 01 | [Execution Plan](./01-execution-plan.md) | Phased implementation roadmap |
| 02 | [API Specification](./02-api-specification.md) | Endpoint contracts, schemas, status codes |
| 03 | [Markdown Content Contract](./03-markdown-content-contract.md) | body_md format, supported syntax, editing rules |
| 04 | [Agent Integration Guide](./04-agent-integration-guide.md) | Step-by-step workflows for OpenClaw agents |
| 05 | [Domain Impact Analysis](./05-domain-impact-analysis.md) | File-by-file implementation impact map |
| 06 | [Validation Rules](./06-validation-rules.md) | Field validation, state transitions, constraints |
| 07 | [Testing Strategy](./07-testing-strategy.md) | Complete test plan with 104 test cases |
| 08 | [Open Questions and Decisions](./08-open-questions-and-decisions.md) | This file — decisions log and open items |
