<?php

namespace Src\Content\Application\Services;

use Src\Content\Domain\Models\Article;

class ArticleStructuredDataService
{
    public function buildForArticle(Article $article): array
    {
        return [
            'articleSchemaJson' => $this->encodeSchema($this->buildArticleSchema($article)),
            'breadcrumbSchemaJson' => $this->encodeSchema($this->buildBreadcrumbSchema($article)),
        ];
    }

    private function buildArticleSchema(Article $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->meta['description'] ?? $article->excerpt,
            'image' => $article->featured_image ?? asset('assets/social/og-default.svg'),
            'datePublished' => $article->published_at->toIso8601String(),
            'dateModified' => $article->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author_name,
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Mercado Veículos'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('assets/img/logo/logo.svg'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url($article->url),
            ],
            'articleSection' => $article->category->name,
            'wordCount' => str_word_count(strip_tags($article->content)),
            'timeRequired' => 'PT'.$article->reading_time.'M',
            'inLanguage' => 'pt-BR',
        ];
    }

    private function buildBreadcrumbSchema(Article $article): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Início',
                    'item' => url('/'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Artigos',
                    'item' => url('/artigos'),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $article->category->name,
                    'item' => url('/artigos/'.$article->category->slug),
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $article->title,
                    'item' => url($article->url),
                ],
            ],
        ];
    }

    private function encodeSchema(array $schema): string
    {
        return (string) json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
