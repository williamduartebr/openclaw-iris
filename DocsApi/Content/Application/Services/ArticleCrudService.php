<?php

namespace Src\Content\Application\Services;

use Src\Content\Domain\Exceptions\InvalidMediaReferenceException;
use Src\Content\Domain\Exceptions\VersionConflictException;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;
use Src\Content\Domain\Services\ArticleLifecycleService;
use Src\Content\Domain\Services\ArticleSlugService;
use Src\Content\Infrastructure\Cache\ContentCacheInvalidationService;

class ArticleCrudService
{
    public function __construct(
        private readonly ArticleSlugService $slugService,
        private readonly ArticleLifecycleService $lifecycleService,
        private readonly MediaAssetResolver $mediaResolver,
        private readonly ContentCacheInvalidationService $cacheInvalidation,
    ) {}

    public function create(array $data): Article
    {
        $categorySlug = $data['category_slug'] ?? 'geral';
        $category = Category::where('slug', $categorySlug)->firstOrFail();

        $slug = $data['slug'] ?? $this->slugService->generate($data['title']);
        $slug = $this->slugService->ensureUnique($slug);

        $status = $data['status'] ?? Article::STATUS_DRAFT;

        $coverImageUrl = $data['cover_image_url'] ?? null;
        $coverMediaId = $data['cover_media_id'] ?? null;

        if ($coverMediaId) {
            $coverImageUrl = $this->resolveMediaUrl($coverMediaId);
        }

        $galleryImageUrls = $data['gallery_image_urls'] ?? [];
        $galleryMedia = null;

        if (! empty($data['gallery_media'])) {
            [$galleryMedia, $galleryImageUrls] = $this->resolveGalleryMedia($data['gallery_media']);
        }

        $galleryMode = $data['gallery_mode'] ?? 'inline';
        $bodyMd = $data['body_md'];

        if ($galleryMode === 'inline' && ! empty($galleryMedia)) {
            $bodyMd = $this->embedGalleryInBody($bodyMd, $galleryMedia, $data['gallery_media'], $data['title']);
        }

        $article = Article::create([
            'category_id' => $category->id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $bodyMd,
            'status' => $status,
            'featured_image' => $coverImageUrl,
            'cover_media_id' => $coverMediaId,
            'gallery_image_urls' => $galleryImageUrls,
            'gallery_media' => $galleryMedia,
            'video_urls' => $data['video_urls'] ?? [],
            'author_name' => $data['author'] ?? 'Equipe Editorial',
            'reading_time' => $data['reading_time'] ?? $this->calculateReadingTime($data['body_md']),
            'is_published' => $status === Article::STATUS_PUBLISHED,
            'published_at' => $this->resolvePublishedAt($status, $data['published_at'] ?? null),
            'meta' => $this->buildMeta($data),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'featured' => $data['featured'] ?? false,
            'image_source' => $data['image_source'] ?? 'ai',
            'version' => 1,
        ]);

        $this->syncCategories($article, $category->id, $data);

        $this->cacheInvalidation->clearForArticleWrite($article->slug, $category->slug);

        return $article->load(['category', 'categories']);
    }

    public function update(Article $article, array $data): Article
    {
        $this->checkVersion($article, $data['version']);

        $category = Category::where('slug', $data['category_slug'])->firstOrFail();

        $slug = $data['slug'] ?? $article->slug;
        if ($slug !== $article->slug) {
            $slug = $this->slugService->ensureUnique($slug, $article->id);
        }

        $status = $data['status'] ?? $article->status;

        if ($status !== $article->status) {
            $this->lifecycleService->transitionStatus($article, $status);
        }

        $coverImageUrl = $data['cover_image_url'] ?? null;
        $coverMediaId = $data['cover_media_id'] ?? null;

        if ($coverMediaId) {
            $coverImageUrl = $this->resolveMediaUrl($coverMediaId);
        }

        $galleryImageUrls = $data['gallery_image_urls'] ?? [];
        $galleryMedia = null;

        if (! empty($data['gallery_media'])) {
            [$galleryMedia, $galleryImageUrls] = $this->resolveGalleryMedia($data['gallery_media']);
        }

        $galleryMode = $data['gallery_mode'] ?? 'inline';
        $bodyMd = $data['body_md'];

        if ($galleryMode === 'inline' && ! empty($galleryMedia)) {
            $bodyMd = $this->embedGalleryInBody($bodyMd, $galleryMedia, $data['gallery_media'], $data['title']);
        }

        $article->update([
            'category_id' => $category->id,
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'slug' => $slug,
            'excerpt' => $data['excerpt'],
            'content' => $bodyMd,
            'featured_image' => $coverImageUrl,
            'cover_media_id' => $coverMediaId,
            'gallery_image_urls' => $galleryImageUrls,
            'gallery_media' => $galleryMedia,
            'video_urls' => $data['video_urls'] ?? [],
            'author_name' => $data['author'] ?? 'Equipe Editorial',
            'reading_time' => $data['reading_time'] ?? $this->calculateReadingTime($data['body_md']),
            'published_at' => $this->resolvePublishedAt($status, $data['published_at'] ?? null) ?? $article->published_at,
            'meta' => $this->buildMeta($data),
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'featured' => $data['featured'] ?? false,
            'image_source' => $data['image_source'] ?? 'ai',
        ]);

        $article->incrementVersion();

        $this->syncCategories($article, $category->id, $data);

        $this->cacheInvalidation->clearForArticleWrite($article->slug, $category->slug);

        return $article->load(['category', 'categories']);
    }

    public function patch(Article $article, array $data): Article
    {
        $this->checkVersion($article, $data['version']);

        $updates = [];

        if (isset($data['title'])) {
            $updates['title'] = $data['title'];
        }
        if (array_key_exists('subtitle', $data)) {
            $updates['subtitle'] = $data['subtitle'];
        }
        if (isset($data['slug'])) {
            $slug = $this->slugService->ensureUnique($data['slug'], $article->id);
            $updates['slug'] = $slug;
        }
        if (array_key_exists('excerpt', $data)) {
            $updates['excerpt'] = $data['excerpt'];
        }
        if (isset($data['body_md'])) {
            $updates['content'] = $data['body_md'];
            if (! isset($data['reading_time'])) {
                $updates['reading_time'] = $this->calculateReadingTime($data['body_md']);
            }
        }
        if (array_key_exists('cover_media_id', $data)) {
            if ($data['cover_media_id']) {
                $updates['featured_image'] = $this->resolveMediaUrl($data['cover_media_id']);
                $updates['cover_media_id'] = $data['cover_media_id'];
            } else {
                $updates['cover_media_id'] = null;
            }
        } elseif (array_key_exists('cover_image_url', $data)) {
            $updates['featured_image'] = $data['cover_image_url'];
            $updates['cover_media_id'] = null;
        }
        if (array_key_exists('gallery_media', $data)) {
            if (! empty($data['gallery_media'])) {
                [$galleryMedia, $galleryUrls] = $this->resolveGalleryMedia($data['gallery_media']);
                $updates['gallery_media'] = $galleryMedia;
                $updates['gallery_image_urls'] = $galleryUrls;

                $galleryMode = $data['gallery_mode'] ?? 'inline';
                if ($galleryMode === 'inline') {
                    $bodyMd = $updates['content'] ?? $article->getRawOriginal('content');
                    $title = $updates['title'] ?? $article->title;
                    $bodyMd = $this->embedGalleryInBody($bodyMd, $galleryMedia, $data['gallery_media'], $title);
                    $updates['content'] = $bodyMd;
                    if (! isset($data['reading_time'])) {
                        $updates['reading_time'] = $this->calculateReadingTime($bodyMd);
                    }
                }
            } else {
                $updates['gallery_media'] = null;
            }
        } elseif (array_key_exists('gallery_image_urls', $data)) {
            $updates['gallery_image_urls'] = $data['gallery_image_urls'] ?? [];
            $updates['gallery_media'] = null;
        }
        if (array_key_exists('video_urls', $data)) {
            $updates['video_urls'] = $data['video_urls'] ?? [];
        }
        if (isset($data['author'])) {
            $updates['author_name'] = $data['author'];
        }
        if (isset($data['reading_time'])) {
            $updates['reading_time'] = $data['reading_time'];
        }
        if (array_key_exists('seo_title', $data)) {
            $updates['seo_title'] = $data['seo_title'];
        }
        if (array_key_exists('seo_description', $data)) {
            $updates['seo_description'] = $data['seo_description'];
        }
        if (array_key_exists('canonical_url', $data)) {
            $updates['canonical_url'] = $data['canonical_url'];
        }
        if (isset($data['featured'])) {
            $updates['featured'] = $data['featured'];
        }
        if (isset($data['image_source'])) {
            $updates['image_source'] = $data['image_source'];
        }
        if (isset($data['published_at'])) {
            $updates['published_at'] = $data['published_at'];
        }

        if (isset($data['status']) && $data['status'] !== $article->status) {
            $this->lifecycleService->transitionStatus($article, $data['status']);
        }

        if (isset($data['category_slug'])) {
            $category = Category::where('slug', $data['category_slug'])->firstOrFail();
            $updates['category_id'] = $category->id;
            $this->syncCategories($article, $category->id, $data);
        } elseif (isset($data['category_slugs'])) {
            $this->syncCategories($article, $article->category_id, $data);
        }

        if (! empty($updates)) {
            $article->update($updates);
        }

        $article->incrementVersion();

        $this->cacheInvalidation->clearForArticleWrite(
            $article->slug,
            $article->category?->slug,
        );

        return $article->load(['category', 'categories']);
    }

    public function delete(Article $article, ?int $version = null): void
    {
        if ($version !== null) {
            $this->checkVersion($article, $version);
        }

        $this->cacheInvalidation->clearForArticleWrite(
            $article->slug,
            $article->category?->slug,
        );

        $article->delete();
    }

    public function restore(int $id): Article
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->restore();
        $article->refresh();

        $this->cacheInvalidation->clearForArticleWrite(
            $article->slug,
            $article->category?->slug,
        );

        return $article->load(['category', 'categories']);
    }

    private function checkVersion(Article $article, int $providedVersion): void
    {
        if ($article->version !== $providedVersion) {
            throw new VersionConflictException($article->version, $providedVersion);
        }
    }

    private function syncCategories(Article $article, int $primaryCategoryId, array $data): void
    {
        if (! empty($data['category_slugs'])) {
            $categoryIds = Category::whereIn('slug', $data['category_slugs'])->pluck('id')->toArray();
            if (! in_array($primaryCategoryId, $categoryIds)) {
                $categoryIds[] = $primaryCategoryId;
            }
            $article->categories()->sync($categoryIds);
        } else {
            $article->categories()->sync([$primaryCategoryId]);
        }
    }

    private function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return max(1, (int) ceil($wordCount / 200));
    }

    private function resolvePublishedAt(string $status, ?string $publishedAt): ?string
    {
        if ($status === Article::STATUS_PUBLISHED && ! $publishedAt) {
            return now()->toDateTimeString();
        }

        return $publishedAt;
    }

    private function resolveMediaUrl(int $mediaId): string
    {
        $asset = $this->mediaResolver->resolve($mediaId);

        if (! $asset) {
            throw new InvalidMediaReferenceException($mediaId, 'not found');
        }

        if ($asset['status'] !== 'completed') {
            throw new InvalidMediaReferenceException($mediaId, "is not completed (status: {$asset['status']})");
        }

        if (! $asset['final_url']) {
            throw new InvalidMediaReferenceException($mediaId, 'has no processed URL');
        }

        return $asset['final_url'];
    }

    /**
     * @return array{0: array, 1: array}
     */
    private function resolveGalleryMedia(array $items): array
    {
        $mediaIds = collect($items)->pluck('media_id')->filter()->values()->toArray();
        $resolved = ! empty($mediaIds) ? $this->mediaResolver->resolveMany($mediaIds) : [];

        $structured = [];
        $flatUrls = [];

        foreach ($items as $item) {
            if (! empty($item['media_id'])) {
                $mediaId = $item['media_id'];

                if (! isset($resolved[$mediaId])) {
                    throw new InvalidMediaReferenceException($mediaId, 'not found');
                }

                $asset = $resolved[$mediaId];

                if ($asset['status'] !== 'completed') {
                    throw new InvalidMediaReferenceException($mediaId, "is not completed (status: {$asset['status']})");
                }

                if (! $asset['final_url']) {
                    throw new InvalidMediaReferenceException($mediaId, 'has no processed URL');
                }

                $structured[] = ['media_id' => $mediaId, 'url' => $asset['final_url']];
                $flatUrls[] = $asset['final_url'];
            } else {
                $url = $item['url'];
                $structured[] = ['url' => $url];
                $flatUrls[] = $url;
            }
        }

        return [$structured, $flatUrls];
    }

    private function embedGalleryInBody(string $bodyMd, array $resolved, array $originalItems, string $articleTitle): string
    {
        foreach ($resolved as $i => $item) {
            $alt = $originalItems[$i]['alt'] ?? ($articleTitle.' — imagem '.($i + 1));
            $url = $item['url'];
            $bodyMd .= "\n\n![{$alt}]({$url})";
        }

        return $bodyMd;
    }

    private function buildMeta(array $data): ?array
    {
        $meta = [];

        if (! empty($data['seo_description'])) {
            $meta['description'] = $data['seo_description'];
        }

        return ! empty($meta) ? $meta : null;
    }
}
