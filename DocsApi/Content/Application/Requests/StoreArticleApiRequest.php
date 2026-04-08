<?php

namespace Src\Content\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticleApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.content_api.key');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:80|unique:articles,slug',
            'excerpt' => 'required|string|max:300',
            'content' => 'required|string',
            'category_slug' => 'required|exists:categories,slug',
            'reading_time' => 'nullable|integer|min:1',
            'meta' => 'nullable|array',
            'featured_image' => 'nullable|string',
            'featured_image_file' => 'nullable|image|mimes:jpeg,png,gif,webp,bmp,tiff|max:5120',
            'content_images' => 'nullable|array|max:10',
            'content_images.*' => 'image|mimes:jpeg,png,gif,webp,bmp,tiff|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'featured_image_file.image' => 'O arquivo de imagem de destaque deve ser uma imagem válida.',
            'featured_image_file.mimes' => 'A imagem de destaque deve ser do tipo: jpeg, png, gif, webp, bmp ou tiff.',
            'featured_image_file.max' => 'A imagem de destaque não pode exceder 5 MB.',
            'content_images.max' => 'É permitido no máximo 10 imagens de conteúdo por artigo.',
            'content_images.*.image' => 'Cada arquivo de conteúdo deve ser uma imagem válida.',
            'content_images.*.mimes' => 'As imagens de conteúdo devem ser do tipo: jpeg, png, gif, webp, bmp ou tiff.',
            'content_images.*.max' => 'Cada imagem de conteúdo não pode exceder 5 MB.',
        ];
    }
}
