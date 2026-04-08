<?php

namespace Src\Content\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Src\Content\Application\Requests\ListArticlesRequest;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class ContentApiArticleQueryService
{
    public function getActiveCategories(): Collection
    {
        return Cache::remember(
            'content_categories_active_v1',
            now()->addDays(7),
            fn () => Category::query()
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'name', 'slug', 'description', 'role', 'funnel_stage'])
        );
    }

    public function findBySlug(string $slug): ?Article
    {
        $article = Cache::remember(
            "content_article_by_slug_v1:slug:{$slug}",
            now()->addHours(6),
            fn () => Article::query()
                ->where('slug', $slug)
                ->first()
        );

        return $article ? $this->loadRelations($article) : null;
    }

    public function findById(int $id): ?Article
    {
        $article = Article::query()->find($id);

        return $article ? $this->loadRelations($article) : null;
    }

    public function findTrashedById(int $id): ?Article
    {
        return Article::onlyTrashed()->find($id);
    }

    public function loadRelations(Article $article): Article
    {
        return $article->load(['category', 'categories']);
    }

    public function list(ListArticlesRequest $request): LengthAwarePaginator
    {
        $query = $this->newListQuery($request->input('trashed'));

        if ($request->filled('slug')) {
            $query->where('slug', $request->input('slug'));

            return $query->paginate(1);
        }

        $this->applyListFilters($query, $request);
        $this->applySort($query, (string) $request->input('sort', '-created_at'));

        $perPage = $request->integer('per_page', 15);

        return $query->paginate($perPage);
    }

    private function newListQuery(?string $trashed): Builder
    {
        return match ($trashed) {
            'only' => Article::onlyTrashed()->with('category'),
            'with' => Article::withTrashed()->with('category'),
            default => Article::query()->with('category'),
        };
    }

    private function applyListFilters(Builder $query, ListArticlesRequest $request): void
    {
        if ($request->filled('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($categoryQuery) use ($request) {
                $categoryQuery->where('categories.slug', $request->input('category'));
            });
        }

        if ($request->filled('featured')) {
            $query->where('featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('author')) {
            $query->where('author_name', 'like', '%'.$request->input('author').'%');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        if ($request->filled('created_after')) {
            $query->where('created_at', '>=', $request->input('created_after'));
        }

        if ($request->filled('created_before')) {
            $query->where('created_at', '<=', $request->input('created_before'));
        }

        if ($request->filled('published_after')) {
            $query->where('published_at', '>=', $request->input('published_after'));
        }

        if ($request->filled('published_before')) {
            $query->where('published_at', '<=', $request->input('published_before'));
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        $direction = 'asc';

        if (str_starts_with($sort, '-')) {
            $direction = 'desc';
            $sort = ltrim($sort, '-');
        }

        $query->orderBy($sort, $direction);
    }
}
