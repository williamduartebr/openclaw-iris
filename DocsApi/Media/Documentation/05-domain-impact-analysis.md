# 05 — Domain Impact Analysis

## Module Boundaries

### `src/Media` (New Module)

**Owns**:
- AI image generation orchestration
- Provider abstraction and integration
- Image generation request lifecycle
- Original file storage (S3 temp)
- Compression dispatch
- Processing status tracking
- Media asset metadata persistence
- Final processed URL resolution
- Media API endpoints

**Does NOT own**:
- Image compression execution (owned by `image-compactor` microservice)
- Compression result consumption infrastructure (owned by `src/Shared`)
- Tenant/user context (system-level operations)
- Content semantics (articles, categories — owned by `src/Content`)

### `src/Content` (Existing Module — Unchanged)

**Current ownership** (remains unchanged):
- Article content management
- Article image references (cover_image_url, gallery_image_urls)
- `ArticleImageStorageService` for WordPress migration uploads

**Future integration point**:
- `src/Content` may store `final_url` from Media in article fields
- No code changes required in Content for this implementation
- Content will consume Media URLs as plain strings

### `src/Shared` (Existing Module — Minor Update)

**Modified file**: `src/Shared/Infrastructure/Console/ConsumeImageCompressionResults.php`

**Change**: Add `case 'media_asset':` handler to the entity type router.

**Impact**: Minimal. The consumer already handles multiple entity types. Adding one more follows the established pattern exactly.

### `src/UserArea` (Existing Module — Unchanged)

No changes. The ticket attachment flow continues to work independently.

### `src/CompanyProfiles` (Existing Module — Unchanged)

No changes. Draft/profile media flows continue independently.

## Database Impact

### New Table: `media_assets`

```sql
CREATE TABLE media_assets (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status          VARCHAR(30) NOT NULL DEFAULT 'pending',
    provider        VARCHAR(50) NOT NULL,
    model           VARCHAR(100) NOT NULL,
    prompt          TEXT NOT NULL,
    negative_prompt TEXT NULL,
    width           INT UNSIGNED NULL,
    height          INT UNSIGNED NULL,
    style           VARCHAR(50) NULL,
    quality         VARCHAR(50) NULL,
    original_path   VARCHAR(500) NULL,
    processed_path  VARCHAR(500) NULL,
    mime_type       VARCHAR(100) NULL,
    file_size       BIGINT UNSIGNED NULL,
    original_size   BIGINT UNSIGNED NULL,
    compression_ratio DECIMAL(6,4) NULL,
    failure_reason  TEXT NULL,
    metadata        JSON NULL,
    orchestrator_context JSON NULL,
    provider_metadata JSON NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,

    INDEX idx_media_assets_status (status),
    INDEX idx_media_assets_provider (provider),
    INDEX idx_media_assets_created_at (created_at)
);
```

**No foreign keys** — The Media module is standalone with no cross-module FK references.

## Configuration Impact

### New File: `config/media.php`

Contains provider configuration, processing defaults, and storage settings.

### Modified File: `config/services.php`

Add:
```php
'media_api' => [
    'key' => env('MEDIA_API_KEY'),
],
```

## Environment Variables

### New Variables

| Variable | Purpose | Example |
|----------|---------|---------|
| `MEDIA_API_KEY` | Bearer token for Media API | `mv-media-...` |
| `MEDIA_DEFAULT_PROVIDER` | Default image generation provider | `openai` |
| `MEDIA_OPENAI_MODEL` | Default OpenAI model | `gpt-image-1` |
| `MEDIA_GEMINI_MODEL` | Default Gemini model | `gemini-2.0-flash-exp` |
| `GOOGLE_GEMINI_API_KEY` | Google Gemini API key | — |

### Existing Variables (Reused)

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY` | OpenAI API key (already exists for Content AI generation) |
| `AWS_*` | S3 credentials (already configured) |
| `RABBITMQ_*` | RabbitMQ connection (already configured) |

## Queue Impact

### New Job: `GenerateMediaImage`

- **Queue**: `media` (new queue name, configurable)
- **Connection**: Default Laravel queue connection
- **Timeout**: 180 seconds (image generation can be slow)
- **Retries**: 1 (failures handled at application level via API retry)

### Existing Job: `DispatchImageCompression`

- No changes to the job itself
- New `entityType` value: `media_asset`
- New entity type routed in consumer

## S3 Storage Impact

### New Paths

| Path | Purpose | Lifecycle |
|------|---------|-----------|
| `temp/Media/{id}/{uuid}.{ext}` | Original generated image | Deleted after successful compression |
| `Media/{id}/{uuid}.webp` | Final processed image | Permanent |

### Storage Estimate

Assuming 100 images/day:
- Temp: ~500 MB/day (cleaned up after compression)
- Final: ~100 MB/day (WebP compressed)
- Monthly: ~3 GB permanent storage

## Risk Assessment

| Risk | Severity | Mitigation |
|------|----------|------------|
| Provider API costs | Medium | Config-driven limits, monitoring via metadata |
| S3 temp file accumulation | Low | Temp files deleted by compactor consumer |
| RabbitMQ message loss | Low | Persistent delivery mode, reprocess API available |
| Provider rate limits | Medium | Retry with backoff in provider layer |
| Large image generation latency | Low | Async job design, 202 response pattern |

## Rollback Plan

1. Remove `MediaServiceProvider` from `bootstrap/providers.php`
2. Remove `case 'media_asset':` from consumer
3. Remove `config/media.php`
4. Remove `media_api` from `config/services.php`
5. Rollback migration: `php artisan migrate:rollback` (drops `media_assets` table)
6. Delete `src/Media/` directory

No other modules are affected by removal.
