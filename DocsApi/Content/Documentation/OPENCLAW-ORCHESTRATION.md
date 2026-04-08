# 08 — Orquestracao via OpenClaw

> Guia para integracao do modulo Content com orquestracao por agentes via OpenClaw. Define workflows, contratos e padroes de automacao.

**Navegacao**: [AI_CONTENT_PROTOCOL.md](./AI_CONTENT_PROTOCOL.md) | [ARTICLE_GENERATION.md](./ARTICLE_GENERATION.md) | [API Spec → content-api/](./content-api/02-api-specification.md)

---

## 1. Contexto

O **Content** e o modulo editorial do MercadoVeiculos — gerencia artigos, categorias, newsletter e comentarios. Integra geracao de conteudo via IA (Claude/OpenAI), publicacao agendada, SEO estruturado e API REST completa. Agentes OpenClaw interagem com este modulo para:

- Gerar artigos SEO automaticamente via IA (Claude ou OpenAI)
- Gerenciar o ciclo de vida de artigos (draft → review → scheduled → published → archived)
- Publicar artigos agendados
- Consultar e listar artigos por categoria, status e slug
- Manter categorias e metadados editoriais
- Integrar imagens via modulo Media (cover, gallery)

**OpenClaw** e a plataforma de orquestracao de agentes LLM usada neste projeto.

---

## 2. Capacidades Atuais (CLI)

### 2.1 Geracao de Artigos via IA

```bash
# Listar titulos disponiveis por categoria
php artisan content:generate --list

# Gerar artigo especifico (categoria + indice do titulo)
php artisan content:generate --category=dicas --index=3 --provider=claude

# Gerar todos os titulos pendentes de uma categoria
php artisan content:generate --category=dicas --all --provider=claude

# Gerar com track especifico
php artisan content:generate --category=dicas --track=technical --provider=openai

# Dry run (preview sem gravar)
php artisan content:generate --category=dicas --index=1 --dry-run
```

**Pre-condicao**: Titulos definidos em `article-titles-seo.json`; API key do provider configurada no .env
**Pos-condicao**: Artigo criado com status `draft` ou `scheduled`, slug unico, metadados SEO preenchidos
**Idempotencia**: Nao — cada execucao gera um novo artigo. Titulos ja publicados sao marcados com ✓ no `--list`

### 2.2 Publicacao de Artigos Agendados

```bash
php artisan content:publish-scheduled
```

**Pre-condicao**: Artigos com `status = scheduled` e `published_at <= now()`
**Pos-condicao**: Artigos transicionados para `published`, `is_published = true`
**Idempotencia**: Sim — artigos ja publicados nao sao afetados

### 2.3 Consolidacao de Categorias

```bash
php artisan content:consolidate-categories
```

**Pre-condicao**: Categorias redundantes existem no banco
**Pos-condicao**: 13 categorias principais ativas; categorias redundantes desativadas; artigos migrados
**Idempotencia**: Sim — usa `firstOrCreate` e verificacoes de existencia

### 2.4 Migracao WordPress

```bash
# Migrar artigos de export JSON do WordPress
php artisan content:migrate-wp --file=blog_export.json
```

**Pre-condicao**: Arquivo JSON de export em `database/import-csv-redirect-301/`
**Pos-condicao**: Artigos migrados com imagens no S3, conteudo convertido para Markdown, redirects 301 gerados
**Idempotencia**: Sim — `updateOrCreate` por `wp_post_id`

### 2.5 Consultas via Tinker

```bash
# Artigos publicados por categoria
php artisan tinker --execute="
    Src\Content\Domain\Models\Article::published()
        ->whereHas('category', fn(\$q) => \$q->where('slug', 'dicas'))
        ->count();
"

# Artigos agendados para publicacao
php artisan tinker --execute="
    Src\Content\Domain\Models\Article::where('status', 'scheduled')
        ->where('published_at', '<=', now())
        ->get(['id', 'title', 'published_at'])
        ->each(fn(\$a) => echo \"#{$a->id} {$a->title} ({$a->published_at})\n\");
"

# Categorias ativas com contagem de artigos
php artisan tinker --execute="
    Src\Content\Domain\Models\Category::where('is_active', true)
        ->withCount('publishedArticles')
        ->orderByDesc('published_articles_count')
        ->get(['id', 'name', 'slug', 'funnel_stage'])
        ->each(fn(\$c) => echo \"{$c->name} [{$c->funnel_stage}]: {$c->published_articles_count}\n\");
"

# Artigos em draft sem revisao
php artisan tinker --execute="
    echo Src\Content\Domain\Models\Article::where('status', 'draft')
        ->where('needs_review', true)
        ->count();
"

# Subscribers da newsletter
php artisan tinker --execute="
    echo Src\Content\Domain\Models\NewsletterSubscriber::whereNotNull('verified_at')->count();
"
```

---

## 3. Capacidades Atuais (API REST)

A API REST do Content ja esta implementada. Base URL: `/api/content`

**Autenticacao**: Bearer token via `Authorization: Bearer {CONTENT_API_KEY}`

### 3.1 Health e Categorias

```bash
# Health check (sem auth)
curl -s "${BASE_URL}/api/content/health"

# Listar categorias ativas
curl -s "${BASE_URL}/api/content/categories" \
  -H "Authorization: Bearer ${TOKEN}"
```

### 3.2 CRUD de Artigos

```bash
# Listar artigos (paginado, com filtros)
curl -s "${BASE_URL}/api/content/articles?status=published&category=dicas&per_page=20" \
  -H "Authorization: Bearer ${TOKEN}"

# Buscar por slug
curl -s "${BASE_URL}/api/content/articles/by-slug/como-trocar-oleo" \
  -H "Authorization: Bearer ${TOKEN}"

# Buscar por ID
curl -s "${BASE_URL}/api/content/articles/42" \
  -H "Authorization: Bearer ${TOKEN}"

# Criar artigo (Markdown)
curl -X POST "${BASE_URL}/api/content/articles" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Como Escolher o Oleo Certo para Seu Carro",
    "subtitle": "Guia completo de viscosidade e especificacoes",
    "body_md": "## Introducao\n\nEscolher o oleo...",
    "category_id": 1,
    "excerpt": "Aprenda a escolher o oleo ideal...",
    "seo_title": "Como Escolher Oleo do Motor | Guia Completo",
    "seo_description": "Descubra qual oleo usar...",
    "featured": false,
    "status": "draft"
  }'

# Atualizar artigo (full)
curl -X PUT "${BASE_URL}/api/content/articles/42" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Titulo Atualizado",
    "body_md": "## Conteudo atualizado...",
    "version": 3
  }'

# Atualizar parcial (patch)
curl -X PATCH "${BASE_URL}/api/content/articles/42" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{ "featured": true, "version": 3 }'

# Soft delete
curl -X DELETE "${BASE_URL}/api/content/articles/42" \
  -H "Authorization: Bearer ${TOKEN}"

# Restaurar artigo deletado
curl -X POST "${BASE_URL}/api/content/articles/42/restore" \
  -H "Authorization: Bearer ${TOKEN}"
```

### 3.3 Ciclo de Vida (Status Transitions)

```bash
# Publicar artigo
curl -X POST "${BASE_URL}/api/content/articles/42/publish" \
  -H "Authorization: Bearer ${TOKEN}"

# Despublicar
curl -X POST "${BASE_URL}/api/content/articles/42/unpublish" \
  -H "Authorization: Bearer ${TOKEN}"

# Agendar publicacao
curl -X POST "${BASE_URL}/api/content/articles/42/schedule" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{ "published_at": "2026-04-01T08:00:00-03:00" }'

# Arquivar
curl -X POST "${BASE_URL}/api/content/articles/42/archive" \
  -H "Authorization: Bearer ${TOKEN}"
```

### 3.4 Transicoes de Status Permitidas

```
draft     → review, scheduled, published
review    → draft, scheduled, published
scheduled → draft, published
published → draft, archived
archived  → draft
```

Transicoes invalidas retornam `422 Unprocessable Entity`.

---

## 4. Workflows de Orquestracao

### Workflow 1: Geracao em Lote de Artigos SEO

Gerar artigos automaticamente a partir do banco de titulos SEO.

```
START
  |
  +-- 1. Listar titulos disponiveis
  |     php artisan content:generate --list
  |     Identificar categorias com titulos pendentes (○)
  |
  +-- 2. Selecionar estrategia
  |     Tracks: technical, anti_ai, b2b
  |     Provider: claude (padrao) ou openai
  |
  +-- 3. Gerar artigos em lote
  |     php artisan content:generate --category=dicas --all --provider=claude
  |     Rate limit interno: 2s entre chamadas
  |     Artigos criados como draft ou scheduled
  |
  +-- 4. Revisar artigos gerados
  |     GET /api/content/articles?status=draft&category=dicas
  |     Para cada artigo:
  |       GET /api/content/articles/{id}
  |       Verificar: qualidade, SEO, formatacao Markdown, negrito estrategico sem excesso
  |
  +-- 5. Publicar ou agendar
  |     Se aprovado: POST /api/content/articles/{id}/publish
  |     Se para data futura: POST /api/content/articles/{id}/schedule
  |     Se precisa ajuste: PATCH /api/content/articles/{id}
  |
  +-- 6. Gerar relatorio
        {
            "total_generated": 15,
            "published": 10,
            "scheduled": 3,
            "needs_review": 2,
            "errors": 0
        }
```

### Workflow 2: Pipeline Editorial Completo (API)

Criar, revisar e publicar artigo via API REST.

```
START
  |
  +-- 1. Verificar categorias disponiveis
  |     GET /api/content/categories
  |     Selecionar category_id adequada ao tema
  |
  +-- 2. Criar artigo em draft
  |     POST /api/content/articles
  |     Body: title, body_md (Markdown), category_id, excerpt, seo_*
  |     Resposta: { "data": { "id": 42, "slug": "...", "version": 1 } }
  |
  +-- 3. (Opcional) Associar media
  |     Se cover image gerada via Media API:
  |       PATCH /api/content/articles/42
  |       Body: { "cover_media_id": "media-uuid", "version": 1 }
  |
  +-- 4. Revisar conteudo
  |     GET /api/content/articles/42
  |     Verificar: body_md, SEO metadata, slug, categorias
  |
  +-- 5. Ajustar se necessario
  |     PATCH /api/content/articles/42
  |     Body: { "seo_title": "Titulo Otimizado", "version": 2 }
  |
  +-- 6. Publicar
  |     POST /api/content/articles/42/publish
  |     Ou: POST /api/content/articles/42/schedule
  |       Body: { "published_at": "2026-04-01T08:00:00-03:00" }
  |
  +-- 7. Verificar publicacao
        GET /api/content/articles/by-slug/{slug}
        Confirmar status = "published" e full_url correto
```

### Workflow 3: Manutencao e Limpeza Editorial

Manter qualidade do acervo: arquivar desatualizados, corrigir SEO, atualizar conteudo.

```
START
  |
  +-- 1. Identificar artigos desatualizados
  |     GET /api/content/articles?status=published&sort=published_at
  |     Filtrar artigos com mais de 12 meses
  |
  +-- 2. Para cada artigo desatualizado:
  |     GET /api/content/articles/{id}
  |     Avaliar: conteudo ainda relevante?
  |
  +-- 3. Decisao por artigo
  |     Se irrelevante: POST /api/content/articles/{id}/archive
  |     Se precisa atualizacao:
  |       Gerar novo conteudo via LLM
  |       PUT /api/content/articles/{id} (body_md atualizado, version)
  |     Se SEO fraco:
  |       PATCH /api/content/articles/{id} (seo_title, seo_description)
  |
  +-- 4. Publicacao agendada
  |     php artisan content:publish-scheduled
  |     (Executado automaticamente via scheduler)
  |
  +-- 5. Gerar relatorio
        {
            "total_reviewed": 50,
            "archived": 8,
            "updated": 15,
            "seo_improved": 12,
            "kept_as_is": 15
        }
```

### Workflow 4: Migracao de Conteudo Externo

Importar artigos de fontes externas (WordPress, CSV, parceiros).

```
START
  |
  +-- 1. Preparar export
  |     Formato: JSON com campos title, content (Markdown), categories, images
  |     Salvar em database/import-csv-redirect-301/
  |
  +-- 2. Executar migracao
  |     php artisan content:migrate-wp --file=export.json
  |     Conversao automatica: HTML → Markdown
  |     Imagens baixadas para S3
  |
  +-- 3. Verificar artigos migrados
  |     GET /api/content/articles?status=draft
  |     Para cada: verificar formatacao Markdown, imagens, categorias
  |
  +-- 4. Ajustar e publicar
  |     PATCH /api/content/articles/{id} (correcoes)
  |     POST /api/content/articles/{id}/publish
  |
  +-- 5. Configurar redirects 301
  |     Arquivo gerado: database/import-csv-redirect-301/redirects.json
  |     Aplicar no Nginx ou middleware
  |
  +-- 6. Gerar relatorio
        {
            "total_imported": 120,
            "images_downloaded": 340,
            "categories_mapped": 11,
            "redirects_generated": 120,
            "errors": 3
        }
```

---

## 5. Integracao com Outros Modulos

### 5.1 AutoInfoCenter

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| Artigos no hub principal | Content → AIC | ViewModels do AIC listam artigos publicados |
| SEO structured data | Content → AIC | ArticleStructuredDataService gera JSON-LD |

### 5.2 VehicleDataCenter

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| Dados tecnicos em artigos | VDC → Content | Artigos referenciam make/model para contexto tecnico |
| Geracao AI com dados | Content → VDC | (Futuro) Agente consulta VDC API para enriquecer artigos |

### 5.3 GuideDataCenter

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| Crosslink guias ↔ artigos | Content ↔ GDC | Artigos linkam para guias relacionados e vice-versa |

### 5.4 Media

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| Cover image | Media → Content | `cover_media_id` referencia `media_assets.id` |
| Gallery images | Media → Content | `gallery_media` JSON array com media IDs |
| Resolucao de URL | Content → Media | `MediaAssetResolver` consulta `media_assets` via DB query (sem FK) |

### 5.5 Sitemap e RSS

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| Artigos publicados | Content → Sitemap | Sitemap inclui URLs de artigos publicados |
| Feed RSS | Content → RSS | RSS gera feeds por categoria com artigos recentes |

### 5.6 Landing

| Ponto de Integracao | Direcao | Mecanismo |
|--------------------|---------|-----------|
| CTAs dinamicos | Content → Landing | Landing pages linkam para artigos relevantes |
| From-advertise flow | Content → Landing | Rota `plans.index` (`/anuncie`) captura tráfego do blog |

---

## 6. Tratamento de Erros e Estrategia de Retry

### 6.1 Erros de Geracao IA (CLI)

| Erro | Causa | Acao do Agente |
|------|-------|---------------|
| `Provider timeout` | Claude/OpenAI lento | Re-executar com `--index` especifico |
| `Invalid response format` | IA retornou formato inesperado | Verificar prompt; tentar outro `--provider` |
| `API key invalid` | Chave expirada ou incorreta | Verificar `ANTHROPIC_API_KEY` ou `OPENAI_API_KEY` no .env |
| `Rate limit exceeded` | Muitas chamadas ao provider | Aguardar 60s; reduzir batch size |
| `Title already published` | Titulo ja usado | Normal — escolher outro titulo (✓ no --list) |

### 6.2 Erros de API REST

| Codigo | Causa | Acao do Agente |
|--------|-------|---------------|
| 401 | Token invalido | Verificar `CONTENT_API_KEY` no .env |
| 404 | Artigo/categoria nao encontrado | Buscar via `/articles/by-slug/{slug}` ou listar |
| 409 | Conflito de versao (optimistic lock) | Recarregar artigo, obter `version` atual, re-enviar |
| 422 | Validacao falhou | Corrigir payload conforme `errors` na resposta |
| 422 | Transicao de status invalida | Verificar status atual e transicoes permitidas (secao 3.4) |
| 429 | Rate limit | Aguardar `retry_after` segundos |
| 500 | Erro interno | Logar e re-tentar ate 3x com backoff |

### 6.3 Estrategia de Retry Padrao

```
Tentativa 1: Imediata
Tentativa 2: Aguardar 5 segundos
Tentativa 3: Aguardar 30 segundos
Apos 3 falhas: Logar erro e reportar
```

Para geracao em lote: falha em um artigo nao bloqueia os demais. Sleep de 2s entre chamadas ja incluso.

---

## 7. Limites e Restricoes

| Restricao | Descricao |
|-----------|-----------|
| **Rate limit leitura** | 120 requests/min |
| **Rate limit escrita** | 30 requests/min |
| **Conteudo Markdown apenas** | Novos artigos via API devem usar Markdown (nao HTML) |
| **HTML legacy read-only** | Artigos importados do WordPress podem conter HTML — nao sobrescrever |
| **Optimistic locking** | PUT/PATCH exigem campo `version` correto; 409 se desatualizado |
| **Soft delete apenas** | Artigos deletados podem ser restaurados via `/restore` |
| **Nao alterar slug de publicado** | Quebraria URLs indexadas e SEO |
| **Nao deletar categorias com artigos** | Cascade delete removeria artigos — usar desativacao (`is_active = false`) |
| **IA rate limit interno** | 2s sleep entre chamadas no `content:generate --all` |
| **Titulo h1 reservado** | Markdown deve iniciar com `##` (h2); `<h1>` e o titulo do sistema |
| **Media sem FK** | `cover_media_id` e referencia logica, sem foreign key constraint |

---

## 8. Monitoramento e Feedback

### Metricas que agentes devem reportar

| Metrica | Descricao |
|---------|-----------|
| `articles_generated` | Artigos gerados via IA na sessao |
| `articles_published` | Artigos publicados (manual ou scheduled) |
| `articles_scheduled` | Artigos agendados para publicacao futura |
| `articles_updated` | Artigos atualizados (conteudo ou SEO) |
| `articles_archived` | Artigos arquivados |
| `categories_active` | Total de categorias ativas |
| `generation_errors` | Falhas na geracao IA |
| `api_errors` | Erros de API (4xx/5xx) |
| `processing_time` | Tempo total de processamento |

### Formato de log recomendado

```json
{
    "workflow": "article_batch_generation",
    "agent_id": "openclaw-agent-xyz",
    "timestamp": "2026-03-19T10:30:00-03:00",
    "result": "success",
    "summary": {
        "articles_generated": 15,
        "articles_published": 10,
        "articles_scheduled": 3,
        "generation_errors": 0,
        "provider": "claude"
    },
    "category": "dicas",
    "track": "technical",
    "duration_seconds": 180
}
```

---

## 9. Compatibilidade com Skills

### /modular-feature-planner

Ao planejar nova feature via OpenClaw:
1. Ler esta documentacao completa
2. Consultar [content-api/10-api-usage-guide.md](./content-api/10-api-usage-guide.md) para endpoints disponiveis
3. Verificar [AI_CONTENT_PROTOCOL.md](./AI_CONTENT_PROTOCOL.md) para regras de conteudo IA
4. Consultar [ARTICLE_GENERATION.md](./ARTICLE_GENERATION.md) para fluxo de geracao

### /modular-feature-builder

Ao implementar:
1. Seguir convencoes do modulo (Domain → Application → Infrastructure → Presentation)
2. Respeitar [03-markdown-content-contract.md](./content-api/03-markdown-content-contract.md) para formato de conteudo
3. Validar com [06-validation-rules.md](./content-api/06-validation-rules.md) para regras de input
4. Testar com [07-testing-strategy.md](./content-api/07-testing-strategy.md) como referencia
5. Usar optimistic locking (`version`) em toda escrita via API

---

**Navegacao**: [AI_CONTENT_PROTOCOL.md](./AI_CONTENT_PROTOCOL.md) | [ARTICLE_GENERATION.md](./ARTICLE_GENERATION.md) | [API Spec → content-api/](./content-api/02-api-specification.md)
