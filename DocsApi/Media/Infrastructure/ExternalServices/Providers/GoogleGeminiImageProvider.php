<?php

namespace Src\Media\Infrastructure\ExternalServices\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Media\Domain\Exceptions\GenerationFailedException;
use Src\Media\Domain\Exceptions\ProviderUnavailableException;

class GoogleGeminiImageProvider implements MediaGenerationProviderInterface
{
    private const MODELS = ['gemini-2.0-flash-exp-image-generation', 'gemini-2.5-flash-image', 'gemini-2.0-flash-exp', 'imagen-3.0-generate-002'];

    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    private string $defaultModel;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://generativelanguage.googleapis.com';
        $this->timeout = $config['timeout'] ?? 120;
        $this->defaultModel = $config['default_model'] ?? 'gemini-2.0-flash-exp';
    }

    public function name(): string
    {
        return 'google_gemini';
    }

    public function supportedModels(): array
    {
        return self::MODELS;
    }

    public function defaultModel(): string
    {
        return $this->defaultModel;
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    public function generate(array $params): array
    {
        if (! $this->isAvailable()) {
            throw new ProviderUnavailableException($this->name(), 'API key not configured');
        }

        $model = $params['model'] ?? $this->defaultModel;
        $prompt = $params['prompt'];

        if (str_starts_with($model, 'imagen')) {
            return $this->generateWithImagen($model, $prompt, $params);
        }

        return $this->generateWithGemini($model, $prompt, $params);
    }

    private function generateWithGemini(string $model, string $prompt, array $params): array
    {
        $fullPrompt = $this->buildPrompt($prompt, $params);

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $fullPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['TEXT', 'IMAGE'],
            ],
        ];

        try {
            $url = "{$this->baseUrl}/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)
                ->post($url, $body);

            if ($response->failed()) {
                $error = $response->json('error.message', $response->body());

                throw new GenerationFailedException($this->name(), $model, $error);
            }

            $data = $response->json();
            $candidates = $data['candidates'] ?? [];

            if (empty($candidates)) {
                throw new GenerationFailedException($this->name(), $model, 'No candidates in response');
            }

            $parts = $candidates[0]['content']['parts'] ?? [];

            foreach ($parts as $part) {
                if (isset($part['inlineData'])) {
                    $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                    $imageData = base64_decode($part['inlineData']['data']);

                    return [
                        'image_data' => $imageData,
                        'mime_type' => $mimeType,
                        'width' => $params['width'] ?? 1024,
                        'height' => $params['height'] ?? 1024,
                        'provider_metadata' => [
                            'model' => $model,
                            'finish_reason' => $candidates[0]['finishReason'] ?? null,
                        ],
                    ];
                }
            }

            throw new GenerationFailedException($this->name(), $model, 'No image data in response parts');
        } catch (GenerationFailedException|ProviderUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Google Gemini image generation error', [
                'provider' => $this->name(),
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw new GenerationFailedException($this->name(), $model, $e->getMessage(), $e);
        }
    }

    private function generateWithImagen(string $model, string $prompt, array $params): array
    {
        $body = [
            'instances' => [
                ['prompt' => $this->buildPrompt($prompt, $params)],
            ],
            'parameters' => [
                'sampleCount' => 1,
            ],
        ];

        if (isset($params['negative_prompt'])) {
            $body['parameters']['negativePrompt'] = $params['negative_prompt'];
        }

        try {
            $url = "{$this->baseUrl}/v1beta/models/{$model}:predict?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)
                ->post($url, $body);

            if ($response->failed()) {
                $error = $response->json('error.message', $response->body());

                throw new GenerationFailedException($this->name(), $model, $error);
            }

            $data = $response->json();
            $predictions = $data['predictions'] ?? [];

            if (empty($predictions)) {
                throw new GenerationFailedException($this->name(), $model, 'No predictions in response');
            }

            $b64 = $predictions[0]['bytesBase64Encoded'] ?? null;
            $mimeType = $predictions[0]['mimeType'] ?? 'image/png';

            if (! $b64) {
                throw new GenerationFailedException($this->name(), $model, 'No image data in prediction');
            }

            return [
                'image_data' => base64_decode($b64),
                'mime_type' => $mimeType,
                'width' => $params['width'] ?? 1024,
                'height' => $params['height'] ?? 1024,
                'provider_metadata' => [
                    'model' => $model,
                ],
            ];
        } catch (GenerationFailedException|ProviderUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Google Imagen generation error', [
                'provider' => $this->name(),
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw new GenerationFailedException($this->name(), $model, $e->getMessage(), $e);
        }
    }

    private function buildPrompt(string $prompt, array $params): string
    {
        $parts = [$prompt];

        if (! empty($params['style'])) {
            $parts[] = "Style: {$params['style']}";
        }

        if (! empty($params['negative_prompt']) && ! str_starts_with($params['model'] ?? '', 'imagen')) {
            $parts[] = "Avoid: {$params['negative_prompt']}";
        }

        return implode('. ', $parts);
    }
}
