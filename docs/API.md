# API

This repository uses the Content module API already documented under `DocsApi/Content`. The OpenClaw workspace mirrors that contract so agents send article payloads to the correct route and in the correct format.

## Canonical Source

The runtime contract here is aligned to:

- `DocsApi/Content/Routes/api.php`
- `DocsApi/Content/Application/Requests/CreateArticleRequest.php`
- `DocsApi/Content/Application/Requests/UpdateArticleRequest.php`
- `DocsApi/Content/Application/Requests/PatchArticleRequest.php`
- `DocsApi/Content/Documentation/content-api/03-markdown-content-contract.md`
- `DocsApi/Content/Documentation/content-api/04-agent-integration-guide.md`
- `DocsApi/Content/Documentation/content-api/10-api-usage-guide.md`

## Base URL

- Agent runtime base URL: `http://host.docker.internal:8080/api/content`
- Browser/local host base URL: `http://localhost:8080/api/content`
- Auth header: `Authorization: Bearer $CONTENT_API_KEY`
- Headers: `Accept: application/json` and `Content-Type: application/json`
- Media API runtime base URL: `http://host.docker.internal:8080/api/media`
- Media API auth header: `Authorization: Bearer $MEDIA_API_KEY`

## Main Endpoints

- `GET /articles?search={slug}&per_page=1`: find an article by slug
- `POST /articles`: create a new article
- `GET /articles/{id}`: fetch a full article resource
- `PATCH /articles/{id}`: partial update with optimistic locking
- `PUT /articles/{id}`: full update with optimistic locking

Lifecycle endpoints:

- `POST /articles/{id}/publish`
- `POST /articles/{id}/unpublish`
- `POST /articles/{id}/schedule`
- `POST /articles/{id}/archive`
- `POST /articles/{id}/restore`

## Write Contract

Minimal create payload accepted by the module:

```json
{
  "title": "Quanto custa trocar a bateria do carro em 2026?",
  "excerpt": "Veja a faixa de preco da troca, os sinais de bateria fraca e quando procurar uma autoeletrica.",
  "body_md": "## Resposta rapida\n\n...",
  "category_slug": "autoeletrica-e-eletronica"
}
```

Preferred editorial payload:

```json
{
  "title": "Quanto custa trocar a bateria do carro em 2026?",
  "subtitle": "Veja a faixa de preco, os sinais de troca e quando procurar uma autoeletrica",
  "slug": "quanto-custa-trocar-bateria-carro-2026",
  "excerpt": "Veja a faixa de preco da troca, os sinais de bateria fraca e quando procurar uma autoeletrica.",
  "body_md": "## Resposta rapida\n\n...",
  "category_slug": "autoeletrica-e-eletronica",
  "status": "draft",
  "author": "Equipe Editorial Mercado Veiculos",
  "seo_title": "Quanto custa trocar a bateria do carro em 2026?",
  "seo_description": "Entenda a faixa de preco, os sinais de desgaste e quando o diagnostico eletrico e necessario.",
  "cover_media_id": 101,
  "featured": false
}
```

## Markdown Rules

- `body_md` must start at `##`
- do not use `#` in the body
- use Markdown, not HTML
- FAQ sections should use `## Perguntas frequentes`
- FAQ entries must use `### FAQ: ...` so the CMS accordion keeps working
- publishable copy must be Brazilian Portuguese

## Media Rules

- Publish-ready articles should include a real hero image before CMS handoff
- Prefer `cover_media_id` generated through the Media API
- When using Gemini image generation, send `model: gemini-2.5-flash-image` explicitly instead of relying on the backend default
- Use `cover_image_url` only as a fallback when a trusted HTTPS image already exists
- Keep cover alt text and image prompts in natural pt-BR when they are stored in the workflow packet
- If cover generation fails, hold publication or state clearly that the article is missing the main image

## Update Rules

- `PATCH` and `PUT` require the current `version`
- fetch the article before editing it
- if the API returns `409 Conflict`, re-fetch, merge, and retry with the new version
- use numeric `id` in paths, not slug

## Response Shape

Successful writes return a `data` wrapper:

```json
{
  "data": {
    "id": 123,
    "slug": "quanto-custa-trocar-bateria-carro-2026",
    "status": "draft",
    "version": 1,
    "url": "/quanto-custa-trocar-bateria-carro-2026"
  }
}
```

## Runtime Notes

- The live Gemini search key stays in the ignored runtime config, not in Git.
- The tracked bootstrap template remains `openclaw-root/openclaw.json.example`.
- Agents read the operational version of this contract from `openclaw-root/workspace/DOCS_API.md`.
