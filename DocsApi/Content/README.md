# Content Module (Blog)

Módulo de conteúdo editorial no estilo Medium para o Mercado Veículos.

## Características

- **URLs SEO-friendly**: Estrutura `/artigos/{categoria}/{slug}`
- **Design Medium-style**: Tipografia grande, espaços em branco, foco na leitura
- **SEO otimizado**: Meta tags, Open Graph, Schema.org JSON-LD
- **Artigos relacionados**: Sugestões automáticas por categoria
- **Tempo de leitura**: Estimativa automática
- **Comentários**: Sistema nativo com moderação automática

---

## Comandos Artisan

### `content:migrate-wp` — Importar artigos do WordPress

```bash
docker exec mercadoveiculos-app php artisan content:migrate-wp
```

**Fonte:** `database/import-csv-redirect-301/blog_export.json`

**O que faz:**
- Lê o JSON exportado do WordPress
- Resolve categorias usando mapeamento `CATEGORY_SLUG_MAP` (evita duplicatas)
- Download de imagens (featured + inline) para S3
- `updateOrCreate` via `wp_post_id` (seguro para re-execuções)
- Sincroniza categorias via pivot `article_category`
- Gera redirects 301 em `database/import-csv-redirect-301/`

**Mapeamento WordPress → Local:**

| Slugs WordPress | → Categoria Local |
|---|---|
| `lancamentos-veiculos` | **Novidades Automotivas** |
| `vistoria`, `vistoria-veicular`, `auto-center`, `manutencao-economia` | **Dicas** |
| `cooperativas-de-seguro`, `seguro-automotivo` | **Seguro Auto** |
| `fidelizacao` | **Gestão de Clientes** |
| `introducao-ao-marketing-digital`, `marketing-digital-no-setor-automotivo`, `marketing-digital-para-oficinas-mecanicas`, `primeiros-passos-no-marketing-digital` | **Marketing Automotivo** |
| `ipva`, `impostos`, `ipva-2025` | **IPVA e Licenciamento** |

> Slugs não mapeados são criados como nova categoria via `firstOrCreate`.

---

### `info:migrate-blog-geral` — Importar artigos da categoria Geral

```bash
docker compose exec app php artisan info:migrate-blog-geral
```

Alias compatível:

```bash
docker compose exec app php artisan content:migrate-blog-geral
```

**Fontes:** 
- `database/import-csv-redirect-301/blog_mercadoveiculos_com_export.json`
- `database/import-csv-redirect-301/redirects.json`

**O que faz:**
- Filtra apenas os posts que constam no `redirects.json`
- Limpa o conteúdo (URLs, autoria editorial, UTMs)
- Download de imagens (featured + inline) para S3
- Atribui todos os posts à categoria `geral`
- `updateOrCreate` via `slug`

---

### `content:consolidate-categories` — Consolidar categorias

```bash
docker exec mercadoveiculos-app php artisan content:consolidate-categories
```

**O que faz:**
1. Garante que as 13 categorias principais existam com nome e descrição
2. Migra artigos de categorias redundantes para a categoria destino (pivot + FK)
3. Desativa (`is_active = false`) as categorias redundantes

**Idempotente** — pode rodar múltiplas vezes sem efeito colateral.

---

### `content:generate` — Gerar artigos via IA

```bash
docker exec mercadoveiculos-app php artisan content:generate
```

---

## Fluxo de Setup (Desenvolvimento)

```bash
# 1. Recriar banco
docker exec mercadoveiculos-app php artisan migrate:fresh --seed

# 2. Importar artigos do WordPress
docker exec mercadoveiculos-app php artisan content:migrate-wp

# 3. Consolidar categorias (se necessário)
docker exec mercadoveiculos-app php artisan content:consolidate-categories
```

---

## Categorias (13)

| Categoria | Slug | Descrição |
|---|---|---|
| Gestão de Clientes | `gestao-de-clientes` | Relacionamento, retenção e recorrência |
| Marketing Automotivo | `marketing-automotivo` | Aquisição e posicionamento |
| Implementação de Estratégias Digitais | `implementacao-de-estrategias-digitais` | Execução de campanhas digitais |
| Análise e Métricas | `analise-e-metricas` | KPIs, funil e indicadores |
| Conversão e Vendas Online | `conversao-e-vendas-online` | Converter visitas em vendas |
| Otimização de Motores de Busca (SEO) | `otimizacao-de-motores-de-busca-seo` | Ranquear no Google |
| Tendências de Marketing Digital | `tendencias-de-marketing-digital` | Mudanças de comportamento e canais |
| Presença Online | `presenca-online` | Consistência da marca digital |
| Seguro Auto | `seguro-auto` | Coberturas e comparativos |
| IPVA e Licenciamento | `ipva-e-licenciamento` | Obrigações veiculares |
| Dicas | `dicas` | Dicas práticas (conteúdo novo manual) |
| Novidades Automotivas | `novidades-automotivas` | Lançamentos e tendências |
| Geral | `geral` | Artigos gerais (temporário → `/guias`) |

---

## Estrutura de URLs

```
/artigos                          → Listagem de todas as categorias
/artigos/{categorySlug}           → Artigos de uma categoria
/artigos/{categorySlug}/{slug}    → Artigo individual
```

Redirects legado: `/blog/*` e `/content/*` → `/artigos/*` (301)

---

## Campos do Modelo Article

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `category_id` | FK | Categoria primária |
| `wp_post_id` | Integer | ID do post no WordPress (para import) |
| `title` | String | Título do artigo |
| `slug` | String | URL amigável (único) |
| `excerpt` | Text | Resumo (1-2 frases) |
| `content` | Long Text | Conteúdo HTML |
| `original_content` | Long Text | Conteúdo original antes de edições |
| `featured_image` | String | Path da imagem no S3 |
| `author_name` | String | Nome do autor |
| `reading_time` | Integer | Minutos de leitura |
| `is_published` | Boolean | Publicado? |
| `published_at` | DateTime | Data de publicação |
| `meta` | JSON | Metadados SEO (`description`, `keywords`) |

---

## Arquivos do Módulo

```
src/Content/
├── Application/
│   ├── Commands/
│   │   ├── MigrateWordPressArticlesCommand.php   # Import WP Geral
│   │   ├── MigrateBlogGeralCommand.php            # Import WP Específico (Geral)
│   │   ├── ConsolidateCategoriesCommand.php       # Consolidação
│   │   └── GenerateArticleCommand.php             # Geração IA
│   ├── Controllers/
│   │   └── ArticleController.php                  # Rotas públicas
│   ├── Actions/                                   # Ações de comentários
│   ├── Requests/                                  # Form Requests
│   └── Events/Listeners/                          # Notificações
├── Domain/
│   └── Models/
│       ├── Article.php
│       ├── Category.php                           # Many-to-many via article_category
│       └── Comment.php
├── Infrastructure/
│   └── Database/
│       └── Migrations/
├── Presentation/
│   └── Resources/
│       ├── views/                                 # Blade templates
│       └── js/                                    # Vue/JS components
├── Providers/
│   └── ContentServiceProvider.php                 # Registro de comandos/rotas
├── Routes/
│   └── web.php
└── README.md                                      # Este arquivo
```

---

## SEO e Schema.org

- **Meta tags**: description, keywords, canonical, Open Graph, Twitter Cards
- **JSON-LD**: Article, BreadcrumbList, Comment schema
- **Campos**: wordCount, timeRequired, articleSection, inLanguage

## Queries Úteis

```php
Article::published()->get();
Category::where('slug', 'dicas')->first()->publishedArticles;
Article::published()->latest('published_at')->take(5)->get();
```
