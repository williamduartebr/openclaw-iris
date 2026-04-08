<?php

namespace Src\Content\Domain\Services;

use Src\Content\Domain\Services\ContentGeneration\ContentGenerationPromptBuilder;
use Src\Content\Domain\Services\ContentGeneration\ContentGenerationProviderGateway;
use Src\Content\Domain\Services\ContentGeneration\ContentGenerationResponseParser;

class ContentGenerationService
{
    public function __construct(
        private readonly ContentGenerationPromptBuilder $promptBuilder,
        private readonly ContentGenerationProviderGateway $providerGateway,
        private readonly ContentGenerationResponseParser $responseParser,
    ) {}

    public function generate(array $titleData, string $provider = 'claude'): array
    {
        $prompt = $this->promptBuilder->build($titleData);
        $response = $this->providerGateway->generate($prompt, $provider);

        return $this->responseParser->parse($response) ?? [];
    }
}
