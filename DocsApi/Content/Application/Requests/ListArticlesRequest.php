<?php

namespace Src\Content\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.content_api.key');
    }

    public function rules(): array
    {
        return [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort' => 'nullable|string|in:created_at,-created_at,updated_at,-updated_at,published_at,-published_at,title,-title',
            'status' => 'nullable|string',
            'category' => 'nullable|string|exists:categories,slug',
            'featured' => 'nullable|in:true,false,1,0',
            'author' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:80',
            'search' => 'nullable|string|max:255',
            'created_after' => 'nullable|date',
            'created_before' => 'nullable|date',
            'published_after' => 'nullable|date',
            'published_before' => 'nullable|date',
            'trashed' => 'nullable|in:only,with',
        ];
    }
}
