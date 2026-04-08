# Redis Query Cache — Content

> Relatório de implementação do cache Redis inteligente para queries SQL do módulo.
> Data: 2026-03-18

## Arquivos modificados

| Arquivo | Ação |
|---------|------|
| `Application/Services/ArticlePageQueryService.php` | Cache adicionado em 4 métodos |
| `Application/Services/ContentApiArticleQueryService.php` | Cache adicionado em 2 métodos |
| `Application/Controllers/NewsletterApiController.php` | Cache adicionado em 2 métodos |
| `Application/Controllers/NewsletterController.php` | Cache adicionado em 2 métodos |
| `Infrastructure/Cache/ContentCacheInvalidationService.php` | **Criado** — invalidação centralizada |
| `Application/Services/ArticleCrudService.php` | Invalidação automática em create/update/patch/delete/restore |
| `Domain/Services/ArticleLifecycleService.php` | Invalidação automática em transitionStatus |
| `Providers/ContentServiceProvider.php` | Singleton registrado |
| `tests/Unit/Content/ArticleLifecycleServiceTest.php` | Adaptado para DI (constructor injection) |

## Queries cacheadas

### SEMI_STATIC — 7 dias

| Método | Arquivo | Cache Key |
|--------|---------|-----------|
| `getActiveCategories()` | ContentApiArticleQueryService | `content_categories_active_v1` |
| `getCategoryOrFail()` | ArticlePageQueryService | `content_category_by_slug_v1:category:{slug}` |
| `create()` — categories | NewsletterController | `content_categories_active_ordered_v1` |
| `verify()` — category slugs | NewsletterController | `content_categories_active_slugs_v1` |

### MODERATE — 24 horas

| Método | Arquivo | Cache Key |
|--------|---------|-----------|
| `getActiveCategoriesWithPublishedCount()` | ArticlePageQueryService | `content_categories_active_with_count_v1` |
| `findPublishedArticleWithActiveCategory()` | ArticlePageQueryService | `content_article_published_v1:slug:{slug}` |
| `getCategoryArticlesPaginator()` | ArticlePageQueryService | `content_category_articles_v1:category:{slug}:page:{page}` |

### ACTIVE — 6 horas

| Método | Arquivo | Cache Key |
|--------|---------|-----------|
| `findBySlug()` | ContentApiArticleQueryService | `content_article_by_slug_v1:slug:{slug}` |
| `stats()` | NewsletterApiController | `content_newsletter_stats_v1` |
| `recentArticles()` | NewsletterApiController | `content_recent_articles_v1:days:{days}[:category:{slug}]` |

## Queries sem cache (REALTIME)

| Método | Justificativa |
|--------|---------------|
| `findById()` | API CRUD — lookup por ID para operações de escrita, precisa ser fresco |
| `findTrashedById()` | Operação de restore, dados em mutação |
| `list()` | API com filtros dinâmicos (status, search, date ranges, sort) — combinações infinitas |
| `getArticleForCategoryOrFail()` | Depende de relation instance (category), dados quentes |
| `getCommentsPaginator()` | Comentários mudam frequentemente (user-generated) |
| `getRelatedArticlesPaginator()` | Paginação com exclusão dinâmica por articleId |
| `subscribers()` | API com filtros dinâmicos (since, category_slug) |

## Invalidação

Serviço: `Infrastructure/Cache/ContentCacheInvalidationService.php`

### Métodos disponíveis

| Método | O que invalida |
|--------|----------------|
| `clearAll()` | Todas as chaves do módulo |
| `clearForArticleWrite(?slug, ?categorySlug)` | Artigo específico + listagens + categoria |
| `clearForCategoryWrite(?categorySlug)` | Todas as chaves de categorias + listagens |
| `clearNewsletterCache()` | Estatísticas de newsletter |

### Invalidação automática via API

| Endpoint API | Método do Service | Invalidação |
|-------------|-------------------|-------------|
| `POST /api/content/articles` | `ArticleCrudService::create()` | slug + category + listagens |
| `PUT /api/content/articles/{id}` | `ArticleCrudService::update()` | slug + category + listagens |
| `PATCH /api/content/articles/{id}` | `ArticleCrudService::patch()` | slug + category + listagens |
| `DELETE /api/content/articles/{id}` | `ArticleCrudService::delete()` | slug + category + listagens |
| `POST /api/content/articles/{id}/restore` | `ArticleCrudService::restore()` | slug + category + listagens |
| `POST /api/content/articles/{id}/publish` | `ArticleLifecycleService::publish()` | via transitionStatus |
| `POST /api/content/articles/{id}/unpublish` | `ArticleLifecycleService::unpublish()` | via transitionStatus |
| `POST /api/content/articles/{id}/schedule` | `ArticleLifecycleService::schedule()` | via transitionStatus |
| `POST /api/content/articles/{id}/archive` | `ArticleLifecycleService::archive()` | via transitionStatus |

### Invalidação manual (quando necessário)

- Após `content:generate` (artisan command) — geração AI cria artigos fora do CrudService
- Após `PublishScheduledArticlesCommand` — publica sem passar pelo lifecycle service
- Após `ConsolidateCategoriesCommand` — reorganiza categorias
- Após `MigrateWordPressArticlesCommand` — importação em massa

Para esses cenários, injetar `ContentCacheInvalidationService` e chamar `clearAll()`.

## Resumo numérico

| Métrica | Valor |
|---------|-------|
| Queries cacheadas | 10 |
| Queries sem cache (REALTIME) | 7 |
| Queries pré-existentes | 0 |
| Testes passando | 151/151 |
