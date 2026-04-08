<?php

namespace Src\Content\Infrastructure\ExternalServices;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function generateContent(string $prompt): string
    {
        $apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (! $apiKey) {
            throw new \Exception('OPENAI_API_KEY não configurada. Adicione ao .env');
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer $apiKey",
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 8192,
        ]);

        if (! $response->successful()) {
            throw new \Exception('Erro API OpenAI: '.$response->body());
        }

        return $response->json('choices.0.message.content');
    }
}
