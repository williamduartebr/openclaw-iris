<?php

namespace Src\Media\Application\Services;

use Illuminate\Support\Str;
use Src\Media\Domain\Enums\MediaStatus;
use Src\Media\Domain\Models\MediaAsset;
use Src\Shared\Application\Jobs\DispatchImageCompression;

class MediaProcessingService
{
    public function __construct(
        private readonly MediaStorageService $storageService,
    ) {}

    public function dispatchCompaction(MediaAsset $asset): void
    {
        $processedPath = $this->storageService->buildProcessedPath($asset->id, $asset->original_path);

        DispatchImageCompression::dispatch(
            jobId: Str::uuid()->toString(),
            tenantId: 0,
            entityType: 'media_asset',
            entityId: $asset->id,
            files: [
                [
                    'file_id' => (string) $asset->id,
                    'source_path' => $asset->original_path,
                    'target_path' => $processedPath,
                    'original_name' => basename($asset->original_path),
                    'mime_type' => $asset->mime_type,
                    'options' => [
                        'quality' => config('media.processing.quality', 82),
                        'max_width' => config('media.processing.max_width', 1920),
                        'max_height' => config('media.processing.max_height', 1080),
                        'format' => config('media.processing.format', 'webp'),
                        'strip_metadata' => config('media.processing.strip_metadata', true),
                    ],
                ],
            ],
        );

        $asset->update(['status' => MediaStatus::QueuedForCompaction]);
    }

    public function handleCompactionResult(int $assetId, array $files): void
    {
        $asset = MediaAsset::find($assetId);

        if (! $asset) {
            \Illuminate\Support\Facades\Log::warning("Media asset not found for compaction result: {$assetId}");

            return;
        }

        foreach ($files as $file) {
            if (($file['status'] ?? '') === 'success') {
                $asset->update([
                    'status' => MediaStatus::Completed,
                    'processed_path' => $file['target_path'],
                    'mime_type' => 'image/webp',
                    'file_size' => $file['compressed_size'] ?? null,
                    'original_size' => $file['original_size'] ?? null,
                    'compression_ratio' => $file['compression_ratio'] ?? null,
                    'completed_at' => now(),
                ]);
            } else {
                $asset->update([
                    'status' => MediaStatus::Failed,
                    'failure_reason' => $file['error'] ?? 'Compression failed',
                ]);
            }
        }
    }
}
