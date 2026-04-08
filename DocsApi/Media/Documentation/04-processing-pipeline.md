# 04 — Processing Pipeline

## End-to-End Flow

```
API Request
    ↓
1. Validate request (FormRequest)
    ↓
2. Create MediaAsset record [status: pending]
    ↓
3. Dispatch GenerateMediaImage job (async via Laravel queue)
    ↓
4. Job: Resolve provider from registry
    ↓
5. Job: Update status [generating]
    ↓
6. Job: Call provider.generate(params)
    ↓
7. Job: Receive raw image bytes [status: generated]
    ↓
8. Job: Upload original to S3 temp path [status: uploading]
    ↓
9. Job: Update asset with original_path, mime_type, original_size
    ↓
10. Job: Dispatch DispatchImageCompression [status: queued_for_compaction]
    ↓
11. RabbitMQ → image-compactor microservice processes image
    ↓
12. Result published to 'image.processed' queue [status: processing]
    ↓
13. ConsumeImageCompressionResults receives result
    ↓
14. Update MediaAsset with processed_path, file_size, compression_ratio
    ↓
15. [status: completed] — final_url is now available
```

## Status State Machine

```
    ┌─────────┐
    │ pending  │ ← initial state / retry resets here
    └────┬─────┘
         │
    ┌────▼──────────┐
    │  generating    │ ← provider call in progress
    └────┬──────────┘
         │
    ┌────▼──────────┐
    │  generated     │ ← provider returned image bytes
    └────┬──────────┘
         │
    ┌────▼──────────┐
    │  uploading     │ ← uploading original to S3
    └────┬──────────┘
         │
    ┌────▼──────────────────┐
    │  queued_for_compaction │ ← DispatchImageCompression dispatched
    └────┬──────────────────┘
         │
    ┌────▼──────────┐
    │  processing    │ ← image-compactor working
    └────┬──────────┘
         │
    ┌────▼──────────┐
    │  completed     │ ← final processed URL available
    └───────────────┘

    Any state → failed (on error)
    failed → pending (retry)
    failed/generated/completed → queued_for_compaction (reprocess)
```

### Valid Status Transitions

| From | To | Trigger |
|------|----|---------|
| `pending` | `generating` | Job starts provider call |
| `generating` | `generated` | Provider returns image |
| `generating` | `failed` | Provider error |
| `generated` | `uploading` | S3 upload begins |
| `uploading` | `queued_for_compaction` | S3 upload complete, compaction dispatched |
| `uploading` | `failed` | S3 upload error |
| `queued_for_compaction` | `processing` | image-compactor picks up job |
| `processing` | `completed` | Compression result received successfully |
| `processing` | `failed` | Compression failed |
| `failed` | `pending` | Retry action |
| `failed` | `queued_for_compaction` | Reprocess action |
| `generated` | `queued_for_compaction` | Reprocess action |
| `completed` | `queued_for_compaction` | Reprocess action |

## Job: GenerateMediaImage

This is a Laravel queued job that orchestrates steps 4-10.

```php
class GenerateMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 180;

    public function __construct(
        public readonly int $mediaAssetId,
    ) {}

    public function handle(
        MediaGenerationService $generationService,
    ): void {
        $generationService->processGeneration($this->mediaAssetId);
    }

    public function failed(\Throwable $exception): void
    {
        // Update asset to failed status with reason
    }
}
```

**Queue**: `media` (configurable via `config('media.queue')`)

## S3 Path Convention

| Stage | Path Pattern | Example |
|-------|-------------|---------|
| Original (temp) | `temp/Media/{assetId}/{uuid}.{ext}` | `temp/Media/42/a1b2c3d4.png` |
| Processed (final) | `Media/{assetId}/{uuid}.webp` | `Media/42/a1b2c3d4.webp` |

## Compression Dispatch Integration

The Media module reuses the existing `DispatchImageCompression` shared job:

```php
DispatchImageCompression::dispatch(
    jobId: Str::uuid()->toString(),
    tenantId: 0,  // System-level (no tenant context)
    entityType: 'media_asset',
    entityId: $asset->id,
    files: [
        [
            'file_id'       => (string) $asset->id,
            'source_path'   => $asset->original_path,
            'target_path'   => "Media/{$asset->id}/{$basename}.webp",
            'original_name' => basename($asset->original_path),
            'mime_type'     => $asset->mime_type,
            'options'       => [
                'quality'        => config('media.processing.quality', 82),
                'max_width'      => config('media.processing.max_width', 1920),
                'max_height'     => config('media.processing.max_height', 1080),
                'format'         => config('media.processing.format', 'webp'),
                'strip_metadata' => config('media.processing.strip_metadata', true),
            ],
        ],
    ],
);
```

## Result Consumer Update

The `ConsumeImageCompressionResults` command must be updated with a new case:

```php
case 'media_asset':
    $this->processMediaAsset($entityId, $files);
    break;
```

```php
private function processMediaAsset(int $assetId, array $files): void
{
    foreach ($files as $file) {
        $asset = MediaAsset::find($assetId);
        if (! $asset) {
            Log::warning("Media asset not found: {$assetId}");
            return;
        }

        if ($file['status'] === 'success') {
            $asset->update([
                'status'            => MediaStatus::Completed->value,
                'processed_path'    => $file['target_path'],
                'mime_type'         => 'image/webp',
                'file_size'         => $file['compressed_size'],
                'original_size'     => $file['original_size'],
                'compression_ratio' => $file['compression_ratio'],
                'completed_at'      => now(),
            ]);
        } else {
            $asset->update([
                'status'         => MediaStatus::Failed->value,
                'failure_reason' => $file['error'] ?? 'Compression failed',
            ]);
        }
    }
}
```

## Failure Handling

| Failure Point | Behavior |
|---------------|----------|
| Provider call fails | Asset marked `failed` with reason. Retryable via API. |
| S3 upload fails | Asset marked `failed` with reason. Retryable via API. |
| Compression dispatch fails | Asset marked `failed`. Retryable via reprocess. |
| Compression service fails | Consumer marks asset `failed`. Reprocessable via API. |
| Asset not found by consumer | Warning logged, message acked (no retry). |

## Timing Expectations

| Step | Expected Duration |
|------|-------------------|
| Provider generation | 5-60 seconds (varies by model) |
| S3 upload | 1-5 seconds |
| Compression pipeline | 5-30 seconds |
| **Total end-to-end** | **~15-90 seconds** |

The API returns `202 Accepted` immediately after creating the record. Callers poll `GET /api/media/images/{id}` to check status.
