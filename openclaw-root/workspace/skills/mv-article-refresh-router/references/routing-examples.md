# Routing Examples

## Example 1
User: `Atualize este artigo e troque a capa: <url sobre oficina perdendo clientes no digital>`

Route:
- `mv-article-refresh-b2b`
- then `mv-article-media-refresh`
- media mode = `replace-media`

## Example 2
User: `Mantenha a capa, mas refaça o texto deste artigo sobre custo de bateria`

Route:
- `mv-article-refresh-b2c`
- media mode = `keep-media`

## Example 3
User: `Troque a capa e liste as imagens para apagar`

Route:
- `mv-article-media-refresh`
- media mode = `media-only`

## Example 4
User: `Atualize esse artigo sobre perfil grátis e upgrade`

Route:
- `mv-article-refresh-commercial-funnel`
- then media refresh by default unless the user says to keep the cover

## Example 5
User: `Atualize esse artigo` with only a URL about digital performance for auto businesses

Route:
- classify as B2B
- refresh text
- replace media by default
- report old media path/url for deletion
