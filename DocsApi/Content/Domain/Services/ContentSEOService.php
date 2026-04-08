<?php

namespace Src\Content\Domain\Services;

use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;
use Src\Shared\Domain\Services\SeoMetaService;
use Torann\LaravelMetaTags\Facades\MetaTag;

class ContentSEOService
{
    public function __construct(
        protected SeoMetaService $seoMetaService
    ) {}

    public function setIndexMetaTags(): void
    {
        $brandName = $this->seoMetaService->brandName();
        $description = "Artigos técnicos sobre marketing automotivo, SEO, vendas online, seguro auto e IPVA no {$brandName}.";

        $this->seoMetaService->applyMetaTags(
            title: "Artigos Técnicos — {$brandName}",
            description: $description,
            canonical: url('/artigos'),
            keywords: "artigos técnicos, blog automotivo, marketing automotivo, seo, seguro auto, ipva, {$brandName}"
        );
    }

    public function setCategoryMetaTags(Category $category): void
    {
        $brandName = $this->seoMetaService->brandName();
        $description = $category->description ?: "Artigos e guias sobre {$category->name} no {$brandName}.";

        $this->seoMetaService->applyMetaTags(
            title: "{$category->name} — {$brandName}",
            description: $description,
            canonical: url('/artigos/'.$category->slug),
            keywords: "{$category->name}, blog, artigos, guias, {$brandName}"
        );
    }

    public function setArticleMetaTags(Article $article): void
    {
        $brandName = $this->seoMetaService->brandName();
        $description = $article->meta['description'] ?? $article->excerpt;
        $keywords = $article->meta['keywords'] ?? "{$article->title}, {$article->category->name}, {$brandName}";
        $image = $article->featured_image ?: $this->seoMetaService->defaultImage();

        $this->seoMetaService->applyMetaTags(
            title: "{$article->title} — {$brandName}",
            description: $description,
            canonical: url($article->url),
            keywords: $keywords,
            image: $image,
            type: 'article'
        );

        MetaTag::set('article:published_time', $article->published_at?->toIso8601String());
        MetaTag::set('article:modified_time', $article->updated_at?->toIso8601String());
        MetaTag::set('article:author', $article->author_name);
        MetaTag::set('article:section', $article->category->name);
    }
}
