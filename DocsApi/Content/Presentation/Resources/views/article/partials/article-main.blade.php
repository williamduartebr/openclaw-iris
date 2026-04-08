@push('head')
<style>
/* Estilos Customizados de Leitura (Medium-like) */
.article-content {
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}
.article-content p {
    font-size: 1.25rem; /* text-xl */
    line-height: 1.8;
    color: #292929;
    margin-bottom: 2rem;
    letter-spacing: -0.01em;
}

.article-content h2 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 2.25rem; /* text-4xl */
    line-height: 1.25;
    color: #1a1a1a;
    margin-top: 3.5rem;
    margin-bottom: 1.5rem;
    letter-spacing: -0.02em;
}

.article-content h3 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: 1.5rem; /* text-2xl */
    color: #292929;
    margin-top: 2.5rem;
    margin-bottom: 1rem;
}

/* Listas Customizadas */
.article-content ul {
    list-style-type: none !important;
    padding-left: 0;
    margin-top: 2rem;
    margin-bottom: 2rem;
}

.article-content ul > li {
    position: relative;
    padding-left: 1.75rem;
    font-size: 1.25rem; /* text-xl */
    line-height: 1.8;
    color: #292929;
    margin-bottom: 1rem;
    list-style: none !important;
}

/* Esconder pseudo-elementos padrão do Tailwind Typography */
.article-content ul > li::marker {
    color: transparent;
    display: none;
    content: none;
}

.article-content ul > li::before {
    content: "";
    position: absolute;
    left: 0.25rem;
    top: 0.85rem;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #1e40af; /* blue-800 */
}

/* Checklists/Tasks forms in Markdown */
.article-content ul > li:has(input[type="checkbox"]) {
    padding-left: 0.5rem;
}
.article-content ul > li:has(input[type="checkbox"])::before {
    display: none;
}
.article-content ul > li input[type="checkbox"] {
    margin-right: 0.75rem;
    transform: scale(1.3);
    accent-color: #1e40af;
    position: relative;
    top: 2px;
}

/* Imagens Horizontais Padrão (Porta-Retrato) */
.article-content img:not(.vertical-bg-cover) {
    display: block;
    margin: 3rem auto;
    border-radius: 6px;
    border: 4px solid #ffffff;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    max-width: 100%;
    height: auto;
}

.article-content img.vertical-bg-cover {
    display: none !important;
}

/* Legendas de imagens (disclaimer genérico) */
.article-content img + em.article-caption,
.article-content img + em {
    display: block;
    text-align: right;
    font-size: 11px;
    color: #9ca3af;
    margin-top: -2.5rem;
    margin-bottom: 3rem;
    font-style: normal;
}

/* Imagens Verticais Tratadas por JS */
.vertical-image-container {
    width: 100%;
    height: 600px;
    margin: 3rem 0;
    border-radius: 8px;
    background-size: cover;
    background-position: center;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    position: relative;
    overflow: hidden;
}

/* Fallback Links e PDFs */
.article-content a {
    color: #1e40af;
    text-decoration-thickness: 1px;
    text-underline-offset: 4px;
    transition: color 0.2s ease;
}
.article-content a:hover {
    color: #1e3a8a;
    text-decoration-thickness: 2px;
}
.article-content a[href$=".pdf"] {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    background-color: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    color: #1f2937;
    font-weight: 500;
    text-decoration: none;
    margin: 1rem 0;
    transition: all 0.2s ease;
}
.article-content a[href$=".pdf"]::before {
    content: "📄";
    margin-right: 0.5rem;
    font-size: 1.2rem;
}
.article-content a[href$=".pdf"]:hover {
    background-color: #e5e7eb;
    border-color: #d1d5db;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Separadores */
.article-content hr {
    margin: 4rem 0;
    border: 0;
    height: 1px;
    background-image: linear-gradient(to right, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.15), rgba(0, 0, 0, 0));
}
</style>
@endpush

    <!-- Article -->
    <article class="py-12">
        <!-- Category Badge -->
        <div class="max-w-178 mx-auto px-6 mb-8">
            <a href="/artigos/{{ $article->category->slug }}"
                class="inline-block text-xs uppercase font-medium tracking-wider text-blue-800 hover:text-blue-900 transition">
                {{ $article->category->name }}
            </a>
        </div>

        <!-- Title -->
        <header class="max-w-178 mx-auto px-6 mb-10">
            <h1
                class="font-['Montserrat'] font-bold text-[2.75rem] md:text-[3.25rem] lg:text-[3.75rem] leading-[1.1] tracking-tight mb-8 text-gray-900">
                {{ $article->title }}
            </h1>

            <!-- Excerpt -->
            <p class="text-[1.375rem] md:text-[1.5rem] text-gray-600 leading-relaxed mb-10 font-normal">
                {{ $article->excerpt }}
            </p>

            <!-- Author & Meta -->
            <div class="flex items-center justify-between border-y border-gray-200 py-4">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 rounded-full bg-linear-to-br from-blue-800 to-blue-600 flex items-center justify-center text-white font-semibold text-lg hover:shadow-md transition">
                        {{ substr($article->author_name, 0, 1) }}
                    </div>
                    <div>
                        <div class="font-medium text-gray-900">{{ $article->author_name }}</div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <time datetime="{{ $article->published_at->toIso8601String() }}">
                                {{ $article->published_at->translatedFormat('d \\d\\e F \\d\\e Y') }}
                            </time>
                            <span>&middot;</span>
                            <span>{{ $article->reading_time }} min de leitura</span>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Featured Image -->
        @if($article->featured_image || $article->category->slug !== 'geral')
        <div class="max-w-250 mx-auto px-6 mb-16 mt-12">
            @if($article->featured_image)
            <figure class="article-cover" data-image-source="{{ $article->image_source ?? 'ai' }}">
                <div class="featured-img-wrapper relative rounded-lg overflow-hidden shadow-xl border-4 border-white isolate">
                    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}" loading="lazy"
                        onerror="this.closest('.featured-img-wrapper').outerHTML='<div class=\'featured-image-placeholder h-[400px] w-full bg-gray-100 flex items-center justify-center rounded-lg shadow-inner\'><span class=\'text-6xl text-gray-300 font-bold\'>{{ substr($article->title, 0, 1) }}</span></div>'"
                        onload="this.classList.add('img-loaded')"
                        class="w-full h-auto object-cover max-h-150">
                </div>
                <figcaption class="image-credit mt-2 text-[11px] text-gray-400 text-right">
                    @switch($article->image_source ?? 'ai')
                        @case('ai')
                            Imagem ilustrativa gerada por IA.
                            @break
                        @case('real')
                            Imagem: acervo Mercado Veículos.
                            @break
                        @case('press')
                            Imagem: divulgação.
                            @break
                        @case('stock')
                            Imagem: banco de imagens.
                            @break
                    @endswitch
                </figcaption>
            </figure>
            @else
            <div class="featured-image-placeholder h-100 w-full bg-gray-50 flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200">
                <span class="text-6xl text-gray-300 font-bold">{{ substr($article->title, 0, 1) }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Banner de Anúncio 1 (bloqueado em BOFU) --}}
        <div class="max-w-178 mx-auto px-6 my-8">
            <x-shared::ad-slot name="content_top" :funnel-stage="$article->category->funnel_stage ?? null" />
        </div>

        <!-- Content -->
        <div class="max-w-178 mx-auto px-6">
            <div class="article-content prose prose-xl max-w-none prose-li:marker:text-transparent" id="article-content-body">
                {!! $article->content_html !!}
            </div>
        </div>

        {{-- Banner de Anúncio 2 (bloqueado em BOFU) --}}
        <div class="max-w-178 mx-auto px-6 my-10">
            <x-shared::ad-slot name="content_mid" :funnel-stage="$article->category->funnel_stage ?? null" />
        </div>

        <!-- Tags/Category Footer -->
        <div class="max-w-178 mx-auto px-6 mt-16 pt-10 border-t border-gray-200 pb-12">
            <div class="flex items-center gap-3 flex-wrap">
                <a href="/artigos/{{ $article->category->slug }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-full text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900 transition shadow-sm">
                    {{ $article->category->name }}
                </a>
            </div>
        </div>
    </article>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const articleContainer = document.getElementById('article-content-body');
    if (!articleContainer) return;

    const images = articleContainer.querySelectorAll('img:not(.vertical-bg-cover)');
    const heroImage = document.querySelector('.featured-img-wrapper img');
    const heroSrc = heroImage ? heroImage.src : null;

    images.forEach(img => {
        // Ignorar SVG, ícones pequenos ou já processadas
        if(img.src.endsWith('.svg') || img.width < 100 || img.hasAttribute('data-processed')) return;
        
        // Evitar imagem repetida do titulo (Hero)
        if (heroSrc && img.src === heroSrc) {
            const parent = img.parentElement;
            img.remove();
            if (parent && parent.tagName === 'P' && parent.innerHTML.trim() === '') {
                parent.remove();
            }
            return;
        }

        img.setAttribute('data-processed', 'true');
        // Remover texto específico para google e aplicar disclaimer genérico (SEO)
        img.setAttribute('alt', 'Imagem meramente ilustrativa');

        // Validar verticalidade apenas após a imagem carregar suas dimensões naturais
        if (img.complete) {
            checkAndTransformImage(img);
        } else {
            img.addEventListener('load', () => checkAndTransformImage(img));
        }
        
        // Adicionar legenda com base na origem da imagem do artigo
        if (!img.closest('figure') && !img.nextElementSibling?.matches('em.article-caption')) {
             const sourceLabels = {
                 ai: 'Imagem ilustrativa gerada por IA.',
                 real: 'Imagem: acervo Mercado Veículos.',
                 press: 'Imagem: divulgação.',
                 stock: 'Imagem: banco de imagens.'
             };
             const articleSource = document.querySelector('.article-cover')?.dataset.imageSource || 'ai';
             const emElement = document.createElement('em');
             emElement.textContent = sourceLabels[articleSource] || sourceLabels.ai;
             emElement.className = "article-caption block text-[11px] text-gray-400 text-right -mt-10 mb-10";
             img.parentNode.insertBefore(emElement, img.nextSibling);
        }
    });

    function checkAndTransformImage(img) {
        if (img.classList.contains('vertical-bg-cover')) return;

        // Tolerância de 1.1 para considerar "muito vertical" e merecer o tratamento cover
        const ratio = img.naturalHeight / img.naturalWidth;
        
        if (ratio > 1.1) {
            // Se vertical, esconde a original e injeta na Div
            const wrapper = document.createElement('div');
            wrapper.className = 'vertical-image-container';
            wrapper.style.backgroundImage = `url(${img.src})`;
            img.parentNode.insertBefore(wrapper, img);
            img.classList.add('hidden', 'vertical-bg-cover'); // Esconde mas mantém source
        }
    }
});
</script>
@endpush
