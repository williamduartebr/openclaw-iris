<?php

namespace Src\Content\Infrastructure\Cache;

use Illuminate\Support\Facades\Cache;

/**
 * Serviço centralizado de invalidação de cache do módulo Content.
 *
 * Agrupa todas as chaves de cache do módulo para invalidação controlada.
 */
class ContentCacheInvalidationService
{
    /** Chaves estáticas (sem parâmetros dinâmicos) */
    private const STATIC_KEYS = [
        'content_categories_active_v1',
        'content_categories_active_with_count_v1',
        'content_categories_active_ordered_v1',
        'content_categories_active_slugs_v1',
        'content_newsletter_stats_v1',
    ];

    /** Slugs de categorias conhecidas para invalidação */
    private const KNOWN_CATEGORY_SLUGS = [
        'dicas', 'manutencao', 'comparativos', 'lancamentos', 'reviews',
        'tecnologia', 'seguranca', 'economia', 'curiosidades', 'geral',
        'eletricos', 'motos',
    ];

    /**
     * Invalida todas as chaves do módulo.
     */
    public function clearAll(): void
    {
        foreach (self::STATIC_KEYS as $key) {
            Cache::forget($key);
        }

        $this->clearArticleListings();
        $this->clearCategoryPages();
        $this->clearNewsletterCache();
    }

    /**
     * Invalida todo o cache afetado pela escrita de um artigo.
     *
     * Chamado após create, update, patch, publish, archive, delete e restore.
     */
    public function clearForArticleWrite(?string $slug = null, ?string $categorySlug = null): void
    {
        // Cache do artigo específico
        if ($slug !== null) {
            Cache::forget("content_article_by_slug_v1:slug:{$slug}");
            Cache::forget("content_article_published_v1:slug:{$slug}");
        }

        // Listagens por categoria
        if ($categorySlug !== null) {
            Cache::forget("content_category_by_slug_v1:category:{$categorySlug}");
            $this->clearCategoryArticlePages($categorySlug);
        }

        // Chaves globais afetadas por escrita de artigo
        Cache::forget('content_categories_active_with_count_v1');
        $this->clearArticleListings();
    }

    /**
     * Invalida todo o cache afetado pela escrita de uma categoria.
     */
    public function clearForCategoryWrite(?string $categorySlug = null): void
    {
        Cache::forget('content_categories_active_v1');
        Cache::forget('content_categories_active_with_count_v1');
        Cache::forget('content_categories_active_ordered_v1');
        Cache::forget('content_categories_active_slugs_v1');

        if ($categorySlug !== null) {
            Cache::forget("content_category_by_slug_v1:category:{$categorySlug}");
            $this->clearCategoryArticlePages($categorySlug);
        }
    }

    /**
     * Invalida cache de newsletter (após subscribe/unsubscribe).
     */
    public function clearNewsletterCache(): void
    {
        Cache::forget('content_newsletter_stats_v1');
    }

    /**
     * Invalida listagens de artigos recentes (NewsletterApiController).
     */
    private function clearArticleListings(): void
    {
        foreach ([7, 14, 30] as $days) {
            Cache::forget("content_recent_articles_v1:days:{$days}");
            foreach (self::KNOWN_CATEGORY_SLUGS as $slug) {
                Cache::forget("content_recent_articles_v1:days:{$days}:category:{$slug}");
            }
        }
    }

    /**
     * Invalida páginas de artigos de categoria (paginadas).
     */
    private function clearCategoryArticlePages(string $categorySlug): void
    {
        for ($page = 1; $page <= 50; $page++) {
            Cache::forget("content_category_articles_v1:category:{$categorySlug}:page:{$page}");
        }
    }

    /**
     * Invalida cache de todas as categorias conhecidas.
     */
    private function clearCategoryPages(): void
    {
        foreach (self::KNOWN_CATEGORY_SLUGS as $slug) {
            Cache::forget("content_category_by_slug_v1:category:{$slug}");
            $this->clearCategoryArticlePages($slug);
        }
    }
}
