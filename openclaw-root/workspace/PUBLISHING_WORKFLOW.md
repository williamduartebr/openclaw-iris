# PUBLISHING_WORKFLOW.md

## Runtime

- API base: `http://host.docker.internal:8080/api/content` from inside the OpenClaw container
- Local host equivalent: `http://localhost:8080/api/content`
- Auth: `Authorization: Bearer $CONTENT_API_KEY`
- Format: JSON with Markdown in `body_md`
- Endpoint contract: read `DOCS_API.md` before any delivery attempt

## Content Packet

Every publish-ready article should ship with:

- title
- slug
- excerpt
- body_md
- category_slug
- seo title
- seo description
- author
- status set to `draft` unless explicitly told to publish

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
- API payload aligned with `DOCS_API.md`
