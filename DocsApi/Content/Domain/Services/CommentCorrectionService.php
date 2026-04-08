<?php

namespace Src\Content\Domain\Services;

use Illuminate\Support\Facades\Http;

class CommentCorrectionService
{
    private string $model;

    public function __construct()
    {
        $this->model = env('CLAUDE_COMMENT_MODEL', 'claude-haiku-4-5-20251001');
    }

    public function correct(string $content): string
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new \Exception('Claude API key ausente');
        }

        $prompt = $this->buildPrompt();

        $body = [
            'model' => $this->model,
            'max_tokens' => 1024,
            'temperature' => 0.1,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
                ['role' => 'user', 'content' => json_encode(['comentario' => $content], JSON_UNESCAPED_UNICODE)],
            ],
        ];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', $body);

        if (! $response->successful()) {
            throw new \Exception('Erro Claude: '.$response->status());
        }

        $result = $response->json('content.0.text') ?? '';

        return $this->parseResponse($result, $content);
    }

    private function buildPrompt(): string
    {
        return <<<'PROMPT'
Você é um corretor ortográfico e gramatical de português brasileiro para comentários de blog.

REGRAS:
1. Corrija APENAS erros de ortografia, gramática, pontuação e concordância
2. NÃO altere o sentido, tom ou estilo do comentário
3. NÃO adicione ou remova informações
4. Mantenha gírias e expressões informais se forem intencionais
5. Preserve emojis e formatação original
6. Se o texto estiver correto, retorne-o sem alterações

Responda APENAS com o texto corrigido, sem explicações ou comentários adicionais.
Não use aspas ou marcações, apenas o texto puro corrigido.
PROMPT;
    }

    private function parseResponse(string $response, string $original): string
    {
        $corrected = trim($response);

        // Se a resposta estiver vazia ou muito diferente, retorna o original
        if (empty($corrected) || strlen($corrected) > strlen($original) * 2) {
            return $original;
        }

        return $corrected;
    }
}
