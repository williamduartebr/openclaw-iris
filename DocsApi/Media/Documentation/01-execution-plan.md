# 01 — Execution Plan

## Module Overview

**Module**: `src/Media`
**Purpose**: AI image generation, multi-provider integration, S3 storage, image compaction pipeline, and media asset tracking.
**Consumers**: OpenClaw orchestrators and agents (programmatic API access).
**Independence**: Fully standalone. `src/Content` may consume final URLs but does not own generation/processing.

## Directory Structure

```
src/Media/
├── Application/
│   ├── Controllers/
│   │   └── MediaApiController.php
│   ├── Middleware/
│   │   └── VerifyMediaApiToken.php
│   ├── Requests/
│   │   ├── GenerateImageRequest.php
│   │   ├── ListMediaRequest.php
│   │   └── ReprocessMediaRequest.php
│   ├── Resources/
│   │   ├── MediaAssetResource.php
│   │   └── MediaAssetCollectionResource.php
│   └── Services/
│       ├── MediaGenerationService.php
│       ├── MediaStorageService.php
│       └── MediaProcessingService.php
├── Domain/
│   ├── Models/
│   │   └── MediaAsset.php
│   ├── Enums/
│   │   ├── MediaStatus.php
│   │   ├── MediaProvider.php
│   │   └── MediaType.php
│   └── Exceptions/
│       ├── ProviderUnavailableException.php
│       ├── GenerationFailedException.php
│       └── InvalidStatusTransitionException.php
├── Infrastructure/
│   ├── ExternalServices/
│   │   └── Providers/
│   │       ├── MediaGenerationProviderInterface.php
│   │       ├── OpenAiImageProvider.php
│   │       ├── GoogleGeminiImageProvider.php
│   │       └── ProviderRegistry.php
│   └── Database/
│       └── Migrations/
│           └── 2026_03_09_000001_create_media_assets_table.php
├── Providers/
│   └── MediaServiceProvider.php
├── Routes/
│   └── api.php
├── Console/
│   └── Commands/ (reserved for future CLI commands)
└── Documentation/
    ├── 00-discovery-summary.md
    ├── 01-execution-plan.md
    ├── 02-media-api-specification.md
    ├── 03-provider-architecture.md
    ├── 04-processing-pipeline.md
    ├── 05-domain-impact-analysis.md
    ├── 06-validation-rules.md
    ├── 07-testing-strategy.md
    └── 08-open-questions-and-decisions.md
```

## Implementation Phases

### Phase 1 — Domain Layer

**Files to create:**

1. `Domain/Enums/MediaStatus.php` — Status state machine (pending, generating, generated, uploading, queued_for_compaction, processing, completed, failed)
2. `Domain/Enums/MediaProvider.php` — Provider identifiers (openai, google_gemini)
3. `Domain/Enums/MediaType.php` — Asset types (generated_image)
4. `Domain/Models/MediaAsset.php` — Primary entity with all metadata
5. `Domain/Exceptions/ProviderUnavailableException.php`
6. `Domain/Exceptions/GenerationFailedException.php`
7. `Domain/Exceptions/InvalidStatusTransitionException.php`
8. `Infrastructure/Database/Migrations/2026_03_09_000001_create_media_assets_table.php`

### Phase 2 — Provider Abstraction

**Files to create:**

1. `Infrastructure/ExternalServices/Providers/MediaGenerationProviderInterface.php` — Contract
2. `Infrastructure/ExternalServices/Providers/OpenAiImageProvider.php` — OpenAI DALL-E / gpt-image adapter
3. `Infrastructure/ExternalServices/Providers/GoogleGeminiImageProvider.php` — Gemini image adapter
4. `Infrastructure/ExternalServices/Providers/ProviderRegistry.php` — Registry/factory

**Files to create (config):**

5. `config/media.php` — Provider keys, defaults, processing options

### Phase 3 — Application Services

**Files to create:**

1. `Application/Services/MediaGenerationService.php` — Orchestrates: validate → select provider → generate → store original → dispatch compaction → track status
2. `Application/Services/MediaStorageService.php` — S3 operations: upload original, build paths, get URLs
3. `Application/Services/MediaProcessingService.php` — Compaction dispatch, status transitions, result handling

### Phase 4 — API Layer

**Files to create:**

1. `Application/Middleware/VerifyMediaApiToken.php` — Bearer token validation
2. `Application/Requests/GenerateImageRequest.php` — Validation for generation
3. `Application/Requests/ListMediaRequest.php` — Validation for listing/filtering
4. `Application/Requests/ReprocessMediaRequest.php` — Validation for reprocess/retry
5. `Application/Resources/MediaAssetResource.php` — Single asset JSON
6. `Application/Resources/MediaAssetCollectionResource.php` — Collection JSON
7. `Application/Controllers/MediaApiController.php` — REST endpoints
8. `Routes/api.php` — Route definitions

### Phase 5 — Service Provider & Integration

**Files to create:**

1. `Providers/MediaServiceProvider.php` — Bindings, route loading, config

**Files to modify:**

2. `bootstrap/providers.php` — Register MediaServiceProvider
3. `config/services.php` — Add media_api.key
4. `src/Shared/Infrastructure/Console/ConsumeImageCompressionResults.php` — Add `media_asset` entity type handler

### Phase 6 — Tests

**Files to create:**

1. `tests/Unit/Media/MediaStatusTest.php`
2. `tests/Unit/Media/MediaAssetModelTest.php`
3. `tests/Unit/Media/ProviderRegistryTest.php`
4. `tests/Feature/Media/MediaApiGenerateTest.php`
5. `tests/Feature/Media/MediaApiListTest.php`
6. `tests/Feature/Media/MediaApiReprocessTest.php`
7. `tests/Feature/Media/MediaProcessingPipelineTest.php`

## Execution Order

```
1. Domain Enums (no dependencies)
2. Migration (no dependencies)
3. MediaAsset model (depends on enums + migration)
4. Domain Exceptions (no dependencies)
5. config/media.php (no dependencies)
6. Provider Interface (no dependencies)
7. OpenAI Provider (depends on interface + config)
8. Gemini Provider (depends on interface + config)
9. Provider Registry (depends on providers)
10. MediaStorageService (depends on model)
11. MediaProcessingService (depends on model + DispatchImageCompression)
12. MediaGenerationService (depends on registry + storage + processing)
13. Middleware (no dependencies)
14. Form Requests (depends on enums)
15. API Resources (depends on model)
16. Controller (depends on services + requests + resources)
17. Routes (depends on controller + middleware)
18. ServiceProvider (depends on all above)
19. Register in bootstrap/providers.php
20. Update ConsumeImageCompressionResults
21. Tests
22. End-to-end validation
```

## Files Summary

| Category | New Files | Modified Files |
|----------|-----------|----------------|
| Domain | 7 | 0 |
| Infrastructure | 5 | 0 |
| Application | 9 | 0 |
| Config | 1 | 1 |
| Routes | 1 | 0 |
| Provider | 1 | 1 |
| Shared | 0 | 1 |
| Tests | 7 | 0 |
| Documentation | 9 | 0 |
| **Total** | **40** | **3** |
