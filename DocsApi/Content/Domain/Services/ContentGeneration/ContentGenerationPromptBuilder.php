<?php

namespace Src\Content\Domain\Services\ContentGeneration;

class ContentGenerationPromptBuilder
{
    public function build(array $titleData): string
    {
        $keywords = implode(', ', $titleData['keywords']);

        return <<<PROMPT
Escreva um artigo de blog completo em português brasileiro para o {$this->brandName()}.

**Título:** {$titleData['title']}

**Palavras-chave para incluir naturalmente:** {$keywords}

**Intenção de busca:** {$titleData['search_intent']}

**Conexão com a plataforma:** {$titleData['ecosystem_value']}

**Requisitos:**
1. Tom profissional mas acessível, como um especialista conversando
2. Entre 1500-2500 palavras
3. Estrutura com H2 e H3 escaneáveis
4. Listas e bullet points para facilitar leitura
5. Introdução com gancho + problema + promessa
6. Corpo com informação valiosa e prática
7. Menção natural ao {$this->brandName()} como solução (não forçada)
8. Conclusão com resumo + próximos passos
9. Sem emojis
10. Markdown válido para o campo content (usar ##, ###, listas e **negrito**; nunca HTML como <strong>)
11. Use negrito de forma estratégica para melhorar escaneabilidade, destacando palavras-chave e trechos de decisão sem transformar o artigo em uma página visualmente poluída

**Formato de saída (JSON válido, sem markdown):**
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

IMPORTANTE: Retorne APENAS o JSON, sem texto adicional, sem blocos de código markdown.
PROMPT;
    }

    private function brandName(): string
    {
        return config('app.name', 'Mercado Veículos');
    }
}
