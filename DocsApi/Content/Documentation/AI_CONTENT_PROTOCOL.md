# Protocolo de Conteúdo para IA - Mercado Veículos

Este documento define os padrões obrigatórios para qualquer conteúdo gerado por IA para o módulo `Content`.

## 1. Formato de Texto
- **Markdown Puro**: Use apenas sintaxe Markdown (CommonMark). Proibido o uso de HTML inline (`<div>`, `<span>`, `<br>`, etc).
- **Títulos**: Use hierarquia lógica começando de `##`. O título `<h1>` é reservado para o título do artigo no sistema.
- **Negrito e Listas**: Use listas e `**negrito**` para melhorar a escaneabilidade, nunca HTML como `<strong>`.
- **Negrito Estratégico**: Destaque palavras-chave principais, benefícios, respostas curtas, termos centrais e frases de alta intenção quando isso ajudar a leitura humana. Em geral, use de 1 a 3 destaques por bloco de texto quando fizer sentido.
- **Sem Poluição Visual**: Não coloque parágrafos inteiros em negrito, não repita a mesma keyword em negrito de forma mecânica e não use o negrito como atalho de ranking.

## 2. Padrão de FAQ (Obrigatório)
Para que o sistema renderize acordeões corretamente, use o prefixo `### FAQ:` seguido da pergunta. A resposta deve vir imediatamente abaixo.

**Exemplo:**
```markdown
### FAQ: Qual o melhor momento para vender meu carro?
O melhor momento costuma ser quando o mercado está aquecido ou antes de grandes revisões...

### FAQ: O Mercado Veículos cobra taxas?
Não, nossa plataforma básica é gratuita para anunciantes individuais...
```

## 3. Imagens e Mídia
- Use o formato padrão Markdown: `![legenda da imagem](url_ou_placeholder)`.
- Se estiver gerando um rascunho sem imagem, use um placeholder descritivo: `![Placeholder: Homem olhando motor de carro]`.

## 4. Estilo de Escrita
- Tom profissional, porém acessível.
- Foco em utilidade para o proprietário ou comprador de veículos.
- Evite jargões técnicos excessivos sem explicação.

## 5. SEO
- Insira a palavra-chave principal no primeiro parágrafo.
- Use subtítulos (`##`, `###`) que contenham variações da palavra-chave.
