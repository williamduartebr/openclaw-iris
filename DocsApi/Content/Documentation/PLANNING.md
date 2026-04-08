# PLANNING — Evolucao do Modulo Content

## Resumo

Este documento detalha o plano de evolucao do modulo `Content` (blog editorial) desde o estado atual (adaptado do sistema UsadosNaWeb, com geração de artigos IA e sistema de comentários) até a integração completa com pipeline editorial, painel admin e funcionalidades avançadas. O trabalho está dividido em 5 fases, ordenadas por prioridade e dependências.

> Referência: [README.md](../README.md) para visão geral do módulo.
> Referência: [ARTICLE_GENERATION.md](./ARTICLE_GENERATION.md) para o sistema de geração IA.

---

## Estado Atual

### O que já funciona

- **4 categorias ativas**: dicas, noticias, manutencao, lojistas (guias removida — conflito com GuideDataCenter)
- **Rotas públicas** em `/content/{categoria}/{slug}` com SEO completo (JSON-LD, Open Graph, meta tags)
- **Sistema de comentários**: CRUD com replies aninhadas, aprovação automática, moderação de texto, rate limiting, notificações por e-mail (admin + usuário)
- **Correção IA de comentários**: Claude Haiku corrige ortografia/gramática
- **Geração de artigos IA**: Claude Sonnet / GPT-4o com 100 títulos pré-planejados
- **Newsletter**: subscribe + verificação por código de 6 dígitos
- **Views adaptadas** ao layout compartilhado (`shared::layouts.app`)
- **5 arquivos de teste**: Feature (4) + Unit (1)

### Débitos técnicos conhecidos

| Item | Descrição |
|------|-----------|
| Newsletter e-mail | `store()` tem TODO — não envia o código por e-mail ainda |
| Categoria lojistas | Sem rota definida (não aparece em `web.php`) |
| Admin comments | Rota `admin.comments.review.*` removida — precisa ser criada no AdminArea |
| Imagens featured | URLs Unsplash hardcoded no `GenerateArticleAction` |
| Testes cobertura | Apenas 5 arquivos; falta cobertura de update/delete comment, newsletter verify, geração IA |
| Paginação SEO | Sem `rel="next/prev"` nos links de paginação |

---

## Visão Geral das Fases

| Fase | Escopo | Dependências | Prioridade |
|------|--------|-------------|------------|
| 0 | Débitos técnicos + Newsletter funcional | Mailer configurado | Crítica |
| 1 | Admin — moderação de comentários + gestão de artigos | AdminArea guard | Alta |
| 2 | Pipeline editorial avançado (agendamento, revisão, drafts) | Fase 1 | Média |
| 3 | SEO avançado + Analytics | Sitemap, RSS modules | Média |
| 4 | Engajamento (reações, bookmarks, perfil de autor) | Fase 0 | Baixa |

```text
Fase 0 ──→ Fase 1 ──→ Fase 2 ──→ Fase 3
Débitos     Admin      Pipeline    SEO Avançado
Newsletter  Moderação  Drafts      Analytics
Lojistas    Artigos    Revisão     Sitemap/RSS
                       Agendamento
                                        ↓
                                    Fase 4
                                    Engajamento
                                    Reações
                                    Bookmarks
```

---

## Fase 0 — Débitos Técnicos e Newsletter

### Objetivo

Resolver todos os problemas conhecidos e tornar a newsletter funcional end-to-end.

### Tarefas

#### 0.1 Newsletter — envio de e-mail de verificação

```
Arquivo: src/Content/Application/Controllers/NewsletterController.php
```

- Criar Mailable `NewsletterVerificationMail` com template em `content::mail.newsletter-verify`
- Enviar código de 6 dígitos via Mail::to() no método `store()`
- Template seguindo padrão visual dos e-mails existentes (Montserrat, #0A2868)

#### 0.2 Adicionar rota para categoria lojistas

```
Arquivo: src/Content/Routes/web.php
```

- Adicionar bloco de rotas `/lojistas` seguindo padrão das demais categorias
- Nomes: `content.lojistas.index`, `content.lojistas.show`

#### 0.3 Substituir imagens Unsplash hardcoded

```
Arquivo: src/Content/Application/Actions/GenerateArticleAction.php
```

- Usar placeholder genérico ou `null` como featured_image default
- Imagem real será adicionada via painel admin (Fase 1)

#### 0.4 Adicionar rel="next/prev" na paginação

```
Arquivos: article.blade.php, category.blade.php
```

- Publicar/customizar pagination view ou adicionar meta tags via `@push('head')`

#### 0.5 Ampliar cobertura de testes

```
Diretório: tests/Feature/Content/, tests/Unit/Content/
```

- Testar update comment (autorização, janela de 5 min)
- Testar delete comment (autorização, janela de 5 min)
- Testar newsletter subscribe + verify flow
- Testar GenerateArticleAction (mock do AI service)
- Testar rate limiting de comentários

---

## Fase 1 — Admin: Moderação e Gestão de Artigos

### Objetivo

Criar interface administrativa para gerenciar comentários e artigos, integrada ao AdminArea.

### Dependências

- AdminArea guard funcional (Fase 0 do AdminArea)
- `Src\AdminArea\Domain\Models\Admin` migrado

### Tarefas

#### 1.1 Rotas admin para comentários

```
Arquivo: src/AdminArea/Routes/web.php (ou novo arquivo de rotas Content-Admin)
```

- `GET /admin/comments` — listar todos os comentários (pendentes primeiro)
- `PATCH /admin/comments/{id}/approve` — aprovar comentário
- `PATCH /admin/comments/{id}/reject` — rejeitar comentário
- `DELETE /admin/comments/{id}` — excluir comentário (force delete)
- `POST /admin/comments/{id}/correct` — trigger correção IA

#### 1.2 Rotas admin para artigos

- `GET /admin/articles` — listar artigos com filtros (categoria, status, data)
- `GET /admin/articles/create` — form de criação manual
- `POST /admin/articles` — salvar artigo
- `GET /admin/articles/{id}/edit` — form de edição
- `PUT /admin/articles/{id}` — atualizar artigo
- `DELETE /admin/articles/{id}` — excluir artigo
- `POST /admin/articles/generate` — trigger geração IA com preview

#### 1.3 Vue pages para AdminArea

```
Diretório: resources/js/Pages/Admin/Content/
```

- `Comments/Index.vue` — tabela com filtros, ações em batch
- `Articles/Index.vue` — tabela com status badges, busca
- `Articles/Form.vue` — editor com preview, campos SEO, upload de imagem

#### 1.4 Rotas admin para newsletter

- `GET /admin/newsletter` — listar subscribers (ativos, pendentes)
- `DELETE /admin/newsletter/{id}` — remover subscriber
- Exportar CSV dos subscribers ativos

---

## Fase 2 — Pipeline Editorial Avançado

### Objetivo

Implementar fluxo editorial profissional: rascunhos, revisão, agendamento e versionamento.

### Tarefas

#### 2.1 Status workflow para artigos

```
Arquivo: src/Content/Domain/Models/Article.php
```

Novo campo `status` com enum:
- `draft` — Rascunho (visível apenas no admin)
- `review` — Em revisão (notifica admin)
- `scheduled` — Agendado para publicação
- `published` — Publicado
- `archived` — Arquivado (oculto do público)

Migration: adicionar coluna `status` com default `draft`, atualizar scope `published()`.

#### 2.2 Agendamento de publicação

```
Arquivo: src/Content/Application/Commands/PublishScheduledArticlesCommand.php
```

- Command `content:publish-scheduled` executado via scheduler (a cada hora)
- Busca artigos com `status = scheduled` e `published_at <= now()`
- Muda status para `published`
- Dispara evento `ArticlePublished` (para RSS/Sitemap rebuild)

#### 2.3 Versionamento de conteúdo

```
Novo arquivo: src/Content/Domain/Models/ArticleRevision.php
```

- Salvar snapshot do content antes de cada update
- Campos: `article_id`, `content`, `meta`, `edited_by`, `created_at`
- Permitir rollback no admin

#### 2.4 Geração IA em batch

```
Arquivo: src/Content/Application/Commands/GenerateArticleCommand.php
```

- Opção `--batch=5` para gerar múltiplos artigos de uma vez
- Status `draft` por default (requer revisão humana)
- Notificar admin quando batch concluir

---

## Fase 3 — SEO Avançado e Analytics

### Objetivo

Maximizar performance orgânica e fornecer métricas para decisão editorial.

### Tarefas

#### 3.1 Integração com Sitemap module

```
Integração: src/Sitemap/
```

- Registrar provider de URLs do Content no Sitemap
- Incluir artigos publicados com lastmod e changefreq
- Incluir páginas de categoria

#### 3.2 Integração com RSS module

```
Integração: src/Rss/
```

- Feed principal: todos os artigos publicados
- Feeds por categoria: `/content/{categoria}/feed`
- Incluir excerpt, featured_image, author_name

#### 3.3 Analytics de artigos

```
Novo: src/Content/Domain/Models/ArticleView.php
```

- Tracking de pageviews por artigo (diário, anônimo)
- Dashboard no admin: artigos mais lidos, tendências, bounce
- Métricas: views, tempo médio, comentários/artigo

#### 3.4 Internal linking automático

```
Integração: src/GenericArticleInterlink/
```

- Ao publicar artigo, detectar keywords e sugerir links internos
- Inserir links no content automaticamente (com aprovação)

---

## Fase 4 — Engajamento e Comunidade

### Objetivo

Aumentar interação dos usuários e criar senso de comunidade.

### Tarefas

#### 4.1 Reações em artigos

- Botões de reação (útil, interessante, etc.) — sem necessidade de login
- Contagem persistida por artigo + cookie/fingerprint

#### 4.2 Bookmarks / Salvar para depois

- Usuários logados podem salvar artigos
- Página `/minha-conta/salvos` com lista

#### 4.3 Perfil de autor

- Página pública `/content/autor/{slug}` com bio e artigos publicados
- Suporte a múltiplos autores (além de "Equipe Editorial")

#### 4.4 Compartilhamento social

- Botões de compartilhar (WhatsApp, Twitter/X, LinkedIn, copiar link)
- Tracking de cliques por canal

---

## Arquivos-Chave por Fase

| Fase | Arquivos Novos | Arquivos Modificados |
|------|---------------|---------------------|
| 0 | `NewsletterVerificationMail.php`, `newsletter-verify.blade.php`, novos testes | `NewsletterController.php`, `web.php`, `GenerateArticleAction.php` |
| 1 | Vue pages (3+), admin routes/controllers | `AdminArea/Routes/web.php` |
| 2 | `ArticleRevision.php`, `PublishScheduledArticlesCommand.php`, migration | `Article.php`, `GenerateArticleCommand.php` |
| 3 | `ArticleView.php`, migration, RSS/Sitemap providers | Integrações cross-module |
| 4 | Reaction model, Bookmark model, AuthorProfile, migrations | `article.blade.php` |

---

## Verificação por Fase

```bash
# Fase 0
php artisan test tests/Feature/Content/ tests/Unit/Content/
php artisan route:list --name=content
php artisan route:list --name=newsletter

# Fase 1
php artisan test tests/Feature/Admin/Content/
php artisan route:list --name=admin.comments
php artisan route:list --name=admin.articles

# Fase 2
php artisan content:publish-scheduled --dry-run
php artisan test --filter=ArticleWorkflow

# Fase 3
php artisan sitemap:generate --clear-cache
php artisan rss:generate --clear-cache

# Fase 4
php artisan test --filter=Engagement
```
