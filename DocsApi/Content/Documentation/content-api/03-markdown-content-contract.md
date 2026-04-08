# 03 — Markdown Content Contract

## Purpose

This document defines the canonical format for article bodies. All content written through the Content API must be Markdown. This contract is the authoritative reference for agents creating or editing article content.

---

## Canonical Field

The article body is stored in the `body_md` field (mapped to the `content` database column).

- **Storage format**: Raw Markdown text
- **Rendering**: Converted to HTML at display time via `league/commonmark` (GithubFlavoredMarkdown)
- **Legacy**: Some older articles contain raw HTML. The API accepts only Markdown for all write operations.

---

## Supported Markdown Syntax

### Headings

Use `##` (h2) as the top-level section heading. Do **not** use `#` (h1) — the article title serves as h1.

```markdown
## Introduction

Content here...

### Subsection

More content...

#### Deep subsection

Further detail...
```

**Rules**:
- `##` — primary section headings
- `###` — subsection headings
- `####` — rarely needed, for deep nesting only
- Never use `#` inside the body — the title is h1
- Never skip levels (e.g., `##` then `####`)

---

### Paragraphs

Plain text separated by blank lines.

```markdown
This is the first paragraph.

This is the second paragraph.
```

---

### Bold and Italic

```markdown
**bold text**
*italic text*
***bold and italic***
```

---

### Links

```markdown
[Anchor text](https://example.com)
[Anchor text](https://example.com "Optional title")
```

**Rules**:
- Always use full URLs (absolute, with `https://`)
- Do not use relative links
- Do not use bare URLs as link text

---

### Images

Images in the article body use standard Markdown syntax. **This is the primary way images appear in the rendered article.** The Blade template renders `body_md` as HTML — only images embedded as Markdown `![alt](url)` in the body will appear inline within the article.

```markdown
![Mechanic inspecting engine oil level](https://s3.../Media/10/image.webp)
```

**Rules**:
- Always include descriptive alt text in Portuguese
- Use full absolute HTTPS URLs
- Prefer `.webp` format (Media API generates this automatically)
- Place images between sections, after relevant content — not at the very top of `body_md`
- The cover image is rendered separately by the template — do not duplicate it in `body_md`

**How images reach `body_md`**:

| Method | When to use | Precision |
|--------|------------|-----------|
| Embed `![alt](url)` directly in `body_md` | Orchestrator knows exact placement for each image | Highest — full control over position |
| Send `gallery_media` with `gallery_mode: "inline"` (default) | Orchestrator wants auto-append at end of body | Convenient — API appends automatically |
| Send `gallery_media` with `gallery_mode: "gallery"` | Rare — separate structured storage only | Images do NOT appear in article body |

**Important**: The article page only renders images from two sources:
1. `featured_image` (cover) — displayed above the article in a dedicated hero section
2. `body_md` content — Markdown `![](url)` converted to `<img>` tags via CommonMark

The `gallery_image_urls` and `gallery_media` JSON fields are **metadata for traceability and API consumers**. They are not rendered by the Blade template. To make images visible to readers, they must be in `body_md`.

**Cover image vs body images**:

| Use case | Where to put it |
|----------|----------------|
| Cover/hero image (displayed above article) | `cover_media_id` or `cover_image_url` field |
| Images at specific body positions | Embed `![alt](url)` directly in `body_md` |
| Auto-append images to end of body | Send `gallery_media` (default `gallery_mode: "inline"`) |
| Structured metadata only (rare) | Send `gallery_media` + `gallery_mode: "gallery"` |

---

### Unordered Lists

```markdown
- First item
- Second item
  - Nested item
  - Another nested item
- Third item
```

---

### Ordered Lists

```markdown
1. First step
2. Second step
3. Third step
   1. Sub-step A
   2. Sub-step B
```

---

### Blockquotes

```markdown
> This is a quoted passage from an expert source.
>
> It can span multiple paragraphs.
```

---

### Code Blocks

Fenced code blocks with optional language identifier:

````markdown
```json
{
  "key": "value"
}
```
````

Inline code:

```markdown
Use the `oil_viscosity` parameter to specify...
```

---

### Tables

GitHub Flavored Markdown tables:

```markdown
| Oil Type | Viscosity | Best For |
|----------|-----------|----------|
| 5W-30 | Low | Modern engines |
| 10W-40 | Medium | Older engines |
| 15W-50 | High | High-performance |
```

**Rules**:
- Always include header row and separator
- Align content for readability (optional)
- Keep tables simple — avoid complex nesting

---

### Horizontal Rules

```markdown
---
```

Use sparingly, only to separate major thematic sections.

---

### FAQ Sections

The rendering engine transforms FAQ headings into accordion elements. Use this exact pattern:

```markdown
### FAQ: What type of oil should I use?

The best oil depends on your engine type and driving conditions. Check your owner's manual for the manufacturer's recommendation.

### FAQ: How often should I change my oil?

Most modern cars need an oil change every 7,500 to 10,000 km, but consult your manual for specifics.
```

**Rules**:
- Prefix must be exactly `### FAQ: ` (h3, space after colon)
- The answer must be the immediately following paragraph
- One question-answer pair per FAQ heading
- No other elements between the FAQ heading and its answer paragraph

---

### Video References

Embed videos by placing YouTube URLs on their own line or as links. The rendering engine auto-converts them to embedded players.

```markdown
## Watch the Tutorial

https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

Or as a link (also auto-embedded):

```markdown
[Watch the full tutorial](https://www.youtube.com/watch?v=dQw4w9WgXcQ)
```

**Supported URL formats**:
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://youtube.com/embed/VIDEO_ID`

**Relationship between inline videos and the `video_urls` field**:

| Use case | Where to put it |
|----------|----------------|
| Video linked in specific body position | Inline YouTube URL in Markdown |
| Structured list of all related videos | `video_urls` array field |
| Both | Allowed — structured field for API consumers, inline for reading flow |

---

## Forbidden Syntax

The following are **not allowed** in `body_md`:

| Forbidden | Reason |
|-----------|--------|
| Raw HTML (`<div>`, `<span>`, `<br>`, etc.) | Stripped by the CommonMark parser (`html_input: strip`) |
| `<script>` or `<style>` tags | Security — stripped and blocked |
| `#` (h1) headings | Title is h1; body starts at `##` |
| Relative URLs | Not resolvable outside the site context |
| Base64-encoded images | Too large, not cacheable |
| HTML entities (`&amp;`, `&lt;`) | Use UTF-8 characters directly |
| Markdown reference-style links `[text][ref]` | Supported by parser but discouraged for agent clarity |

---

## Complete Article Body Example

```markdown
## Introduction

Changing your car's engine oil is one of the most fundamental maintenance tasks every driver should know. Regular oil changes extend engine life and improve fuel efficiency.

![Mechanic draining engine oil](https://images.example.com/oil-drain.webp)

## Why Oil Changes Matter

Engine oil serves three critical functions:

- **Lubrication** — reduces friction between moving parts
- **Cooling** — carries heat away from the combustion chamber
- **Cleaning** — suspends contaminants and carries them to the filter

> According to automotive experts, skipping oil changes is the single most damaging thing you can do to a modern engine.

## What You Need

| Item | Approximate Cost |
|------|-----------------|
| Oil filter | R$ 25-60 |
| Engine oil (4-5L) | R$ 80-200 |
| Drain pan | R$ 30-50 |
| Wrench set | R$ 40-100 |

## Step-by-Step Process

### 1. Warm Up the Engine

Run your engine for 3-5 minutes. Warm oil drains more completely than cold oil.

### 2. Locate the Drain Plug

Position your drain pan under the oil pan. The drain plug is typically a single bolt on the bottom of the oil pan.

### 3. Drain the Old Oil

Remove the drain plug and let the oil drain completely. This takes about 5-10 minutes.

### 4. Replace the Filter

Remove the old oil filter and install the new one. Apply a thin layer of new oil to the gasket of the new filter.

### 5. Add New Oil

Replace the drain plug and pour in the new oil. Check your manual for the correct amount and viscosity.

## Video Tutorial

https://www.youtube.com/watch?v=dQw4w9WgXcQ

## Maintenance Schedule

Most manufacturers recommend oil changes every **7,500-10,000 km** or every **6 months**, whichever comes first. Check your owner's manual for specifics.

---

### FAQ: What type of oil should I use?

The best oil type depends on your engine and climate. Most modern cars use synthetic 5W-30 or 5W-20. Always check your owner's manual for the manufacturer's recommendation.

### FAQ: Can I mix different oil brands?

Yes, you can mix brands as long as they have the same viscosity rating and meet the same API certification. However, it is best practice to use a single brand for consistency.

### FAQ: How do I know if my oil needs changing?

Check the oil dipstick. If the oil is very dark, gritty, or below the minimum line, it is time for a change. Many modern cars also have an oil life monitor on the dashboard.
```

---

## Content Safety Rules for Agents

### Full body replacement

When replacing the entire `body_md`, send the complete new Markdown content. The previous body is completely overwritten.

```json
{
  "version": 3,
  "body_md": "## New Content\n\nFull replacement text..."
}
```

### Partial body updates

The API does not support inline section-level edits (e.g., "replace only section 3"). To update a portion of the body:

1. **Read** the current article (`GET /api/content/articles/{id}`)
2. **Extract** `body_md` from the response
3. **Modify** the desired section in your local copy
4. **Send** the full modified `body_md` via `PATCH` with the current `version`

This ensures the Markdown structure remains valid and prevents accidental corruption.

### Appending a section

To add a new section at the end:

1. **Read** the current article
2. **Append** your new Markdown section to the existing `body_md`
3. **Send** the combined content via `PATCH`

```json
{
  "version": 3,
  "body_md": "... existing content ...\n\n## New Section\n\nNew content appended here."
}
```

### Avoiding corruption

- Always read the latest version before editing
- Always include `version` in PATCH/PUT requests
- Never guess at existing content — always read first
- Never insert raw HTML into `body_md`
- Validate your Markdown locally before sending if possible

---

## Content Length Guidelines

| Aspect | Guideline |
|--------|-----------|
| Minimum body length | 500 characters |
| Recommended body length | 1,500-5,000 characters |
| Maximum body length | 100,000 characters (database limit practical) |
| Maximum heading depth | `####` (h4) |
| Maximum images per article | No hard limit; recommend 10 or fewer for performance |
| Maximum FAQ items | No hard limit; recommend 5-10 for readability |

---

*Previous: [02-api-specification.md](./02-api-specification.md)*
*Next: [04-agent-integration-guide.md](./04-agent-integration-guide.md)*
