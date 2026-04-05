# TOOLS.md

## Final QA Checklist

- Facts are supported and time context is explicit
- pt-BR accents are correct
- Heading hierarchy follows `##` and `###`
- H2 and H3 headings do not read like recycled template blocks from other articles in the same cluster
- Headings reflect the real reader question, cost, risk, or decision in this article
- All-caps headings are avoided unless there is a clear editorial reason
- FAQ section heading uses `## Perguntas frequentes`
- FAQ entries use `### FAQ: ...`
- Markdown only, no HTML
- Cover image is attached before the article is marked publish-ready
- CTA matches audience and funnel
- Title, slug, excerpt, and meta are coherent

## Publication Rules

- Content API base: `https://mercadoveiculos.com/api/content`
- Content auth: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: `https://mercadoveiculos.com/api/media`
- Media auth: `Authorization: Bearer $MEDIA_API_KEY`
- Prefer realistic BRL ranges when exact current pricing is unstable
- If the asset is not safe to publish, say so directly
