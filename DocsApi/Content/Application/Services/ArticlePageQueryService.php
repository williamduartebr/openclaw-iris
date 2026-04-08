<?php

namespace Src\Content\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class ArticlePageQueryService
{
    public function getActiveCategoriesWithPublishedCount(): Collection
    {
        return Cache::remember(
            'content_categories_active_with_count_v1',
            now()->addHours(24),
            fn () => Category::query()
                ->where('is_active', true)
                ->withCount([
                    'articles as published_articles_count' => fn ($query) => $query
                        ->where('is_published', true)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now()),
                ])
                ->orderBy('order')
                ->get()
        );
    }

    public function getCategoryOrFail(string $categorySlug): Category
    {
        return Cache::remember(
            "content_category_by_slug_v1:category:{$categorySlug}",
            now()->addDays(7),
            fn () => Category::query()
                ->where('slug', $categorySlug)
                ->where('is_active', true)
                ->firstOrFail()
        );
    }

    public function getArticleForCategoryOrFail(Category $category, string $articleSlug): Article
    {
        $article = $category->publishedArticles()
            ->where('articles.slug', $articleSlug)
            ->firstOrFail();

        $article->setRelation('category', $category);

        return $article;
    }

    public function getCommentsPaginator(Article $article): LengthAwarePaginator
    {
        return $article->comments()
            ->whereNull('parent_id')
            ->approved()
            ->with(['user', 'replies' => function ($query) {
                $query->approved()->with('user');
            }])
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function getRelatedArticlesPaginator(Category $category, int $articleId): LengthAwarePaginator
    {
        $relatedArticles = $category->publishedArticles()
            ->where('articles.id', '!=', $articleId)
            ->paginate(2, ['*'], 'related_page');

        $relatedArticles->each(
            fn (Article $article) => $article->setRelation('category', $category)
        );

        return $relatedArticles;
    }

    public function findPublishedArticleWithActiveCategory(string $articleSlug): ?Article
    {
        return Cache::remember(
            "content_article_published_v1:slug:{$articleSlug}",
            now()->addHours(24),
            fn () => Article::query()
                ->published()
                ->where('slug', $articleSlug)
                ->whereHas('category', function ($query) {
                    $query->where('is_active', true);
                })
                ->with('category')
                ->first()
        );
    }

    public function getCategoryArticlesPaginator(Category $category): LengthAwarePaginator
    {
        $page = request()->integer('page', 1);

        return Cache::remember(
            "content_category_articles_v1:category:{$category->slug}:page:{$page}",
            now()->addHours(24),
            fn () => $category->publishedArticles()->paginate(15)
        );
    }
}
