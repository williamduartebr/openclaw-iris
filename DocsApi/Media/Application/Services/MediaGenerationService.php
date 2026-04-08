<?php

namespace Src\Media\Application\Services;

use Illuminate\Support\Facades\Log;
use Src\Media\Domain\Enums\MediaStatus;
use Src\Media\Domain\Exceptions\GenerationFailedException;
use Src\Media\Domain\Exceptions\ProviderUnavailableException;
use Src\Media\Domain\Models\MediaAsset;
use Src\Media\Infrastructure\ExternalServices\Providers\ProviderRegistry;

class MediaGenerationService
{
    public function __construct(
        private readonly ProviderRegistry $providerRegistry,
        private readonly MediaStorageService $storageService,
        private readonly MediaProcessingService $processingService,
    ) {}

    public function createAsset(array $validated): MediaAsset
    {
        $providerName = $validated['provider'] ?? config('media.default_provider', 'openai');
        $provider = $this->providerRegistry->resolve($providerName);
        $model = $validated['model'] ?? $provider->defaultModel();

        return MediaAsset::create([
            'status' => MediaStatus::Pending,
            'provider' => $providerName,
            'model' => $model,
            'prompt' => $validated['prompt'],
            'negative_prompt' => $validated['negative_prompt'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'style' => $validated['style'] ?? null,
            'quality' => $validated['quality'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'orchestrator_context' => $validated['orchestrator_context'] ?? null,
        ]);
    }

    public function processGeneration(int $assetId): void
    {
        $asset = MediaAsset::findOrFail($assetId);

        try {
            // Step 1: Mark as generating
            $asset->update(['status' => MediaStatus::Generating]);

            // Step 2: Resolve provider and generate
            $provider = $this->providerRegistry->resolve($asset->provider);

            $result = $provider->generate([
                'model' => $asset->model,
                'prompt' => $asset->prompt,
                'negative_prompt' => $asset->negative_prompt,
                'width' => $asset->width,
                'height' => $asset->height,
                'style' => $asset->style,
                'quality' => $asset->quality,
            ]);

            // Step 3: Mark as generated
            $asset->update([
                'status' => MediaStatus::Generated,
                'width' => $result['width'],
                'height' => $result['height'],
                'provider_metadata' => $result['provider_metadata'] ?? null,
            ]);

            // Step 4: Upload original to S3
            $asset->update(['status' => MediaStatus::Uploading]);

            $originalPath = $this->storageService->uploadOriginal(
                $asset->id,
                $result['image_data'],
                $result['mime_type'],
            );

            $asset->update([
                'original_path' => $originalPath,
                'mime_type' => $result['mime_type'],
                'original_size' => strlen($result['image_data']),
            ]);

            // Step 5: Dispatch compaction
            $this->processingService->dispatchCompaction($asset);

            Log::info('Media image generation completed, compaction dispatched', [
                'asset_id' => $asset->id,
                'provider' => $asset->provider,
                'model' => $asset->model,
                'original_path' => $originalPath,
            ]);

        } catch (ProviderUnavailableException|GenerationFailedException $e) {
            $asset->update([
                'status' => MediaStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Media generation failed', [
                'asset_id' => $asset->id,
                'provider' => $asset->provider,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Throwable $e) {
            $asset->update([
                'status' => MediaStatus::Failed,
                'failure_reason' => $e->getMessage(),
            ]);

            Log::error('Media generation unexpected error', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function retryGeneration(MediaAsset $asset): MediaAsset
    {
        $asset->update([
            'status' => MediaStatus::Pending,
            'failure_reason' => null,
            'original_path' => null,
            'processed_path' => null,
            'mime_type' => null,
            'file_size' => null,
            'original_size' => null,
            'compression_ratio' => null,
            'provider_metadata' => null,
            'completed_at' => null,
        ]);

        return $asset->fresh();
    }

    public function reprocessAsset(MediaAsset $asset): MediaAsset
    {
        $asset->update([
            'status' => MediaStatus::QueuedForCompaction,
            'failure_reason' => null,
            'processed_path' => null,
            'file_size' => null,
            'compression_ratio' => null,
            'completed_at' => null,
        ]);

        $this->processingService->dispatchCompaction($asset->fresh());

        return $asset->fresh();
    }
}
