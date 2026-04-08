<?php

namespace Src\Content\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Content\Domain\Models\Article;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.content_api.key');
    }

    public function rules(): array
    {
        $articleId = $this->route('contentArticle')?->id ?? $this->route('contentArticle');

        return [
            'version' => 'required|integer',
            'title' => 'required|string|min:3|max:255',
            'subtitle' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:80', Rule::unique('articles', 'slug')->ignore($articleId)],
            'excerpt' => 'required|string|max:300',
            'body_md' => 'required|string|min:100',
            'category_slug' => 'required|string|exists:categories,slug',
            'category_slugs' => 'nullable|array',
            'category_slugs.*' => 'string|exists:categories,slug',
            'status' => 'nullable|string|in:'.implode(',', Article::VALID_STATUSES),
            'featured' => 'nullable|boolean',
            'cover_image_url' => 'nullable|url:https|max:2048',
            'cover_media_id' => 'nullable|integer|min:1',
            'gallery_image_urls' => 'nullable|array|max:20',
            'gallery_image_urls.*' => 'url:https|max:2048',
            'gallery_media' => 'nullable|array|max:20',
            'gallery_media.*.media_id' => 'required_without:gallery_media.*.url|integer|min:1',
            'gallery_media.*.url' => 'required_without:gallery_media.*.media_id|url:https|max:2048',
            'gallery_media.*.alt' => 'nullable|string|max:255',
            'gallery_mode' => 'nullable|string|in:inline,gallery',
            'video_urls' => 'nullable|array|max:10',
            'video_urls.*' => 'url:https|max:2048',
            'author' => 'nullable|string|max:255',
            'reading_time' => 'nullable|integer|min:1|max:120',
            'seo_title' => 'nullable|string|max:70',
            'seo_description' => 'nullable|string|max:160',
            'canonical_url' => 'nullable|url:https|max:2048',
            'published_at' => 'nullable|date',
            'image_source' => 'nullable|string|in:ai,real,press,stock',
        ];
    }
}
