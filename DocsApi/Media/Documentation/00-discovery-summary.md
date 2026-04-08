# 00 — Discovery Summary

## Overview

This document captures the codebase patterns, conventions, and integration points discovered during analysis of the MercadoVeiculos repository, specifically relevant to the new `src/Media` module.

## Module Architecture

MercadoVeiculos uses DDD-Simplified with independent modules under `src/`. Each module has:

```
src/{Module}/
├── Application/     (Controllers, Actions, Requests, Services, Middleware)
├── Domain/          (Models, Services, ValueObjects, Enums, Exceptions)
├── Infrastructure/  (Repositories, ExternalServices, Database/)
├── Presentation/    (Resources/views or js/Pages)
├── Providers/       ({Module}ServiceProvider.php)
├── Routes/          (web.php, api.php)
└── Documentation/   (README.md)
```

**Reference module**: `src/VehicleDataCenter/`

## S3 Integration Pattern

- **Disk**: `s3` configured in `config/filesystems.php`
- **Upload method**: `Storage::disk('s3')->putFileAs($dir, $file, $name, ['visibility' => 'private'])`
- **Path convention**: `{Module}/{Entity}/{entityId}/{type}/{filename}`
- **Temp path convention**: `temp/{Module}/{Entity}/{entityId}/{filename}`
- **URL generation**: `Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30))`

### Existing S3 paths per module

| Module | Temp Path | Final Path |
|--------|-----------|------------|
| UserArea (Tickets) | `temp/Support/Ticket/{id}/` | `Support/Ticket/{id}/` |
| Content (Articles) | `temp/Content/Article/{id}/{type}/` | `Content/Article/{id}/{type}/` |
| CompanyProfiles (Drafts) | `temp/CompanyProfiles/Draft/{id}/{type}/` | `CompanyProfiles/Draft/{id}/{type}/` |
| CompanyProfiles (Profiles) | `temp/CompanyProfiles/Profile/{id}/{type}/` | `CompanyProfiles/Profile/{id}/{type}/` |

## Image Compression Pipeline

### Architecture

```
Application uploads to S3 temp path
    → Dispatches DispatchImageCompression job (Laravel queue)
        → Job connects to RabbitMQ directly via AMQP
            → Publishes to exchange: 'image.processing' (routing key: 'image.compress')
                → External Python microservice 'image-compactor' consumes
                    → Compresses to WebP (82% quality, configurable)
                    → Uploads result to final S3 path
                    → Publishes result to queue: 'image.processed'
                        → Laravel command 'image-compactor:consume' reads results
                            → Updates database record with compressed metadata
                            → Deletes temp file from S3
```

### Key Files

| Component | Path |
|-----------|------|
| Dispatch Job | `src/Shared/Application/Jobs/DispatchImageCompression.php` |
| Result Consumer | `src/Shared/Infrastructure/Console/ConsumeImageCompressionResults.php` |
| Queue Config | `config/queue.php` (image_compactor section) |

### DispatchImageCompression Constructor

```php
public function __construct(
    public readonly string $jobId,       // UUID
    public readonly int $tenantId,
    public readonly string $entityType,  // 'ticket_attachment', 'article_image', etc.
    public readonly int $entityId,
    public readonly array $files,        // Array of file descriptors
    public readonly string $source = 'mercadoveiculos',
)
```

### File Descriptor Shape

```php
[
    'file_id'       => string,     // Attachment/entity ID
    'source_path'   => string,     // S3 temp path
    'target_path'   => string,     // S3 final path (.webp)
    'original_name' => string,
    'mime_type'     => string,
    'options'       => [
        'quality'        => 82,
        'max_width'      => 1920,
        'max_height'     => 1080,
        'format'         => 'webp',
        'strip_metadata' => true,
    ],
]
```

### Result Consumer Entity Types

The `ConsumeImageCompressionResults` command routes by `entity_type`:

- `ticket_attachment` → Updates `TicketAttachment` model
- `profile_draft_media` → Updates `ProfileDraft` media JSON
- `article_image` → Updates article content/featured_image
- **`media_asset`** → *New entity type to be added for the Media module*

### Result Payload Shape

```php
[
    'job_id'            => string,
    'entity_type'       => string,
    'entity_id'         => int,
    'status'            => 'success' | 'failed',
    'files'             => [
        [
            'status'            => 'success' | 'failed',
            'file_id'           => string,
            'source_path'       => string,
            'target_path'       => string,
            'compressed_size'   => int,
            'original_size'     => int,
            'compression_ratio' => float,
            'error'             => string|null,
        ],
    ],
]
```

## API Route Patterns

### Authentication

The project uses **Bearer token authentication** for agent-facing APIs:

- Middleware: `VerifyContentApiToken` validates `Authorization: Bearer {token}`
- Token source: `config('services.content_api.key')` from env `CONTENT_API_KEY`
- FormRequests also validate token in `authorize()` method

### Route Registration

Routes are registered in the module's ServiceProvider:

```php
Route::middleware([VerifyApiTokenMiddleware::class])
    ->prefix('api/{module}')
    ->group(__DIR__.'/../Routes/api.php');
```

### Response Format

- Standard `JsonResource` classes for single and collection responses
- HTTP status codes: 200, 201, 404, 409, 422
- ISO 8601 timestamps
- Paginated collections for list endpoints

### Throttling

- Read endpoints: `throttle:60,1`
- Write endpoints: `throttle:30,1`
- Webhook endpoints: `throttle:120,1`

## RabbitMQ Configuration

| Setting | Value | Env Variable |
|---------|-------|-------------|
| Host | `rabbitmq` | `RABBITMQ_HOST` |
| Port | `5672` | `RABBITMQ_PORT` |
| User | `compactor` | `RABBITMQ_USER` |
| Pass | `secret` | `RABBITMQ_PASS` |
| VHost | `/` | `RABBITMQ_VHOST` |
| Exchange | `image.processing` | Hardcoded |
| Dispatch Routing Key | `image.compress` | Hardcoded |
| Result Queue | `image.processed` | `RABBITMQ_QUEUE` |

## Existing Provider/API Integration Patterns

### Content Module AI Generation

The Content module integrates with Claude and OpenAI for **text** generation via:

- `config/services.php` keys
- ExternalService classes in `Infrastructure/ExternalServices/`
- Provider selection in service layer

### Stripe Integration

Uses raw SDK, not Cashier. Key pattern:
- Config in `config/services.php`
- ExternalService class handles API calls
- Webhook controller handles async results
- Status tracking with dedicated model

## Key Decisions from Discovery

1. **Reuse `DispatchImageCompression`** — The shared job is designed for multi-entity-type dispatch. Adding `media_asset` is straightforward.
2. **Add result handler to consumer** — The `ConsumeImageCompressionResults` command needs a new `case 'media_asset':` branch.
3. **Follow Content API auth pattern** — Bearer token middleware, consistent with OpenClaw agent access.
4. **Follow S3 path conventions** — `temp/Media/{assetId}/` and `Media/{assetId}/` for final.
5. **Use JsonResource** — For consistent API response formatting.
6. **Config-driven provider setup** — `config/media.php` for providers, API keys, defaults.
