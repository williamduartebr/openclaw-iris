<?php

namespace Src\Media\Infrastructure\ExternalServices\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Media\Domain\Exceptions\GenerationFailedException;
use Src\Media\Domain\Exceptions\ProviderUnavailableException;

class OpenAiImageProvider implements MediaGenerationProviderInterface
{
    private const MODELS = ['gpt-image-1', 'dall-e-3', 'dall-e-2'];

    private string $apiKey;

    private string $baseUrl;

    private int $timeout;

    private string $defaultModel;

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'] ?? '';
        $this->baseUrl = $config['base_url'] ?? 'https://api.openai.com/v1';
        $this->timeout = $config['timeout'] ?? 120;
        $this->defaultModel = $config['default_model'] ?? 'gpt-image-1';
    }

    public function name(): string
    {
        return 'openai';
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

        $body = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
        ];

        if ($model === 'gpt-image-1') {
            $body['output_format'] = 'png';

            if (isset($params['quality'])) {
                $body['quality'] = $this->mapQualityForGptImage($params['quality']);
            }

            if (isset($params['width'], $params['height'])) {
                $body['size'] = $this->resolveSize($params['width'], $params['height'], $model);
            }
        } elseif ($model === 'dall-e-3') {
            $body['response_format'] = 'b64_json';

            if (isset($params['quality'])) {
                $body['quality'] = $params['quality'] === 'high' ? 'hd' : 'standard';
            }

            if (isset($params['style'])) {
                $body['style'] = $params['style'];
            }

            if (isset($params['width'], $params['height'])) {
                $body['size'] = $this->resolveSize($params['width'], $params['height'], $model);
            }
        } elseif ($model === 'dall-e-2') {
            $body['response_format'] = 'b64_json';

            if (isset($params['width'], $params['height'])) {
                $body['size'] = $this->resolveSize($params['width'], $params['height'], $model);
            }
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
            ])
                ->timeout($this->timeout)
                ->post("{$this->baseUrl}/images/generations", $body);

            if ($response->failed()) {
                $error = $response->json('error.message', $response->body());

                throw new GenerationFailedException($this->name(), $model, $error);
            }

            $data = $response->json();
            $imageItem = $data['data'][0] ?? null;

            if (! $imageItem) {
                throw new GenerationFailedException($this->name(), $model, 'No image data in response');
            }

            $imageData = $this->extractImageData($imageItem, $model);
            $size = $this->parseSizeFromResponse($body['size'] ?? '1024x1024');

            return [
                'image_data' => $imageData,
                'mime_type' => 'image/png',
                'width' => $size['width'],
                'height' => $size['height'],
                'provider_metadata' => [
                    'model' => $model,
                    'revised_prompt' => $imageItem['revised_prompt'] ?? null,
                ],
            ];
        } catch (GenerationFailedException|ProviderUnavailableException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('OpenAI image generation error', [
                'provider' => $this->name(),
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw new GenerationFailedException($this->name(), $model, $e->getMessage(), $e);
        }
    }

    private function extractImageData(array $imageItem, string $model): string
    {
        if ($model === 'gpt-image-1') {
            // gpt-image-1 returns b64_json directly in the data array
            $b64 = $imageItem['b64_json'] ?? null;
            if (! $b64) {
                throw new GenerationFailedException($this->name(), $model, 'No b64_json in response');
            }

            return base64_decode($b64);
        }

        // dall-e-3 and dall-e-2 with response_format=b64_json
        $b64 = $imageItem['b64_json'] ?? null;
        if ($b64) {
            return base64_decode($b64);
        }

        throw new GenerationFailedException($this->name(), $model, 'No image data in response');
    }

    private function resolveSize(int $width, int $height, string $model): string
    {
        $validSizes = match ($model) {
            'gpt-image-1' => ['1024x1024', '1536x1024', '1024x1536', 'auto'],
            'dall-e-3' => ['1024x1024', '1792x1024', '1024x1792'],
            'dall-e-2' => ['256x256', '512x512', '1024x1024'],
            default => ['1024x1024'],
        };

        $requested = "{$width}x{$height}";

        if (in_array($requested, $validSizes)) {
            return $requested;
        }

        // Find the closest valid size
        $ratio = $width / $height;

        if ($ratio > 1.2) {
            // Landscape
            return match ($model) {
                'gpt-image-1' => '1536x1024',
                'dall-e-3' => '1792x1024',
                default => '1024x1024',
            };
        }

        if ($ratio < 0.8) {
            // Portrait
            return match ($model) {
                'gpt-image-1' => '1024x1536',
                'dall-e-3' => '1024x1792',
                default => '1024x1024',
            };
        }

        return '1024x1024';
    }

    private function mapQualityForGptImage(string $quality): string
    {
        return match ($quality) {
            'standard', 'low' => 'low',
            'high', 'hd' => 'high',
            default => 'medium',
        };
    }

    private function parseSizeFromResponse(string $size): array
    {
        $parts = explode('x', $size);

        return [
            'width' => (int) ($parts[0] ?? 1024),
            'height' => (int) ($parts[1] ?? 1024),
        ];
    }
}
