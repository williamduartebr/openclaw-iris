# 06 — Validation Rules

## Purpose

Complete validation rules for every Content API operation. These rules define what the API accepts, rejects, and how edge cases are handled. Agents must comply with these rules to avoid 422 errors.

---

## 1. Create Article (`POST /api/content/articles`)

### Required fields

| Field | Type | Rules |
|-------|------|-------|
| `title` | string | Required. Min 3 characters. Max 255 characters. |
| `body_md` | string | Required. Min 500 characters. Must not start with `<` (rejects raw HTML). |
| `category_slug` | string | Required. Must exist in `categories` table with `is_active = true`. |

### Optional fields

| Field | Type | Rules | Default |
|-------|------|-------|---------|
| `subtitle` | string | Max 255 characters. | `null` |
| `slug` | string | Max 80 characters. URL-safe (lowercase alphanumeric + hyphens). Unique across all articles (including soft-deleted). If omitted, auto-generated from `title`. | Auto-generated |
| `excerpt` | string | Max 300 characters. | `null` |
| `status` | string | One of: `draft`, `review`, `scheduled`, `published`. If `published`, requires `published_at` or auto-sets to now. If `scheduled`, requires `published_at` in the future. | `draft` |
| `featured` | boolean | `true` or `false`. | `false` |
| `cover_image_url` | string | Valid URL (https). Max 2048 characters. Must end with an image extension (`.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`, `.svg`, `.bmp`, `.tiff`) or be a known image CDN URL. | `null` |
| `gallery_image_urls` | array | Array of URL strings. Max 20 items. Each URL: valid https, max 2048 characters. | `[]` |
| `video_urls` | array | Array of URL strings. Max 10 items. Each URL: valid https, max 2048 characters. Must be a recognized video platform (YouTube, Vimeo). | `[]` |
| `category_slugs` | array | Array of category slug strings. Each must exist in `categories` table with `is_active = true`. Used for multi-category assignment. | `[category_slug]` |
| `author` | string | Max 255 characters. | `Equipe Editorial` |
| `reading_time` | integer | Min 1, max 120 (minutes). | Auto-calculated from `body_md` word count |
| `seo_title` | string | Max 70 characters. | `null` |
| `seo_description` | string | Max 160 characters. | `null` |
| `canonical_url` | string | Valid URL (https). Max 2048 characters. | `null` |
| `published_at` | ISO 8601 | Valid datetime. Required if `status` is `scheduled` (must be future). Auto-set to now if `status` is `published` and field is omitted. | `null` |

---

## 2. Full Update Article (`PUT /api/content/articles/{id}`)

### Required fields

| Field | Type | Rules |
|-------|------|-------|
| `version` | integer | Required. Must match the current article version. |
| `title` | string | Required. Same rules as create. |
| `body_md` | string | Required. Same rules as create. |
| `category_slug` | string | Required. Same rules as create. |
| `excerpt` | string | Required. Max 300 characters. |

### Optional fields

Same as create, with the following differences:

- `slug`: If changed, must still be unique. If omitted, slug is **not** regenerated (it keeps the current value).
- `status`: If changed, the transition must be valid (see Section 6 below).

---

## 3. Partial Update Article (`PATCH /api/content/articles/{id}`)

### Required fields

| Field | Type | Rules |
|-------|------|-------|
| `version` | integer | Required. Must match the current article version. |

### Optional fields

All fields from create are optional. Only provided fields are validated and updated. Omitted fields are not changed.

**Minimum payload**: `{"version": 3}` (no-op update, increments version).

**Field-level rules**: Same as create for each individual field when present.

---

## 4. List Articles (`GET /api/content/articles`)

### Query parameters

| Parameter | Type | Rules |
|-----------|------|-------|
| `page` | integer | Min 1. |
| `per_page` | integer | Min 1. Max 100. |
| `sort` | string | Must be one of: `created_at`, `-created_at`, `updated_at`, `-updated_at`, `published_at`, `-published_at`, `title`, `-title`. |
| `status` | string | Comma-separated list. Each value must be one of: `draft`, `review`, `scheduled`, `published`, `archived`. |
| `category` | string | Must exist in `categories` table. |
| `featured` | string | Must be `true` or `false` (parsed as boolean). |
| `author` | string | Max 255 characters. Partial match. |
| `search` | string | Max 255 characters. Searched in `title` and `excerpt`. |
| `created_after` | string | Valid ISO 8601 datetime. |
| `created_before` | string | Valid ISO 8601 datetime. Must be after `created_after` if both present. |
| `published_after` | string | Valid ISO 8601 datetime. |
| `published_before` | string | Valid ISO 8601 datetime. Must be after `published_after` if both present. |
| `trashed` | string | Must be `only` or `with`. Default: excluded. |

---

## 5. Slug Rules

### Generation

When `slug` is not provided on create:

1. Take the `title`
2. Convert to lowercase
3. Transliterate accented characters (e.g., `é` → `e`, `ão` → `ao`)
4. Replace non-alphanumeric characters with hyphens
5. Collapse consecutive hyphens
6. Trim leading/trailing hyphens
7. Truncate to 80 characters

Example: `"Como Verificar o Nível de Óleo do Motor"` → `"como-verificar-o-nivel-de-oleo-do-motor"`

### Uniqueness

- Slugs must be unique across **all** articles, including soft-deleted ones
- If a generated slug conflicts, append `-2`, `-3`, etc.
- Uniqueness is enforced at the database level (`UNIQUE` index)
- On update: if `slug` is changed, uniqueness is checked excluding the current article's ID

### Immutability recommendation

Agents should avoid changing slugs on published articles. Slug changes break existing URLs and SEO rankings. If a slug change is necessary on a published article, the API allows it but does not create redirects automatically.

---

## 6. Status Transition Rules

### Allowed transitions

```
Current Status   → Allowed Target Statuses
─────────────────────────────────────────
draft            → review, scheduled, published
review           → draft, scheduled, published
scheduled        → draft, published
published        → draft, archived
archived         → draft
```

### Forbidden transitions

Any transition not listed above returns HTTP 422 with:

```json
{
  "message": "Invalid status transition.",
  "errors": {
    "status": [
      "Cannot transition from 'archived' to 'published'. Allowed transitions: draft."
    ]
  }
}
```

### Transition side effects

| Transition | Side Effect |
|------------|-------------|
| `* → published` | Sets `published_at = now()` if not already set. Sets `is_published = true`. |
| `* → scheduled` | Requires `published_at` in the future. Sets `is_published = false`. |
| `published → draft` | Sets `is_published = false`. Clears `published_at`. Article disappears from public view. |
| `published → archived` | Sets `is_published = false`. Preserves `published_at` for historical record. |
| `archived → draft` | Sets `is_published = false`. Preserves `published_at`. |
| `* → review` | No special side effects. Article remains non-public. |

### Direct status changes via PATCH

When `status` is included in a PATCH payload, the transition rules apply. The agent cannot set any arbitrary status — the transition from current to target must be allowed.

---

## 7. Media URL Validation

### Cover image URL

| Rule | Detail |
|------|--------|
| Protocol | Must be `https://` |
| Max length | 2048 characters |
| Format | Must be a valid URL |
| Extension check | Should end with `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp`, `.svg`, `.bmp`, `.tiff`, or be from a known image CDN (relaxed check) |
| Reachability | **Not** validated — the API does not fetch the URL to verify it exists |

### Gallery image URLs

| Rule | Detail |
|------|--------|
| Type | Array of strings |
| Max items | 20 |
| Per-item rules | Same as cover image URL |
| Duplicates | Allowed (no deduplication enforced) |
| Order | Preserved as provided |

### Video URLs

| Rule | Detail |
|------|--------|
| Type | Array of strings |
| Max items | 10 |
| Protocol | Must be `https://` |
| Max length | 2048 characters per URL |
| Platform check | Must match known video platform patterns |

**Accepted video URL patterns**:

```
https://www.youtube.com/watch?v={ID}
https://youtube.com/watch?v={ID}
https://youtu.be/{ID}
https://www.youtube.com/embed/{ID}
https://vimeo.com/{ID}
https://player.vimeo.com/video/{ID}
```

**Rejected examples**:
- `http://youtube.com/watch?v=...` (http, not https)
- `https://dailymotion.com/video/...` (unsupported platform)
- `https://example.com/video.mp4` (direct file, not platform URL)

---

## 8. Content Validation (`body_md`)

| Rule | Detail |
|------|--------|
| Required on | Create, full update (PUT) |
| Min length | 500 characters |
| Max length | 100,000 characters |
| HTML rejection | If content starts with `<`, return 422 with message "Content must be Markdown, not HTML" |
| Encoding | UTF-8 |
| Line endings | `\n` (LF). CRLF is accepted and normalized to LF. |

### What is NOT validated

- Markdown structure correctness (heading hierarchy, balanced formatting)
- Link reachability
- Image URL validity within the body
- Presence of specific sections

**Rationale**: Markdown is plain text with conventions. Agents are trusted to produce valid Markdown. Enforcing Markdown structure would be overly restrictive and fragile.

---

## 9. Delete Constraints

### Soft delete

- Any article can be soft-deleted regardless of status
- Soft-deleted articles are excluded from default list results
- Soft-deleted articles return 404 on direct GET unless `?trashed=only` or `?trashed=with` is used on the list endpoint
- Soft-deleted slugs **still occupy the uniqueness constraint** — a new article cannot reuse a deleted article's slug

### Restore

- Only soft-deleted articles can be restored
- Restored articles return to their previous `status`
- If the previous status was `published`, the article becomes immediately visible
- No version check required for restore (it is a recovery operation)

### Hard delete

- Not available through the API
- Hard deletion is an admin-only database operation
- This is intentional: agents should not be able to permanently destroy content

---

## 10. Authorization Validation

| Check | Enforcement Point | Failure Response |
|-------|-------------------|------------------|
| Bearer token present | Middleware | 401 `{"message": "Unauthorized."}` |
| Bearer token valid | Middleware | 401 `{"message": "Unauthorized."}` |
| Article exists | Route model binding | 404 `{"message": "Article not found."}` |
| Version match | Service layer | 409 with version details |
| Status transition valid | Domain service | 422 with allowed transitions |

---

## 11. Field Length Summary

| Field | Min | Max | Unit |
|-------|-----|-----|------|
| `title` | 3 | 255 | characters |
| `subtitle` | — | 255 | characters |
| `slug` | 1 | 80 | characters |
| `excerpt` | — | 300 | characters |
| `body_md` | 500 | 100,000 | characters |
| `author` | — | 255 | characters |
| `seo_title` | — | 70 | characters |
| `seo_description` | — | 160 | characters |
| `canonical_url` | — | 2,048 | characters |
| `cover_image_url` | — | 2,048 | characters |
| `gallery_image_urls` | — | 20 | items |
| `video_urls` | — | 10 | items |
| `reading_time` | 1 | 120 | minutes |
| `per_page` | 1 | 100 | items |

---

*Previous: [05-domain-impact-analysis.md](./05-domain-impact-analysis.md)*
*Next: [07-testing-strategy.md](./07-testing-strategy.md)*
