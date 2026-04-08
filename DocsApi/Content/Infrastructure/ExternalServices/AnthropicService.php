<?php

namespace Src\Content\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;

class AnthropicService
{
    public function generateContent(string $prompt): string
    {
        $apiKey = config('services.anthropic.api_key') ?? env('ANTHROPIC_API_KEY');

        if (! $apiKey) {
            throw new \Exception('ANTHROPIC_API_KEY não configurada. Adicione ao .env');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-20250514', // Using the model from the original command
            'max_tokens' => 8192,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Erro API Claude: '.$response->body());
        }

        return $response->json('content.0.text');
    }
}
