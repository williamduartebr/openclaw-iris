---
name: mv-article-media-refresh
description: Update Mercado Veiculos article media while preserving the article URL by default. Use when swapping or preserving covers, removing weak inline images, cleaning up legacy article media, and reporting old image URLs/paths for deletion after CMS updates.
---

# MV Article Media Refresh

Use this skill when the main task is media cleanup or media replacement on an existing article.

## Default outcome

Keep the article URL stable while improving the visual package and cleaning obsolete media references.

## Workflow

1. Fetch live page and CMS article.
2. Preserve slug/URL unless explicitly told otherwise.
3. Identify:
   - current cover image path or media id
   - inline image URLs in body
   - whether the user wants to keep or replace the cover
4. If replacing the cover, generate or attach a stronger editorial image aligned to Brazilian reality.
5. Remove obsolete inline images from `body_md` when requested or when they weaken the article.
6. PATCH with latest version.
7. Report old image path(s)/URL(s) that can be deleted from storage.

## Media rules

- Keep the current cover if the user explicitly asks.
- Do not silently change slug just because media is changing.
- If the article body no longer needs old inline images, remove them from CMS instead of leaving dead clutter.
- When reporting cleanup, distinguish between:
  - old cover path/url
  - old inline image path/url
  - whether the file was only detached from CMS or physically deleted from storage

## Reporting format

Tell the user:

- article id
- whether slug stayed the same
- whether cover stayed or changed
- current version
- old cover path/url
- old inline image path/url(s)
- whether deletion from storage still needs to be performed separately
