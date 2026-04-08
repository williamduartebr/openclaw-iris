<?php

namespace Src\Media\Infrastructure\ExternalServices\Providers;

interface MediaGenerationProviderInterface
{
    /**
     * Unique provider identifier (e.g., 'openai', 'google_gemini').
     */
    public function name(): string;

    /**
     * List of supported model identifiers.
     *
     * @return string[]
     */
    public function supportedModels(): array;

    /**
     * Default model if none specified in the request.
     */
    public function defaultModel(): string;

    /**
     * Check if the provider is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Generate an image from a prompt.
     *
     * @param  array{
     *     model?: string,
     *     prompt: string,
     *     negative_prompt?: string,
     *     width?: int,
     *     height?: int,
     *     style?: string,
     *     quality?: string,
     * } $params
     * @return array{
     *     image_data: string,
     *     mime_type: string,
     *     width: int,
     *     height: int,
     *     provider_metadata: array,
     * }
     *
     * @throws \Src\Media\Domain\Exceptions\GenerationFailedException
     * @throws \Src\Media\Domain\Exceptions\ProviderUnavailableException
     */
    public function generate(array $params): array;
}
