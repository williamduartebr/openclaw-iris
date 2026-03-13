# PUBLISHING_WORKFLOW.md

## Runtime

- API base: `http://host.docker.internal:8080/api/content` from inside the OpenClaw container
- Local host equivalent: `http://localhost:8080/api/content`
- Auth: `Authorization: Bearer $CONTENT_API_KEY`
- Media API base: `http://host.docker.internal:8080/api/media`
- Media auth: `Authorization: Bearer $MEDIA_API_KEY`
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
- cover_media_id or trusted cover_image_url
- status set to `draft` unless explicitly told to publish

## Body Rules

- Never use `#` inside the article body
- Use `##` for major sections
- Use `###` for subsections
- Use `### FAQ: ...` inside `## Perguntas frequentes`
- No HTML tags

## Cover Workflow

1. Generate the hero image through the Media API for every CMS-bound article unless the user explicitly waives imagery.
2. Poll the media job until it is complete.
3. When using Gemini, send `model: gemini-2.5-flash-image` explicitly instead of relying on the backend default.
4. Attach `cover_media_id` to the article payload.
5. If cover generation fails, do not say the packet is fully publish-ready.

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
- FAQ section heading written in pt-BR
- Cover image present and attached to the CMS payload
- CTA aligned with funnel and audience
- API payload aligned with `DOCS_API.md`
