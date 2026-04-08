# 08 — Open Questions and Decisions

## Decisions Made

### D1: Tenant Context

**Decision**: Media assets are **system-level** (tenantId = 0).

**Rationale**: The Media module serves OpenClaw orchestrators and agents, not tenant-bound users. Image generation is a platform capability, not a per-tenant feature. If tenant attribution is needed later, the `metadata` JSON field can carry tenant references.

### D2: Single Model vs Separate Job Model

**Decision**: Use a **single `MediaAsset` model** for both the asset and its processing state.

**Rationale**: The processing lifecycle is tightly coupled to the asset. A separate `MediaJob` model would add complexity without clear benefit. Status, timing, and failure tracking all live on the asset itself. If batch operations are needed later, a `MediaBatch` model can wrap multiple assets.

### D3: Synchronous vs Asynchronous Generation

**Decision**: Generation is **asynchronous** via Laravel queued job.

**Rationale**: Image generation can take 5-60 seconds depending on the provider and model. Blocking the HTTP request would cause timeouts. The API returns `202 Accepted` immediately. Callers poll for status.

### D4: Original File Retention

**Decision**: Original files in `temp/` are **deleted after successful compression** (handled by the image-compactor consumer pattern).

**Rationale**: Consistent with existing behavior for ticket attachments and article images. If audit/reprocessing needs arise, this can be changed by updating the consumer to skip cleanup.

### D5: API Authentication Method

**Decision**: **Bearer token** via environment variable, matching the Content API pattern.

**Rationale**: Consistent with existing agent-facing APIs. Simple, stateless, no OAuth complexity needed for machine-to-machine communication.

### D6: Queue Name

**Decision**: New queue name `media` for generation jobs.

**Rationale**: Isolates media generation from default queue workers. Allows independent scaling and monitoring. Compression dispatch continues to use the existing `image_compactor` queue configuration.

### D7: Provider HTTP Client

**Decision**: Use **Guzzle** directly (not OpenAI PHP SDK).

**Rationale**: Both OpenAI and Google Gemini APIs are simple REST APIs. Direct Guzzle calls avoid extra SDK dependencies and give full control over request/response handling. The project already depends on Guzzle via Laravel.

### D8: WebP as Default Output Format

**Decision**: Final output is always **WebP** via the existing compression pipeline.

**Rationale**: WebP provides the best compression-to-quality ratio. The image-compactor microservice already handles WebP conversion. This is consistent with all other modules.

## Open Questions

### Q1: Rate Limiting Strategy for Providers

**Status**: Deferred to production monitoring.

**Context**: OpenAI and Google both have rate limits on their image generation APIs. Currently, the module does not implement provider-level rate limiting.

**Options**:
1. **Application-level throttle** — Limit concurrent generation jobs per provider
2. **Provider-level backoff** — Catch 429 errors and retry with exponential backoff
3. **Queue concurrency** — Limit worker concurrency on the `media` queue

**Current approach**: Option 2 (catch and fail, retryable via API). Can add Option 3 via worker configuration.

### Q2: Cost Tracking

**Status**: Deferred.

**Context**: AI image generation has per-request costs. The `provider_metadata` JSON field can store cost information returned by providers, but there is no dedicated cost tracking or budget enforcement.

**Future option**: Add `estimated_cost` decimal column, populate from provider response metadata.

### Q3: Webhook/Callback on Completion

**Status**: Deferred.

**Context**: Currently callers must poll for status. A webhook callback mechanism would allow push notifications.

**Future option**: Add `callback_url` field to generation request. On completion, POST the asset data to the callback URL.

### Q4: Batch Generation

**Status**: Deferred.

**Context**: Generating multiple images in a single API call (e.g., 4 variations) is not supported in v1.

**Future option**: Add `POST /api/media/images/generate-batch` endpoint that creates multiple `MediaAsset` records and dispatches multiple jobs.

### Q5: Image Variation / Edit

**Status**: Deferred.

**Context**: Some providers support image editing (inpainting, outpainting) and variation generation from a reference image.

**Future option**: Add `reference_image_url` field to generation request. Provider interface already has space for this in the params array.

### Q6: Content Safety / Moderation

**Status**: Relies on provider-side moderation.

**Context**: Both OpenAI and Google enforce content policies on their APIs. The module does not add an additional moderation layer.

**Future option**: Add a pre-generation moderation check using a text classifier.

## Technical Debt Awareness

1. **Hardcoded exchange/routing keys** — The `DispatchImageCompression` job hardcodes RabbitMQ exchange and routing key names. This is an existing pattern, not introduced by Media.

2. **Consumer coupling** — `ConsumeImageCompressionResults` grows with each entity type. If more than 6-7 types are added, consider refactoring to a strategy pattern or separate consumers per entity type.

3. **No soft deletes** — `MediaAsset` does not use soft deletes. Failed assets remain in the table. Consider a cleanup job for old failed assets.
