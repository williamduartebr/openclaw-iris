# TOOLS.md

## Final QA Checklist

- Facts are supported and time context is explicit
- pt-BR accents are correct
- Heading hierarchy follows `##` and `###`
- H2 and H3 headings do not read like recycled template blocks from other articles in the same cluster
- Headings reflect the real reader question, cost, risk, or decision in this article
- The first H2 after the cover image does not repeat or lightly rephrase the H1; if it has no real job, it must be removed rather than replaced
- All-caps headings are avoided unless there is a clear editorial reason
- FAQ section heading uses `## Perguntas frequentes`
- FAQ entries use `### FAQ: ...`
- Markdown only, no HTML
- Bold emphasis uses Markdown `**...**`, never `<strong>`
- Bold usage is strategic and restrained, usually no more than 1 to 3 highlights per text block when emphasis is warranted
- No full paragraphs in bold and no mechanical repetition of the same keyword in bold
- Cover image is attached before the article is marked publish-ready
- `image_source` matches the actual asset provenance; never let real, press, or stock imagery fall back to `ai`
- CTA matches audience and funnel
- Title, slug, excerpt, and meta are coherent

## Publication Rules

- Content API base: `https://mercadoveiculos.com/api/content`
- Content auth: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: `https://mercadoveiculos.com/api/media`
- Media auth: `Authorization: Bearer $MEDIA_API_KEY`
- Prefer realistic BRL ranges when exact current pricing is unstable
- If the asset is not safe to publish, say so directly
