# 04 — Agent Integration Guide

## Purpose

Step-by-step guide for OpenClaw agents and external LLMs that will create, read, edit, and delete articles through the Content API. Every workflow is explicit, with request examples. No ambiguity.

---

## Prerequisites

Before making any API call, you need:

1. **Content API base URL**: `{BASE_URL}/api/content/articles`
2. **Content API Bearer token**: Include in every Content API request:

```
Authorization: Bearer {CONTENT_API_KEY}
Content-Type: application/json
Accept: application/json
```

3. **Media API base URL** (for image generation): `{BASE_URL}/api/media/images`
4. **Media API Bearer token**: Include in every Media API request:

```
Authorization: Bearer {MEDIA_API_KEY}
```

5. **Category slugs** (optional): If omitted, articles default to the `geral` category. Available slugs include: `dicas-e-curiosidades`, `manutencao-e-revisao-programada`, `seguro-auto`, `novidades-automotivas`, `marketing-automotivo`, `gestao-de-clientes`, etc.

---

## Workflow 1: Create a New Article

### Minimal creation (draft)

```
POST /api/content/articles
```

```json
{
  "title": "Como verificar o nível de óleo do motor",
  "body_md": "## Introdução\n\nVerificar o nível de óleo do motor é uma tarefa simples que todo motorista deve saber fazer.\n\n## Passo a Passo\n\n### 1. Estacione em superfície plana\n\nCertifique-se de que o veículo está em uma superfície nivelada.\n\n### 2. Aguarde o motor esfriar\n\nEspere pelo menos 5 minutos após desligar o motor.\n\n### 3. Localize a vareta de óleo\n\nA vareta geralmente tem uma alça amarela ou laranja.\n\n### 4. Verifique o nível\n\nPuxe a vareta, limpe com um pano, insira novamente e puxe para verificar o nível entre as marcas MIN e MAX.",
  "category_slug": "dicas",
  "excerpt": "Aprenda a verificar o nível de óleo do motor do seu carro em 4 passos simples."
}
```

**What happens**: Article is created with `status: draft`. Slug is auto-generated from the title. Version starts at 1. The article is not visible to readers.

**Response** (201):

```json
{
  "data": {
    "id": 99,
    "title": "Como verificar o nível de óleo do motor",
    "slug": "como-verificar-o-nivel-de-oleo-do-motor",
    "status": "draft",
    "version": 1,
    "created_at": "2026-03-15T14:00:00Z",
    "..."
  }
}
```

**Save the `id` and `version`** — you need them for subsequent operations.

### Full creation (with AI-generated images)

```json
{
  "title": "Como verificar o nível de óleo do motor",
  "subtitle": "Guia completo para iniciantes",
  "slug": "como-verificar-nivel-oleo-motor",
  "body_md": "## Introdução\n\nVerificar o nível de óleo...\n\n![Vareta de óleo sendo verificada](https://s3.../Media/10/image.webp)\n\n## Passo a Passo\n\n...",
  "category_slug": "dicas-e-curiosidades",
  "excerpt": "Aprenda a verificar o nível de óleo do motor em 4 passos simples.",
  "status": "draft",
  "featured": false,
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Vareta de óleo sendo verificada em motor frio" },
    { "media_id": 11, "alt": "Nível de óleo entre marcas MIN e MAX" }
  ],
  "author": "Equipe Editorial",
  "image_source": "ai",
  "seo_title": "Como Verificar Nível de Óleo do Motor — Guia 2026",
  "seo_description": "Passo a passo para verificar o nível de óleo do motor."
}
```

**Note on `image_source`**: Set this field to control the image credit caption on the article page. Values: `ai` (default — "Imagem ilustrativa gerada por IA."), `real` ("Imagem: acervo Mercado Veículos."), `press` ("Imagem: divulgação."), `stock` ("Imagem: banco de imagens."). If omitted, defaults to `ai`. You can update it later via PATCH.

**Note**: The orchestrator embedded one image directly in `body_md` at the precise position. The remaining `gallery_media` images will be auto-appended to the end of `body_md` (default `gallery_mode: "inline"`). To prevent auto-append, embed all images manually in `body_md` and omit `gallery_media`, or use `"gallery_mode": "gallery"`.

---

## Workflow 2: Read an Article

### By ID

```
GET /api/content/articles/99
```

**Response** (200): Full article resource with `body_md`, all metadata, and `version`.

### Find by slug (via list endpoint)

```
GET /api/content/articles?search=como-verificar-nivel-oleo-motor&per_page=1
```

---

## Workflow 3: List Articles

### All published articles

```
GET /api/content/articles?status=published&sort=-published_at
```

### Drafts only

```
GET /api/content/articles?status=draft&sort=-created_at
```

### By category

```
GET /api/content/articles?category=dicas&status=published
```

### Search

```
GET /api/content/articles?search=óleo+motor&status=published
```

### Include soft-deleted articles

```
GET /api/content/articles?trashed=with
```

### Only soft-deleted articles

```
GET /api/content/articles?trashed=only
```

---

## Workflow 4: Update Only Title and SEO Metadata

Use PATCH to change specific fields without affecting the body or other content.

**Step 1**: Read the article to get the current `version`.

```
GET /api/content/articles/99
```

Save `version` from the response (e.g., `1`).

**Step 2**: Send the PATCH with only the fields you want to change.

```
PATCH /api/content/articles/99
```

```json
{
  "version": 1,
  "title": "Como Verificar o Nível de Óleo — Guia Atualizado",
  "seo_title": "Verificar Nível de Óleo do Motor — Guia Completo 2026",
  "seo_description": "Aprenda a verificar o nível de óleo do motor passo a passo. Atualizado para 2026."
}
```

**Result**: Only `title`, `seo_title`, and `seo_description` change. The `body_md`, images, category, and everything else remain untouched. Version increments to 2.

---

## Workflow 5: Replace the Markdown Body

**Step 1**: Read the current article.

```
GET /api/content/articles/99
```

**Step 2**: Send PATCH with the new complete body.

```
PATCH /api/content/articles/99
```

```json
{
  "version": 2,
  "body_md": "## Introdução\n\nConteúdo completamente reescrito...\n\n## Novos Passos\n\n### 1. Novo primeiro passo\n\nDescrição...\n\n### 2. Novo segundo passo\n\nDescrição..."
}
```

**Warning**: This replaces the entire body. There is no section-level replacement endpoint. If you want to edit a specific section, see Workflow 6.

---

## Workflow 6: Append a New Section

**Step 1**: Read the current article and extract `body_md`.

```
GET /api/content/articles/99
```

Extract the `body_md` field from the response.

**Step 2**: Append your new section to the existing content.

```
PATCH /api/content/articles/99
```

```json
{
  "version": 2,
  "body_md": "... existing body_md content ...\n\n## Nova Seção Adicionada\n\nConteúdo da nova seção aqui.\n\n### FAQ: Pergunta frequente?\n\nResposta para a pergunta frequente."
}
```

**Critical rule**: Always concatenate the existing `body_md` + your new section. Never send only the new section — that would erase everything else.

---

## Workflow 7: Edit a Specific Section

There is no section-level edit endpoint. Follow this procedure:

**Step 1**: Read the article.

**Step 2**: Parse the `body_md` in your context. Identify the section you want to change by its heading.

**Step 3**: Modify that section in your local copy of `body_md`.

**Step 4**: Send the full modified `body_md` via PATCH.

```
PATCH /api/content/articles/99
```

```json
{
  "version": 2,
  "body_md": "## Introdução\n\nConteúdo original...\n\n## Seção Editada\n\nNovo conteúdo para esta seção específica.\n\n## Conclusão\n\nConteúdo original..."
}
```

---

## Workflow 8: Update Images

### Replace cover image (via Media asset)

```
PATCH /api/content/articles/99
```

```json
{
  "version": 2,
  "cover_media_id": 50
}
```

### Replace cover image (via plain URL)

```json
{
  "version": 2,
  "cover_image_url": "https://images.example.com/new-cover.webp"
}
```

**Note**: Setting `cover_image_url` without `cover_media_id` clears the media reference. Setting `cover_media_id` takes precedence and auto-resolves the URL.

### Add gallery images (auto-embedded in body)

```json
{
  "version": 2,
  "gallery_media": [
    { "media_id": 50, "alt": "Novo filtro de óleo instalado" },
    { "media_id": 51, "alt": "Motor limpo após troca" }
  ]
}
```

Default behavior (`gallery_mode: "inline"`): images are appended as `![alt](url)` to the end of `body_md`. The structured data is also saved in `gallery_media` and `gallery_image_urls`.

### Add gallery images (structured only, no body embed)

```json
{
  "version": 2,
  "gallery_media": [
    { "media_id": 50 },
    { "media_id": 51 }
  ],
  "gallery_mode": "gallery"
}
```

### Remove cover image

```json
{
  "version": 2,
  "cover_image_url": null
}
```

### Remove all gallery images

```json
{
  "version": 2,
  "gallery_media": []
}
```

**Note**: Removing `gallery_media` does NOT remove `![](url)` from `body_md` if they were previously embedded. To fully remove inline images, also update `body_md`.

---

## Workflow 9: Update Video URLs

Same array-replacement pattern as gallery images.

### Set video URLs

```json
{
  "version": 2,
  "video_urls": [
    "https://www.youtube.com/watch?v=abc123",
    "https://www.youtube.com/watch?v=def456"
  ]
}
```

### Add a video (preserving existing)

First read the current `video_urls`, then send the combined array:

```json
{
  "version": 2,
  "video_urls": [
    "https://www.youtube.com/watch?v=abc123",
    "https://www.youtube.com/watch?v=def456",
    "https://www.youtube.com/watch?v=newVideo"
  ]
}
```

### Remove all videos

```json
{
  "version": 2,
  "video_urls": []
}
```

---

## Workflow 10: Publish an Article

### Direct publish

```
POST /api/content/articles/99/publish
```

```json
{
  "version": 2
}
```

**Result**: Status changes to `published`. `published_at` is set to now.

### Schedule for future publication

```
POST /api/content/articles/99/schedule
```

```json
{
  "version": 2,
  "published_at": "2026-04-01T09:00:00Z"
}
```

### Unpublish (revert to draft)

```
POST /api/content/articles/99/unpublish
```

```json
{
  "version": 3
}
```

### Archive

```
POST /api/content/articles/99/archive
```

```json
{
  "version": 3
}
```

---

## Workflow 11: Delete an Article Safely

### Soft delete

```
DELETE /api/content/articles/99
```

```json
{
  "version": 3
}
```

**Result**: Article is soft-deleted. It is no longer visible to readers or in default list results. It can be restored.

### Restore a deleted article

```
POST /api/content/articles/99/restore
```

**Result**: Article is restored to its previous status.

### Check for deleted articles

```
GET /api/content/articles?trashed=only
```

---

## Workflow 12: Avoid Destructive Overwrites

### Always read before write

Every update or patch must include the `version` field. This prevents two agents from overwriting each other's changes.

**Sequence**:

1. `GET /api/content/articles/99` → response includes `"version": 3`
2. `PATCH /api/content/articles/99` with `"version": 3` → succeeds, version becomes 4
3. Another agent sends `PATCH` with `"version": 3` → fails with `409 Conflict`

### Handle 409 Conflict

When you receive a 409:

1. Read the article again to get the latest content and version
2. Re-evaluate your changes against the current state
3. Merge if needed
4. Retry with the new version number

**Never** retry a failed update without re-reading the article first.

### Do not use PUT for small changes

`PUT` replaces all fields. If you only need to change the title, use `PATCH`. This minimizes the risk of accidentally clearing fields.

---

## Workflow 13: Full Content Automation with Media API

This is the **recommended end-to-end workflow** for an external orchestrator (OpenClaw) that generates articles with AI-generated images.

### Step 1: Generate images via Media API

```
POST /api/media/images/generate
Authorization: Bearer {MEDIA_API_KEY}
```

```json
{
  "prompt": "Mecânico inspecionando nível de óleo do motor em oficina moderna brasileira",
  "provider": "google_gemini",
  "quality": "high",
  "style": "natural",
  "metadata": { "usage": "cover_image", "article_topic": "oil_check" }
}
```

**Response** (202):

```json
{
  "data": {
    "id": 7,
    "status": "pending",
    "provider": "google_gemini"
  }
}
```

Repeat for each image needed. Typically: 1 cover + 3-4 inline images = 4-5 total.

### Step 2: Poll until all images are completed

```
GET /api/media/images/7
Authorization: Bearer {MEDIA_API_KEY}
```

Wait until `status: "completed"` and `final_url` is present for each image. The Media pipeline generates the image, uploads to S3, dispatches WebP compression, and updates the status.

**Response when completed**:

```json
{
  "data": {
    "id": 7,
    "status": "completed",
    "final_url": "https://s3.../Media/7/uuid.webp",
    "mime_type": "image/webp"
  }
}
```

### Step 3: Write article Markdown with images at correct positions

The orchestrator should compose `body_md` with `![alt](final_url)` placed at the exact sections where each image belongs:

```markdown
## Introdução

Verificar o nível de óleo do motor é uma tarefa simples...

![Vareta de óleo sendo verificada em motor frio](https://s3.../Media/10/uuid.webp)

## Passo a Passo

### 1. Estacione em superfície plana

Certifique-se de que o veículo está nivelado...

![Nível de óleo entre marcas MIN e MAX na vareta](https://s3.../Media/11/uuid.webp)

### 2. Aguarde o motor esfriar

Espere pelo menos 5 minutos...
```

**This is the most precise method** — the orchestrator decides exactly where each image appears.

### Step 4: Create article via Content API

```
POST /api/content/articles
Authorization: Bearer {CONTENT_API_KEY}
```

```json
{
  "title": "Como Verificar o Nível de Óleo do Motor",
  "subtitle": "Guia completo para iniciantes",
  "body_md": "## Introdução\n\nVerificar o nível...\n\n![Vareta de óleo](https://s3.../Media/10/uuid.webp)\n\n## Passo a Passo\n\n...",
  "excerpt": "Aprenda a verificar o nível de óleo do motor em 4 passos.",
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Vareta de óleo sendo verificada" },
    { "media_id": 11, "alt": "Nível de óleo na vareta" }
  ],
  "gallery_mode": "gallery",
  "image_source": "ai",
  "seo_title": "Como Verificar Nível de Óleo do Motor",
  "seo_description": "Passo a passo para verificar o nível de óleo do motor."
}
```

**Important**: Since images are already embedded in `body_md`, use `gallery_mode: "gallery"` to prevent duplication. The `gallery_media` field preserves media traceability without re-appending images.

### Step 5: Publish

```
POST /api/content/articles/{id}/publish
Authorization: Bearer {CONTENT_API_KEY}
```

### Alternative: Auto-append (simpler but less precise)

If the orchestrator does not need precise image placement, it can skip embedding images in `body_md` and let the API auto-append:

```json
{
  "title": "Como Verificar o Nível de Óleo do Motor",
  "body_md": "## Introdução\n\n...\n\n## Passo a Passo\n\n...",
  "cover_media_id": 7,
  "gallery_media": [
    { "media_id": 10, "alt": "Vareta de óleo" },
    { "media_id": 11, "alt": "Nível de óleo" }
  ]
}
```

With the default `gallery_mode: "inline"`, images are appended as `![alt](url)` at the end of `body_md`. Less precise, but zero orchestrator effort for image placement.

---

## Quick Reference: Common Operations

| I want to... | Method | Endpoint | Required fields |
|---------------|--------|----------|-----------------|
| Create a draft | POST | `/api/content/articles` | `title`, `body_md` |
| Create with images | POST | `/api/content/articles` | `title`, `body_md`, `cover_media_id`, `gallery_media` |
| Set image credit type | PATCH | `/api/content/articles/{id}` | `version`, `image_source` |
| Read an article | GET | `/api/content/articles/{id}` | — |
| List all drafts | GET | `/api/content/articles?status=draft` | — |
| Change the title | PATCH | `/api/content/articles/{id}` | `version`, `title` |
| Change SEO metadata | PATCH | `/api/content/articles/{id}` | `version`, `seo_title`/`seo_description` |
| Replace the body | PATCH | `/api/content/articles/{id}` | `version`, `body_md` |
| Add images (inline) | PATCH | `/api/content/articles/{id}` | `version`, `gallery_media` |
| Add images (gallery only) | PATCH | `/api/content/articles/{id}` | `version`, `gallery_media`, `gallery_mode: "gallery"` |
| Set cover image | PATCH | `/api/content/articles/{id}` | `version`, `cover_media_id` |
| Add a video | PATCH | `/api/content/articles/{id}` | `version`, `video_urls` (full array) |
| Publish | POST | `/api/content/articles/{id}/publish` | `version` |
| Schedule | POST | `/api/content/articles/{id}/schedule` | `version`, `published_at` |
| Unpublish | POST | `/api/content/articles/{id}/unpublish` | `version` |
| Archive | POST | `/api/content/articles/{id}/archive` | `version` |
| Soft delete | DELETE | `/api/content/articles/{id}` | `version` (optional) |
| Restore | POST | `/api/content/articles/{id}/restore` | — |
| Search by slug | GET | `/api/content/articles?search={slug}` | — |

---

## Error Handling for Agents

### How to interpret error responses

**401 Unauthorized**:
- Your token is missing or invalid
- Check `Authorization: Bearer {token}` header

**404 Not Found**:
- The article ID does not exist or is soft-deleted
- If looking for deleted articles, use `?trashed=only` on the list endpoint

**409 Conflict**:
- Another agent modified the article since your last read
- Re-read the article, get the new version, and retry

**422 Unprocessable Entity**:
- Validation failure. Parse the `errors` object for details
- Example: `{"errors": {"slug": ["The slug has already been taken."]}}`

**429 Too Many Requests**:
- Rate limit exceeded. Wait for the time specified in `Retry-After` header

---

## Idempotency Considerations

The API does not support idempotency keys. To avoid duplicate creation:

1. Before creating, search for an article with the same slug: `GET /api/content/articles?search={slug}`
2. If found, update it instead of creating a new one
3. Slug uniqueness is enforced — a duplicate slug creation will return 422

For updates, the `version` field provides natural idempotency — the same PATCH with the same version will succeed exactly once.

---

*Previous: [03-markdown-content-contract.md](./03-markdown-content-contract.md)*
*Next: [05-domain-impact-analysis.md](./05-domain-impact-analysis.md)*
