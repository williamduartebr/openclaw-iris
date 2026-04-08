@vite(['src/Content/Presentation/Resources/js/article.js'])
<script src="https://accounts.google.com/gsi/client?hl=pt-BR" async defer></script>
<script>
    // Lazy-load para imagens dentro do conteúdo do artigo (HTML dinâmico)
    (function () {
        function applyLazyLoad() {
            const content = document.querySelector('.article-content');
            if (!content) return;

            const imgs = content.querySelectorAll('img');

            if ('loading' in HTMLImageElement.prototype) {
                // Suporte nativo: apenas marca como lazy
                imgs.forEach(function (img) {
                    img.setAttribute('loading', 'lazy');
                    img.setAttribute('decoding', 'async');
                    // Efeito de fade-in ao carregar
                    img.classList.add('img-loading');
                    if (img.complete && img.naturalWidth > 0) {
                        img.classList.remove('img-loading');
                    } else {
                        img.addEventListener('load', function () {
                            img.classList.remove('img-loading');
                        });
                    }
                });
            } else {
                // Fallback com IntersectionObserver para browsers antigos
                var observer = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            var dataSrc = img.getAttribute('data-src');
                            if (dataSrc) {
                                img.src = dataSrc;
                                img.removeAttribute('data-src');
                            }
                            img.classList.remove('img-loading');
                            obs.unobserve(img);
                        }
                    });
                }, { rootMargin: '200px 0px' });

                imgs.forEach(function (img) {
                    img.setAttribute('decoding', 'async');
                    img.setAttribute('data-src', img.src);
                    img.src = '';
                    img.classList.add('img-loading');
                    observer.observe(img);
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyLazyLoad);
            document.addEventListener('DOMContentLoaded', initFaqAccordion);
        } else {
            applyLazyLoad();
            initFaqAccordion();
        }

        function initFaqAccordion() {
            const faqTitles = document.querySelectorAll('.faq-accordion-title');
            faqTitles.forEach(title => {
                title.addEventListener('click', () => {
                    const isActive = title.classList.contains('is-active');

                    // Fecha todos os outros (opcional, mas elegante)
                    faqTitles.forEach(t => t.classList.remove('is-active'));

                    // Se não estava ativo, abre
                    if (!isActive) {
                        title.classList.add('is-active');
                    }
                });
            });
        }
    })();
</script>
