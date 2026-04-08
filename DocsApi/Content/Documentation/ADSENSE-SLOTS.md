# Adsense Slots — Content

> Data: 2026-03-18

## Blocos configurados

| Slot Key | Slot ID | Formato | Posição na Página | Shortcode |
|----------|---------|---------|-------------------|-----------|
| `content_top` | `4811164894` | in-article | Após imagem destaque, antes do texto | `ADSENSE-CONTENT-TOP` |
| `content_mid` | `2398362779` | in-article | Após texto do artigo, antes das tags | `ADSENSE-CONTENT-MID` |
| `content_bottom` | `8897990811` | in-article | Após newsletter, antes de relacionados | `ADSENSE-CONTENT-BOTTOM` |
| `content_category` | `8075924079` | display | Após header da categoria, antes do grid | `ADSENSE-CONTENT-CATEGORY` |

## Arquivos modificados

| Arquivo | Ação |
|---------|------|
| `config/adsense.php` | Slot IDs preenchidos (4 slots) |

## Lógica de exibição

- Ads bloqueados em categorias BOFU via `funnel-stage` prop (config `adsense.blocked_funnel_stages`)
- Middleware `DisableAdsForReview`: admin logado, headless browsers, `?noads=1`

## Manutenção futura

- Para trocar um slot: alterar `ADSENSE_SLOT_CONTENT_*` no `.env`
- Para desabilitar temporariamente: `ADSENSE_ENABLED=false` no `.env`
