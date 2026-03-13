# PUBLISHING_WORKFLOW.md

## Runtime

- API base: `http://localhost:8080`
- Auth: `Authorization: Bearer $ARTICLE_API_KEY`
- Format: Markdown only

## Content Packet

Every publish-ready article should ship with:

- title
- slug
- excerpt
- markdown body
- reading time
- meta description
- keyword set

## Body Rules

- Never use `#` inside the article body
- Use `##` for major sections
- Use `###` for subsections
- Use `### FAQ: ...` inside `## Frequently Asked Questions`
- No HTML tags

## Funnel Rules

- TOFU: educational, broad, authority-building, low pressure
- MOFU: comparative, cost-aware, problem-solving, directory-aware
- BOFU: decision-focused, trust-building, direct action
- B2B: ROI, lead flow, local visibility, operational leverage, `/anuncie` CTA

## B2B CTA Map

- Generic: `/anuncie`
- Workshops: `/anuncie/oficinas`
- Auto parts: `/anuncie/autopecas`
- Car wash: `/anuncie/lava-jato`
- Body shop: `/anuncie/funilaria-pintura`
- Auto electrical: `/anuncie/autoeletrica`
- Tires and alignment: `/anuncie/pneus-alinhamento`
- AC services: `/anuncie/ar-condicionado`

## Final QA Before Publish

- Correct pt-BR accents
- 2026 present-day references where appropriate
- Realistic BRL ranges
- Heading hierarchy correct
- FAQ syntax correct
- CTA aligned with funnel and audience
