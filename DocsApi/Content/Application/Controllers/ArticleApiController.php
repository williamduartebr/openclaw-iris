<?php

namespace Src\Content\Application\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Src\Content\Application\Requests\StoreArticleApiRequest;
use Src\Content\Application\Services\ArticleImageStorageService;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class ArticleApiController extends Controller
{
    public function __construct(
        private readonly ArticleImageStorageService $imageService,
    ) {}

    public function store(StoreArticleApiRequest $request): JsonResponse
    {
        $category = Category::where('slug', $request->category_slug)->firstOrFail();

        $article = Article::create([
            'category_id' => $category->id,
            'title' => $request->title,
            'slug' => $request->slug,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'featured_image' => $request->featured_image,
            'author_name' => 'Equipe Editorial',
            'reading_time' => $request->reading_time,
            'is_published' => true,
            'published_at' => now(),
            'meta' => $request->meta,
        ]);

        $article->categories()->sync([$category->id]);

        $this->processFeaturedImageUpload($request, $article);
        $this->processContentImages($request, $article);

        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'url' => "/{$article->slug}",
            'status' => 'published',
        ], 201);
    }

    private function processFeaturedImageUpload(StoreArticleApiRequest $request, Article $article): void
    {
        if (! $request->hasFile('featured_image_file')) {
            return;
        }

        $result = $this->imageService->upload(
            $request->file('featured_image_file'),
            $article->id,
            'featured'
        );

        $article->update([
            'featured_image' => $result['queued_for_compression']
                ? $result['compressed_path']
                : $result['path'],
        ]);
    }

    private function processContentImages(StoreArticleApiRequest $request, Article $article): void
    {
        if (! $request->hasFile('content_images')) {
            return;
        }

        $content = $article->getRawOriginal('content');

        foreach ($request->file('content_images') as $index => $file) {
            $result = $this->imageService->upload($file, $article->id, 'content');

            $imageUrl = $result['queued_for_compression']
                ? $result['compressed_url']
                : $result['url'];

            $content = str_replace("{{IMG:{$index}}}", $imageUrl, $content);
        }

        $article->update(['content' => $content]);
    }
}
