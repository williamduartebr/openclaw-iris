<?php

namespace Src\Content\Domain\Services\ContentGeneration;

use Src\Content\Infrastructure\ExternalServices\AnthropicService;
use Src\Content\Infrastructure\ExternalServices\OpenAIService;

class ContentGenerationProviderGateway
{
    public function __construct(
        private readonly AnthropicService $anthropicService,
        private readonly OpenAIService $openAIService,
    ) {}

    public function generate(string $prompt, string $provider): string
    {
        if ($provider === 'openai') {
            return $this->openAIService->generateContent($prompt);
        }

        return $this->anthropicService->generateContent($prompt);
    }
}
