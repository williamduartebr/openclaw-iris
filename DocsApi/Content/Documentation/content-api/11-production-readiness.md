# Content API — Production Readiness Review

## Production Checklist

| Area | Status | Notes |
|------|--------|-------|
| CRUD endpoints | Ready | All 12 endpoints implemented and tested |
| Authentication (Bearer token) | Ready | `VerifyContentApiToken` middleware, `CONTENT_API_KEY` env var |
| Input validation | Ready | 4 FormRequest classes with full field validation |
| Optimistic concurrency | Ready | Version field, 409 on conflict |
| Soft deletes | Ready | SoftDeletes trait, restore endpoint |
| Status state machine | Ready | 5 statuses, transition map enforced in domain service |
| Slug auto-generation | Ready | Transliteration, truncation to 80 chars, uniqueness with suffix |
| JSON API resources | Ready | Full and collection resources, field mapping |
| Rate limiting | Ready | 60/min reads, 30/min writes (Laravel throttle) |
| Database indexes | Ready | `status`, `featured`, `deleted_at` indexed |
| Automated tests | Ready | 80 tests, 160 assertions across 5 files |
| Legacy endpoint compatibility | Ready | Old route moved to `/api/content/articles/legacy` |
| Scheduled auto-publish | Partially ready | Command exists and is scheduled. Needs monitoring in production to confirm cron/scheduler runs reliably |
| API key rotation | Pending | No rotation mechanism. Changing `CONTENT_API_KEY` requires env update + app restart. No key expiration or multiple active keys |
| Request/response logging | Pending | No dedicated API audit log. Standard Laravel log only |
| Observability | Pending | No metrics, no structured logging for API calls, no alerting |
| Soft-delete cleanup policy | Pending | No automatic purge of soft-deleted records. Will accumulate indefinitely |
| CORS configuration | Partially ready | Uses global Laravel CORS config. No Content API-specific CORS rules |
| Input sanitization (XSS) | Ready | Markdown stored raw, rendered server-side with sanitization on output |
| SQL injection | Ready | All queries use Eloquent/query builder with parameter binding |

## Production-Readiness Gaps (Detail)

### 1. API Key Rotation (Pending)

**Current state:** Single static key from `CONTENT_API_KEY` env var. No expiration, no multi-key support.

**Risk:** If the key is compromised, rotation requires deployment. During rotation, all active agents lose access simultaneously.

**Recommendation:** For Phase 2, consider a `content_api_tokens` table with created_at, expires_at, revoked_at. Allow 2 active keys during rotation windows.

### 2. Request Logging / Audit Trail (Pending)

**Current state:** No dedicated logging for API operations. Failed auth attempts produce standard 401 responses with no additional logging.

**Risk:** No visibility into who created/modified/deleted articles, or detection of abuse patterns.

**Recommendation:** Add a lightweight middleware that logs method, path, status code, article ID, and action to a dedicated log channel or `content_api_audit_log` table.

### 3. Observability (Pending)

**Current state:** No metrics collection for API performance, error rates, or throughput.

**Recommendation:** Instrument key operations (create, publish, delete) with Laravel events or a metrics package. At minimum, log response times for slow queries (>500ms).

### 4. Soft-Delete Cleanup (Pending)

**Current state:** Soft-deleted articles remain in the database indefinitely.

**Risk:** Table growth over time. No impact on queries (SoftDeletes scope excludes them), but storage grows.

**Recommendation:** Add a scheduled command `content:purge-deleted` that permanently removes articles soft-deleted more than 90 days ago. Run weekly.

### 5. Scheduled Publisher Monitoring (Partially Ready)

**Current state:** `content:publish-scheduled` runs every minute via Laravel scheduler. Works correctly in tests and manual runs.

**Risk:** If the scheduler stops (cron failure, container restart), scheduled articles silently miss their publish time.

**Recommendation:** Add a heartbeat check or log entry each run. Alert if no heartbeat for 5+ minutes.

### 6. Missing Database Indexes (Low Priority)

The following columns are frequently queried but lack dedicated indexes:

- `articles.published_at` — used by scheduled publisher and sort queries
- `articles.category_id` — used in filter queries (likely already indexed via FK)

**Recommendation:** Add index on `published_at` if query performance degrades with table growth.

## Security Assessment

| Vector | Status |
|--------|--------|
| Authentication bypass | Protected — bearer token required on all routes |
| SQL injection | Protected — Eloquent parameter binding |
| Mass assignment | Protected — explicit `$fillable` whitelist |
| XSS via Markdown | Protected — raw storage, sanitized on render |
| Rate limiting | Protected — 30-60 req/min per IP |
| IDOR (accessing other articles) | N/A — API is service-to-service, no user-scoped data |
| Timing attacks on token comparison | Low risk — PHP string comparison, not constant-time. Acceptable for service tokens |

No critical security gaps found.

---

## Future Boundary: `src/Media` Integration

`src/Content` consumes final media URLs only. It stores:

- `cover_image_url` (string) — previously `featured_image`
- `gallery_image_urls` (JSON array of URLs)
- `video_urls` (JSON array of URLs)

All media generation, upload, compression, format conversion (e.g., WebP), CDN placement, and URL signing are **out of scope** for `src/Content`.

When `src/Media` is implemented:

1. **`src/Media`** owns the full media pipeline: upload → validate → compress → store → return final URL
2. **`src/Content`** receives and stores only the final URLs returned by `src/Media`
3. **Integration point:** agents call `src/Media` API first to get URLs, then pass those URLs to `src/Content` API when creating/updating articles
4. **No direct coupling:** `src/Content` must never import or depend on `src/Media` classes. The contract is URLs only.

This separation ensures that media processing can evolve independently (new formats, CDN providers, compression algorithms) without impacting the Content API contract.
