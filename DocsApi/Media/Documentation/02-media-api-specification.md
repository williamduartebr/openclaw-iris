# 02 — Media API Specification

## Authentication

All endpoints require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {MEDIA_API_KEY}
```

Token is configured via environment variable `MEDIA_API_KEY` and stored in `config('services.media_api.key')`.

## Base URL

```
/api/media
```

## Throttling

| Operation | Limit |
|-----------|-------|
| Read (GET) | 60 requests/minute |
| Write (POST) | 30 requests/minute |

---

## Endpoints

### 1. Generate Image

**`POST /api/media/images/generate`**

Creates a new image generation request and begins async processing.

#### Request Body

```json
{
    "provider": "openai",
    "model": "gpt-image-1",
    "prompt": "A modern electric car parked in front of a Brazilian dealership at sunset",
    "negative_prompt": "blurry, low quality, watermark",
    "width": 1024,
    "height": 1024,
    "style": "natural",
    "quality": "high",
    "metadata": {
        "article_id": 42,
        "usage": "cover_image",
        "campaign": "summer-2026"
    },
    "orchestrator_context": {
        "workflow_id": "wf_abc123",
        "step": "generate_hero_image"
    }
}
```

#### Field Definitions

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `provider` | string | No | Provider identifier. Default: `config('media.default_provider')` |
| `model` | string | No | Model identifier. Default: provider's default model |
| `prompt` | string | **Yes** | Image generation prompt. Min 3, max 4000 chars |
| `negative_prompt` | string | No | What to avoid. Max 1000 chars. Provider support varies |
| `width` | integer | No | Image width in pixels. Default: provider default |
| `height` | integer | No | Image height in pixels. Default: provider default |
| `style` | string | No | Style hint (e.g., `natural`, `vivid`). Provider support varies |
| `quality` | string | No | Quality hint (e.g., `standard`, `high`, `hd`). Provider support varies |
| `metadata` | object | No | Arbitrary key-value metadata for caller tracking |
| `orchestrator_context` | object | No | Orchestrator/agent workflow tracking data |

#### Response — `202 Accepted`

```json
{
    "data": {
        "id": 1,
        "status": "pending",
        "provider": "openai",
        "model": "gpt-image-1",
        "prompt": "A modern electric car...",
        "width": 1024,
        "height": 1024,
        "style": "natural",
        "quality": "high",
        "original_path": null,
        "processed_path": null,
        "final_url": null,
        "mime_type": null,
        "file_size": null,
        "original_size": null,
        "compression_ratio": null,
        "metadata": { "article_id": 42, "usage": "cover_image" },
        "orchestrator_context": { "workflow_id": "wf_abc123" },
        "failure_reason": null,
        "created_at": "2026-03-09T12:00:00.000000Z",
        "updated_at": "2026-03-09T12:00:00.000000Z",
        "completed_at": null
    }
}
```

#### Error Responses

| Code | Condition |
|------|-----------|
| 401 | Invalid or missing Bearer token |
| 422 | Validation error (missing prompt, invalid provider, etc.) |
| 503 | Provider unavailable |

---

### 2. Get Media Asset

**`GET /api/media/images/{id}`**

Returns a single media asset with full metadata.

#### Response — `200 OK`

```json
{
    "data": {
        "id": 1,
        "status": "completed",
        "provider": "openai",
        "model": "gpt-image-1",
        "prompt": "A modern electric car...",
        "width": 1024,
        "height": 1024,
        "style": "natural",
        "quality": "high",
        "original_path": "temp/Media/1/abc123.png",
        "processed_path": "Media/1/abc123.webp",
        "final_url": "https://s3.amazonaws.com/mercadoveiculos/Media/1/abc123.webp",
        "mime_type": "image/webp",
        "file_size": 125000,
        "original_size": 500000,
        "compression_ratio": 0.25,
        "metadata": { "article_id": 42 },
        "orchestrator_context": { "workflow_id": "wf_abc123" },
        "failure_reason": null,
        "created_at": "2026-03-09T12:00:00.000000Z",
        "updated_at": "2026-03-09T12:00:15.000000Z",
        "completed_at": "2026-03-09T12:00:15.000000Z"
    }
}
```

#### Error Responses

| Code | Condition |
|------|-----------|
| 401 | Invalid token |
| 404 | Asset not found |

---

### 3. List Media Assets

**`GET /api/media/images`**

Returns a paginated list of media assets with filtering support.

#### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | Filter by status (e.g., `completed`, `failed`, `processing`) |
| `provider` | string | Filter by provider |
| `per_page` | integer | Items per page (default: 15, max: 100) |
| `page` | integer | Page number |
| `sort` | string | Sort field: `created_at`, `updated_at` (default: `-created_at`) |
| `search` | string | Search in prompt text |

#### Response — `200 OK`

```json
{
    "data": [
        {
            "id": 1,
            "status": "completed",
            "provider": "openai",
            "model": "gpt-image-1",
            "prompt": "A modern electric car...",
            "final_url": "https://s3.../Media/1/abc123.webp",
            "mime_type": "image/webp",
            "file_size": 125000,
            "created_at": "2026-03-09T12:00:00.000000Z",
            "completed_at": "2026-03-09T12:00:15.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73
    }
}
```

---

### 4. Retry Failed Generation

**`POST /api/media/images/{id}/retry`**

Retries a failed generation from scratch (new provider call).

#### Precondition

Asset must be in `failed` status.

#### Response — `202 Accepted`

Same shape as Generate response, with status reset to `pending`.

#### Error Responses

| Code | Condition |
|------|-----------|
| 404 | Asset not found |
| 422 | Asset not in `failed` status |

---

### 5. Reprocess Asset

**`POST /api/media/images/{id}/reprocess`**

Re-dispatches the compression/optimization pipeline for an existing generated image.

#### Precondition

Asset must have an `original_path` and be in `generated`, `completed`, or `failed` status.

#### Response — `202 Accepted`

Same shape as asset response, with status set to `queued_for_compaction`.

#### Error Responses

| Code | Condition |
|------|-----------|
| 404 | Asset not found |
| 422 | No original file to reprocess, or invalid status |

---

## Status Flow Diagram

```
pending → generating → generated → uploading → queued_for_compaction → processing → completed
    ↓          ↓                        ↓              ↓                    ↓
  failed     failed                   failed         failed              failed
```

Any `failed` asset can be retried (→ `pending`) or reprocessed (→ `queued_for_compaction`) depending on where the failure occurred.

## Error Response Format

All error responses follow a consistent shape:

```json
{
    "message": "Human-readable error description.",
    "errors": {
        "field": ["Specific validation error."]
    }
}
```

For non-validation errors:

```json
{
    "message": "Provider unavailable: openai",
    "error_code": "provider_unavailable",
    "details": {
        "provider": "openai",
        "reason": "API key not configured"
    }
}
```
