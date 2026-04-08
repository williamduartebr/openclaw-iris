# 07 — Testing Strategy

## Purpose

Define the complete test plan for the Content API. Every endpoint, validation rule, lifecycle transition, and edge case must be covered. Tests follow the project convention: PHPUnit with `RefreshDatabase` trait, in-memory SQLite.

---

## Test Configuration

```php
// phpunit.xml (already configured)
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
QUEUE_CONNECTION=sync
CACHE_DRIVER=array
SESSION_DRIVER=array
```

All tests extend `Tests\TestCase` and use `Illuminate\Foundation\Testing\RefreshDatabase`.

---

## Test Files

| File | Type | Scope |
|------|------|-------|
| `tests/Feature/Content/ContentApiCrudTest.php` | Feature | Create, read, update, patch, delete, restore |
| `tests/Feature/Content/ContentApiLifecycleTest.php` | Feature | Publish, unpublish, schedule, archive transitions |
| `tests/Feature/Content/ContentApiFilterSortTest.php` | Feature | Filtering, sorting, pagination |
| `tests/Unit/Content/ArticleLifecycleServiceTest.php` | Unit | Status transition logic |
| `tests/Unit/Content/ArticleSlugServiceTest.php` | Unit | Slug generation and uniqueness |

---

## Test Helpers

### Common setup

Each feature test file should define:

```php
private function authHeaders(): array
{
    return ['Authorization' => 'Bearer ' . config('services.content_api.key')];
}

private function validPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Test Article Title for API',
        'body_md' => str_repeat('This is test content for the article body. ', 20),
        'category_slug' => 'dicas',
        'excerpt' => 'Test excerpt for the article.',
    ], $overrides);
}
```

### Factory or seeder

Create a `Category` with slug `dicas` in `setUp()` for all tests that require it.

---

## 1. ContentApiCrudTest — Test Cases

### Authentication (5 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 1 | `test_requires_authentication` | Send request without Authorization header | 401 |
| 2 | `test_rejects_invalid_token` | Send request with wrong Bearer token | 401 |
| 3 | `test_rejects_empty_bearer_token` | Send `Authorization: Bearer ` (empty) | 401 |
| 4 | `test_rejects_non_bearer_auth` | Send `Authorization: Basic ...` | 401 |
| 5 | `test_accepts_valid_token` | Send request with correct token | 200/201 |

### Create Article (12 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 6 | `test_can_create_article_with_minimal_fields` | title + body_md + category_slug | 201, article in DB |
| 7 | `test_can_create_article_with_all_fields` | All fields populated | 201, all fields stored |
| 8 | `test_auto_generates_slug_from_title` | Omit slug field | 201, slug generated |
| 9 | `test_auto_generates_unique_slug_on_conflict` | Create two articles with same title | Both get unique slugs |
| 10 | `test_defaults_status_to_draft` | Omit status field | 201, status=draft |
| 11 | `test_can_create_with_published_status` | Set status=published | 201, published_at set |
| 12 | `test_can_create_with_scheduled_status` | Set status=scheduled + future published_at | 201 |
| 13 | `test_rejects_scheduled_without_future_date` | status=scheduled, published_at in past | 422 |
| 14 | `test_syncs_multiple_categories` | Send category_slugs array | Pivot table populated |
| 15 | `test_stores_gallery_image_urls` | Send gallery_image_urls array | JSON stored correctly |
| 16 | `test_stores_video_urls` | Send video_urls array | JSON stored correctly |
| 17 | `test_returns_full_article_resource` | Create article | Response matches ArticleResource schema |

### Read Article (5 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 18 | `test_can_get_article_by_id` | GET existing article | 200, full resource |
| 19 | `test_returns_404_for_nonexistent_article` | GET with bad ID | 404 |
| 20 | `test_returns_404_for_soft_deleted_article` | GET soft-deleted article | 404 |
| 21 | `test_includes_body_md_in_single_resource` | GET single article | body_md present |
| 22 | `test_includes_category_relationships` | GET article with categories | category and categories present |

### Update Article — PUT (6 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 23 | `test_can_full_update_article` | PUT with all required fields + version | 200, all fields updated |
| 24 | `test_full_update_requires_version` | PUT without version | 422 |
| 25 | `test_full_update_rejects_version_conflict` | PUT with outdated version | 409 |
| 26 | `test_full_update_increments_version` | PUT with correct version | version +1 in response |
| 27 | `test_full_update_clears_omitted_optional_fields` | PUT without subtitle | subtitle becomes null |
| 28 | `test_full_update_validates_slug_uniqueness` | PUT with slug of another article | 422 |

### Partial Update Article — PATCH (10 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 29 | `test_can_patch_title_only` | PATCH with version + title | 200, only title changed |
| 30 | `test_can_patch_body_md_only` | PATCH with version + body_md | 200, only body changed |
| 31 | `test_can_patch_seo_fields` | PATCH with seo_title + seo_description | 200 |
| 32 | `test_can_patch_cover_image_url` | PATCH with cover_image_url | 200 |
| 33 | `test_can_patch_gallery_image_urls` | PATCH with gallery array | 200, array replaced |
| 34 | `test_can_patch_video_urls` | PATCH with video_urls array | 200, array replaced |
| 35 | `test_patch_requires_version` | PATCH without version | 422 |
| 36 | `test_patch_rejects_version_conflict` | PATCH with outdated version | 409 |
| 37 | `test_patch_preserves_unmentioned_fields` | PATCH title only | body_md, images unchanged |
| 38 | `test_patch_increments_version` | PATCH any field | version +1 |

### Delete Article (5 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 39 | `test_can_soft_delete_article` | DELETE existing article | 200, deleted_at set |
| 40 | `test_soft_deleted_article_excluded_from_list` | List after delete | Article not in results |
| 41 | `test_delete_returns_404_for_nonexistent` | DELETE bad ID | 404 |
| 42 | `test_delete_returns_404_for_already_deleted` | DELETE twice | 404 on second |
| 43 | `test_delete_with_version_check` | DELETE with version mismatch | 409 |

### Restore Article (4 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 44 | `test_can_restore_soft_deleted_article` | POST restore | 200, deleted_at null |
| 45 | `test_restore_returns_previous_status` | Restore published article | status=published |
| 46 | `test_restore_returns_404_for_non_deleted` | Restore active article | 404 |
| 47 | `test_restore_returns_404_for_nonexistent` | Restore bad ID | 404 |

---

## 2. ContentApiLifecycleTest — Test Cases

### Valid Transitions (7 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 48 | `test_can_publish_draft_article` | POST publish on draft | 200, status=published |
| 49 | `test_can_publish_review_article` | POST publish on review | 200 |
| 50 | `test_can_publish_scheduled_article` | POST publish on scheduled | 200 |
| 51 | `test_publish_sets_published_at` | Publish article without published_at | published_at set to now |
| 52 | `test_can_unpublish_published_article` | POST unpublish | 200, status=draft |
| 53 | `test_can_schedule_draft_article` | POST schedule with future date | 200, status=scheduled |
| 54 | `test_can_archive_published_article` | POST archive | 200, status=archived |

### Invalid Transitions (5 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 55 | `test_cannot_publish_archived_article` | POST publish on archived | 422 |
| 56 | `test_cannot_archive_draft_article` | POST archive on draft | 422 |
| 57 | `test_cannot_unpublish_draft_article` | POST unpublish on draft | 422 |
| 58 | `test_cannot_schedule_published_article` | POST schedule on published | 422 |
| 59 | `test_schedule_rejects_past_date` | POST schedule with past published_at | 422 |

### Transition Side Effects (3 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 60 | `test_unpublish_clears_published_at` | Unpublish published article | published_at null |
| 61 | `test_archive_preserves_published_at` | Archive published article | published_at preserved |
| 62 | `test_publish_sets_is_published_true` | Publish article | is_published=true (backward compat) |

---

## 3. ContentApiFilterSortTest — Test Cases

### Filtering (8 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 63 | `test_can_filter_by_status` | ?status=published | Only published articles |
| 64 | `test_can_filter_by_multiple_statuses` | ?status=draft,review | Draft and review articles |
| 65 | `test_can_filter_by_category` | ?category=dicas | Only articles in category |
| 66 | `test_can_filter_by_featured` | ?featured=true | Only featured articles |
| 67 | `test_can_filter_by_author` | ?author=Equipe | Partial match |
| 68 | `test_can_search_title_and_excerpt` | ?search=keyword | Matching articles |
| 69 | `test_can_filter_by_date_range` | ?created_after=...&created_before=... | Articles in range |
| 70 | `test_can_list_trashed_articles` | ?trashed=only | Only soft-deleted |

### Sorting (4 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 71 | `test_default_sort_is_created_at_desc` | No sort param | Newest first |
| 72 | `test_can_sort_by_published_at_asc` | ?sort=published_at | Oldest published first |
| 73 | `test_can_sort_by_title` | ?sort=title | Alphabetical |
| 74 | `test_rejects_invalid_sort_field` | ?sort=invalid_field | 422 |

### Pagination (4 tests)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 75 | `test_default_pagination` | No params | 15 items, page 1 |
| 76 | `test_custom_per_page` | ?per_page=5 | 5 items |
| 77 | `test_max_per_page_is_100` | ?per_page=200 | 422 or capped at 100 |
| 78 | `test_pagination_metadata_present` | Any list request | meta.total, meta.last_page present |

---

## 4. ArticleLifecycleServiceTest — Unit Tests

| # | Test Name | Description |
|---|-----------|-------------|
| 79 | `test_allows_draft_to_published` | Valid transition |
| 80 | `test_allows_draft_to_review` | Valid transition |
| 81 | `test_allows_draft_to_scheduled` | Valid transition |
| 82 | `test_allows_review_to_published` | Valid transition |
| 83 | `test_allows_published_to_archived` | Valid transition |
| 84 | `test_allows_archived_to_draft` | Valid transition |
| 85 | `test_rejects_archived_to_published` | Throws InvalidStatusTransitionException |
| 86 | `test_rejects_draft_to_archived` | Throws InvalidStatusTransitionException |
| 87 | `test_publish_sets_published_at` | Side effect check |
| 88 | `test_schedule_requires_future_date` | Validates published_at |

---

## 5. ArticleSlugServiceTest — Unit Tests

| # | Test Name | Description |
|---|-----------|-------------|
| 89 | `test_generates_slug_from_title` | Basic generation |
| 90 | `test_transliterates_accented_characters` | `ção` → `cao` |
| 91 | `test_removes_special_characters` | Strips non-alphanumeric |
| 92 | `test_collapses_consecutive_hyphens` | `a--b` → `a-b` |
| 93 | `test_truncates_to_max_length` | Long title → 80 chars |
| 94 | `test_ensures_uniqueness` | Appends `-2` on conflict |
| 95 | `test_ensures_uniqueness_with_existing_suffix` | Handles `-2` already taken |

---

## 6. Validation Edge Case Tests (within ContentApiCrudTest)

| # | Test Name | Description | Expected |
|---|-----------|-------------|----------|
| 96 | `test_rejects_html_in_body_md` | body_md starts with `<div>` | 422 |
| 97 | `test_rejects_body_md_under_min_length` | body_md = 100 chars | 422 |
| 98 | `test_rejects_invalid_cover_image_url` | Non-https URL | 422 |
| 99 | `test_rejects_invalid_video_url` | Non-YouTube/Vimeo URL | 422 |
| 100 | `test_rejects_too_many_gallery_images` | 25 URLs in array | 422 |
| 101 | `test_rejects_invalid_category_slug` | Non-existent category | 422 |
| 102 | `test_rejects_duplicate_slug_on_create` | Existing slug | 422 |
| 103 | `test_rejects_seo_title_over_70_chars` | 80-char seo_title | 422 |
| 104 | `test_rejects_seo_description_over_160_chars` | 200-char description | 422 |

---

## Test Count Summary

| Test File | Count |
|-----------|-------|
| ContentApiCrudTest | 47 |
| ContentApiLifecycleTest | 15 |
| ContentApiFilterSortTest | 16 |
| ArticleLifecycleServiceTest (Unit) | 10 |
| ArticleSlugServiceTest (Unit) | 7 |
| Validation edge cases (in CrudTest) | 9 |
| **Total** | **104** |

---

## Running Tests

```bash
# Run all Content API tests
php artisan test --filter=ContentApi

# Run specific test file
php artisan test --filter=ContentApiCrudTest

# Run specific test
php artisan test --filter=test_can_create_article_with_minimal_fields

# Run all tests (project-wide)
composer test
```

---

*Previous: [06-validation-rules.md](./06-validation-rules.md)*
*Next: [08-open-questions-and-decisions.md](./08-open-questions-and-decisions.md)*
