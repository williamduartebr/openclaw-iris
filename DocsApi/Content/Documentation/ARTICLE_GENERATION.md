# Geração de Artigos SEO com IA

Este documento explica como utilizar o arquivo `article-titles-seo.json` para gerar artigos de blog otimizados para SEO.

## Visão Geral

O arquivo `src/Content/Data/article-titles-seo.json` contém **100 títulos de artigos** pré-planejados, organizados em 5 categorias, prontos para serem expandidos em artigos completos usando IA (Claude, GPT, etc).

### Distribuição por Categoria

| Categoria | Slug | Títulos | Público-alvo |
|-----------|------|---------|--------------|
| Dicas | `/dicas` | 25 | Compradores |
| Guias | `/guias` | 25 | Compradores e Lojistas |
| Notícias | `/noticias` | 25 | Compradores e Lojistas |
| Manutenção | `/manutencao` | 25 | Compradores (pós-venda) |
| Para Lojistas | `/lojistas` | 20 | Lojistas |

**Total: 100 artigos disponíveis para geração**

---

## Estrutura de Cada Título

Cada entrada no JSON contém:

```json
{
  "title": "Como Identificar um Carro Batido: 17 Sinais que Vendedores Desonestos Escondem",
  "keywords": ["carro batido", "como identificar carro batido", "sinais carro acidentado"],
  "search_intent": "informational",
  "ecosystem_value": "Valoriza lojas verificadas que não escondem informações"
}
```

| Campo | Descrição |
|-------|-----------|
| `title` | Título otimizado para SEO (usar como H1) |
| `keywords` | Palavras-chave para incluir naturalmente no texto |
| `search_intent` | Intenção de busca: `informational`, `commercial`, `transactional` |
| `ecosystem_value` | Como o artigo conecta com a proposta do UsadosNaWeb |

---

## Geração Automatizada (Recomendado)

O comando `content:generate` automatiza todo o processo de geração de artigos.

### Configuração

Adicione sua API key no `.env`:

```bash
# Para usar Claude (padrão)
ANTHROPIC_API_KEY=sk-ant-...

# Para usar OpenAI
OPENAI_API_KEY=sk-...
```

### Comandos Disponíveis

```bash
# Listar todos os títulos e status (✓ publicado, ○ pendente)
php artisan content:generate --list

# Gerar artigo interativamente (escolhe categoria e título)
php artisan content:generate

# Gerar artigo específico por categoria e índice
php artisan content:generate --category=lojistas --index=0

# Gerar todos os artigos pendentes de uma categoria
php artisan content:generate --category=dicas --all

# Ver o prompt que seria enviado (sem chamar API)
php artisan content:generate --category=lojistas --index=0 --dry-run

# Usar OpenAI em vez de Claude
php artisan content:generate --provider=openai
```

### Exemplo de Uso

```bash
$ php artisan content:generate --list

=== Dicas (dicas) - Compradores ===
  [✓] 0: [INFO] Como Identificar um Carro Batido...
  [○] 1: [TRAN] Quanto Vale Meu Carro?...
  ...

$ php artisan content:generate --category=dicas --index=1

Gerando: Quanto Vale Meu Carro? Como Calcular o Preço Justo...
Chamando API...
Artigo criado: /dicas/quanto-vale-meu-carro-como-calcular-preco-justo
```

### Produção em Massa

Para gerar muitos artigos de uma vez:

```bash
# Gerar todos os artigos pendentes de lojistas
php artisan content:generate --category=lojistas --all

# O comando aguarda 2 segundos entre cada chamada (rate limiting)
```

---

## Geração Manual

Se preferir gerar manualmente, siga os passos abaixo.

### Passo 1: Escolher um Título

Acesse o arquivo JSON e escolha um título que ainda não foi publicado.

```bash
# Ver todos os títulos de uma categoria
cat src/Content/Data/article-titles-seo.json | jq '.categories.lojistas.titles[].title'
```

### Passo 2: Usar o Prompt de Geração

Use este prompt com Claude ou outra IA:

```
Escreva um artigo de blog completo em português brasileiro para o UsadosNaWeb.

**Título:** [TÍTULO DO JSON]

**Palavras-chave para incluir naturalmente:** [KEYWORDS DO JSON]

**Intenção de busca:** [SEARCH_INTENT DO JSON]

**Conexão com a plataforma:** [ECOSYSTEM_VALUE DO JSON]

**Requisitos:**
1. Tom profissional mas acessível, como um especialista conversando
2. Entre 1500-2500 palavras
3. Estrutura com H2 e H3 escaneáveis
4. Listas e bullet points para facilitar leitura
5. Introdução com gancho + problema + promessa
6. Corpo com informação valiosa e prática
7. Menção natural ao UsadosNaWeb como solução (não forçada)
8. Conclusão com resumo + próximos passos
9. Sem emojis
10. Markdown válido para o campo content (usar `##`, `###`, listas e `**negrito**`; nunca HTML como `<strong>`)
11. Use negrito de forma estratégica para melhorar escaneabilidade, com destaque pontual de palavras-chave, benefícios, respostas curtas e frases de decisão, sem exagero visual

**Formato de saída (JSON):**
{
  "title": "...",
  "slug": "...",
  "excerpt": "Resumo de 150-200 caracteres para meta description",
  "content": "## Introdução\n\nConteúdo do artigo em Markdown...",
  "reading_time": 8,
  "meta": {
    "description": "Meta description para SEO (150-160 chars)",
    "keywords": "keyword1, keyword2, keyword3"
  }
}
```

### Passo 3: Inserir no Banco

Após gerar o JSON do artigo, você pode:

**Opção A: Via Tinker**
```bash
php artisan tinker
```
```php
$category = \Src\Content\Domain\Models\Category::where('slug', 'lojistas')->first();

\Src\Content\Domain\Models\Article::create([
    'category_id' => $category->id,
    'title' => 'Título do Artigo',
    'slug' => 'slug-do-artigo',
    'excerpt' => 'Resumo do artigo...',
    'content' => '## Conteúdo em Markdown...',
    'featured_image' => 'https://images.unsplash.com/photo-XXXXX?w=1200&h=675&fit=crop',
    'author_name' => 'Equipe UsadosNaWeb',
    'reading_time' => 8,
    'is_published' => true,
    'published_at' => now(),
    'meta' => [
        'description' => 'Meta description...',
        'keywords' => 'keyword1, keyword2',
    ],
]);
```

**Opção B: Adicionar na Seeder**

Edite `src/Content/Infrastructure/Database/Seeders/ContentSeeder.php` e adicione o artigo.

---

## Guidelines de SEO

### Padrões de Título que Funcionam

- `Como + [Verbo]`: "Como Identificar", "Como Fazer", "Como Economizar"
- `Número + Substantivo`: "7 Erros", "12 Técnicas", "17 Sinais"
- `Guia Completo de + [Tema]`
- `[Tema]: Tudo o Que Você Precisa Saber`
- `[Opção A] ou [Opção B]: Qual Escolher`

### Estrutura do Conteúdo

```
Introdução (Hook + problema + promessa)
│
├── H2: Primeiro ponto principal
│   ├── H3: Subponto (se necessário)
│   └── H3: Subponto
│
├── H2: Segundo ponto principal
│   └── Lista com bullets
│
├── H2: Terceiro ponto principal
│
├── H2: Como o UsadosNaWeb pode ajudar (CTA sutil)
│
└── Conclusão (Resumo + próximos passos)
```

### Integração com o Ecossistema

**Para artigos de compradores:**
- Mencionar lojas verificadas como solução segura
- Destacar análises técnicas disponíveis na plataforma
- Reforçar transparência de preços e histórico
- Comparador de modelos como ferramenta útil

**Para artigos de lojistas:**
- Destacar visibilidade para compradores qualificados
- Mencionar credibilidade de lojas verificadas
- Ferramentas de gestão e leads da plataforma
- Diferenciais competitivos para lojistas parceiros

---

## Templates de CTA

### Para Compradores

> Na UsadosNaWeb, você encontra apenas lojas verificadas com histórico comprovado.

> Compare modelos lado a lado usando nossa ferramenta gratuita.

> Veja análises técnicas de especialistas antes de decidir sua compra.

### Para Lojistas

> Lojistas parceiros da UsadosNaWeb têm acesso a compradores mais qualificados.

> Destaque sua loja com o selo de Loja Verificada e aumente suas conversões.

> Profissionalize seu negócio com as ferramentas da UsadosNaWeb.

---

## Imagens

Use Unsplash para imagens destacadas. Recomendações por categoria:

| Categoria | Termos de busca no Unsplash |
|-----------|----------------------------|
| Dicas | `car inspection`, `used car`, `car buying` |
| Guias | `car document`, `car finance`, `car dealer` |
| Notícias | `car market`, `electric car`, `car technology` |
| Manutenção | `car repair`, `car mechanic`, `car engine` |
| Lojistas | `car dealership`, `car showroom`, `car sales` |

Formato da URL:
```
https://images.unsplash.com/photo-XXXXXXXXXXXXX?w=1200&h=675&fit=crop
```

---

## Checklist de Publicação

- [ ] Título está otimizado para SEO
- [ ] Slug é amigável (minúsculas, sem acentos, hífens)
- [ ] Excerpt tem 150-200 caracteres
- [ ] Content é HTML válido
- [ ] Meta description tem 150-160 caracteres
- [ ] Keywords estão incluídas naturalmente
- [ ] Imagem destacada está definida (Unsplash)
- [ ] Tempo de leitura está calculado (~200 palavras/minuto)
- [ ] CTA para UsadosNaWeb está presente (sutil)
- [ ] Artigo está marcado como publicado

---

## Exemplo Completo

```php
Article::firstOrCreate(['slug' => 'como-precificar-carros-usados'], [
    'category_id' => $lojistas->id,
    'title' => 'Como Precificar Carros Usados: Estratégias para Maximizar Lucro e Giro',
    'excerpt' => 'Aprenda a definir preços competitivos que equilibram margem de lucro com velocidade de venda. Estratégias testadas por lojistas de sucesso.',
    'content' => '<p>Precificar um veículo usado é uma das decisões mais críticas...</p>...',
    'featured_image' => 'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=1200&h=675&fit=crop',
    'author_name' => 'Equipe UsadosNaWeb',
    'reading_time' => 10,
    'is_published' => true,
    'published_at' => now(),
    'meta' => [
        'description' => 'Estratégias de precificação para lojas de veículos. Maximize lucro mantendo giro de estoque saudável.',
        'keywords' => 'precificar carro usado, preço carro revenda, margem carro usado',
    ],
]);
```

---

## Próximos Passos

### Usando Automação (Recomendado)

1. Configure `ANTHROPIC_API_KEY` ou `OPENAI_API_KEY` no `.env`
2. Execute `php artisan content:generate --list` para ver títulos disponíveis
3. Execute `php artisan content:generate --category=dicas --index=0`
4. Revise o artigo gerado em `/{categoria}/{slug}`
5. Repita ou use `--all` para gerar em lote

### Usando Geração Manual

1. Escolha um título do JSON
2. Gere o artigo usando o prompt acima
3. Revise o conteúdo gerado
4. Insira no banco via Tinker ou Seeder
5. Verifique a URL: `/{categoria}/{slug}`
6. Repita para os próximos artigos

**Meta sugerida:** 2-3 artigos por semana para construir autoridade SEO.

---

## Expandindo o Conteúdo

O JSON pode ser expandido indefinidamente. Para adicionar novos títulos:

1. Edite `src/Content/Data/article-titles-seo.json`
2. Adicione novos objetos ao array `titles` da categoria
3. Siga a estrutura: `title`, `keywords`, `search_intent`, `ecosystem_value`
4. Execute `php artisan content:generate --list` para verificar
5. Gere os novos artigos normalmente

Não há limite de artigos no banco - produza conteúdo ilimitado.
