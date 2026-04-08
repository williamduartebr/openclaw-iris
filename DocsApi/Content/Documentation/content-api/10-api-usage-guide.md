# Content API — Usage Guide for Agents

Base URL: `{APP_URL}/api/content`
Auth: `Authorization: Bearer {CONTENT_API_KEY}`

All requests require `Accept: application/json`. Write requests also require `Content-Type: application/json`.

---

## Create Article

```bash
curl -X POST {BASE}/articles \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Como Trocar o Óleo do Motor",
    "category_slug": "manutencao",
    "excerpt": "Guia passo a passo para troca de óleo.",
    "body_md": "## Introdução\n\nTrocar o óleo do motor é uma das manutenções mais importantes para a longevidade do seu veículo.\n\n### Materiais Necessários\n\n- Óleo novo (consulte o manual)\n- Filtro de óleo\n- Chave de drenagem\n- Recipiente coletor\n\n### Passo a Passo\n\n1. Aqueça o motor por 5 minutos\n2. Desligue e posicione o recipiente sob o cárter\n3. Remova o bujão de drenagem\n4. Aguarde o escoamento completo\n5. Substitua o filtro\n6. Recoloque o bujão e adicione o óleo novo\n7. Verifique o nível com a vareta\n\n> **Importante:** Descarte o óleo usado em postos de coleta autorizados.",
    "seo_title": "Como Trocar Óleo do Motor | Guia Completo",
    "seo_description": "Aprenda a trocar o óleo do motor do seu carro com segurança.",
    "cover_image_url": "https://example.com/images/oil-change.jpg",
    "author": "Equipe Editorial",
    "featured": false
  }'
```

Response: `201 Created` with full article resource. Default status: `draft`.

---

## List Articles

```bash
# All published, sorted by newest, page 1
curl "{BASE}/articles?status=published&sort=-published_at&per_page=15&page=1" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"

# Filter by category and search
curl "{BASE}/articles?category=manutencao&search=óleo&sort=-created_at" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"

# Featured articles only
curl "{BASE}/articles?featured=1&status=published" \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Query parameters: `status`, `category` (slug), `featured` (0/1), `search`, `sort` (`created_at`, `-created_at`, `published_at`, `-published_at`, `title`, `-title`), `per_page` (1-100, default 15), `page`.

Response includes `data` (array, no `body_md`) and `meta` (pagination).

---

## Show Article

```bash
curl {BASE}/articles/50 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Response: `200 OK` with full article resource including `body_md`.

---

## Patch Article (Partial Update)

**Always include the current `version` for optimistic concurrency.**

```bash
curl -X PATCH {BASE}/articles/50 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "version": 1,
    "subtitle": "Guia atualizado para 2026",
    "featured": true
  }'
```

Response: `200 OK` with updated article. Version is incremented automatically.

---

## Full Update (PUT)

Replaces all fields. All required fields must be present.

```bash
curl -X PUT {BASE}/articles/50 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "version": 2,
    "title": "Novo Título Completo",
    "category_slug": "manutencao",
    "excerpt": "Novo excerpt.",
    "body_md": "## Conteúdo completo substituído\n\nTodo o corpo do artigo deve ser enviado novamente neste endpoint."
  }'
```

---

## Publish Article

```bash
curl -X POST {BASE}/articles/50/publish \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Valid from: `draft`, `review`, `scheduled`. Sets `published_at` and `is_published=true`.

---

## Archive Article

```bash
curl -X POST {BASE}/articles/50/archive \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Valid from: `published` only. To re-publish, transition archived→draft first, then publish.

---

## Schedule Article

```bash
curl -X POST {BASE}/articles/50/schedule \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"published_at": "2026-04-01T09:00:00-03:00"}'
```

Valid from: `draft`, `review`. The `content:publish-scheduled` command auto-publishes when `published_at` arrives.

---

## Delete Article (Soft Delete)

```bash
curl -X DELETE {BASE}/articles/50 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Response: `200 OK` with `deleted_at` timestamp. Article is excluded from listings but remains in database.

---

## Restore Article

```bash
curl -X POST {BASE}/articles/50/restore \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Accept: application/json"
```

Clears `deleted_at`. Article returns to its previous status.

---

## Version Conflict Handling

If another process updated the article since you last read it:

```bash
# You read article at version 2, but it was updated to version 3
curl -X PATCH {BASE}/articles/50 \
  -H "Authorization: Bearer {TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"version": 2, "title": "My Update"}'
```

Response: `409 Conflict`
```json
{
  "message": "Version conflict. The article has been modified since your last read.",
  "current_version": 3,
  "your_version": 2
}
```

**Resolution:** Re-fetch the article with GET, merge your changes with the current state, and retry with the current version number.

---

## Status Transition Map

```
draft ──→ review ──→ published ──→ archived
  │                      ↑              │
  ├──→ published ────────┘              │
  ├──→ scheduled ──→ published          │
  │         (auto)                      │
  └─────────────────────────────────────┘
                archived → draft (only)
```

## Error Codes

| Code | Meaning |
|------|---------|
| 401 | Missing or invalid Bearer token |
| 403 | Valid token but unauthorized action |
| 404 | Article not found |
| 409 | Version conflict (optimistic lock) |
| 422 | Validation error or invalid status transition |
| 429 | Rate limit exceeded |
