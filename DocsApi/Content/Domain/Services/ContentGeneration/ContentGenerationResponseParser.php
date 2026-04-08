<?php

namespace Src\Content\Domain\Services\ContentGeneration;

class ContentGenerationResponseParser
{
    public function parse(string $response): ?array
    {
        $response = $this->normalizeResponse($response);
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return null;
        }

        if (! $this->hasRequiredFields($data)) {
            return null;
        }

        return $data;
    }

    private function normalizeResponse(string $response): string
    {
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/\s*```$/i', '', $response);

        return trim($response);
    }

    private function hasRequiredFields(array $data): bool
    {
        $required = ['title', 'slug', 'excerpt', 'content'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        return true;
    }
}
