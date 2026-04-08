<?php

namespace Src\Content\Domain\Services;

use Carbon\Carbon;
use Src\Content\Domain\Exceptions\InvalidStatusTransitionException;
use Src\Content\Domain\Models\Article;
use Src\Content\Infrastructure\Cache\ContentCacheInvalidationService;

class ArticleLifecycleService
{
    private const TRANSITIONS = [
        Article::STATUS_DRAFT => [Article::STATUS_REVIEW, Article::STATUS_SCHEDULED, Article::STATUS_PUBLISHED],
        Article::STATUS_REVIEW => [Article::STATUS_DRAFT, Article::STATUS_SCHEDULED, Article::STATUS_PUBLISHED],
        Article::STATUS_SCHEDULED => [Article::STATUS_DRAFT, Article::STATUS_PUBLISHED],
        Article::STATUS_PUBLISHED => [Article::STATUS_DRAFT, Article::STATUS_ARCHIVED],
        Article::STATUS_ARCHIVED => [Article::STATUS_DRAFT],
    ];

    public function __construct(
        private readonly ContentCacheInvalidationService $cacheInvalidation,
    ) {}

    public function transitionStatus(Article $article, string $newStatus): Article
    {
        $currentStatus = $article->status;
        $allowed = self::TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidStatusTransitionException($currentStatus, $newStatus, $allowed);
        }

        $article->status = $newStatus;
        $this->syncIsPublished($article);
        $article->save();

        $this->cacheInvalidation->clearForArticleWrite(
            $article->slug,
            $article->category?->slug,
        );

        return $article;
    }

    public function publish(Article $article): Article
    {
        $article = $this->transitionStatus($article, Article::STATUS_PUBLISHED);

        if (! $article->published_at) {
            $article->published_at = now();
            $article->save();
        }

        return $article;
    }

    public function unpublish(Article $article): Article
    {
        $article = $this->transitionStatus($article, Article::STATUS_DRAFT);
        $article->published_at = null;
        $article->save();

        return $article;
    }

    public function schedule(Article $article, Carbon $publishAt): Article
    {
        $article = $this->transitionStatus($article, Article::STATUS_SCHEDULED);
        $article->published_at = $publishAt;
        $article->save();

        return $article;
    }

    public function archive(Article $article): Article
    {
        return $this->transitionStatus($article, Article::STATUS_ARCHIVED);
    }

    private function syncIsPublished(Article $article): void
    {
        $article->is_published = ($article->status === Article::STATUS_PUBLISHED);
    }
}
