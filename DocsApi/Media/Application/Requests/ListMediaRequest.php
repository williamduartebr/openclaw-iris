<?php

namespace Src\Media\Application\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Media\Domain\Enums\MediaStatus;

class ListMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->bearerToken() === config('services.media_api.key');
    }

    public function rules(): array
    {
        $statuses = implode(',', array_column(MediaStatus::cases(), 'value'));
        $providerKeys = implode(',', array_keys(config('media.providers', [])));

        return [
            'status' => "nullable|string|in:{$statuses}",
            'provider' => "nullable|string|in:{$providerKeys}",
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|in:created_at,-created_at,updated_at,-updated_at',
            'search' => 'nullable|string|max:200',
        ];
    }
}
