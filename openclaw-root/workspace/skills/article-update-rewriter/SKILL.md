---
name: article-update-rewriter
description: Rewrite, modernize, and republish existing Mercado Veiculos articles while keeping the same slug/URL unless explicitly told otherwise. Use when updating weak legacy articles, swapping or preserving covers, removing stale inline images, improving SEO traffic capture, reducing AI-summary replaceability, avoiding templated openings/excerpts/H2s, and aligning old articles to the newer Mercado Veiculos editorial standard.
---

# Article Update Rewriter

Rewrite existing Mercado Veiculos articles so they feel materially stronger, more current, and more useful without breaking the URL unless the user explicitly asks.

## Core outcome

Turn old generic articles into pieces that:

- capture a real search pain or decision
- stay useful even if AI gives the basic summary
- sound editorial, not templated
- connect discovery, trust, and conversion
- keep operational details clean during CMS updates

## Default workflow

1. Fetch the live article and the CMS article record.
2. Preserve the slug/URL unless the user explicitly asks to change it.
3. Check whether the cover should be kept or replaced.
4. Rewrite the article around a sharper search-intent conflict, not around generic explanation.
5. Replace weak titles, excerpts, and SEO descriptions with stronger traffic-capture variants.
6. Remove stale inline images from `body_md` when they weaken the article or when the user asks for cleanup.
7. Normalize any article links that remain in prose to standard Markdown: `[label](https://full-url)`.
8. If the cover is replaced, keep note of the old cover path/URL so the user can delete it from storage.
9. PATCH the article in CMS with the latest `version`.
10. Report what changed, what stayed, and which image URLs/paths are safe to delete.

## Rewrite standards

Apply these standards by default:

- Do not write in encyclopedia mode.
- Do not preserve generic intros just because they are technically correct.
- Do not leave raw URLs in article prose when the line should be a labeled Markdown link.
- Do not use broad openers like `Muita empresa...`, `Muita oficina...`, or similar flattening formulas.
- Do not use `Resposta rápida` as the default first H2.
- Do not let adjacent articles share the same excerpt/SEO opening pattern such as serial `Veja por que...` / `Descubra por que...`.
- Do not use rigid all-caps H2s unless the user explicitly wants that style.
- Do not leave the article sounding like conference-talk futurism or institutional brand copy.

## Search and anti-AI rule

For update rewrites, write so the article wins on **pain recognition + decision value**.

Always ask silently:

- What pain or business consequence is the reader actually searching?
- If Google or AI summarized the obvious answer, what would still make this article worth the click?

Prefer:

- concrete search phrasing
- practical consequences
- cost, margin, contact, trust, agenda, or demand impact when relevant
- comparisons between appearing active and actually generating business
- lines that connect visibility to contact and contact to revenue

## Mercado Veiculos-specific editorial pattern

For old B2B / marketing / visibility articles, usually reframe around one of these tensions:

- the business is losing contacts before the first conversation
- the business appears weaker than it really is
- the owner confuses movement with result
- the company depends too much on indication or old clients
- the digital presence does not transmit trust, specialty, or clarity fast enough

## Excerpts and SEO descriptions

Excerpts and SEO descriptions must feel alive, not mass-produced.

Rotate lead structures. Mix patterns like:

- direct consequence
- hidden loss
- search-behavior angle
- trust/perception angle
- comparison angle

Avoid repeating the same structure across neighboring articles.

## Covers and images

When updating imagery:

- keep the same cover if the user asks
- otherwise replace with a stronger editorial cover aligned to Brazilian reality
- keep `image_source` intentional
- if old inline images remain in the article body and they weaken the piece, remove them from `body_md`
- after updating, give the user the old image path(s)/URL(s) that can be deleted from storage

## CMS update rules

- Fetch the current article first and use the latest `version`.
- Keep the same slug unless explicitly instructed otherwise.
- Prefer PATCH for updates.
- If only rewriting text while preserving cover, keep the existing cover reference unchanged.
- If swapping cover, provide the old cover path or URL in the final report.

## Output report to user

After updating, report briefly:

- article id
- whether the slug stayed the same
- whether the cover was kept or replaced
- current version
- what changed editorially
- which old image path(s)/URL(s) can be deleted

## When needed

If you need examples of rewrite patterns, read `references/patterns.md`.
