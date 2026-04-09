# TOOLS.md

## Final QA Checklist

- Facts are supported and time context is explicit
- pt-BR accents are correct
- Heading hierarchy follows `##` and `###`
- H2 and H3 headings do not read like recycled template blocks from other articles in the same cluster
- Headings reflect the real reader question, cost, risk, or decision in this article
- The article structure feels modular and intent-led, not like a memorized sequence of reusable buckets
- The first H2 after the cover image does not repeat or lightly rephrase the H1; if it has no real job, it must be removed rather than replaced
- The opening does not rely on broad filler such as `Para muita empresa...`, `Muita oficina...`, or similar category-level warm-up lines
- If the topic has practical wallet impact, the article includes a financial layer: bounded BRL range, order of magnitude, cost drivers, or clear explanation of what makes the mistake expensive
- All-caps headings are avoided unless there is a clear editorial reason
- FAQ section heading uses `## Perguntas frequentes`
- FAQ entries use `### FAQ: ...`
- Markdown only, no HTML
- Article copy, prompts, and CTA examples must use Markdown links in the form `[label](https://full-url)`
- Bold emphasis uses Markdown `**...**`, never `<strong>`
- Raw URLs inside prose are a QA issue when a labeled Markdown link is the appropriate form
- Bold usage is strategic and restrained, usually no more than 1 to 3 highlights per text block when emphasis is warranted
- No full paragraphs in bold and no mechanical repetition of the same keyword in bold
- Cover image is attached before the article is marked publish-ready
- `image_source` matches the actual asset provenance; never let real, press, or stock imagery fall back to `ai`
- CTA matches audience and funnel
- CTA bridge names the reader's next move instead of using generic platform-self-introduction such as `Onde o Mercado Veiculos entra nessa etapa`
- Tool links, when present, match the editorial function of the article and do not compete with the main CTA
- Tool URLs use live production slugs under [Ferramentas Mercado Veiculos](https://mercadoveiculos.com/ferramentas), not guessed or legacy paths
- Title, slug, excerpt, and meta are coherent
- The article adds enough practical value that a reader should not need another search to finish the task
- There is no obvious word-count padding, freshness theater, or summary-without-value filler

## Publication Rules

- Content API base: [Mercado Veiculos Content API](https://mercadoveiculos.com/api/content)
- Content auth: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: [Mercado Veiculos Media API](https://mercadoveiculos.com/api/media)
- Media auth: `Authorization: Bearer $MEDIA_API_KEY`
- Prefer realistic BRL ranges when exact current pricing is unstable
- If the asset is not safe to publish, say so directly
