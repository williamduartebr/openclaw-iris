# 07 — Testing Strategy

## Test Organization

```
tests/
├── Unit/
│   └── Media/
│       ├── MediaStatusTest.php
│       ├── MediaAssetModelTest.php
│       └── ProviderRegistryTest.php
└── Feature/
    └── Media/
        ├── MediaApiGenerateTest.php
        ├── MediaApiListTest.php
        ├── MediaApiReprocessTest.php
        └── MediaProcessingPipelineTest.php
```

## Unit Tests

### MediaStatusTest

Tests the `MediaStatus` enum:

- All expected values exist
- Status transition validation (allowed/disallowed transitions)
- `isFinal()` returns true for `completed` and `failed`
- `isRetryable()` returns true only for `failed`
- `isReprocessable()` returns true for `failed`, `generated`, `completed`

### MediaAssetModelTest

Tests the `MediaAsset` model:

- Fillable attributes are correct
- JSON cast fields (`metadata`, `orchestrator_context`, `provider_metadata`)
- Date cast fields (`completed_at`)
- Status enum cast
- `getFinalUrlAttribute()` returns S3 URL when `processed_path` exists
- `getFinalUrlAttribute()` returns null when `processed_path` is null
- `isCompleted()` helper method
- `isFailed()` helper method

### ProviderRegistryTest

Tests the `ProviderRegistry`:

- Registers a provider and resolves it by name
- Resolves the default provider from config
- Throws exception for unregistered provider name
- `available()` returns only providers where `isAvailable()` is true
- `all()` returns all registered providers

## Feature Tests

### MediaApiGenerateTest

Tests `POST /api/media/images/generate`:

- Returns 401 without bearer token
- Returns 401 with invalid bearer token
- Returns 422 when prompt is missing
- Returns 422 when prompt is too short
- Returns 422 when prompt is too long
- Returns 422 when invalid provider is specified
- Returns 422 when invalid model is specified for provider
- Returns 202 with valid request (mocked provider)
- Creates `MediaAsset` record in database with `pending` status
- Dispatches `GenerateMediaImage` job to queue
- Returns correct JSON structure
- Stores `metadata` and `orchestrator_context` when provided
- Uses default provider when none specified

### MediaApiListTest

Tests `GET /api/media/images`:

- Returns 401 without auth
- Returns 200 with empty list
- Returns paginated results
- Filters by status
- Filters by provider
- Sorts by created_at desc by default
- Searches in prompt text
- Respects per_page limit

### MediaApiReprocessTest

Tests `POST /api/media/images/{id}/reprocess` and `POST /api/media/images/{id}/retry`:

- Retry returns 422 when asset is not in `failed` status
- Retry resets asset to `pending` and dispatches job
- Reprocess returns 422 when asset has no `original_path`
- Reprocess dispatches compression with correct entity type
- Returns 404 for non-existent asset

### MediaProcessingPipelineTest

Tests the integration between generation and compression:

- Provider generates image → asset transitions to `generated`
- S3 upload succeeds → asset transitions to `queued_for_compaction`
- Compression result handler updates asset to `completed`
- Compression failure handler updates asset to `failed`
- Failed asset can be retried
- Completed asset can be reprocessed

## Test Utilities

### Mocking Providers

```php
$mockProvider = Mockery::mock(MediaGenerationProviderInterface::class);
$mockProvider->shouldReceive('name')->andReturn('openai');
$mockProvider->shouldReceive('isAvailable')->andReturn(true);
$mockProvider->shouldReceive('generate')->andReturn([
    'image_data' => base64_decode('iVBORw0KGgo...'),
    'mime_type'  => 'image/png',
    'width'      => 1024,
    'height'     => 1024,
    'provider_metadata' => ['model' => 'gpt-image-1'],
]);
```

### Test Environment

- Database: In-memory SQLite (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
- Queue: Sync driver (jobs execute immediately in tests)
- Storage: Fake S3 disk (`Storage::fake('s3')`)
- External APIs: Mocked via `Http::fake()` or Mockery

### Test Configuration

```php
// TestCase setUp
protected function setUp(): void
{
    parent::setUp();

    config(['services.media_api.key' => 'test-media-api-key']);
    config(['media.providers.openai.api_key' => 'test-openai-key']);
}
```

## Coverage Goals

| Area | Target |
|------|--------|
| API endpoints (auth, validation, responses) | 100% |
| Status transitions | 100% |
| Provider registry | 100% |
| Processing pipeline (mocked) | Core happy path + failure paths |
| Individual providers (real API) | Manual validation only |
