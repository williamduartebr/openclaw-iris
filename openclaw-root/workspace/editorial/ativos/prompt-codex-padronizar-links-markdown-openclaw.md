# Prompt para Codex — padronizar links Markdown no OpenClaw

Use este prompt no Codex para ajustar internamente o OpenClaw e os documentos operacionais do workspace.

---

Quero que você padronize **todos os links em artigos e instruções editoriais** para o formato Markdown clássico:

`[label](https://url-completa)`

## Objetivo

Garantir que todo link dentro de `body_md`, prompts editoriais, instruções de refresh e documentação operacional use o mesmo padrão, compatível com transpilers Markdown e fácil de converter depois.

## Regra obrigatória

Sempre usar:

- `[]` para o **label/texto clicável**
- `()` para a **URL completa**

Exemplo correto:

- `[Ubersuggest](https://neilpatel.com/ubersuggest/)`
- `[Google Search Console](https://search.google.com/search-console)`
- `[Mercado Veiculos](https://mercadoveiculos.com/anuncie)`

## Bloqueios

Não permitir:

- `()[]`
- `<a href="...">...</a>`
- URL crua no meio do parágrafo quando um link rotulado fizer mais sentido
- sintaxe híbrida ou inventada
- links relativos quando a instrução exigir URL canônica completa

## Escopo

1. Revisar documentos operacionais do workspace que orientam escrita e publicação.
2. Revisar skills de refresh/rewrite que mencionem links em artigos.
3. Atualizar instruções para que novos artigos e refreshes já saiam nesse padrão.
4. Se houver exemplos antigos conflitantes, substituir pelos exemplos em Markdown padrão.
5. Preservar o restante das regras editoriais e não alterar a lógica de CTA, funnel ou CMS além do necessário para a padronização de links.

## Prioridades de arquivo

Procure especialmente em:

- `TOOLS.md`
- `SOP.md`
- `MEMORY.md`
- skills em `skills/`
- ativos editoriais em `editorial/ativos/`

## Critério de conclusão

Considere concluído somente quando:

- a regra estiver documentada de forma explícita
- os exemplos estiverem em `[label](https://url-completa)`
- não restarem instruções recomendando HTML ou formatos alternativos para links em artigo
- o diff estiver limpo e focado

## Entrega esperada

Ao final:

1. liste os arquivos alterados
2. resuma a regra final em 2 a 4 bullets
3. aponte qualquer arquivo antigo que ainda mereça revisão manual
