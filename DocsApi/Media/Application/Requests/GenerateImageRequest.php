<?php

namespace Src\Media\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.media_api.key');
    }

    public function rules(): array
    {
        $providerKeys = implode(',', array_keys(config('media.providers', [])));

        return [
            'prompt' => 'required|string|min:3|max:4000',
            'provider' => "nullable|string|in:{$providerKeys}",
            'model' => 'nullable|string|max:100',
            'negative_prompt' => 'nullable|string|max:1000',
            'width' => 'nullable|integer|min:256|max:2048',
            'height' => 'nullable|integer|min:256|max:2048',
            'style' => 'nullable|string|in:natural,vivid',
            'quality' => 'nullable|string|in:standard,high,hd,low,medium',
            'metadata' => 'nullable|array|max:20',
            'orchestrator_context' => 'nullable|array|max:20',
        ];
    }
}
