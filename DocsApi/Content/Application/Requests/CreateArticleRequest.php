<?php

namespace Src\Content\Application\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Content\Domain\Models\Article;
use Src\Content\Domain\Models\Category;

class CreateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.content_api.key');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'subtitle' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:80|unique:articles,slug',
            'excerpt' => 'nullable|string|max:300',
            'body_md' => 'required|string|min:100',
            'category_slug' => 'nullable|string|exists:categories,slug',
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

    /**
     * Quando category_slug é inválido, retorna hints com slugs válidos.
     */
    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        $response = [
            'message' => 'Validation failed.',
            'errors' => $errors,
        ];

        // Se o erro é de category_slug ou category_slugs, incluir hints
        if (isset($errors['category_slug']) || isset($errors['category_slugs']) || isset($errors['category_slugs.0'])) {
            $response['hints'] = [
                'valid_category_slugs' => Category::where('is_active', true)
                    ->orderBy('order')
                    ->pluck('slug')
                    ->toArray(),
            ];
        }

        throw new HttpResponseException(
            response()->json($response, 422)
        );
    }
}
