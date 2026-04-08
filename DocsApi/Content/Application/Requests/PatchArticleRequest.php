<?php

namespace Src\Content\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Src\Content\Domain\Models\Article;

class PatchArticleRequest extends FormRequest
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
            'title' => 'sometimes|string|min:3|max:255',
            'subtitle' => 'sometimes|nullable|string|max:255',
            'slug' => ['sometimes', 'string', 'max:80', Rule::unique('articles', 'slug')->ignore($articleId)],
            'excerpt' => 'sometimes|nullable|string|max:300',
            'body_md' => 'sometimes|string|min:100',
            'category_slug' => 'sometimes|string|exists:categories,slug',
            'category_slugs' => 'sometimes|array',
            'category_slugs.*' => 'string|exists:categories,slug',
            'status' => 'sometimes|string|in:'.implode(',', Article::VALID_STATUSES),
            'featured' => 'sometimes|boolean',
            'cover_image_url' => 'sometimes|nullable|url:https|max:2048',
            'cover_media_id' => 'sometimes|nullable|integer|min:1',
            'gallery_image_urls' => 'sometimes|nullable|array|max:20',
            'gallery_image_urls.*' => 'url:https|max:2048',
            'gallery_media' => 'sometimes|nullable|array|max:20',
            'gallery_media.*.media_id' => 'required_without:gallery_media.*.url|integer|min:1',
            'gallery_media.*.url' => 'required_without:gallery_media.*.media_id|url:https|max:2048',
            'gallery_media.*.alt' => 'sometimes|nullable|string|max:255',
            'gallery_mode' => 'sometimes|nullable|string|in:inline,gallery',
            'video_urls' => 'sometimes|nullable|array|max:10',
            'video_urls.*' => 'url:https|max:2048',
            'author' => 'sometimes|string|max:255',
            'reading_time' => 'sometimes|nullable|integer|min:1|max:120',
            'seo_title' => 'sometimes|nullable|string|max:70',
            'seo_description' => 'sometimes|nullable|string|max:160',
            'canonical_url' => 'sometimes|nullable|url:https|max:2048',
            'published_at' => 'sometimes|nullable|date',
            'image_source' => 'sometimes|string|in:ai,real,press,stock',
        ];
    }
}
