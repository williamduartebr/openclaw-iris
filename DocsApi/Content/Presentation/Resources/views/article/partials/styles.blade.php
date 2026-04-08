<style>
    /* Medium-style typography — optimized for AI-generated content */
    .article-content {
        font-family: 'Roboto', system-ui, -apple-system, sans-serif;
        font-size: 19px;
        line-height: 1.85;
        letter-spacing: -0.001em;
        font-weight: 400;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .article-content h1,
    .article-content h2,
    .article-content h3,
    .article-content h4 {
        font-family: 'Montserrat', system-ui, -apple-system, sans-serif;
    }

    .article-content p {
        margin-bottom: 2rem;
    }

    .article-content h2 {
        margin-top: 3.5rem;
        margin-bottom: 1.5rem;
        letter-spacing: -0.02em;
    }

    .article-content h3 {
        margin-top: 2.5rem;
        margin-bottom: 1rem;
    }

    .article-content h4 {
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        font-size: 1.125rem;
        font-weight: 600;
    }

    /* Listas — garantindo visibilidade em Markdown */
    .article-content ul,
    .article-content ol {
        margin-bottom: 2rem;
        padding-left: 1.5rem;
    }

    .article-content ul {
        list-style-type: disc;
    }

    .article-content ol {
        list-style-type: decimal;
    }

    .article-content li {
        margin-bottom: 0.5rem;
        padding-left: 0.5rem;
    }

    /* Estilo de FAQ Acordeão (Markdown dinâmico) */
    .article-content .faq-accordion-title {
        background-color: #f8fafc;
        padding: 1.25rem 1.5rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        border-left: 4px solid #0A2868;
        margin-bottom: 0.75rem;
        font-size: 1.125rem;
        color: #1e293b;
        margin-top: 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
        user-select: none;
        gap: 1rem;
    }

    .article-content .faq-accordion-title:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }

    .article-content .faq-accordion-title:hover .faq-chevron {
        color: #0A2868;
    }

    .article-content .faq-accordion-title .faq-chevron {
        position: relative;
        width: 12px;
        height: 12px;
        border-right: 2px solid #64748b;
        border-bottom: 2px solid #64748b;
        transform: rotate(45deg);
        transition: all 0.3s ease;
        margin-right: 5px;
        flex-shrink: 0;
    }

    /* Estado Ativo (Aberto) */
    .article-content .faq-accordion-title.is-active {
        background-color: #f1f5f9;
        border-radius: 8px 8px 0 0;
        margin-bottom: 0;
        border-bottom: none;
    }

    .article-content .faq-accordion-title.is-active .faq-chevron {
        transform: rotate(-135deg);
        border-color: #0A2868;
        margin-top: 5px;
    }


    /* Resposta (Conteúdo) */
    .article-content .faq-accordion-answer {
        background-color: #ffffff;
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out, padding 0.3s ease;
        border-left: 4px solid #0A2868;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 0 0 8px 8px;
        margin-bottom: 1.5rem;
    }

    .article-content .faq-accordion-answer p {
        padding: 1.25rem 1.5rem 1.5rem;
        margin: 0;
        font-size: 1.05rem;
        color: #475569;
        line-height: 1.7;
    }

    .article-content .faq-accordion-title.is-active + .faq-accordion-answer {
        max-height: 1000px; /* Suficiente para qualquer resposta */
    }

    /* Vídeos Responsivos */
    .article-video-wrapper {
        position: relative;
        padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
        height: 0;
        overflow: hidden;
        max-width: 100%;
        margin: 2.5rem 0;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    .article-video-wrapper iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 12px;
        border: none;
    }


    /* WordPress Migrated Custom Blocks */
    .key-info-box {
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }
    .key-info-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #0A2868;
        font-weight: bold;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }
    .key-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    .key-info-item {
        background-color: white;
        border-radius: 6px;
        padding: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .key-info-label {
        font-size: 0.85rem;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .key-info-value {
        font-weight: bold;
        color: #0A2868;
        font-size: 1.1rem;
    }
    .alert-box {
        display: flex;
        gap: 1rem;
        border-radius: 8px;
        padding: 1.5rem;
        margin: 2rem 0;
        background-color: #f0fdf4;
        border-left: 4px solid #22c55e;
    }
    .alert-box.warning {
        background-color: #fffbeb;
        border-left-color: #f59e0b;
    }
    .alert-box.warning .alert-icon { color: #f59e0b; }
    .alert-box.tip .alert-icon { color: #22c55e; }
    .alert-icon svg { width: 24px; height: 24px; flex-shrink: 0; }
    .alert-title {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
        color: #1e293b;
    }
    .alert-content p { margin: 0 !important; }
    
    .maintenance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin: 2rem 0;
    }
    .maintenance-card {
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    .maintenance-card-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: bold;
        color: #1e293b;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
    }
    .maintenance-card-title svg { color: #0A2868; }
    .maintenance-card p { font-size: 0.95rem; margin: 0 !important; }

    .checklist {
        list-style: none !important;
        padding-left: 0 !important;
    }
    .checklist li {
        position: relative;
        padding-left: 1.75rem;
        margin-bottom: 0.75rem;
    }
    .checklist li::before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #22c55e;
        font-weight: bold;
    }

    .faq-section { margin: 2rem 0; }
    .faq-item {
        border-bottom: 1px solid #e2e8f0;
        padding: 1.5rem 0;
    }
    .faq-question {
        font-weight: bold;
        font-size: 1.15rem;
        color: #1e293b;
        margin-bottom: 0.75rem;
    }
    .faq-answer p { margin: 0 !important; font-size: 0.95rem; color: #475569; }

    .conclusion {
        background-color: #f1f5f9;
        padding: 1.5rem;
        border-radius: 8px;
        margin: 2rem 0;
        border-left: 4px solid #0A2868;
    }
    .conclusion p { margin: 0 !important; }

    /* Blockquote Medium style */
    .article-content blockquote {
        border-left: 3px solid #0A2868;
        padding-left: 1.5rem;
        margin: 2.5rem 0;
        font-size: 1.25rem;
        color: #374151;
    }

    /* Images */
    .article-content img {
        width: 100%;
        height: auto;
        border-radius: 4px;
        margin: 3rem 0;
        background: #f3f4f6;
        display: block;
        transition: opacity 0.3s ease;
    }

    /* Image loading state */
    .article-content img[src=""],
    .article-content img:not([src]),
    .article-content img.img-loading {
        min-height: 280px;
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        opacity: 0.6;
    }

    @keyframes shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Featured image skeleton */
    .featured-img-wrapper {
        position: relative;
        background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
        overflow: hidden;
        min-height: 300px;
    }
    .featured-img-wrapper img {
        display: block;
        width: 100%;
        height: auto;
        opacity: 0;
        transition: opacity 0.4s ease;
    }
    .featured-img-wrapper img.img-loaded {
        opacity: 1;
    }

    /* Fallback for missing featured image */
    .featured-image-placeholder {
        width: 100%;
        height: 400px;
        background: linear-gradient(135deg, #0A2868 0%, #0E368A 100%);
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 600;
    }

    /* Tables */
    .article-content table {
        width: 100%;
        border-collapse: collapse;
        margin: 2.5rem 0;
        font-size: 0.95rem;
    }

    .article-content table th {
        background: #f3f4f6;
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        border-bottom: 2px solid #e5e7eb;
    }

    .article-content table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    /* Code blocks */
    .article-content pre {
        background: #1f2937;
        color: #f3f4f6;
        padding: 1.5rem;
        border-radius: 8px;
        overflow-x: auto;
        margin: 2rem 0;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .article-content code {
        background: #f3f4f6;
        color: #1f2937;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-size: 0.9em;
        font-family: 'Courier New', monospace;
    }

    .article-content pre code {
        background: transparent;
        padding: 0;
        color: inherit;
    }

    /* Horizontal rules */
    .article-content hr {
        margin: 3.5rem 0;
        border: none;
        height: 1px;
        background: linear-gradient(to right, transparent, #e5e7eb, transparent);
    }

    /* Nested lists */
    .article-content ul ul,
    .article-content ol ol {
        margin-top: 0.5rem;
        margin-bottom: 0.5rem;
    }

    /* Links */
    .article-content a {
        color: #0A2868;
        text-decoration: underline;
        text-decoration-thickness: 2px;
        text-underline-offset: 3px;
        transition: all 0.2s;
    }

    .article-content a:hover {
        color: #0E368A;
        text-decoration-thickness: 3px;
    }

    /* Figure captions */
    .article-content figure {
        margin: 3rem 0;
    }

    .article-content figcaption {
        margin-top: 0.75rem;
        text-align: center;
        font-size: 0.875rem;
        color: #6b7280;
    }
</style>
