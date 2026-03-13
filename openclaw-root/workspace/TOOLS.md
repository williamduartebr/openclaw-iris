# TOOLS.md

## Publishing Stack

- Publishing API base: `http://localhost:8080`
- Auth header: `Authorization: Bearer $ARTICLE_API_KEY`
- Primary content format: Markdown only
- If `ARTICLE_API_KEY` is not exported, the local Laravel project usually stores it in its `.env`

## Formatting Rules

- The CMS handles the article title, so the body must start at `##`
- Use `##` for primary sections and `###` for subsections
- FAQ entries must use `### FAQ: ...`
- Never emit HTML for article bodies
- Use Brazilian Portuguese with full accents in publishable content
- Use 2026 as the present-year reference unless the task is explicitly historical

## CTA Rules

- B2B automotive growth content should point to `/anuncie` or the most relevant `/anuncie/{segment}`
- B2C BOFU assets should drive to directory pages, profiles, or direct WhatsApp contact
- Mention Mercado Veiculos naturally, never as forced keyword stuffing

## Delivery Rules

- If a fact can change, verify it before using it
- Use BRL pricing and Brazilian context by default
- For messaging surfaces such as WhatsApp and Telegram, avoid markdown tables and convert them to bullets
- For the detailed publication packet, read `PUBLISHING_WORKFLOW.md`
