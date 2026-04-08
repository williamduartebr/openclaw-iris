# 06 — Validation Rules

## Generate Image Request

**Endpoint**: `POST /api/media/images/generate`

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `prompt` | string | **Yes** | `required`, `string`, `min:3`, `max:4000` |
| `provider` | string | No | `nullable`, `string`, must exist in `config('media.providers')` keys |
| `model` | string | No | `nullable`, `string`, `max:100`, validated against provider's `supportedModels()` |
| `negative_prompt` | string | No | `nullable`, `string`, `max:1000` |
| `width` | integer | No | `nullable`, `integer`, `min:256`, `max:2048` |
| `height` | integer | No | `nullable`, `integer`, `min:256`, `max:2048` |
| `style` | string | No | `nullable`, `string`, `in:natural,vivid` |
| `quality` | string | No | `nullable`, `string`, `in:standard,high,hd,low,medium` |
| `metadata` | object | No | `nullable`, `array`, max 20 keys, values must be scalar or null |
| `orchestrator_context` | object | No | `nullable`, `array`, max 20 keys |

### Custom Validation

- If `provider` is specified, it must have `isAvailable() === true`
- If `model` is specified without `provider`, use default provider and validate model against it
- If `model` is specified with `provider`, validate model belongs to that provider

## List Media Request

**Endpoint**: `GET /api/media/images`

| Parameter | Type | Required | Rules |
|-----------|------|----------|-------|
| `status` | string | No | `nullable`, `string`, valid `MediaStatus` value |
| `provider` | string | No | `nullable`, `string`, valid `MediaProvider` value |
| `per_page` | integer | No | `nullable`, `integer`, `min:1`, `max:100` |
| `page` | integer | No | `nullable`, `integer`, `min:1` |
| `sort` | string | No | `nullable`, `string`, `in:created_at,-created_at,updated_at,-updated_at` |
| `search` | string | No | `nullable`, `string`, `max:200` |

## Reprocess Request

**Endpoint**: `POST /api/media/images/{id}/reprocess`

| Validation | Rule |
|-----------|------|
| `{id}` | Must exist in `media_assets` table |
| `status` | Must be `failed`, `generated`, or `completed` |
| `original_path` | Must not be null (required for reprocessing) |

## Retry Request

**Endpoint**: `POST /api/media/images/{id}/retry`

| Validation | Rule |
|-----------|------|
| `{id}` | Must exist in `media_assets` table |
| `status` | Must be `failed` |

## Authorization

All endpoints validate:

1. **Bearer token** — `Authorization: Bearer {token}` must match `config('services.media_api.key')`
2. **FormRequest `authorize()`** — Double validation of bearer token (defense in depth, matching Content API pattern)

## Error Messages

Error messages are returned in English (machine-facing API for agents/orchestrators).

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "prompt": ["The prompt field is required."],
        "provider": ["The selected provider is not available."],
        "model": ["The selected model is not supported by provider openai."]
    }
}
```
