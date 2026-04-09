---
name: mv-article-refresh-router
description: Analyze an existing Mercado Veiculos article first, classify the article type, and route the update to the correct refresh workflow: B2B, B2C, commercial-funnel, media-only, or combined text-plus-media. Use when the user gives a live article URL or slug and wants the assistant to decide how to update it instead of manually choosing a specialized article-refresh skill.
---

# MV Article Refresh Router

Use this skill as the generic entrypoint for article updates.

## Goal

Inspect the article first, decide which specialized refresh path fits best, then execute the right update flow.

## Specialized routes

Choose among these existing skills conceptually:

- `mv-article-refresh-b2b`
- `mv-article-refresh-b2c`
- `mv-article-refresh-commercial-funnel`
- `mv-article-media-refresh`

If the task is mixed, do text refresh first and media refresh second.

## Required inputs

At minimum, you should have one of:

- live article URL
- slug
- article id

## Media argument rule

Always determine whether media should be changed.

### Default behavior

**Default: replace the cover/media.**

Unless the user explicitly says to keep the current cover, assume the media should be refreshed and the old media URL/path should be reported back for deletion.

### Supported media decision states

Interpret the request as one of these:

- `replace-media` → default if the user does not specify otherwise
- `keep-media` → when the user explicitly says to preserve the current cover/media
- `media-only` → when the task is only cover/image cleanup
- `text-only` → only when the user explicitly says not to touch media

## Routing logic

### Route to B2B refresh

Use when the article is mainly about:

- marketing
- visibility
- digital presence
- demand generation
- lead flow
- commercial operation
- growth
- performance measurement
- discovery/trust for business owners

### Route to B2C refresh

Use when the article is mainly about:

- driver questions
- maintenance
- symptoms
- timing of repair
- costs
- risks
- service choice
- practical ownership decisions

### Route to commercial-funnel refresh

Use when the article is mainly about:

- free profile
- paid plan
- upgrade
- claiming profile
- creating profile
- product/plan maturity
- Mercado Veiculos commercial ladder content

### Route to media refresh

Use when the task is limited to:

- changing the cover
- removing inline images
- listing old image URLs/paths to delete
- cleaning article media without rewriting body copy

### Route to combined refresh

Use text refresh plus media refresh when:

- the article is weak and generic
- the user asks to improve the article and also update imagery
- the article needs stronger cover alignment after rewrite
- the body contains stale inline images that should be removed during the update

## Decision checklist

Before acting, classify:

1. What is the audience: business owner or driver?
2. Is the main problem commercial, operational, or driver-facing?
3. Is the article really about product/funnel logic?
4. Did the user ask for text, media, or both?
5. Did the user explicitly say to keep media?
6. If not, default to replacing media.

## Execution order

### If route = text + media

1. Fetch live page and CMS article.
2. Decide refresh type: B2B, B2C, or commercial funnel.
3. Rewrite text with the correct specialized standard.
4. Refresh cover unless `keep-media` was explicitly requested.
5. Remove weak inline images if present and useful to clean.
6. PATCH with latest version.
7. Report article id, version, whether slug stayed the same, whether media changed, and old image URLs/paths for deletion.

### If route = media only

1. Fetch live page and CMS article.
2. Preserve slug.
3. Replace cover by default unless explicitly told to keep it.
4. Remove inline images if requested or if clearly obsolete.
5. PATCH.
6. Return old media URLs/paths for deletion.

## Reporting format

Always tell the user:

- chosen route
- article id
- whether slug stayed the same
- whether media was kept or replaced
- current version
- old cover path/url
- old inline image path/url(s)
- whether physical deletion from storage still needs to be done separately

## Notes

- Do not change slug/URL unless the user explicitly asks.
- Default to replacing media, not preserving it.
- If you preserve media, do it because the user explicitly requested it.
- When routing text rewrites, enforce variation in openings and excerpt leads so adjacent updated articles do not repeat formulas like `muita empresa...`, `veja por que...`, or `descubra por que...` in sequence.
- Keep the final report operational and deletion-friendly.
